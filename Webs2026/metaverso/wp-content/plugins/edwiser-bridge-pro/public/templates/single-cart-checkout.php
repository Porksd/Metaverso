<?php
/**
 * Woo Int Public Module
 * This class is responsible for SSO module.
 * This is used to define internationalization, admin-specific hooks, and public-facing site hooks for SSO module.
 *
 * @link       http://edwiser.org
 * @since      3.0.0
 * @package    Edwiser Bridge Pro
 */

?>

<div class="wi-scc-wrapper">

<input type="hidden" id="wi-scc-url" value="<?php echo esc_url( get_permalink() ); ?>" />

<?php
echo do_shortcode( '[woocommerce_cart]' );
echo do_shortcode( '[woocommerce_checkout]' );
?>
</div>
