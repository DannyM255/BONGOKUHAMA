<?php
if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    // URL of the API endpoint
    $url = "https://ezycard.zeno.africa/process.php";

    // Data to send in the POST request
    $data = [
        'create_order' => 1,
        'buyer_email' => $_POST['buyer_email'],
        'buyer_name' => $_POST['buyer_name'],
        'buyer_phone' => $_POST['buyer_phone'],
        'amount' => $_POST['amount'],
        'buy_number' => $_POST['buy_number'],
        'account_id' => 'zp66072',
        'api_key' => '315cfb2f39b1c1da523e48a59bb34500',
        'secret_key' => '31278648f87c824f6907d50499273ab1a521556487b378403752e7b7541c3d9c'
    ];

    // Initialize cURL session
    $ch = curl_init();

    // Set cURL options
    curl_setopt($ch, CURLOPT_URL, $url);
    curl_setopt($ch, CURLOPT_POST, true);
    curl_setopt($ch, CURLOPT_POSTFIELDS, http_build_query($data));
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);

    // Execute cURL session
    $response = curl_exec($ch);

    // Check for errors
    if ($response === false) {
        $result = 'Curl error: ' . curl_error($ch);
    } else {
        $result = 'Response: ' . $response;
    }

    // Close cURL session
    curl_close($ch);
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Payment Form</title>
    <style>
        body {
            font-family: Arial, sans-serif;
            background-color: #f4f4f4;
            display: flex;
            justify-content: center;
            align-items: center;
            height: 100vh;
            margin: 0;
        }
        .container {
            background-color: #fff;
            padding: 20px;
            border-radius: 5px;
            box-shadow: 0 0 10px rgba(0, 0, 0, 0.1);
            width: 300px;
        }
        .form-group {
            margin-bottom: 15px;
        }
        .form-group label {
            display: block;
            margin-bottom: 5px;
        }
        .form-group input {
            width: 100%;
            padding: 8px;
            box-sizing: border-box;
        }
        .form-group button {
            width: 100%;
            padding: 10px;
            background-color: #28a745;
            border: none;
            color: #fff;
            font-size: 16px;
            cursor: pointer;
        }
        .form-group button:hover {
            background-color: #218838;
        }
        .result {
            margin-top: 20px;
            padding: 10px;
            background-color: #f8f9fa;
            border: 1px solid #ddd;
            border-radius: 5px;
        }
    </style>
</head>
<body>
    <div class="container">
        <h2>Payment Form</h2>
        <form method="POST" action="">
            <div class="form-group">
                <label for="buyer_email">Email:</label>
                <input type="email" id="buyer_email" name="buyer_email" required>
            </div>
            <div class="form-group">
                <label for="buyer_name">Name:</label>
                <input type="text" id="buyer_name" name="buyer_name" required>
            </div>
            <div class="form-group">
                <label for="buyer_phone">Phone:</label>
                <input type="text" id="buyer_phone" name="buyer_phone" required>
            </div>
            <div class="form-group">
                <label for="amount">Amount:</label>
                <input type="number" id="amount" name="amount" required>
            </div>
            <div class="form-group">
                <label for="buy_number">Buy Number:</label>
                <input type="text" id="buy_number" name="buy_number" required>
            </div>
            <div class="form-group">
                <button type="submit">Submit</button>
            </div>
        </form>
        <?php if (isset($result)): ?>
            <div class="result">
                <?php echo $result; ?>
            </div>
        <?php endif; ?>
    </div>
</body>
</html>
