<?php
/**
 * @license GPL-2.0-only
 *
 * Modified on 01-August-2024 using Strauss.
 * @see https://github.com/BrianHenryIE/strauss
 */

namespace BitCode\BitFormPro\Dependencies\Mpdf\Language;

interface ScriptToLanguageInterface
{

	public function getLanguageByScript($script);

	public function getLanguageDelimiters($language);

}
