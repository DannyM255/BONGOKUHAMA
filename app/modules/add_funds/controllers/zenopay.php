<?php
defined('BASEPATH') OR exit('No direct script access allowed');
 
class zenopay extends MX_Controller {
    public $tb_users;
    public $tb_transaction_logs;
    public $tb_payments;
    public $tb_payments_bonuses;
    public $stripeapi;
    public $payment_type;
    public $payment_fee;
    public $payment_lib;
    public $currency_code;
    public $mode;

    public function __construct($payment = ""){
        parent::__construct();
        $this->load->model('add_funds_model', 'model');

        $this->tb_users            = USERS;
        $this->tb_transaction_logs = TRANSACTION_LOGS;
        $this->tb_payments         = PAYMENTS_METHOD;
        $this->tb_payments_bonuses = PAYMENTS_BONUSES;
        $this->payment_type		   = "stripe";
        $this->currency_code       = get_option("currency_code", "USD");
        if ($this->currency_code == "") {
            $this->currency_code = 'USD';
        }

        if (!$payment) {
            $payment = $this->model->get('id, type, name, params', $this->tb_payments, ['type' => $this->payment_type]);
        }
        $this->payment_id 	= $payment->id;
        $params  			= $payment->params;
        $option             = get_value($params, 'option');
        $this->mode         = get_value($option, 'environment');
        $this->payment_fee  = get_value($option, 'tnx_fee');
        $this->load->library("stripeapi");
        $this->payment_lib = new stripeapi(get_value($option, 'secret_key'), get_value($option, 'public_key'));
    }

    public function index(){
        
        redirect(cn("add_funds"));
    }

    /**
     *
     * Create payment
     *
     */
    public function create_payment($data_payment = ""){
        _is_ajax($data_payment['module']);
        $amount = $data_payment['amount'];
        $phone = $data_payment['phone'];
        
       
        if (!$amount) {
            _validation('error', lang('There_was_an_error_processing_your_request_Please_try_again_later'));
        }


        $user = session('user_current_info');
        //new 
        
         $url = 'https://apigw.zeno.africa';

 // Data to be sent in the POST request
  $data = [
             'create_order' => 1,
             'buyer_email' => $user['email'],
             'buyer_name' => $user['first_name'],
             'buyer_phone' => $phone,
             'amount' => $amount,
             'account_id' => 'zp66072',
             "webhook" => base64_encode("https://bongofollowers.com/zenopay/complete"),
             'api_key' => '315cfb2f39b1c1da523e48a59bb34500',
             'secret_key' => '31278648f87c824f6907d50499273ab1a521556487b378403752e7b7541c3d9c'
         ]; 

 // Convert the data array to JSON
 $jsonData = json_encode($data);

 // Initialize cURL session
 $ch = curl_init($url);

 // Set the necessary cURL options
 curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);  // Return the response as a string
 curl_setopt($ch, CURLOPT_POST, true);  // HTTP POST method
 curl_setopt($ch, CURLOPT_POSTFIELDS, $jsonData);  // Attach the JSON data

 // Set the request headers (Content-Type and Accept)
 curl_setopt($ch, CURLOPT_HTTPHEADER, array(
     'Content-Type: application/json',  // Indicate that we are sending JSON data
     'Accept: application/json'         // Expecting a JSON response
 ));

 // Execute the POST request and get the response
 $response = curl_exec($ch);

 var_dump($response);
return;
        
        
         //end new 
        

         

        $orderData = [
            'create_order' => 1,
            'buyer_email' => $user['email'],
            'buyer_name' => $user['first_name'],
            'buyer_phone' => $phone,
            'amount' => $amount,
            'account_id' => 'zp66072',
            "webhook" => base64_encode("https://bongofollowers.com/zenopay/complete"),
            'api_key' => '315cfb2f39b1c1da523e48a59bb34500',
            'secret_key' => '31278648f87c824f6907d50499273ab1a521556487b378403752e7b7541c3d9c'
        ]; 
        
         
         
          // Convert the data array to JSON
            $jsonData = json_encode($orderData);
     
        // Initialize cURL session for creating the order
        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL, "https://apigw.zeno.africa");
        curl_setopt($ch, CURLOPT_POST, true);
        curl_setopt($ch, CURLOPT_POSTFIELDS, $orderData);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        // Set the request headers (Content-Type and Accept)
        curl_setopt($ch, CURLOPT_HTTPHEADER, array(
            'Content-Type: application/json',  // Indicate that we are sending JSON data
            'Accept: application/json'  ,
            // Expecting a JSON response
        ));
        $response = curl_exec($ch);
       
        $response = json_decode($response);
        //  var_dump($response);
        //  return;
       
       
        $order_id;
        if ($response->status=== 'success') {
            
             $order_id = $response->order_id;
                    
                    //save transaction  
                    
                    $data_tnx = array(
                        "ids" 				=> ids(),
                        "uid" 				=> session("uid"),
                        "type" 				=> "zenopay",
                        "transaction_id" 	=> $order_id,
                        "amount" 	        => $amount,
                        
                         "status"           => false,
                        'txn_fee'           =>0,
                        "created" 			=> NOW,
                    );
                    
                    
                    $this->db->insert($this->tb_transaction_logs, $data_tnx);
                    
                    
                    $result_array= array(
                        'success'=>true,
                        'order_id'=>$order_id);
                        // _validation('success','Unlock your phone and wait for a prompt to pay'  );
                    
                    ms( $result_array);
          
        } else {
            
              echo 'Curl error: ' . curl_error($ch);
            logError(curl_error($ch));
              _validation('error','Failed to process wallet payment. Please check your payment details or credentials.'  );
              
               $result_array= array(
                        'success'=>false,
                        'order_id'=>$order_id);
                    
                    ms( $result_array);
                  
            }
              
                   
        //  $result_array= array(
        //                 'success'=>false,
        //                 'order_id'=>$order_id);
                    
        //             ms( $result_array);c
               
        //      _validation('error','Failed to process wallet payment. Please check your payment details or credentials.'  );
            
                 
            
    }
        
        
      
    
        
    
    
    
    public function complete(){
       
		error_reporting(1);
		ini_set("display_errors", 1);
		$request = "";
		if ($_SERVER["REQUEST_METHOD"] == "POST") {
			$request = json_decode(file_get_contents('php://input'), true);
		}
	
		

		$order_id = $request['order_id'];
	

		$transaction   = $this->model->get('*', $this->tb_transaction_logs, ['transaction_id' => $order_id]);
		
		if (!$transaction) {
			redirect(cn("add_funds"));
		}

		if ($request['payment_status'] == "COMPLETED") {

			$this->db->update($this->tb_transaction_logs, ['status' => 1, 'transaction_id' => $order_id],  ['id' => $transaction->id]);
			$real_amount = $transaction->amount;
			$user_balance = get_field($this->tb_users, ["id" => $transaction->uid], "balance");
			$user_balance += $real_amount;
			$this->db->update($this->tb_users, ["balance" => $user_balance], ["id" => $transaction->uid]);


			/*----------  Send payment notification email  ----------*/
			if (get_option("is_payment_notice_email", '')) {
				$CI = &get_instance();
				if (empty($CI->payment_model)) {
					$CI->load->model('model', 'payment_model');
				}
				$check_send_email_issue = $CI->payment_model->send_email(get_option('email_payment_notice_subject', ''), get_option('email_payment_notice_content', ''), session('uid'));
				if ($check_send_email_issue) {
					ms(array(
						"status" => "error",
						"message" => $check_send_email_issue,
					));
				}
			}
			set_session("transaction_id", $transaction->id);
			echo "umelubali";
			// redirect(cn("add_funds/success"));
		} else {

			$this->db->update($this->tb_transaction_logs, ['status' => -1],  ['id' => $transaction->id]);
			echo "imekataa";
		}
	}
	
	public function getStatus(){
		error_reporting(1);
		ini_set("display_errors", 1);
		$request = "";
		if ($_SERVER["REQUEST_METHOD"] == "POST") {
			$request = json_decode(file_get_contents('php://input'), true);
		}
		$order_id = $request['order_id'];
		
	    //check order status from zenopay
	    $zeno_response = $this->checkOrderStatus($order_id);
	   // var_dump($zeno_response);
	   // return;
	   
	    if($zeno_response['payment_status']==="COMPLETED"){
	        $transaction   = $this->model->get('*', $this->tb_transaction_logs, ['transaction_id' => $order_id]);
	        
	        $this->db->update($this->tb_transaction_logs, ['status' => 1, 'transaction_id' => $order_id],  ['id' => $transaction->id]);
			$real_amount = $transaction->amount;
			$user_balance = get_field($this->tb_users, ["id" => $transaction->uid], "balance");
			$user_balance += $real_amount;
			$this->db->update($this->tb_users, ["balance" => $user_balance], ["id" => $transaction->uid]);


			/*----------  Send payment notification email  ----------*/
// 			if (get_option("is_payment_notice_email", '')) {
// 				$CI = &get_instance();
// 				if (empty($CI->payment_model)) {
// 					$CI->load->model('model', 'payment_model');
// 				}
// 				$check_send_email_issue = $CI->payment_model->send_email(get_option('email_payment_notice_subject', ''), get_option('email_payment_notice_content', ''), session('uid'));
// 				if ($check_send_email_issue) {
// 					ms(array(
// 						"status" => "error",
// 						"message" => $check_send_email_issue,
// 					));
// 				}
// 			}
// 			set_session("transaction_id", $transaction->id);
			
			$result_array= array('success'=>true);
                        
                    
           return ms( $result_array);
	        
	    }else{
	        $result_array= array('success'=>false);
                        
          return  ms( $result_array);
	        
	    }
	    

		    
	
	}
	
	private function checkStatus($order_id) {
        // Define the maximum time for the status check (60 seconds)
        $maxDuration = 60; // in seconds
        $checkInterval = 5; // Check every 5 seconds
        $elapsedTime = 0;
        $errorPageRedirectTime = 30; // Time after which to redirect to an error page
        
        while ($elapsedTime < $maxDuration) {
            // Check the order status
            $status = $this->getStatus($order_id);
            
            // If the status is found or whatever condition you're looking for, you can break the loop
            if ($status) {
                header('Location: https://bongofollowers.com/add_funds/success');
            }
            
            // Sleep for the interval (5 seconds)
            sleep($checkInterval);
            $elapsedTime += $checkInterval;
            
            // Check if it's time to redirect to the error page
            if ($elapsedTime >= $errorPageRedirectTime) {
                header('Location: https://bongofollowers.com/add_funds/unsuccess');
                exit; // Ensure no further code is executed
            }
        }
        
        // After 60 seconds, stop checking and return a default message or take another action
        return 'Order status check timed out';
    }
    
    private function checkOrderStatus($order_id) {
       
        
        // Data to send for checking the order status
        $statusData = [
            'check_status' => 1,
            'order_id' => $order_id,
           'api_key' => '315cfb2f39b1c1da523e48a59bb34500',
            'secret_key' => '31278648f87c824f6907d50499273ab1a521556487b378403752e7b7541c3d9c'
        ];
    
        // Initialize cURL session for checking the order status
        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL, "https://apigw.zeno.africa/order");
        curl_setopt($ch, CURLOPT_POST, true);
        curl_setopt($ch, CURLOPT_POSTFIELDS, http_build_query($statusData));
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        $response = curl_exec($ch);
        
        
    
        if ($response === false) {
            return array('payment_status'=>'error');
        } else {
            return json_decode( $response, true);
        }
        curl_close($ch);
    }

}