<?php

if (!defined('WPO_VERSION')) die('No direct access allowed');

class WP_Optimize_Lazy_Load {

	/**
	 * Lazy load options.
	 *
	 * @var array
	 */
	private $options;

	/**
	 * WP_Optimize_Lazy_Load constructor.
	 */
	public function __construct() {
		if (!defined('WPO_CACHE_YOUTUBE_THUMBNAILS_DIR')) define('WPO_CACHE_YOUTUBE_THUMBNAILS_DIR', WP_CONTENT_DIR.'/cache/wpo-youtube-thumbnails');
		if (!defined('WPO_CACHE_YOUTUBE_THUMBNAILS_URL')) define('WPO_CACHE_YOUTUBE_THUMBNAILS_URL', WP_CONTENT_URL.'/cache/wpo-youtube-thumbnails');

		add_action('add_meta_boxes', array($this, 'add_lazyload_control_metabox'));

		$default_options = array(
			'images' => false,
			'iframes' => false,
			'backgrounds' => false,
			'youtube_preview' => false,
			'skip_classes' => '',
		);

		$this->options = wp_parse_args(WP_Optimize()->get_options()->get_option('lazyload'), $default_options);

		$skip_classes = array_map('trim', explode(',', $this->options['skip_classes']));
		$skip_classes[] = 'no-lazy';
		$skip_classes[] = 'skip-lazy';

		$this->options['skip_classes'] = apply_filters('wp_optimize_lazy_load_skip_classes', $skip_classes);
		
		$hook_these = apply_filters('wp_optimize_lazy_load_hook_these', array('get_avatar', 'the_content', 'widget_text', 'get_image_tag', 'post_thumbnail_html', 'woocommerce_product_get_image', 'woocommerce_single_product_image_thumbnail_html'));
		
		$hook_priority = apply_filters('wp_optimize_lazy_load_hook_priority', PHP_INT_MAX);
		
		foreach ($hook_these as $hook) {
			add_filter($hook, array($this, 'process_content'), $hook_priority);
		}

		add_action('wpo_delete_lazyload_image_cache', array($this, 'delete_image_cache'));

		if ($this->options['youtube_preview']) {
			if (!is_dir(WPO_CACHE_YOUTUBE_THUMBNAILS_DIR)) {
				wp_mkdir_p(WPO_CACHE_YOUTUBE_THUMBNAILS_DIR);
			}
		}
	}

	/**
	 * Add lazy-load metabox to admin.
	 */
	public function add_lazyload_control_metabox() {
		if ($this->is_enabled()) {
			add_meta_box('wpo-lazyload-metabox', '<span title="' . __('by WP-Optimize', 'wp-optimize') . '">' . __('Lazy-load', 'wp-optimize') . '</span>', array($this, 'render_lazyload_control_metabox'), get_post_types(array('public' => true)), 'side');
		}
	}

	/**
	 * Render lazy-load metabox.
	 */
	public function render_lazyload_control_metabox($post) {
		$post_id = $post->ID;
		$meta_key = '_wpo_disable_lazyload';
		$disable_lazyload = get_post_meta($post_id, $meta_key, true);

		$post_type_obj = get_post_type_object(get_post_type($post_id));

		$extract = array(
			'disable_lazyload' => $disable_lazyload,
			'post_id' => $post_id,
			'post_type' => strtolower($post_type_obj->labels->singular_name),
		);

		WP_Optimize()->include_template('images/admin-metabox-lazyload-control.php', false, $extract);
	}

	/**
	 * Returns true if Lazy loading enabled.
	 *
	 * @return bool
	 */
	public function is_enabled(): bool {
		$post_id = get_queried_object_id();

		// if disabled on the post editr page then return false.
		if ($post_id && get_post_meta($post_id, '_wpo_disable_lazyload', true)) {
			return false;
		}

		return $this->options['images']
			|| $this->options['iframes']
			|| $this->options['backgrounds']
			|| $this->options['youtube_preview'];
	}

	/**
	 * Filter the content and replace corresponding tags for lazy loading.
	 *
	 * @param string $content
	 * @return string
	 */
	public function process_content(string $content): string {

		if (!$this->is_enabled() || is_admin()) return $content;

		if ($this->options['images']) {
			$content = preg_replace_callback('/\<picture.+\/picture\>/Uis', array($this, 'update_picture_callback'), $content);
			$content = preg_replace_callback('/\<img(.+)\>/Uis', array($this, 'update_images_callback'), $content);
		}

		if ($this->options['backgrounds']) {
			$regexp = '/\<([a-z]+)\s+([^\>]*style=[\'|\"][^\>]*(background-image|background):[^\>;]*url\(([^\>]+)\)[^\>;]*[^\>\'\"]*[\'|\"][^\>]*)\>/i';
			$content = preg_replace_callback($regexp, array($this, 'update_background_callback'), $content);
		}

		if ($this->options['youtube_preview']) {
			$content = preg_replace_callback('/<iframe.+<\/iframe>/Uis', array($this, 'youtube_iframe_preview_callback'), $content);
		}

		if ($this->options['iframes']) {
			$content = preg_replace_callback('/\<iframe(.+)\>/Uis', array($this, 'update_iframes_callback'), $content);
		}

		return $content;
	}

	/**
	 * Update PICTURE tag.
	 *
	 * @param array $picture matched element from preg_replace_callback.
	 * @return string
	 */
	private function update_picture_callback(array $picture): string {
		$picture = $picture[0];

		preg_match('/<picture(.*)\>/Uis', $picture, $picture_tag);
		$picture_tag = $picture_tag[0];

		$attributes = WP_Optimize_Utils::parse_attributes($picture_tag);

		// don't use lazy load for images with no-lazy class.
		if (array_key_exists('class', $attributes) && $this->has_class($attributes['class'], $this->options['skip_classes'])) return $picture;

		$attributes['class'] = array_key_exists('class', $attributes) ? $attributes['class'] . ($this->has_class($attributes['class'], 'lazyload') ? '' : ' lazyload') : 'lazyload';

		// update inner img, source tags.
		$picture = preg_replace_callback('/\<(img|source)(.+)\>/Uis', array($this, 'update_picture_item_callback'), $picture);

		$picture = preg_replace('/<picture(.*)\>/Uis', '<picture '.WP_Optimize_Utils::build_attributes($attributes).'>', $picture);

		return $picture;
	}

	/**
	 * Update PICTURE inner tags.
	 *
	 * @param array $picture_item matched element from preg_replace_callback.
	 * @return string
	 */
	private function update_picture_item_callback(array $picture_item): string {
		$attributes = WP_Optimize_Utils::parse_attributes($picture_item[2]);

		return $this->build_lazy_load_tag($picture_item[1], $attributes);
	}

	/**
	 * Update image tag to use lazy load.
	 *
	 * @param array $image
	 * @return string
	 */
	private function update_images_callback(array $image): string {
		$image_tag = $image[1];
		$attributes = WP_Optimize_Utils::parse_attributes($image_tag);

		// don't use lazy load for images with no-lazy class.
		if (array_key_exists('class', $attributes) && $this->has_class($attributes['class'], $this->options['skip_classes'])) return $image[0];

		// don't change anything if data-src already set.
		if (array_key_exists('data-src', $attributes)) return $image[0];

		$attributes['class'] = array_key_exists('class', $attributes) ? $attributes['class'] . ($this->has_class($attributes['class'], 'lazyload') ? '' : ' lazyload') : 'lazyload';

		return $this->build_lazy_load_tag('img', $attributes);
	}

	/**
	 * Update background inline styles callback.
	 *
	 * @param array $match [1] - tag name, [2] - tag attributes
	 * @return string
	 */
	private function update_background_callback(array $match): string {
		$original = $match[0];
		$tag = $match[1];

		$tag_attributes = $match[2];

		$attributes = WP_Optimize_Utils::parse_attributes($tag_attributes);
		// don't use lazy load for images with no-lazy class.

		if (array_key_exists('class', $attributes) && $this->has_class($attributes['class'], $this->options['skip_classes'])) return $original;

		// split style attribute.
		$style = $attributes['style'];
		$style_items = explode(';', $style);

		// check style properties for background and background-image items.
		foreach ($style_items as &$item) {
			$item = trim($item);
			$regexp = '/^([^:]+):[^;]*/i';

			if (!preg_match($regexp, $item, $match)) continue;
			$property = strtolower($match[1]);

			if (!in_array($property, array('background', 'background-image'))) continue;

			// get all urls in property.
			if (!preg_match_all('/url\((.+)\)/Ui', $item, $match)) continue;

			$original_urls = $match[1];

			foreach ($original_urls as &$url) {
				$url = trim($url, '\'"');
			}

			// add data-* attribute with original images urls.
			$attributes['data-'.$property] = join(';', $original_urls);

			// replace original urls with the blank image.
			$replace = 'url('.includes_url('/images/blank.gif').')';
			$replaced = preg_replace('/url\((.+)\)/Ui', $replace, $item);
			$item = $replaced;
		}

		// update style attribute.
		$attributes['style'] = implode(';', $style_items);

		// add lazyload class to the element.
		$attributes['class'] = array_key_exists('class', $attributes) ? $attributes['class'] . ($this->has_class($attributes['class'], 'lazyload') ? '' : ' lazyload') : 'lazyload';

		return '<'.$tag.' '.WP_Optimize_Utils::build_attributes($attributes).'>';
	}

	/**
	 * Update IFRAME tag.
	 *
	 * @param array $iframe matched element from preg_replace_callback.
	 * @return string
	 */
	private function update_iframes_callback(array $iframe): string {
		$iframe_tag = $iframe[1];

		// don't use lazy load for Gravity Form ajax iframe.
		if (strpos($iframe[0], 'gform_ajax_frame')) return $iframe[0];

		$attributes = WP_Optimize_Utils::parse_attributes($iframe_tag);

		// don't use lazy load for iframes with no-lazy class.
		if (array_key_exists('class', $attributes) && $this->has_class($attributes['class'], $this->options['skip_classes'])) return $iframe[0];

		$attributes['class'] = array_key_exists('class', $attributes) ? $attributes['class'] . ($this->has_class($attributes['class'], 'lazyload') ? '' : ' lazyload') : 'lazyload';

		return $this->build_lazy_load_tag('iframe', $attributes);
	}

	/**
	 * Update YouTube iframe tag with an img.
	 *
	 * @param array $iframe matched element from preg_replace_callback.
	 * @return string
	 */
	private function youtube_iframe_preview_callback(array $iframe) : string {
		preg_match('/<iframe(.+)>/Uis', $iframe[0], $tag);
		$iframe_tag = $tag[1];

		$attributes = WP_Optimize_Utils::parse_attributes($iframe_tag);

		
		if (empty($attributes['src'])) return $iframe[0];

		$video_id = $this->get_youtube_video_id_from_src($attributes['src']);
		if ($video_id) {
			$thumbnail_markup = $this->get_youtube_thumbnail_markup($video_id, $attributes);
			if (!empty($thumbnail_markup)) return $thumbnail_markup;
		}

		return $iframe[0];
	}

	/**
	 * Build tag for lazy loading.
	 *
	 * @param string $tag
	 * @param array $attributes
	 * @return string
	 */
	private function build_lazy_load_tag(string $tag, array $attributes): string {
		if (array_key_exists('src', $attributes)) {
			$attributes['data-src'] = $attributes['src'];
			if ('iframe' == $tag) {
				$attributes['src'] = 'about:blank';
			} else {
				$attributes['src'] = 'data:image/gif;base64,R0lGODlhAQABAAAAACH5BAEKAAEALAAAAAABAAEAAAICTAEAOw==';
			}
		}

		if (array_key_exists('srcset', $attributes)) {
			$attributes['data-srcset'] = $attributes['srcset'];
			unset($attributes['srcset']);
		}

		return '<'.$tag.' '.WP_Optimize_Utils::build_attributes($attributes).'>';
	}

	/**
	 * Check if class or one of classes exists in class attribute value.
	 *
	 * @param string $class_attr
	 * @param string|array $class_name
	 * @return bool
	 */
	private function has_class(string $class_attr, $class_name): bool {
		if (is_array($class_name)) {
			foreach ($class_name as $_class_name) {
				$_class_name = str_replace('*', '.*', $_class_name);
				if (preg_match('/(^|\s)'.$_class_name.'(\s|$)/', $class_attr)) return true;
			}
		} else {
			$class_name = str_replace('*', '.*', $class_name);
			if (preg_match('/(^|\s)'.$class_name.'(\s|$)/', $class_attr)) {
				return true;
			}
		}

		return false;
	}

	/**
	 * Parse potential YouTube video link and return video id.
	 *
	 * @param string $src
	 * @return string|bool
	 */
	private function get_youtube_video_id_from_src(string $src) {
		$pattern = '%(?:youtube(?:-nocookie)?\\.com/(?:[^/]+/.+/|(?:v|e(?:mbed)?)/|.*[?&]v=)|youtu\\.be/)([^"&?/ ]{11})%i';
		preg_match($pattern, $src, $matches);
		return $matches[1] ?? false;
	}

	/**
	 * Generate a YouTube preview image markup using video id, returns false if unable to get valid thumbnail url.
	 *
	 * @param string $video_id
	 * @param array $attributes
	 * @return string | boolean
	 */
	private function get_youtube_thumbnail_markup(string $video_id, array $attributes) {
		$youtube_preview_image = $this->download_preview_image($video_id);

		if (!$youtube_preview_image) return false;

		$img_tag = sprintf(
			'<img src="%s" class="wpo_youtube_preview" data-video-url="%s" alt="%s" data-iframe-attr="%s"/>',
			esc_url($youtube_preview_image),
			esc_url($attributes['src']),
			esc_attr__('YouTube thumbnail', 'wp-optimize'),
			esc_attr(json_encode($attributes))
		);

		$img_overlay = sprintf('<span class="wpo_youtube_preview_overlay"><img src="%s" alt="%s"/></span>',
			esc_url(WPO_PLUGIN_URL.'images/icon/icons8-youtube-96.svg'),
			esc_attr__('YouTube icon', 'wp-optimize'));

		return "<div class='wpo_youtube_preview_container'>" . $img_tag . $img_overlay . "</div>";
	}

	/**
	 * Downloads and saves YouTube thumbnail images
	 *
	 * @param string $video_id
	 * @param string $resolution
	 */
	private function remote_get_youtube_thumbnail_image(string $video_id, string $resolution) {
		$file_name = $video_id . '-' . $resolution;
		$base_path = WPO_CACHE_YOUTUBE_THUMBNAILS_DIR . '/' . $file_name;
		
		$webp_image_url = sprintf('https://i.ytimg.com/vi_webp/%s/%s.webp', $video_id, $resolution);
		$response = wp_remote_get($webp_image_url);
		if (200 === wp_remote_retrieve_response_code($response)) {
			$blob = wp_remote_retrieve_body($response);
			@file_put_contents($base_path . '.webp', $blob); // phpcs:ignore Generic.PHP.NoSilencedErrors.Discouraged -- suppress the warning due to network issues
		}

		$jpg_image_url = sprintf('https://i.ytimg.com/vi/%s/%s.jpg', $video_id, $resolution);
		$response = wp_remote_get($jpg_image_url);
		if (200 === wp_remote_retrieve_response_code($response)) {
			$blob = wp_remote_retrieve_body($response);
			@file_put_contents($base_path . '.jpg', $blob); // phpcs:ignore Generic.PHP.NoSilencedErrors.Discouraged -- suppress the warning due to network issues
		}
	}

	/**
	 * Returns a valid url to preview image or false if unable to download file.
	 *
	 * @param string $video_id
	 *
	 * @return string | false
	 */
	private function download_preview_image(string $video_id) {
		// Fallback to other resolutions if this is not available
		$accepted_resolutions = array('maxresdefault', 'sddefault', 'hqdefault', 'mqdefault', 'default');
		$filter_resolution = apply_filters('wpo_youtube_thumbnail_resolution', 'maxresdefault');
		
		$filter_resolution = in_array($filter_resolution, $accepted_resolutions) ? $filter_resolution : 'maxresdefault';
		
		// Start with preferred resolution, then others
		$resolutions = array_unique(array_merge(array($filter_resolution), $accepted_resolutions));
		
		foreach ($resolutions as $resolution) {
			$file_name   = $video_id . '-' . $resolution;
			// Check cached image
			$cached_image = $this->get_cached_image_file($file_name);
			if ($cached_image) {
				return $cached_image;
			}
			// Remote fetch webp & jpg both
			$this->remote_get_youtube_thumbnail_image($video_id, $resolution);

			$cached_image = $this->get_cached_image_file($file_name);
			if ($cached_image) {
				return $cached_image;
			}

		}
		return false;
	}

	/**
	 * Returns a valid url for cached preview image or false if it does not exist yet.
	 *
	 * @param string $file_name
	 *
	 * @return string | false
	 */
	private function get_cached_image_file(string $file_name) {
		$base_path = WPO_CACHE_YOUTUBE_THUMBNAILS_DIR . '/' . $file_name;
		$base_url  = WPO_CACHE_YOUTUBE_THUMBNAILS_URL . '/' . $file_name;
		
		$browser_support_webp = WPO_WebP_Utils::is_browser_accepting_webp();
		if ($browser_support_webp) {
			if (file_exists($base_path . '.webp')) {
				return $base_url . '.webp';
			}
		}

		if (file_exists($base_path . '.jpg')) {
			return $base_url . '.jpg';
		}
		return false;
	}

	/**
	 * Return an array of lazyload options.
	 *
	 * @return array
	 */
	public function get_lazy_options(): array {
		return $this->options;
	}

	/**
	 * Delete cached images.
	 *
	 */
	public function delete_image_cache() {
		wpo_delete_files(WPO_CACHE_YOUTUBE_THUMBNAILS_DIR);
	}

	/**
	 * Returns singleton instance object
	 *
	 * @return WP_Optimize_Lazy_Load Returns `WP_Optimize_Lazy_Load` object
	 */
	public static function instance(): WP_Optimize_Lazy_Load {
		static $_instance = null;
		if (null === $_instance) {
			$_instance = new self();
		}
		return $_instance;
	}
}
