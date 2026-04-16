<?php

namespace BitCode\BitForm\Frontend\Form\View\Theme\Fields;

use BitCode\BitForm\Core\Util\FrontendHelpers;

class AdvanceDateTimeField
{
  public static function init($field, $rowID, $field_name, $form_atomic_Cls_map, $formID, $error = null, $value = null)
  {
    $inputWrapper = new ClassicInputWrapper($field, $rowID, $field_name, $form_atomic_Cls_map, $formID, $error, $value);
    $input = self::field($field, $rowID, $field_name, $form_atomic_Cls_map, $formID, $error, $value);
    return $inputWrapper->wrapper($input);
  }

  private static function field($field, $rowID, $field_name, $form_atomic_Cls_map, $formID, $error = null, $value = null)
  {
    $fieldHelpers = new ClassicFieldHelpers($field, $rowID, $form_atomic_Cls_map);
    $prefixIcn = $fieldHelpers->icon('prefixIcn', 'pre-i');
    $suffixIcn = $fieldHelpers->icon('suffixIcn', 'suf-i');
    $name = $fieldHelpers->name();
    $disabled = $fieldHelpers->disabled();
    $readonly = $fieldHelpers->readonly();
    $bfFrontendFormIds = FrontendHelpers::$bfFrontendFormIds;
    $contentCount = count($bfFrontendFormIds);
    $ph = $fieldHelpers->placeholder();
    $ac = $fieldHelpers->autocomplete();

    return <<<ADVANCEDATETIMEFIELD
    <div 
      {$fieldHelpers->getCustomAttributes('inp-fld-wrp')}
      class="{$fieldHelpers->getAtomicCls('inp-fld-wrp')} {$fieldHelpers->getCustomClasses('inp-fld-wrp')}"
    >
      <input
         {$fieldHelpers->getCustomAttributes('fld')}
        id="{$rowID}-{$contentCount}"
        class="{$rowID}-advanced-datetime {$fieldHelpers->getAtomicCls('fld')} {$fieldHelpers->getCustomClasses('fld')} bf-advanced-datetime-hidden-input"
        type="text"
        autocomplete="off"
        {$name}
        {$value}
        {$ph}
        {$ac}
        {$disabled}
        {$readonly}
      />
      {$prefixIcn}
      {$suffixIcn}
    </div>
ADVANCEDATETIMEFIELD;
  }
}
