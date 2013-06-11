<?php include("wordpress.prep.php"); ?>
<!DOCTYPE html>
<html <?php language_attributes(); ?>>
	<head>
		<meta charset="<?php bloginfo( 'charset' ); ?>" />
		<meta name="viewport" content="width=device-width, initial-scale=1, maximum-scale=1">
		<title><?php wp_title( '-', true, 'right' ); bloginfo( 'name' ); ?> - <?php bloginfo('description'); ?></title>
        <link href='http://fonts.googleapis.com/css?family=Raleway:200,300,400,500' rel='stylesheet' type='text/css'>
		<link href='http://fonts.googleapis.com/css?family=Droid+Sans:400,700' rel='stylesheet' type='text/css'>
		<link rel="stylesheet" type="text/css" media="all" href="<?php bloginfo( 'template_url' ); ?>/css/reset.css" />
		<link rel="stylesheet" type="text/css" media="all" href="<?php bloginfo( 'stylesheet_url' ); ?>" />
		<link rel="stylesheet" type="text/css" media="all" href="<?php bloginfo( 'template_url' ); ?>/css/responsive.css" />
        <!--[if lt IE 9]>
        <script src="<?php bloginfo('template_url'); ?>/js/html5shiv.js"></script>
        <![endif]-->
		<script src="<?php bloginfo('template_url'); ?>/js/modernizr.js"></script>
		<?php wp_head(); ?>
	</head>
	<body <?php body_class(); ?>>

		<script src="http://ajax.googleapis.com/ajax/libs/jquery/1/jquery.min.js"></script>
		<script src="<?php bloginfo('template_url'); ?>/js/jquery.plugins.js"></script>
		<script src="<?php bloginfo('template_url'); ?>/js/pilr.slider.js"></script>
		<script src="<?php bloginfo('template_url'); ?>/js/script.js"></script>
		<?php wp_footer(); ?>
	</body>
</html>