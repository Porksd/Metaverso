<?php

/**
 * This file is part of FPDI
 *
 * @package   setasign\Fpdi
 * @copyright Copyright (c) 2023 Setasign GmbH & Co. KG (https://www.setasign.com)
 * @license   http://opensource.org/licenses/mit-license The MIT License
 *
 * Modified on 01-August-2024 using Strauss.
 * @see https://github.com/BrianHenryIE/strauss
 */

namespace BitCode\BitFormPro\Dependencies\setasign\Fpdi;

use BitCode\BitFormPro\Dependencies\setasign\Fpdi\PdfParser\CrossReference\CrossReferenceException;
use BitCode\BitFormPro\Dependencies\setasign\Fpdi\PdfParser\PdfParserException;
use BitCode\BitFormPro\Dependencies\setasign\Fpdi\PdfParser\Type\PdfIndirectObject;
use BitCode\BitFormPro\Dependencies\setasign\Fpdi\PdfParser\Type\PdfNull;

/**
 * Class Fpdi
 *
 * This class let you import pages of existing PDF documents into a reusable structure for FPDF.
 */
class Fpdi extends FpdfTpl
{
    use FpdiTrait;
    use FpdfTrait;

    /**
     * FPDI version
     *
     * @string
     */
    const VERSION = '2.6.0';
}
