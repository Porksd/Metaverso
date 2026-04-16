<?php if (!defined('WPO_VERSION')) die('No direct access allowed'); ?>
<?php
$fcp_link = 'https://getwpo.com/first-contentful-paint/';
$cls_link = 'https://getwpo.com/cumulative-layout-shift/';
?>

<div class="wpo-fieldgroup wpo-first-child dimensions-options">
	<p>
		<?php
			// translators: %1$s - First Contentful Paint link, %2$s - Cumulative Layout Shift link (articles about core web vitals, FCP and CLS).
			printf(esc_html__('WP-Optimize will automatically detect images with missing width or height, and add those attributes for improving %1$s and %2$s.', 'wp-optimize'),
			$wp_optimize->wp_optimize_url($fcp_link, esc_html__('First Contentful Paint time', 'wp-optimize'), '', '', true), // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- Output already escaped
			$wp_optimize->wp_optimize_url($cls_link, esc_html__('Cumulative Layout Shift', 'wp-optimize'), '', '', true)); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- Output already escaped
		?>
	</p>
	<div class="switch-container">
		<label class="switch">
			<input name="images_dimensions" id="images_dimensions" class="dimensions-settings" type="checkbox" <?php checked($images_dimensions_status); ?>>
			<span class="slider round"></span>
		</label>
		<label for="images_dimensions">
			<?php esc_html_e('Enable images dimensions', 'wp-optimize'); ?>
		</label>
	</div>
	<div id="image_dimensions_hidden_content" style="<?php echo $images_dimensions_status ? 'display:block' : 'display:none'; ?>">
		<div>
			<p><?php esc_html_e('Skip image CSS classes', 'wp-optimize'); ?></p>
			<input type="text" name="image_dimensions_ignore_classes" id="image_dimensions_ignore_classes" value="<?php echo esc_attr($ignore_classes); ?>">
			<p><small><?php
					esc_html_e('Enter the image class or classes comma-separated.', 'wp-optimize');
					echo ' ';
					esc_html_e('Supports wildcards.', 'wp-optimize');
					echo ' ';
					esc_html_e('Example: image-class1, image-class2, thumbnail*, ...', 'wp-optimize');
					?>
				</small></p>

		</div>
		<p>
			<small><?php esc_html_e("For advanced users: use `data-no-image-dimensions` in image tags to bypass this feature.", 'wp-optimize'); ?></small>
		</p>

		<input
			class="wp-optimize-settings-save button button-primary"
			id="image_dimensions_save_settings_btn"
			type="button"
			value="<?php esc_attr_e('Save settings', 'wp-optimize'); ?>"
		>
		<img class="wpo_spinner display-none" src="<?php echo esc_url(admin_url('images/spinner-2x.gif')); ?>" alt="..."> <?php // phpcs:ignore PluginCheck.CodeAnalysis.ImageFunctions.NonEnqueuedImage -- N/A ?>
		<span class="save-done dashicons dashicons-yes display-none"></span>
	</div>

</div>