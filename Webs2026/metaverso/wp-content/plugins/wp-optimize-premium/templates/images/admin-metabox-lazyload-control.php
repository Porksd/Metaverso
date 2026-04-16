<?php if (!defined('WPO_VERSION')) die('No direct access allowed'); ?>
<div>
	<label for="wpo_disable_single_post_lazyload">
		<input id="wpo_disable_single_post_lazyload" type="checkbox" data-id="<?php echo esc_attr($post_id); ?>" <?php checked($disable_lazyload); ?> >
		<?php
			// translators: %s is a post type
			printf(esc_html__('Disable lazy-load on this %s', 'wp-optimize'), esc_html($post_type)); ?>
	</label>
</div>
