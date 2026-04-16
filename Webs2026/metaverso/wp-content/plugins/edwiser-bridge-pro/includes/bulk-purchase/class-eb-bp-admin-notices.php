<?php
/**
 * Handles the notices structure genration.
 *
 * @package    BulkPurchase
 */

namespace app\wisdmlabs\edwiserBridgePro\includes\bulkPurchase;

if ( ! defined( 'ABSPATH' ) ) {
	exit; // Exit if accessed directly.
}
if ( ! class_exists( '\app\wisdmlabs\edwiserBridgePro\includes\bulkPurchase\Eb_Bp_Admin_Notices' ) ) {

	/**
	 * Class provides the functionality to display admin notivcess
	 */
	class Eb_Bp_Admin_Notices {


		/**
		 * Message to display in admin notice.
		 *
		 * @var String
		 */
		private $message;
		/**
		 * Css clsses in string format.
		 * Use space for the class sepration
		 *
		 * @var String
		 */
		private $css_class = 'notice is-dismissible';
		/**
		 * Message Type
		 * Use success= 0, warning= 1, error= 2
		 *
		 * @var Integer
		 */
		private $type = 4;

		/**
		 * The only prameterised constructor to trigger the wp admin notice.
		 * This will access two parmeters first one is message and second is messge type
		 *
		 * Message type are in integer format
		 * 0= Success
		 * 1= Warning
		 * 2= Error
		 *
		 * @param String  $message String formated message can cointain HTML.
		 * @param Integer $type type of the message Use success= 0, warning= 1, error= 2.
		 */
		public function __construct( $message, $type ) {
			$this->message = $message;
			$this->type    = $type;
			$this->trigger_message();
		}


		/**
		 * Method Checkes the notice type to display and prepares the parameters
		 */
		private function trigger_message() {
			switch ( $this->type ) {
				case 0:
					$this->css_class .= 'notice-success';
					break;
				case 1:
					$this->css_class .= 'notice-warning';
					break;
				case 2:
					$this->css_class .= 'notice-error';
					break;
				case 3:
					$this->css_class .= 'notice-info';
					break;
				default:
					break;
			}
			if ( $this->type < 4 ) {
				add_action( 'admin_notices', array( $this, 'display_admin_notice' ) );
			}
		}

		/**
		 * Provides the functionality to trigger the message.
		 */
		public function display_admin_notice() {
			?>
			<div class="<?php echo esc_attr( $this->css_class ); ?>">
				<p><?php esc_html_e( $this->message, 'edwiser-bridge-pro' ); // @codingStandardsIgnoreLine. ?></p>
			</div>
			<?php
		}
	}
}

