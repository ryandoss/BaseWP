<?php
$config['url'] = 'http://basewp';
$config['theme'] = 'base_wp';
$config['template_url'] = 'wp-content/themes/'.$config['theme'];
$config['name'] = 'Base Wordpress';
$config['description'] = 'Template for Wordpress';
$config['stylesheet_url'] = 'style.css';
$config['charset'] = 'utf-8';

function bloginfo($name)
{
	global $config;
	print($config[$name]);
}
function language_attributes()
{
	print('lang="en"');
}
function body_class()
{
	print('class="home"');
}
function wp_head()
{
	return;
}
function wp_footer()
{
	return;
}
function wp_title()
{
	return;
}
?>