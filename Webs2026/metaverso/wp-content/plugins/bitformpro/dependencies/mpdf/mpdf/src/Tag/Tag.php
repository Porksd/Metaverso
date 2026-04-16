<?php
/**
 * @license GPL-2.0-only
 *
 * Modified on 01-August-2024 using Strauss.
 * @see https://github.com/BrianHenryIE/strauss
 */

namespace BitCode\BitFormPro\Dependencies\Mpdf\Tag;

use BitCode\BitFormPro\Dependencies\Mpdf\Strict;

use BitCode\BitFormPro\Dependencies\Mpdf\Cache;
use BitCode\BitFormPro\Dependencies\Mpdf\Color\ColorConverter;
use BitCode\BitFormPro\Dependencies\Mpdf\CssManager;
use BitCode\BitFormPro\Dependencies\Mpdf\Form;
use BitCode\BitFormPro\Dependencies\Mpdf\Image\ImageProcessor;
use BitCode\BitFormPro\Dependencies\Mpdf\Language\LanguageToFontInterface;
use BitCode\BitFormPro\Dependencies\Mpdf\Mpdf;
use BitCode\BitFormPro\Dependencies\Mpdf\Otl;
use BitCode\BitFormPro\Dependencies\Mpdf\SizeConverter;
use BitCode\BitFormPro\Dependencies\Mpdf\TableOfContents;

abstract class Tag
{

	use Strict;

	/**
	 * @var \BitCode\BitFormPro\Dependencies\Mpdf\Mpdf
	 */
	protected $mpdf;

	/**
	 * @var \BitCode\BitFormPro\Dependencies\Mpdf\Cache
	 */
	protected $cache;

	/**
	 * @var \BitCode\BitFormPro\Dependencies\Mpdf\CssManager
	 */
	protected $cssManager;

	/**
	 * @var \BitCode\BitFormPro\Dependencies\Mpdf\Form
	 */
	protected $form;

	/**
	 * @var \BitCode\BitFormPro\Dependencies\Mpdf\Otl
	 */
	protected $otl;

	/**
	 * @var \BitCode\BitFormPro\Dependencies\Mpdf\TableOfContents
	 */
	protected $tableOfContents;

	/**
	 * @var \BitCode\BitFormPro\Dependencies\Mpdf\SizeConverter
	 */
	protected $sizeConverter;

	/**
	 * @var \BitCode\BitFormPro\Dependencies\Mpdf\Color\ColorConverter
	 */
	protected $colorConverter;

	/**
	 * @var \BitCode\BitFormPro\Dependencies\Mpdf\Image\ImageProcessor
	 */
	protected $imageProcessor;

	/**
	 * @var \BitCode\BitFormPro\Dependencies\Mpdf\Language\LanguageToFontInterface
	 */
	protected $languageToFont;

	const ALIGN = [
		'left' => 'L',
		'center' => 'C',
		'right' => 'R',
		'top' => 'T',
		'text-top' => 'TT',
		'middle' => 'M',
		'baseline' => 'BS',
		'bottom' => 'B',
		'text-bottom' => 'TB',
		'justify' => 'J'
	];

	public function __construct(
		Mpdf $mpdf,
		Cache $cache,
		CssManager $cssManager,
		Form $form,
		Otl $otl,
		TableOfContents $tableOfContents,
		SizeConverter $sizeConverter,
		ColorConverter $colorConverter,
		ImageProcessor $imageProcessor,
		LanguageToFontInterface $languageToFont
	) {

		$this->mpdf = $mpdf;
		$this->cache = $cache;
		$this->cssManager = $cssManager;
		$this->form = $form;
		$this->otl = $otl;
		$this->tableOfContents = $tableOfContents;
		$this->sizeConverter = $sizeConverter;
		$this->colorConverter = $colorConverter;
		$this->imageProcessor = $imageProcessor;
		$this->languageToFont = $languageToFont;
	}

	public function getTagName()
	{
		$tag = get_class($this);
		return strtoupper(str_replace('BitCode\BitFormPro\Dependencies\Mpdf\Tag\\', '', $tag));
	}

	protected function getAlign($property)
	{
		$property = strtolower($property);
		return array_key_exists($property, self::ALIGN) ? self::ALIGN[$property] : '';
	}

	abstract public function open($attr, &$ahtml, &$ihtml);

	abstract public function close(&$ahtml, &$ihtml);

}
