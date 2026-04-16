<?php

namespace BitCode\BitFormPro\Admin\FormSettings;

use BitCode\BitForm\Admin\Form\FrontEndScriptGenerator;

final class StandaloneForm
{
  public static function saveStandaloneCSS()
  {
    if (wp_verify_nonce(sanitize_text_field($_REQUEST['_ajax_nonce']), 'bitforms_save')) {
      $inputJSON = file_get_contents('php://input');
      $requestsParams = json_decode($inputJSON);
      $formId = $requestsParams->formID;
      $css = $requestsParams->css;

      $path = 'form-styles';
      $fileName = "bitform-standalone-$formId.css";
      FrontEndScriptGenerator::customCodeFileSaveOrDelete($css, $path, $fileName);
      wp_send_json_success(__('CSS Saved Successfully!', 'bitform'), 200);
    } else {
      wp_send_json_error(
        __(
          'Token expired',
          'bitform'
        ),
        401
      );
    }
  }
}
