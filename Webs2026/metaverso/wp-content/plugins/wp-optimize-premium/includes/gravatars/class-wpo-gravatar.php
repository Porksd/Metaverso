<?php
if (!defined('WPO_VERSION')) die('No direct access allowed');

if (!class_exists('WPO_Gravatar')) :

class WPO_Gravatar {
	
	
	/**
	 * @var Updraft_File_Logger
	 */
	private $logger;
	
	/**
	 * Constructor.
	 */
	private function __construct() {
		wp_mkdir_p(WPO_Gravatar_Data::WPO_CACHE_GRAVATAR_DIR);
		$this->logger = $this->get_file_logger();
		add_filter('get_avatar_url', [$this, 'filter_gravatar_url'], 10, 2);
		add_action('wpo_cache_flush', [$this, 'maybe_purge_cached_gravatars']);
		add_action('wpo_purge_cached_gravatars', [$this, 'remove_gravatar_folder']);
		add_action('wpo_prune_gravatar_logs', [$this, 'prune_gravatar_logs']);
	}
	
	/**
	 * Singleton instance.
	 *
	 * @return WPO_Gravatar
	 */
	public static function instance(): WPO_Gravatar {
		static $instance = null;
		if ($instance === null) {
			$instance = new self();
		}
		return $instance;
	}
	
	/**
	 * Change remote gravatar URLs to local URLs
	 *
	 * @param string $url Remote gravatar URL
	 * @param mixed $id_or_email The gravatar to retrieve
	 * @return string Gravatar URL
	 */
	public function filter_gravatar_url(string $url, $id_or_email): string {
		$gravatar_data = new WPO_Gravatar_Data($url, $id_or_email);
		return !empty($gravatar_data->local_url) ? $gravatar_data->local_url : $url;
	}
	
	/**
	 * Purge cached gravatars
	 */
	public function maybe_purge_cached_gravatars() {
		if (apply_filters('wpo_remove_gravatars_on_purge', false)) {
			$this->remove_gravatar_folder();
		}
	}
	
	/**
	 * Remove gravatar folder
	 */
	public function remove_gravatar_folder() {
		WPO_Page_Cache::delete(WPO_Gravatar_Data::WPO_CACHE_GRAVATAR_DIR);
	}
	
	/**
	 * @return Updraft_File_Logger
	 */
	private function get_file_logger(): Updraft_File_Logger {
		return new Updraft_File_Logger(WP_Optimize_Utils::get_log_file_path('gravatar'));
	}
	
	/**
	 * Logs error messages
	 *
	 * @param string $message
	 * @return void
	 */
	public function log(string $message) {
		if (!empty($this->logger)) {
			$this->logger->log($message, 'error');
		}
	}
	
	/**
	 * Prunes the log file
	 */
	public function prune_gravatar_logs() {
		$this->logger->prune_logs("1 day ago");
		$this->logger->info("Pruning the gravatar log file");
	}
}
endif;