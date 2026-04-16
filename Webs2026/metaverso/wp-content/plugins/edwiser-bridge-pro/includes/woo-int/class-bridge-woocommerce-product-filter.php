<?php
/**
 * Woo Integration Module
 * This class is responsible for Woo Integration module.
 * This is used to define internationalization, admin-specific hooks, and public-facing site hooks for Woo Integration module.
 *
 * @link       http://edwiser.org
 * @since      3.0.0
 * @package    Edwiser Bridge Pro
 */

namespace app\wisdmlabs\edwiserBridgePro\includes\wooInt;

use \app\wisdmlabs\edwiserBridge\EdwiserBridge;

/**
 * Woo Integration product filter class
 */
class Bridge_Woocommerce_Product_Filter {
	/**
	 * The ID of this plugin.
	 *
	 * @since    1.0.0
	 *
	 * @var string The ID of this plugin.
	 */
	private $plugin_name;

	/**
	 * The version of this plugin.
	 *
	 * @since    1.0.0
	 *
	 * @var string The current version of this plugin.
	 */
	private $version;

	/**
	 * Initialize the class and set its properties.
	 *
	 * @param string $plugin_name plugin name.
	 * @param string $version     plugin version.
	 */
	public function __construct( $plugin_name, $version ) {
		$this->plugin_name = $plugin_name;
		$this->version     = $version;
	}

	/**
	 * Add custom column to product list page.
	 *
	 * @param array $columns columns.
	 */
	public function add_custom_column( $columns ) {
		$new_columns = array(
			'associated_courses' => 'Associated Courses',
		);
		return array_merge( $columns, $new_columns );
	}

	/**
	 * Add custom column value to product list page.
	 *
	 * @param string $column column.
	 * @param int    $post_id post id.
	 */
	public function add_custom_column_value( $column, $post_id ) {
		switch ( $column ) {
			case 'associated_courses':
				$courses  = $this->get_associated_course_title( $post_id );
				$iterator = 0;
				if ( ! empty( $courses ) ) {
					$courses_count = count( $courses );
					foreach ( $courses as $value ) {
						if ( ++$iterator === $courses_count ) {
							echo $value; // @codingStandardsIgnoreLine
						} else {
							echo $value . ', '; // @codingStandardsIgnoreLine
						}
					}
				} else {
						echo '&ndash;';
				}

				break;
		}
	}

	/**
	 * This function returns associated courses
	 *
	 * @param int $post_id post id.
	 */
	public function get_associated_course_title( $post_id ) {
		$product_options = get_post_meta( $post_id, 'product_options', true );

		$product_object = wc_get_product( $post_id );

		if ( $product_object->is_type( 'variable' ) ) {
			$associated_courses = $this->get_variations_associated_courses( $product_object );
			return $associated_courses;
		} else {
			if ( ! empty( $product_options['moodle_post_course_id'] ) ) {
						$course_post_ids = $product_options['moodle_post_course_id'];
				foreach ( $course_post_ids as $course_post_id ) {
					$associated_courses[] = get_the_title( $course_post_id );
				}
				return $associated_courses;
			}
		}
		return false;
	}
	/**
	 * This function returns associated courses
	 * of the variable product.
	 *
	 * @param object $product_object product object.
	 */
	public function get_variations_associated_courses( $product_object ) {
		$variations         = $product_object->get_available_variations();
		$associated_courses = array();
		foreach ( $variations as $var ) {
				$product_options = get_post_meta( $var['variation_id'], 'product_options', true );
			if ( false !== $product_options && ! empty( $product_options['moodle_post_course_id'] ) ) {
						$course_post_ids = $product_options['moodle_post_course_id'];
				foreach ( $course_post_ids as $course_post_id ) {
					$associated_courses[] = get_the_title( $course_post_id );
				}
			}
		}
		return $associated_courses;
	}

	/**
	 * Html for moodle course dropdown.
	 *
	 * @param array $output output.
	 */
	public function moodle_course_in_dropdown( $output ) {
		$output = array();
		global $wp_query;
		$terms   = get_terms( 'product_type' );
		$output  = '<select name="product_type" id="dropdown_product_type">';
		$output .= '<option value="">' . __( 'Filter by product type', '' ) . '</option>';

		foreach ( $terms as $term ) {
			$output .= '<option value="' . sanitize_title( $term->name ) . '" ';

			if ( isset( $wp_query->query['product_type'] ) ) {
				$output .= selected( $term->slug, $wp_query->query['product_type'], false );
			}

			$output .= '>';

			switch ( $term->name ) {
				case 'grouped':
					$output .= __( 'Grouped product', '' );
					break;
				case 'external':
					$output .= __( 'External/Affiliate product', '' );
					break;
				case 'variable':
					$output .= __( 'Variable product', '' );
					break;
				case 'simple':
					$output .= __( 'Simple product', 'woocommerce' );
					break;
				default:
					// Assuming that we have other types in future.
					$output .= ucfirst( $term->name );
					break;
			}

			$output .= '</option>';

			if ( 'simple' === $term->name ) {
				$output .= '<option value="downloadable" ';

				if ( isset( $wp_query->query['product_type'] ) ) {
					$output .= selected( 'downloadable', $wp_query->query['product_type'], false );
				}

				$output .= '> ' . ( is_rtl() ? '&larr;' : '&rarr;' ) . ' ' . __( 'Downloadable', 'woocommerce' ) . '</option>';

				$output .= '<option value="virtual" ';

				if ( isset( $wp_query->query['product_type'] ) ) {
					$output .= selected( 'virtual', $wp_query->query['product_type'], false );
				}

				$output .= '> ' . ( is_rtl() ? '&larr;' : '&rarr;' ) . ' ' . __( 'Virtual', 'woocommerce' ) . '</option>';
			}
		}
		$output .= '<option value="moodle_course">Moodle Course</option>';
		$output .= '</select>';
		echo $output; // @codingStandardsIgnoreLine.
	}

	/**
	 * Products filter query.
	 *
	 * @param array $query query.
	 */
	public function product_filters_query( $query ) {
		global $typenow;
		if ( 'product' === $typenow ) {
			if ( isset( $query->query_vars['product_type'] ) ) {
				// Subtypes.
				if ( 'moodle_course' === $query->query_vars['product_type'] ) {
					$query->query_vars['product_type'] = '';
					$query->is_tax                     = false;
					$query->query_vars['meta_value']   = 'yes'; // @codingStandardsIgnoreLine.
					$query->query_vars['meta_key']     = 'is_product_a_moodle_course'; // @codingStandardsIgnoreLine.
				}
			}
		}
	}
}

