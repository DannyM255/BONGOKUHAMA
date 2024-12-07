<?php
defined('BASEPATH') or exit('No direct script access allowed');

class Add_funds extends My_UserController
{
    public $tb_users;
    public $tb_transaction_logs;
    public $tb_payments;
    public $tb_payments_bonuses;
    public $module;
    public $module_icon;

    public function __construct()
    {
        parent::__construct();
        $this->load->model(get_class($this) . '_model', 'model');
        $this->module = get_class($this);
        $this->tb_users = USERS;
        $this->tb_transaction_logs = TRANSACTION_LOGS;
        $this->tb_payments = PAYMENTS_METHOD;
        $this->tb_payments_bonuses = PAYMENTS_BONUSES;
    }

    public function index()
    {
        // Fetch payment gateways available to the user
        $payments = $this->model->fetch('type, name, id, params', $this->tb_payments, ['status' => 1], 'sort', 'ASC');
        $user_settings = $this->model->get('settings', $this->tb_users, ['id' => session('uid')])->settings;
        $user_settings = json_decode($user_settings);

        if (isset($user_settings->limit_payments)) {
            $limit_payments = (array) $user_settings->limit_payments;
            foreach ($payments as $key => $payment) {
                if (isset($limit_payments[$payment->type]) && !$limit_payments[$payment->type]) {
                    unset($payments[$key]);
                }
            }
        }

        $data = [
            "module" => $this->module,
            "payments" => $payments,
            "currency_code" => get_option("currency_code", 'USD'),
            "currency_symbol" => get_option("currency_symbol", '$'),
        ];

        $this->template->set_layout('user');
        $this->template->build('index', $data);
    }

    public function process() {
    // Set form validation rules
    $this->form_validation->set_rules('amount', 'Amount', 'required|numeric');
    $this->form_validation->set_rules('buyer_number', 'Buyer Phone', 'required');
    
    if ($this->form_validation->run() == FALSE) {
        // Validation failed, reload the form with errors
        $data['currency_code'] = 'TZS';
        $this->load->view('stripe/index', $data);
    } else {
        // Validation passed, process the payment
        $user_email = $this->model->get('email', $this->tb_users, ['id' => session('uid')])->email;

        $orderData = [
            'create_order' => 1,
            'buyer_email' => $user_email,
            'buyer_name' => $user_email,
            'buyer_phone' => $this->input->post('buyer_number'),
            'amount' => $this->input->post('amount'),
            'account_id' => 'zp66072',
            "webhook" => base64_encode("https://ezycard.zeno.africa/webhook.php"),
            'api_key' => '315cfb2f39b1c1da523e48a59bb34500',
            'secret_key' => '31278648f87c824f6907d50499273ab1a521556487b378403752e7b7541c3d9c'
        ];

        // Initialize cURL session for creating the order
        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL, "https://apigw.zeno.africa");
        curl_setopt($ch, CURLOPT_POST, true);
        curl_setopt($ch, CURLOPT_POSTFIELDS, http_build_query($orderData));
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        $response = curl_exec($ch);

        if ($response === false) {
            echo 'Curl error: ' . curl_error($ch);
            logError(curl_error($ch));
        } else {
            echo 'Create Order Response: ' . $response . "<br>";
            
            if (strpos($response, 'Failed to process wallet payment') !== false) {
                echo 'Failed to process wallet payment. Please check your payment details or credentials.';
                logError($response);
            } else {
                // Extract the order ID if possible
                if (preg_match('/Order ID: (\w+)/', $response, $matches)) {
                    $order_id = $matches[1];
                    echo 'Extracted Order ID: ' . $order_id . "<br>";

                    // Retry mechanism for checking order status
                    $max_retries = 5;
                    $retry_count = 0;
                    $payment_completed = false;

                    while ($retry_count < $max_retries) {
                        // Check the order status
                        $statusData = [
                            'check_status' => 1,
                            'order_id' => $order_id,
                            'api_key' => $orderData['api_key'],
                            'secret_key' => $orderData['secret_key']
                        ];

                        $ch = curl_init();
                        curl_setopt($ch, CURLOPT_URL, "https://apigw.zeno.africa");
                        curl_setopt($ch, CURLOPT_POST, true);
                        curl_setopt($ch, CURLOPT_POSTFIELDS, http_build_query($statusData));
                        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
                        $statusResponse = curl_exec($ch);

                        if ($statusResponse === false) {
                            echo 'Curl error: ' . curl_error($ch);
                            logError(curl_error($ch));
                        } else {
                            echo 'Check Order Status Response: ' . $statusResponse . "<br>";

                            if (preg_match('/status:\s?(COMPLETE|COMPLETED)/i', $statusResponse)) {
                                $payment_completed = true;
                                break; // Exit loop if payment is completed
                            }
                        }

                        $retry_count++;
                        sleep(50); // Wait 10 seconds before retrying
                    }

                    if ($payment_completed) {
                        // Log transaction and update user balance
                        $log_data = [
                            'ids' => json_encode([session('uid')]),
                            'payer_email' => $user_email,
                            'type' => 'ZenoPay',
                            'transaction_id' => isset($response_data['transaction_id']) ? $response_data['transaction_id'] : uniqid(),
                            'txn_fee' => 0,
                            'note' => '',
                            'data' => json_encode($statusResponse),
                            'old_balance' => $this->get_user_balance(session('uid')),
                            'amount' => $this->input->post('amount'),
                            'status' => 'completed',
                            'created' => date('Y-m-d H:i:s')
                        ];

                        // Insert the transaction log into the database
                        $insert_result = $this->db->insert($this->tb_transaction_logs, $log_data);

                        if (!$insert_result) {
                            $db_error = $this->db->error();
                            log_message('error', 'Transaction log insertion failed: ' . $db_error['message']);
                            show_error('Transaction log insertion failed: ' . $db_error['message']);
                        } else {
                            $this->db->set('balance', 'balance + ' . $this->input->post('amount'), FALSE);
                            $this->db->where('id', session('uid'));
                            $update_balance_result = $this->db->update($this->tb_users);

                            if (!$update_balance_result) {
                                $db_error = $this->db->error();
                                log_message('error', 'Balance update failed: ' . $db_error['message']);
                                show_error('Balance update failed: ' . $db_error['message']);
                            }

                            set_session("transaction_id", $this->db->insert_id());
                            redirect(cn("add_funds/success"));
                        }
                    } else {
                        echo 'Payment not completed after maximum retries.';
                        logError('Payment not completed for order ID: ' . $order_id);
                    }

                    curl_close($ch);
                } else {
                    echo 'Failed to create order or retrieve order ID.';
                    logError($response);
                }
            }
        }

        curl_close($ch);
        $data['result'] = 'Payment has been marked as successful.';
        $this->load->view('zenopay_result', $data);
    }
}


    private function get_user_balance($uid)
    {
        $user = $this->model->get('balance', $this->tb_users, ['id' => $uid]);
        return $user ? $user->balance : 0;
    }

    public function success()
    {
        $id = session("transaction_id");
        $transaction = $this->model->get("*", $this->tb_transaction_logs, "id = '{$id}' AND uid ='" . session('uid') . "'");

        if (!empty($transaction)) {
            $data = [
                "module" => $this->module,
                "transaction" => $transaction,
            ];
            unset_session("transaction_id");
            $this->template->set_layout('user');
            $this->template->build('payment_successfully', $data);
        } else {
            redirect(cn("add_funds"));
        }
    }

    public function unsuccess()
    {
        $data = [
            "module" => $this->module,
        ];
        $this->template->set_layout('user');
        $this->template->build('payment_unsuccessfully', $data);
    }
}
