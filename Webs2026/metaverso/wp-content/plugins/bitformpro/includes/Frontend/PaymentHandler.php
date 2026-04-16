<?php

namespace BitCode\BitFormPro\Frontend;

use BitCode\BitFormPro\API\Controller\MolliePaymentController;


class PaymentHandler
{
  public function __construct()
  {
    add_shortcode('bitform_payments', [$this, 'paymentFrontendRender']);
  }

  public function paymentFrontendRender()
  {
    $getParams = $_GET;
    if (!isset($getParams['integID'])) {
      return sprintf(__('Payment integration Id missing', 'bit-form'));
    }

    if (!is_numeric($getParams['integID'])) {
      return sprintf(__('Payment integration Id must be numeric', 'bit-form'));
    }
    $integID = sanitize_text_field($getParams['integID']);

    $transactionID = get_option('bf_mollie_transaction_id');

    $transactionDetails = (new MolliePaymentController())->getMolliePaymentInfo($integID, $transactionID);

    if ($transactionDetails->status === 'paid') {
      (new MolliePaymentController())->saveMolliePaymentInfo($transactionDetails);
    }
    ob_start();
    echo $this->style();
    switch ($transactionDetails->status) {
      case 'paid':
        echo $this->paymentSuccessMarkup($transactionDetails->id);
        break;
      case 'canceled':
        echo $this->paymentCanceledMarkup($transactionDetails->id);
        break;
      case 'failed':
        echo $this->paymentFailedMarkup();
        break;
      default:
        echo $this->paymentStatusDefaultMarkup($transactionDetails);
    }
    return ob_get_clean();

  }

  private function paymentSuccessMarkup($transactionID)
  {
    $html = <<<HTML
    <div class="bf-container">
      <div class="bf-payment-box">
            <div class="bf-icon-box">
                <svg xmlns="http://www.w3.org/2000/svg" width="100" height="100" viewBox="0 0 24 24">
                  <path fill="currentColor" d="m10.562 15.908l6.396-6.396l-.708-.708l-5.688 5.688l-2.85-2.85l-.708.708zM12.003 21q-1.866 0-3.51-.708q-1.643-.709-2.859-1.924t-1.925-2.856T3 12.003t.709-3.51Q4.417 6.85 5.63 5.634t2.857-1.925T11.997 3t3.51.709q1.643.708 2.859 1.922t1.925 2.857t.709 3.509t-.708 3.51t-1.924 2.859t-2.856 1.925t-3.509.709M12 20q3.35 0 5.675-2.325T20 12t-2.325-5.675T12 4T6.325 6.325T4 12t2.325 5.675T12 20m0-8"/>
                </svg>
            </div>
            <h1 class="bf-payment-status">Payment Successful!</h1>
            <p class="bf-status-msg">Thank you for your purchase. Your transaction has been completed successfully.</p>
            <p>Transaction ID: <strong>{$transactionID}</strong></p>
        </div>
    </div>
HTML;
    return $html;
  }

  private function paymentFailedMarkup()
  {
    $html = <<<HTML
    <div class="bf-container">
      <div class="bf-payment-box">
          <div class="bf-icon-box">
            <svg xmlns="http://www.w3.org/2000/svg" width="100" height="100" viewBox="0 0 21 21">
              <g fill="none" fill-rule="evenodd" stroke="currentColor" stroke-linecap="round" stroke-linejoin="round" transform="translate(2 2)">
                <circle cx="8.5" cy="8.5" r="8"/>
                <path d="m5.5 5.5l6 6m0-6l-6 6"/>
              </g>
            </svg>
          </div>
          <h1 class="bf-payment-status">Payment Failed!</h1>
          <p class="bf-status-msg">Sorry, your transaction has been failed. Please try again.</p>
      </div>
    </div>
HTML;
    return $html;
  }
  private function paymentStatusDefaultMarkup($transactionDetails)
  {
    $html = <<<HTML
    <div class="bf-container">
      <div class="bf-payment-box">
          <div class="bf-icon-box">
            <svg xmlns="http://www.w3.org/2000/svg" width="100" height="100" viewBox="0 0 21 21">
              <g fill="none" fill-rule="evenodd" stroke="currentColor" stroke-linecap="round" stroke-linejoin="round" transform="translate(2 2)">
                <circle cx="8.5" cy="8.5" r="8"/>
                <path d="m5.5 5.5l6 6m0-6l-6 6"/>
              </g>
            </svg>
          </div>
          <h1 class="bf-payment-status">Payment {$transactionDetails->status}</h1>
          <p class="bf-status-msg">Sorry, your transaction has been {$transactionDetails->status}. Please try again.</p>
          <p>Transaction ID: <strong>{$transactionDetails->id}</strong></p>
      </div>
    </div>
HTML;
    return $html;
  }
  private function paymentCanceledMarkup($transactionID)
  {
    $html = <<<HTML
    <div class="bf-container">
      <div class="bf-payment-box">
          <div class="bf-icon-box">
            <svg xmlns="http://www.w3.org/2000/svg" width="100" height="100" viewBox="0 0 21 21">
              <g fill="none" fill-rule="evenodd" stroke="currentColor" stroke-linecap="round" stroke-linejoin="round" transform="translate(2 2)">
                <circle cx="8.5" cy="8.5" r="8"/>
                <path d="m5.5 5.5l6 6m0-6l-6 6"/>
              </g>
            </svg>
          </div>
          <h1 class="bf-payment-status">Payment Canceled!</h1>
          <p class="bf-status-msg">Sorry, your transaction has been canceled. Please try again.</p>
          <p>Transaction ID: <strong>{$transactionID}</strong></p>
      </div>
    </div>
HTML;
    return $html;
  }

  public function style()
  {
    return <<<CSS
    <style>
    .bf-container {
        text-align: center;
        background: #fff;
        padding: 40px 20px;
        border-radius: 10px;
        box-shadow: 0 0 20px rgba(0, 0, 0, 0.1);
        max-width: 400px;
        width: 100%;
    }

    .bf-payment-box {
        display: flex;
        flex-direction: column;
        align-items: center;
    }

    .bf-icon-box {
        color: #0062ff;
    }

    .bf-payment-status {
        font-size: 24px;
        margin-bottom: 10px;
        color: #333;
    }

    .bf-status-msg {
        font-size: 16px;
        margin-bottom: 20px;
        color: #666;
    }
    </style>
CSS;
  }

}