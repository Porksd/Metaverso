<?php
defined( 'ABSPATH' ) || die();
define( 'WP_INSTALLING', true );
?>
<!DOCTYPE html>
<html lang="en">

<head>
	<meta charset="UTF-8">
	<meta http-equiv="X-UA-Compatible" content="IE=edge">
	<meta name="viewport" content="width=device-width, initial-scale=1.0">
	<title>	<?php if ( isset( $data['title'] ) ) {
    echo $data['title'];
} else {
    echo "Document";
}
?></title>
</head>

<body>
	<div id="bf_content">

			<?php
if ( isset( $data['message'] ) ) {
    echo $data['message'];
}
?>
	</div>
</body>

</html>