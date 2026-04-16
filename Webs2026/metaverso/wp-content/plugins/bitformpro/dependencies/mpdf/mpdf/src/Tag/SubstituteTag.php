<?php
/**
 * @license GPL-2.0-only
 *
 * Modified on 01-August-2024 using Strauss.
 * @see https://github.com/BrianHenryIE/strauss
 */

namespace BitCode\BitFormPro\Dependencies\Mpdf\Tag;

abstract class SubstituteTag extends Tag
{

	public function close(&$ahtml, &$ihtml)
	{
		$tag = $this->getTagName();
		if ($this->mpdf->InlineProperties[$tag]) {
			$this->mpdf->restoreInlineProperties($this->mpdf->InlineProperties[$tag]);
		}
		unset($this->mpdf->InlineProperties[$tag]);
		$ltag = strtolower($tag);
		$this->mpdf->$ltag = false;
	}
}
