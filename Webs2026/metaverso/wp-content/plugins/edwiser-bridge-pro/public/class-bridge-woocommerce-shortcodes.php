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

namespace app\wisdmlabs\edwiserBridgePro\pb;

/**
 * Class Bridge Woocommerce Shortcodes.
 */
class Bridge_Woocommerce_Shortcodes {
	/**
	 * Init shortcodes.
	 */
	public static function init() {
		// Define shortcodes.
		$shortcodes = array(
			'bridge_woo_display_associated_courses' => __CLASS__ . '::display_associated_course',
			'bridge_woo_single_cart_checkout'       => __CLASS__ . '::single_cart_checkout',
		);

		foreach ( $shortcodes as $shortcode => $function ) {
			add_shortcode( apply_filters( "{$shortcode}_shortcode_tag", $shortcode ), $function );
		}
	}

	/**
	 * Shortcode Wrapper.
	 *
	 * @since  1.0.0
	 *
	 * @param mixed $function function name.
	 * @param array $atts     (default: array()).
	 * @param array $wrapper  (default: array('class' => 'bridge-woo-associated-courses', 'before' => null, 'after' => null)).
	 */
	public static function shortcode_wrapper(
		$function,
		$atts = array(),
		$wrapper = array(
			'class'  => 'bridge-woo-associated-courses',
			'before' => null,
			'after'  => null,
		)
	) {
		ob_start();
		$before = empty( $wrapper['before'] ) ? '<div class="' . esc_attr( $wrapper['class'] ) . '">' : $wrapper['before'];
		$after  = empty( $wrapper['after'] ) ? '</div>' : $wrapper['after'];

		echo $before; // @codingStandardsIgnoreLine
		call_user_func( $function, $atts );
		echo $after; // @codingStandardsIgnoreLine
		echo ob_get_clean(); // @codingStandardsIgnoreLine
	}

	/**
	 * User account shortcode.
	 *
	 * @since  1.0.0
	 *
	 * @param mixed $atts shortcode attributes.
	 *
	 * @return string
	 */
	public static function display_associated_course( $atts ) {
		return self::shortcode_wrapper( array( 'app\wisdmlabs\edwiserBridgePro\pb\Bridge_Woocommerce_Shortcode_Associated_Courses', 'output' ), $atts );
	}

	/**
	 * Single cart checkout Page shortcode
	 *
	 * @since  1.1.3
	 *
	 * @param mixed $atts shortcode attributes.
	 *
	 * @return string
	 */
	public static function single_cart_checkout( $atts ) {
		ob_start();

		self::shortcode_wrapper(
			array( 'app\wisdmlabs\edwiserBridgePro\pb\Bridge_Woocommerce_Shortcode_Single_Cart_Checkout', 'output' ),
			$atts
		);
		return ob_get_clean();
	}
}
