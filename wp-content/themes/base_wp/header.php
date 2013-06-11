<!DOCTYPE html>
<html <?php language_attributes(); ?> <?php body_class(); ?>>
	<head>
        <meta charset="<?php bloginfo( 'charset' ); ?>" />
        <meta name="viewport" content="width=device-width, initial-scale=1.0, maximum-scale=1.0, user-scalable=yes">
        <meta http-equiv="X-UA-Compatible" content="IE=Edge"/>
        <title><?php wp_title( '-', true, 'right' ); bloginfo( 'name' ); ?> - <?php bloginfo('description'); ?></title>
        <link rel="icon" href="<?php bloginfo('template_url'); ?>/images/faveicon.png" type="image/png">
        <link href='http://fonts.googleapis.com/css?family=Droid+Sans:400,700' rel='stylesheet' type='text/css'>
		<link href='http://fonts.googleapis.com/css?family=Raleway:400,100,200,300,500,600,700,800,900' rel='stylesheet' type='text/css'>
        <link rel="stylesheet" type="text/css" media="all" href="<?php bloginfo( 'stylesheet_url' ); ?>">
        <?php wp_head(); ?>
    </head>
    <body>