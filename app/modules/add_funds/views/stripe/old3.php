
<style>
    /**
   * The CSS shown here will not be introduced in the Quickstart guide, but shows
   * how you can use CSS to style your Element's container.
   */
  .StripeElement {
    box-sizing: border-box;
    width: 100%;
    height: 40px;
    margin: 10px;
    padding: 10px 12px;
    border: 1px solid transparent;
    border-radius: 4px;
    background-color: white;
    box-shadow: 0 1px 3px 0 #e6ebf1;
    -webkit-transition: box-shadow 150ms ease;
    transition: box-shadow 150ms ease;
  }

  .StripeElement--focus {
    box-shadow: 0 1px 3px 0 #cfd7df;
  }

  .StripeElement--invalid {
    border-color: #fa755a;
  }

  .StripeElement--webkit-autofill {
    background-color: #fefde5 !important;
  }
</style>

<style>
  .creditCardForm .form-control {
    font-size: 14px;
    border: 1px solid #ccc;
    border-radius: 4px;
  }
  .creditCardForm .form-group .transparent {
    opacity: 0.1;
  }
</style>
<script src="https://js.stripe.com/v3/"></script>
<?php
  $option           = get_value($payment_params, 'option');
  $min_amount       = get_value($payment_params, 'min');
  $max_amount       = get_value($payment_params, 'max');
  $type             = get_value($payment_params, 'type');
  $tnx_fee          = get_value($option, 'tnx_fee');
  $currency_code    = get_option("currency_code",'TZS');
  $currency_symbol  = get_option("currency_symbol",'TZS');
?>

<form class="creditCardForm actionAddFundsStripeCheckoutForm" action="#" method="post" id="payment-form">
  <div class="form-group text-center">
    <img src="<?=BASE?>/assets/images/payments/zenopay-logo.png" alt="ZenoPay icon">
    <p class="p-t-10"><small><?=sprintf(lang("you_can_deposit_funds_with_paypal_they_will_be_automaticly_added_into_your_account"), 'Stripe')?></small></p>
  </div>

  <fieldset class="form-fieldset m-t-10">
  <div class="form-group">
                <label class="form-label"><?= sprintf(lang("amount"), $currency_code) ?></label>
                <input type="text" name="amount" class="form-control" required>
            </div>
            <div class="form-group">
                <label class="form-label"><?= lang("buyer_phone") ?></label>
                <input type="tel" name="buyer_phone" id="buyer_phone" class="form-control" pattern="255[0-9]{9}" placeholder="255XXXXXXXXX" required>
            </div>
            <div class="form-group">
                <label class="form-label"><?= lang("buyer_email") ?></label>
                <input type="email" name="buyer_email" class="form-control" required>
            </div>
            <div class="form-group">
                <label class="form-label"><?= lang("buyer_name") ?></label>
                <input type="text" name="buyer_name" class="form-control" required>
            </div>

    <div class="form-row mt15">
      <div id="card-element"></div>
      <div id="card-errors" role="alert" class="text-danger"></div>
    </div>
    
    <div class="form-group">
      <label><?php echo lang("note"); ?></label>
      <ul>
        <?php if ($tnx_fee > 0): ?>
          <li><?=lang("transaction_fee")?>: <strong><?php echo $tnx_fee; ?>%</strong></li>
        <?php endif; ?>
        <li><?=lang("Minimal_payment")?>: <strong><?php echo $currency_symbol.$min_amount; ?></strong></li>
        <?php if ($max_amount > 0): ?>
          <li><?=lang("Maximal_payment")?>: <strong><?php echo $currency_symbol.$max_amount; ?></strong></li>
        <?php endif; ?>
      </ul>
    </div>

    <div class="form-group">
      <label class="custom-control custom-checkbox">
        <input type="checkbox" class="custom-control-input" name="agree" value="1" required>
        <span class="custom-control-label text-uppercase"><strong><?=lang("yes_i_understand_after_the_funds_added_i_will_not_ask_fraudulent_dispute_or_chargeback")?></strong></span>
      </label>
    </div>

    <div class="form-group">
      <input type="hidden" name="payment_id" value="<?php echo $payment_id; ?>">
      <input type="hidden" name="payment_method" value="<?php echo $type; ?>">
      <input type="hidden" name="buyer_email" value="<?php echo post('buyer_email'); ?>">
      <input type="hidden" name="buyer_name" value="<?php echo post('buyer_name'); ?>">
      <input type="hidden" name="buyer_phone" value="<?php echo post('buyer_phone'); ?>">
      <button type="submit" class="btn btn-dark btn-lg btn-block m-t-15"><?=lang('Submit')?></button>
    </div>
  </fieldset>
</form>


<script type="text/javascript">
document.addEventListener('DOMContentLoaded', function() {
  // Handle form submission
  var form = document.getElementById('payment-form');
  form.addEventListener('submit', function(event) {
      event.preventDefault();
      var _that = $(this);

      // Collect form data
      var formData = new FormData(form);
      
      // Show overlay
      pageOverlay.show();

      // Send form data to server for processing
      $.ajax({
        type: 'POST',
        url: PATH + 'add_funds/process',
        data: formData,
        processData: false, // Prevent jQuery from automatically transforming the data into a query string
        contentType: false, // Let the browser set the content type
        success: function(response) {
          console.log(response);
          setTimeout(function() {
            pageOverlay.hide();
          }, 1500);

          if (is_json(response)) {
            response = JSON.parse(response);
            setTimeout(function() {
              notify(response.message, response.status);
              if (response.result === 'success' && response.redirect) {
                window.location.href = response.redirect;
              }
            }, 1500);
          } else {
            setTimeout(function() {
              $(".actionAddFundsStripeCheckoutForm").html(response);
            }, 1500);
          }
        },
        error: function() {
          pageOverlay.hide();
          notify('An error occurred while processing your request.', 'error');
        }
      });
  });
});
</script>
