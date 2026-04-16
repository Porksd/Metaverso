<?php
/**
 * @license GPL-2.0-only
 *
 * Modified on 01-August-2024 using Strauss.
 * @see https://github.com/BrianHenryIE/strauss
 */

namespace BitCode\BitFormPro\Dependencies\Mpdf;

use BitCode\BitFormPro\Dependencies\Mpdf\Color\ColorConverter;
use BitCode\BitFormPro\Dependencies\Mpdf\Color\ColorModeConverter;
use BitCode\BitFormPro\Dependencies\Mpdf\Color\ColorSpaceRestrictor;
use BitCode\BitFormPro\Dependencies\Mpdf\Fonts\FontCache;
use BitCode\BitFormPro\Dependencies\Mpdf\Fonts\FontFileFinder;
use BitCode\BitFormPro\Dependencies\Mpdf\Image\ImageProcessor;
use BitCode\BitFormPro\Dependencies\Mpdf\Pdf\Protection;
use BitCode\BitFormPro\Dependencies\Mpdf\Pdf\Protection\UniqidGenerator;
use BitCode\BitFormPro\Dependencies\Mpdf\Writer\BaseWriter;
use BitCode\BitFormPro\Dependencies\Mpdf\Writer\BackgroundWriter;
use BitCode\BitFormPro\Dependencies\Mpdf\Writer\ColorWriter;
use BitCode\BitFormPro\Dependencies\Mpdf\Writer\BookmarkWriter;
use BitCode\BitFormPro\Dependencies\Mpdf\Writer\FontWriter;
use BitCode\BitFormPro\Dependencies\Mpdf\Writer\FormWriter;
use BitCode\BitFormPro\Dependencies\Mpdf\Writer\ImageWriter;
use BitCode\BitFormPro\Dependencies\Mpdf\Writer\JavaScriptWriter;
use BitCode\BitFormPro\Dependencies\Mpdf\Writer\MetadataWriter;
use BitCode\BitFormPro\Dependencies\Mpdf\Writer\OptionalContentWriter;
use BitCode\BitFormPro\Dependencies\Mpdf\Writer\PageWriter;
use BitCode\BitFormPro\Dependencies\Mpdf\Writer\ResourceWriter;
use BitCode\BitFormPro\Dependencies\Psr\Log\LoggerInterface;

class ServiceFactory
{

	public function getServices(
		Mpdf $mpdf,
		LoggerInterface $logger,
		$config,
		$restrictColorSpace,
		$languageToFont,
		$scriptToLanguage,
		$fontDescriptor,
		$bmp,
		$directWrite,
		$wmf
	) {
		$sizeConverter = new SizeConverter($mpdf->dpi, $mpdf->default_font_size, $mpdf, $logger);

		$colorModeConverter = new ColorModeConverter();
		$colorSpaceRestrictor = new ColorSpaceRestrictor(
			$mpdf,
			$colorModeConverter,
			$restrictColorSpace
		);
		$colorConverter = new ColorConverter($mpdf, $colorModeConverter, $colorSpaceRestrictor);

		$tableOfContents = new TableOfContents($mpdf, $sizeConverter);

		$cacheBasePath = $config['tempDir'] . '/mpdf';

		$cache = new Cache($cacheBasePath, $config['cacheCleanupInterval']);
		$fontCache = new FontCache(new Cache($cacheBasePath . '/ttfontdata', $config['cacheCleanupInterval']));

		$fontFileFinder = new FontFileFinder($config['fontDir']);

		$remoteContentFetcher = new RemoteContentFetcher($mpdf, $logger);

		$cssManager = new CssManager($mpdf, $cache, $sizeConverter, $colorConverter, $remoteContentFetcher);

		$otl = new Otl($mpdf, $fontCache);

		$protection = new Protection(new UniqidGenerator());

		$writer = new BaseWriter($mpdf, $protection);

		$gradient = new Gradient($mpdf, $sizeConverter, $colorConverter, $writer);

		$formWriter = new FormWriter($mpdf, $writer);

		$form = new Form($mpdf, $otl, $colorConverter, $writer, $formWriter);

		$hyphenator = new Hyphenator($mpdf);

		$imageProcessor = new ImageProcessor(
			$mpdf,
			$otl,
			$cssManager,
			$sizeConverter,
			$colorConverter,
			$colorModeConverter,
			$cache,
			$languageToFont,
			$scriptToLanguage,
			$remoteContentFetcher,
			$logger
		);

		$tag = new Tag(
			$mpdf,
			$cache,
			$cssManager,
			$form,
			$otl,
			$tableOfContents,
			$sizeConverter,
			$colorConverter,
			$imageProcessor,
			$languageToFont
		);

		$fontWriter = new FontWriter($mpdf, $writer, $fontCache, $fontDescriptor);
		$metadataWriter = new MetadataWriter($mpdf, $writer, $form, $protection, $logger);
		$imageWriter = new ImageWriter($mpdf, $writer);
		$pageWriter = new PageWriter($mpdf, $form, $writer, $metadataWriter);
		$bookmarkWriter = new BookmarkWriter($mpdf, $writer);
		$optionalContentWriter = new OptionalContentWriter($mpdf, $writer);
		$colorWriter = new ColorWriter($mpdf, $writer);
		$backgroundWriter = new BackgroundWriter($mpdf, $writer);
		$javaScriptWriter = new JavaScriptWriter($mpdf, $writer);

		$resourceWriter = new ResourceWriter(
			$mpdf,
			$writer,
			$colorWriter,
			$fontWriter,
			$imageWriter,
			$formWriter,
			$optionalContentWriter,
			$backgroundWriter,
			$bookmarkWriter,
			$metadataWriter,
			$javaScriptWriter,
			$logger
		);

		return [
			'otl' => $otl,
			'bmp' => $bmp,
			'cache' => $cache,
			'cssManager' => $cssManager,
			'directWrite' => $directWrite,
			'fontCache' => $fontCache,
			'fontFileFinder' => $fontFileFinder,
			'form' => $form,
			'gradient' => $gradient,
			'tableOfContents' => $tableOfContents,
			'tag' => $tag,
			'wmf' => $wmf,
			'sizeConverter' => $sizeConverter,
			'colorConverter' => $colorConverter,
			'hyphenator' => $hyphenator,
			'remoteContentFetcher' => $remoteContentFetcher,
			'imageProcessor' => $imageProcessor,
			'protection' => $protection,

			'languageToFont' => $languageToFont,
			'scriptToLanguage' => $scriptToLanguage,

			'writer' => $writer,
			'fontWriter' => $fontWriter,
			'metadataWriter' => $metadataWriter,
			'imageWriter' => $imageWriter,
			'formWriter' => $formWriter,
			'pageWriter' => $pageWriter,
			'bookmarkWriter' => $bookmarkWriter,
			'optionalContentWriter' => $optionalContentWriter,
			'colorWriter' => $colorWriter,
			'backgroundWriter' => $backgroundWriter,
			'javaScriptWriter' => $javaScriptWriter,

			'resourceWriter' => $resourceWriter
		];
	}

}
