<?php
/*
	Plugin Name: PILR Admin
	Plugin URI: http://pilrtech.com/
	Description: Settings for PILR designed themes
	Version: 1.0
	Author: Ryan Doss
	Author URI: http://ryandoss.com/
	License: GPL2
*/

 // Add link to Admin menu
function display_pilr_menu()
{
  add_menu_page('PILR Admin', 'PILR Admin', 'administrator', 'pilr_admin', 'display_settings_page');
  add_submenu_page('pilr_admin', 'Shortcode Usage', 'Shortcode Usage', 'administrator', 'shortcode_usage', 'display_shortcodes_page');
}

add_action('admin_menu', 'display_pilr_menu');


/**
* Include admin section for site settings
 **********************************************/
function register_settings()
{
  // Add main settings section
  add_settings_section( 'site_settings', 'Site Settings', 'display_site_settings', 'pilr_admin' );
  
  // Register all available settings
  register_setting( 'pilr_admin', 'site_setting' );
  
  //
  add_settings_field( 'copyright_text', 'Copyright Text', 'copyright_field', 'pilr_admin', 'site_settings' );
  add_settings_field( 'address', 'Address', 'address_field', 'pilr_admin', 'site_settings' );
}

add_action( 'admin_init', 'register_settings' );

// Site settings section output
function display_site_settings()
{
  echo '<div class="inside"><p>Fill out information below for specific site settings.</p></div>';
}

// Functions for display fields
function copyright_field()
{
  $options = get_option('site_setting');
  echo '<input id="site_setting" name="site_setting[copyright]" size="120" type="text" value="'.htmlspecialchars($options['copyright']).'" />';
}
function address_field()
{
  $options = get_option('site_setting');
  echo '<input id="site_setting" name="site_setting[address]" size="120" type="text" value="'.htmlspecialchars($options['address']).'" />';
}

// Settings Page
function display_settings_page()
{
  include( 'admin-settings.php' );
}

// Shortcodes Page
function display_shortcodes_page()
{
  include( 'shortcode-docs.php' );
}
?>