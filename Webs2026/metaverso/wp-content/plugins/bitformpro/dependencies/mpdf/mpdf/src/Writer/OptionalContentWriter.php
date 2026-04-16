<?php
/**
 * @license GPL-2.0-only
 *
 * Modified on 01-August-2024 using Strauss.
 * @see https://github.com/BrianHenryIE/strauss
 */

namespace BitCode\BitFormPro\Dependencies\Mpdf\Writer;

use BitCode\BitFormPro\Dependencies\Mpdf\Strict;
use BitCode\BitFormPro\Dependencies\Mpdf\Mpdf;

final class OptionalContentWriter
{

	use Strict;

	/**
	 * @var \BitCode\BitFormPro\Dependencies\Mpdf\Mpdf
	 */
	private $mpdf;

	/**
	 * @var \BitCode\BitFormPro\Dependencies\Mpdf\Writer\BaseWriter
	 */
	private $writer;

	public function __construct(Mpdf $mpdf, BaseWriter $writer)
	{
		$this->mpdf = $mpdf;
		$this->writer = $writer;
	}

	public function writeOptionalContentGroups() // _putocg Optional Content Groups
	{
		if ($this->mpdf->hasOC) {

			$this->writer->object();
			$this->mpdf->n_ocg_print = $this->mpdf->n;
			$this->writer->write('<</Type /OCG /Name ' . $this->writer->string('Print only'));
			$this->writer->write('/Usage <</Print <</PrintState /ON>> /View <</ViewState /OFF>>>>>>');
			$this->writer->write('endobj');

			$this->writer->object();
			$this->mpdf->n_ocg_view = $this->mpdf->n;
			$this->writer->write('<</Type /OCG /Name ' . $this->writer->string('Screen only'));
			$this->writer->write('/Usage <</Print <</PrintState /OFF>> /View <</ViewState /ON>>>>>>');
			$this->writer->write('endobj');

			$this->writer->object();
			$this->mpdf->n_ocg_hidden = $this->mpdf->n;
			$this->writer->write('<</Type /OCG /Name ' . $this->writer->string('Hidden'));
			$this->writer->write('/Usage <</Print <</PrintState /OFF>> /View <</ViewState /OFF>>>>>>');
			$this->writer->write('endobj');
		}

		if (count($this->mpdf->layers)) {

			ksort($this->mpdf->layers);
			foreach ($this->mpdf->layers as $id => $layer) {
				$this->writer->object();
				$this->mpdf->layers[$id]['n'] = $this->mpdf->n;

				if (isset($this->mpdf->layerDetails[$id]['name']) && $this->mpdf->layerDetails[$id]['name']) {
					$name = $this->mpdf->layerDetails[$id]['name'];
				} else {
					$name = $layer['name'];
				}

				$this->writer->write('<</Type /OCG /Name ' . $this->writer->utf16BigEndianTextString($name) . '>>');
				$this->writer->write('endobj');
			}
		}
	}

}
