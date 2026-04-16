<?php
/**
 * Handles the user csv related functionality.
 *
 * @package    BulkPurchase
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit; // Exit if accessed directly.
}
require_once dirname( dirname( dirname( dirname( __DIR__ ) ) ) ) . '/wp-load.php';
$csv_file = $_FILES['wdm_user_csv']['tmp_name']; // @codingStandardsIgnoreLine
echo $csv_file; // @codingStandardsIgnoreLine
die();
