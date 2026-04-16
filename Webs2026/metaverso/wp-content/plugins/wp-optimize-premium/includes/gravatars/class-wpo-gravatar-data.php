<?php
if (!defined('WPO_VERSION')) die('No direct access allowed');

if (!class_exists('WPO_Gravatar_Data')) :

class WPO_Gravatar_Data {
	
	/**
	 * Directory to store gravatar images
	 */
	const WPO_CACHE_GRAVATAR_DIR = WP_CONTENT_DIR . '/cache/wpo-gravatars';
	
	/**
	 * URL to the stored gravatar images
	 */
	const WPO_CACHE_GRAVATAR_URL = WP_CONTENT_URL . '/cache/wpo-gravatars';
	
	/**
	 * Remote gravatar URL
	 */
	private $remote_url;
	
	/**
	 * Local gravatar URL
	 */
	public $local_url;
	
	/**
	 * Gravatar identifier or email
	 */
	private $id_or_email;
	
	/**
	 * Hashed filename for the gravatar
	 */
	private $filename_hash;
	
	/**
	 * Temporary file path for downloaded gravatar
	 */
	private $temporary_file;
	
	/**
	 * Constructor.
	 *
	 * @param string $url Remote gravatar URL
	 * @param mixed $id_or_email Identifier or email for the gravatar
	 */
	public function __construct(string $url, $id_or_email) {
		$this->remote_url = $url;
		$this->id_or_email = $id_or_email;
		$this->set_filename_hash();
		if ($this->is_gravatar_already_cached()) {
			$this->local_url = $this->get_cached_gravatar_url();
		} else {
			$this->cache_gravatar();
		}
	}
	
	/**
	 * Set hashed filename for the gravatar
	 */
	private function set_filename_hash() {
		$parsed_url = wp_parse_url($this->remote_url);
		if (isset($parsed_url['path'])) {
			$path = trim($parsed_url['path'], '/');
			$this->filename_hash = md5(basename($path) . $this->remote_url);
		} else {
			$this->filename_hash = 'unknown_' . md5(serialize($this->id_or_email) . $this->remote_url);
		}
	}
	
	/**
	 * Check if gravatar is already cached
	 *
	 * @return bool True if gravatar is cached, false otherwise
	 */
	private function is_gravatar_already_cached(): bool {
		return $this->is_jpeg_gravatar_exists() || $this->is_png_gravatar_exists();
	}
	
	/**
	 * Check if jpeg gravatar exists
	 *
	 * @return bool
	 */
	private function is_jpeg_gravatar_exists(): bool {
		return file_exists(WPO_Gravatar_Data::WPO_CACHE_GRAVATAR_DIR . '/' . $this->filename_hash . '.jpg');
	}
	
	/**
	 * Check if png gravatar exists
	 *
	 * @return bool
	 */
	private function is_png_gravatar_exists(): bool {
		return file_exists(WPO_Gravatar_Data::WPO_CACHE_GRAVATAR_DIR . '/' . $this->filename_hash . '.png');
	}
	
	/**
	 * Get cached gravatar file url
	 *
	 * @return string
	 */
	private function get_cached_gravatar_url(): string {
		if ($this->is_png_gravatar_exists()) {
			return WPO_Gravatar_Data::WPO_CACHE_GRAVATAR_URL . '/' . $this->filename_hash . '.png';
		}
		if ($this->is_jpeg_gravatar_exists()) {
			return WPO_Gravatar_Data::WPO_CACHE_GRAVATAR_URL . '/' . $this->filename_hash . '.jpg';
		}
		return '';
	}
	
	/**
	 * Caches gravatar by downloading, and storing it locally
	 */
	private function cache_gravatar() {
		try {
			$this->temporary_file = $this->download_gravatar();
			$this->store_gravatar();
			$this->local_url = $this->get_cached_gravatar_url();
		} catch (Exception $e) {
			WPO_Gravatar::instance()->log($e->getMessage());
		}
	}
	
	/**
	 * Download gravatar from given URL
	 *
	 * @return string|WP_Error Temporary file path or error
	 */
	private function download_gravatar() {
		if (!function_exists('download_url')) {
			require_once ABSPATH . 'wp-admin/includes/file.php';
		}
		return download_url($this->remote_url);
	}
	
	/**
	 * Store downloaded gravatar in the local folder
	 */
	private function store_gravatar() {
		if (!is_wp_error($this->temporary_file)) {
			$filename = $this->filename_hash . $this->get_extension();
			if (is_file($this->temporary_file) && is_dir(self::WPO_CACHE_GRAVATAR_DIR)) {
				@copy($this->temporary_file, self::WPO_CACHE_GRAVATAR_DIR . '/' . $filename); // phpcs:ignore Generic.PHP.NoSilencedErrors.Discouraged -- Temporary file issue due to network
				wp_delete_file($this->temporary_file);
			} else {
				WPO_Gravatar::instance()->log('Either ' . $this->temporary_file . ' file does not exists or ' . self::WPO_CACHE_GRAVATAR_DIR . ' folder does not exists');
			}
		} else {
			WPO_Gravatar::instance()->log($this->remote_url . ':' . $this->temporary_file->get_error_message());
		}
	}
	
	/**
	 * Determine and set file extension for the gravatar
	 *
	 * @return string
	 */
	private function get_extension(): string {
		$mime = @mime_content_type($this->temporary_file); // phpcs:ignore Generic.PHP.NoSilencedErrors.Discouraged -- suppress PHP warning in case of failure
		$mime = false === $mime ? 'image/jpg' : $mime;
		$ext = substr($mime, strpos($mime, '/') + 1);
		return in_array($ext, ['jpeg', 'jpg']) ? '.jpg' : ".$ext";
	}
}

endif;
