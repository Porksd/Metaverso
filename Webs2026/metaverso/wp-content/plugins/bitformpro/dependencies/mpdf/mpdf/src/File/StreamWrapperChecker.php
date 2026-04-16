<?php
/**
 * @license GPL-2.0-only
 *
 * Modified on 01-August-2024 using Strauss.
 * @see https://github.com/BrianHenryIE/strauss
 */

namespace BitCode\BitFormPro\Dependencies\Mpdf\File;

use BitCode\BitFormPro\Dependencies\Mpdf\Mpdf;

final class StreamWrapperChecker
{

	private $mpdf;

	public function __construct(Mpdf $mpdf)
	{
		$this->mpdf = $mpdf;
	}

	/**
	 * @param string $filename
	 * @return bool
	 * @since 7.1.8
	 */
	public function hasBlacklistedStreamWrapper($filename)
	{
		if (strpos($filename, '://') > 0) {
			$wrappers = stream_get_wrappers();
			$whitelistStreamWrappers = $this->getWhitelistedStreamWrappers();
			foreach ($wrappers as $wrapper) {
				if (in_array($wrapper, $whitelistStreamWrappers)) {
					continue;
				}

				if (stripos($filename, $wrapper . '://') === 0) {
					return true;
				}
			}
		}

		return false;
	}

	public function getWhitelistedStreamWrappers()
	{
		return array_diff($this->mpdf->whitelistStreamWrappers, ['phar']); // remove 'phar' (security issue)
	}

}
