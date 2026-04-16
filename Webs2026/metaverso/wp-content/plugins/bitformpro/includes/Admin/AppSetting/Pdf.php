<?php

namespace BitCode\BitFormPro\Admin\AppSetting;

use BitCode\BitForm\Core\Integration\IntegrationHandler;
use BitCode\BitForm\Core\Util\IpTool;
use BitCode\BitFormPro\Dependencies\Mpdf\Config\ConfigVariables;
use BitCode\BitFormPro\Dependencies\Mpdf\HTMLParserMode;
use BitCode\BitFormPro\Dependencies\Mpdf\Mpdf;
use BitCode\BitFormPro\Dependencies\Mpdf\MpdfException;
use WP_Error;


final class Pdf
{
    private static $instance;

    private static $ipTool;

    /**
     * @var array ( fonts array )
     */
    private $fontsArr;

    /**
     * @var string
     */
    private $githubRepo;
    public function __construct()
    {
        static::$ipTool = new IpTool();
        $this->githubRepo = 'https://raw.githubusercontent.com/mpdf/mpdf/master/ttfonts/';
        $fontStrFile = BITFORMPRO_PLUGIN_DIR_PATH . DIRECTORY_SEPARATOR . 'includes' . DIRECTORY_SEPARATOR . 'mpdf-fonts.json';
        $fontStr = file_get_contents($fontStrFile);
        $this->fontsArr = json_decode($fontStr, true);
    }

    /**
     * Get font variant
     * @param $fontName
     *
     * @return array ( font variants array )
     */
    private function getFontVariant($fontName)
    {
        if (array_key_exists($fontName, $this->fontsArr)) {
            return $this->fontsArr[$fontName]['variants'];
        }
        return [];
    }

    /**
     * Save PDF setting
     *
     * @param object $pdfConfig Configuration for pdf
     *
     * @return object response
     */
    public function savePdfSetting($pdfConfig)
    {
        $integrationID = null;

        if (isset($pdfConfig->id)) {
            $integrationID = $pdfConfig->id;
            unset($pdfConfig->id);
        }

        $integrationName = 'PDF';
        $integrationType = 'pdf';
        $font = $pdfConfig->font;
        $pdfConfig = json_encode($pdfConfig);
        $integrationDetails = $pdfConfig;
        $user_details = static::$ipTool->getUserDetail();
        $integrationHandler = new IntegrationHandler(0, $user_details);
        $response = [];

        if (!empty($font)) {
            $this->fontsDownload($font->name);
            $this->updateDownloadedOption([$font->name]);
        }

        if (empty($integrationID)) {
            $pdfSaveStatus = $integrationHandler->saveIntegration($integrationName, $integrationType, $integrationDetails, 'app');
            $response['id'] = $pdfSaveStatus;
            $response['message'] = __('PDF setting saved successfully', 'bit-form');
        } else {
            $pdfSaveStatus = $integrationHandler->updateIntegration($integrationID, $integrationName, $integrationType, $integrationDetails, 'app');
            $response['message'] = __('PDF setting updated successfully', 'bit-form');
        }

        if (is_wp_error($pdfSaveStatus))
            return $pdfSaveStatus;

        return $response;
    }

    public function getPdfSetting()
    {
        $user_details = static::$ipTool->getUserDetail();
        $integrationHandler = new IntegrationHandler(0, $user_details);
        $formIntegrations = $integrationHandler->getAllIntegration('app', 'pdf');

        if (is_wp_error($formIntegrations))
            return $formIntegrations;

        return $formIntegrations[0];
    }

    public function fontsDownload($fontName)
    {
        $variants = $this->getFontVariant($fontName);

        foreach ($variants as $f)
            $this->fontDownload($f);

        return true;
    }

    /**
     * Download font from github mpdf repo
     * @param $fontType
     * @return boolean success or error
     */
    private function fontDownload($fontType)
    {
        if (file_exists($this->getFontDir() . DIRECTORY_SEPARATOR . $fontType))
            return true;

        $destination = $this->getFontDir();

        if (is_wp_error($destination))
            return $destination;

        $response = wp_remote_get(
            $this->githubRepo . $fontType,
            [
                'timeout' => 60,
                'stream' => true,
                'filename' => $destination . DIRECTORY_SEPARATOR . $fontType,
            ]
        );

        if (is_wp_error($response))
            return $response;

        $resCode = wp_remote_retrieve_response_code($response);

        if (200 !== $resCode)
            return new \WP_Error('failed', 'Font Download Failed');

        return true;
    }

    private function getFontDir()
    {
        return $this->getDir('fonts');
    }
    private function getTempPdfDir()
    {
        return $this->getDir('pdf');
    }

    private function getDir($dir)
    {
        if (defined('BITFORMS_CONTENT_DIR')) {
            if (!is_dir(BITFORMS_CONTENT_DIR . DIRECTORY_SEPARATOR . $dir))
                mkdir(BITFORMS_CONTENT_DIR . DIRECTORY_SEPARATOR . $dir);

            return BITFORMS_CONTENT_DIR . DIRECTORY_SEPARATOR . $dir;
        }
        return false;
    }

    /**
     * Generate PDF
     *
     * @param $setting object
     * @param $body HTML
     * @param $path string
     * @param $fileName string default bit-form
     * @param $outPut string default I [I, D, F, S]
     * D: Force the download of the PDF file.
     * I: Display the PDF inline in the browser.
     * F: Save the PDF to a file on the server.
     * S: Return the PDF as a string, allowing you to manipulate it further.
     * @return string | pdf pdf temp path
     */
    public function generator($setting, $body, $path, $uniqueId = null, $outPut = 'I')
    {
        $defaultConfig = (new ConfigVariables())->getDefaults();
        $defaultFontDir = $defaultConfig['fontDir'];
        $mPdfConfig = [
            'fontDir' => array_merge($defaultFontDir, [
                $this->getFontDir(),
            ]),
            'mode' => 'utf-8',
            // 'mode' => '+aCJK',
            'format' => isset($setting->paperSize) ? $setting->paperSize : 'a4',
            'margin_header' => 10,
            'margin_footer' => 10,
            'orientation' => isset($setting->orientation) ? $setting->orientation : 'p',
            'default_font_size' => isset($setting->fontSize) ? $setting->fontSize : 10,
        ];

        // check file name
        $fileName = 'bit-form';

        if (isset($setting->pdfFileName) && !empty($setting->pdfFileName))
            $fileName = $setting->pdfFileName;

        if (isset($uniqueId) && !empty($uniqueId))
            $fileName .= '-' . $uniqueId;

        $mPdfConfig['default_font'] = 'dejavusans';
        if (isset($setting->font->name)) {
            $getFontVariant = $this->getFontVariant($setting->font->name);
            $getInstallFonts = scandir($this->getFontDir());
            $missingFonts = array_diff($getFontVariant, $getInstallFonts);
            if (empty($missingFonts)) {
                $mPdfConfig['default_font'] = strtolower($setting->font->fontFamily);
            }
        }

        $body = str_replace('\n', '', $body);
        try {
            $mpdf = new Mpdf($mPdfConfig);
            $mpdf->useAdobeCJK = true;
            $mpdf->autoScriptToLang = true;
            $mpdf->autoLangToFont = true;
            // https://github.com/mpdf/mpdf/discussions/1488
            // $mpdf->backupSubsFont = array(
            //     'dejavusanscondensed',
            //     'arialunicodems',
            //     'sun-exta',
            // );

            if (isset($setting->direction))
                $mpdf->SetDirectionality($setting->direction);

            // for watermark
            if (isset($setting->watermark->active) && !empty($setting->watermark->active)) {
                $alpha = null;
                $watermark = $setting->watermark;

                if (isset($watermark->alpha))
                    $alpha = (int) $watermark->alpha;

                if (!$alpha || $alpha > 100)
                    $alpha = 20;

                $alpha = $alpha / 100;

                if (isset($watermark->img) && !empty($watermark->img) && 'img' === $watermark->active) {
                    $imgConf = $watermark->img;

                    $src = $imgConf->src;
                    $size = 'D';
                    $pos = 'F';

                    if (isset($imgConf->width) && isset($imgConf->height)) {
                        $size = [
                            (int) $imgConf->width,
                            (int) $imgConf->height,
                        ];
                    }

                    if (isset($imgConf->posX) && isset($imgConf->posY)) {
                        $pos = [
                            (int) $imgConf->posX,
                            (int) $imgConf->posY,
                        ];
                    }

                    $mpdf->SetWatermarkImage($src, $alpha, $size, $pos);

                    if (isset($imgConf->imgBehind) && $imgConf->imgBehind === 'true')
                        $mpdf->watermarkImgBehind = true;

                    $mpdf->showWatermarkImage = true;

                } elseif (isset($watermark->txt) && !empty(trim($watermark->txt)) && 'txt' === $watermark->active) {
                    $mpdf->SetWatermarkText($watermark->txt, $alpha);
                    // $mpdf->watermark($watermark->txt, 60, 50, $alpha);
                    $mpdf->showWatermarkText = true;
                }
            }

            if (isset($setting->style) && !empty($setting->style)) {
                $mpdf->WriteHTML($setting->style, HTMLParserMode::HEADER_CSS);
                $mpdf->WriteHTML($body, HTMLParserMode::HTML_BODY);
            } else {
                $mpdf->WriteHTML($body);
            }

            // if ($outPut === 'S')
            //     return $mpdf->Output($fileName . '.pdf', $outPut);

            if ($outPut === 'F') {
                if (empty($path))
                    $path = $this->getTempPdfDir();

                $fileName = $path . DIRECTORY_SEPARATOR . $fileName . '.pdf';

                $mpdf->Output($fileName, $outPut);
                return $fileName;
            }

            return $mpdf->Output($fileName . '.pdf', $outPut);
        } catch (MpdfException $e) {
            return new WP_Error('failed', __($e->getMessage(), 'bitform'));
        }
    }

    /**
     * @method for get instance of this class
     */
    public static function getInstance()
    {
        if (!isset(static::$instance))
            static::$instance = new static();

        return static::$instance;

    }

    public function downloadPdfFont($fontTypes)
    {
        $fontNameArr = [];

        if (is_array($fontTypes)) {
            $fontNameArr = $fontTypes;
            foreach ($fontTypes as $font) {
                $status = $this->fontsDownload($font);
            }
        } else {
            $fontNameArr = [$fontTypes];
            $status = $this->fontsDownload($fontTypes);
        }

        $this->updateDownloadedOption($fontNameArr);

        if (is_wp_error($status)) {
            return $status;
        } else {
            $res['message'] = 'Font download successfully!';
        }
        return $res;
    }

    private function updateDownloadedOption($fontNames)
    {
        $getFonts = get_option('bitforms_pdf_fonts');

        if (!empty($getFonts) && count($getFonts) <= 0) {
            $allFonts = $fontNames;
        } else {
            if (empty($getFonts)) {
                $allFonts = $fontNames;
            } else {
                $allFonts = array_merge($fontNames, $getFonts);
            }
        }
        update_option('bitforms_pdf_fonts', $allFonts);
    }
}