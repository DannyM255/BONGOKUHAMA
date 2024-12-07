<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>ZenoPay Payment Form</title>
    <style>
        /* Spinner styling */
        .spinner {
            border: 8px solid #f3f3f3; /* Light grey */
            border-top: 8px solid #3498db; /* Blue */
            border-radius: 50%;
            width: 50px;
            height: 50px;
            animation: spin 1s linear infinite;
            margin: 20px auto;
        }

        @keyframes spin {
            0% { transform: rotate(0deg); }
            100% { transform: rotate(360deg); }
        }

        /* Loading message styling */
        .loading-message {
            text-align: center;
            margin-top: 20px;
        }

        /* Hide spinner initially */
        .spinner-container {
            display: none;
        }
    </style>
</head>
<body>
    <?php echo form_open('add_funds/process', ['id' => 'payment-form']); ?>
        <div class="form-group text-center">
            <img src="<?=BASE?>/assets/images/payments/zenopay-logo.png" alt="ZenoPay icon">
            <p class="p-t-10"><small><?=sprintf(lang("you_can_deposit_funds_with_paypal_they_will_be_automaticly_added_into_your_account"), 'ZenoPay')?></small></p>
        </div>
        <fieldset class="form-fieldset m-t-10">
            <div class="form-group">
                <label class="form-label"><?= sprintf(lang("Kiasi"), $currency_code) ?></label>
                <input type="number" id="amount" name="amount" class="form-control" required>
            </div>
            <div class="form-group">
                <label class="form-label"><?= lang("Namba ya Malipo") ?></label>
                <input type="text" name="buyer_number" id="buyer_number" class="form-control" required>
            </div>
            <div class="form-group">
                <button type="submit" id="submit-button">Submit</button>
            </div>
        </fieldset>
    </form>

    <!-- Spinner and message container -->
    <div class="spinner-container" id="spinner-container">
        <div class="spinner"></div>
        <p class="loading-message" id="loading-message">Weka namba ya siri kwenye mtandao husika kufanikisha malipo</p>
    </div>

    <?php if (validation_errors()): ?>
        <div>
            <?php echo validation_errors(); ?>
        </div>
    <?php endif; ?>

    <script>
        // Show spinner and message on form submission
        document.getElementById('payment-form').addEventListener('submit', function() {
            document.getElementById('spinner-container').style.display = 'block';
            checkPaymentStatus();
        });

        function checkPaymentStatus() {
            // Replace with the actual URL to check the payment status
            const statusUrl = 'path/to/check_payment_status'; 
            const pollingInterval = 5000; // 5 seconds

            const polling = setInterval(async function() {
                try {
                    const response = await fetch(statusUrl, { method: 'GET' });
                    const data = await response.json();

                    if (data.status === 'success') {
                        document.getElementById('loading-message').innerText = 'Hongera sana';
                        // Continue loading to check for further updates or user actions
                    } else if (data.status === 'pending') {
                        document.getElementById('loading-message').innerText = 'Tunasubiri ufanye malipo';
                    } else if (data.status === 'failed') {
                        document.getElementById('loading-message').innerText = 'Malipo yamekataa';
                        clearInterval(polling); // Stop polling on failure
                    }
                } catch (error) {
                    console.error('Error checking payment status:', error);
                }
            }, pollingInterval);
        }
    </script>
</body>
</html>
