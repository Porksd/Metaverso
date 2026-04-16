<style>
  .cashback-wrapper {
  background-color: #fff;
  border-radius: 5px;
    box-shadow: 0px 3px 10px 1px rgb(0 0 0 / 5%);
  }

  .cashback-wrapper .title-wrapper {
  display: flex;
  flex-direction: column;
  justify-content: center;
  align-items: center;
  font-family: 'Outfit', sans-serif;
  background-image: url("<?= BITFORMPRO_ASSET_URI ?>/img/cashback.jpg");
  background-repeat: no-repeat;
  background-position: left;
  color: #fff;
  padding: 35px;
  border-top-left-radius: 5px;
    border-top-right-radius: 5px;
  }

  .cashback-wrapper .title {
  margin: 0;
  font-size: 35px;
  font-weight: 700;
  color: #fff;
  }

  .cashback-wrapper .details-wrapper {
  display: flex;
  flex-direction: column;
  justify-content: center;
  align-items: center;
  text-align: justify;
  padding: 15px;
  }

  .cashback-wrapper .details {
  margin: 0;
  }

  .cashback-wrapper .footer-wrapper {
  display: flex;
  justify-content: center;
  align-items: center;
  padding-bottom: 20px;
  }

  .cashback-wrapper .footer-btn {
    display: inline-block;
    border: none;
    border-radius: 5px;
    text-decoration: none;
    font-weight: 500;
    line-height: 1.4;
    padding: 2px 10px;
    position: relative;
    font-size: 1rem;
    color: #fff;
    padding: 5px 12px;
  }

  .cashback-wrapper .footer-btn:focus {
  color: #fff;
  }

  .cashback-wrapper .footer-btn:hover {
  background-color: #3582c4;
  color: #fff;
  }
</style>

<?php

$productName = 'Bit Form';
$reviewUrl = 'https://wordpress.org/support/plugin/bit-form/reviews/#new-post';

?>

<div class="cashback-wrapper">
  <div class="title-wrapper">
    <h3 class="title">
      Get $10 Cashback
    </h3>
    <b>
      Thank you for using <?= $productName ?>
    </b>
  </div>
  <div class="details-wrapper">
    <p class="details">
      Give us a review on WordPress by clicking the 
      <a href="<?= $reviewUrl ?>" target="_blank" rel="noreferrer">Review us</a>
      button and send an email with the review link to
      <a href="mailto:support@bitapps.pro" target="_blank" rel="noreferrer">support@bitapps.pro</a>.
      We will honour you with
      <strong>$10 cashback</strong>
      for your time & effort.
    </p>
  </div>
  <div class="footer-wrapper">
    <a class="footer-btn blue" href="<?= $reviewUrl ?>" target="_blank" rel="noreferrer">Review us</a>
  </div>
</div>