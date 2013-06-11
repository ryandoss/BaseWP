<?php 

/*
Plugin Name: UberMenu 2 - WordPress Mega Menu Plugin
Plugin URI: http://wpmegamenu.com
Description: Create highly customizable Mega Menus through an easy-to-use WordPress Plugin.  Please be sure to follow the <a href="http://bit.ly/i1zVXL" target="_blank">installation instructions</a> precisely.
Version: 2.0.1.0
Author: Chris Mavricos, SevenSpark
Author URI: http://sevenspark.com
License: You should have purchased a license from http://codecanyon.net/item/ubermenu-wordpress-mega-menu-plugin/154703?ref=sevenspark
Copyright 2011-2012  Chris Mavricos, SevenSpark http://sevenspark.com (email : chris@sevenspark.com) 
*/

/* Constants */
define('UBERMENU_VERSION', 		'2.0.1.0' );
define('UBERMENU_NAV_LOCS', 	'wp-mega-menu-nav-locations');
define('UBERMENU_SETTINGS', 	'wp-mega-menu-settings' );
define('UBERMENU_STYLES', 		'wp-mega-menu-styles');
define('UBERMENU_PLUGIN_URL', 	plugins_url().'/'.str_replace(basename( __FILE__),"",plugin_basename(__FILE__)));	//WP_PLUGIN_URL
define('UBERMENU_TT', 			UBERMENU_PLUGIN_URL.'timthumb/tt.php');
define('UBERMENU_ADMIN_PATH', 	trim( plugins_url().'/'.str_replace(basename( __FILE__),"",plugin_basename(__FILE__)), '/'));
define('UBERMENU_LESS',			dirname(__FILE__).'/stylegenerator/skin.less' );
define('UBERMENU_GEN_SKIN',		dirname(__FILE__).'/stylegenerator/skin.css' );

/* Load Required Files */
require_once( 'UberMenuWalker.class.php' );					//Handles Menu Walkers for UberMenu front end and Menu Management Backend
require_once( 'ubermenu.shortcodes.php' );					//Adds useful shortcodes
require_once( 'stylegenerator/StyleGenerator.class.php' ); 	//Helps generate user-defined CSS styles

require_once( 'tiptour/TipTour.class.php' );				//WordPress ToolTips
require_once( 'sparkoptions/SparkOptions.class.php' );		//SevenSpark Options Panel
require_once( 'UberOptions.class.php' );					//UberMenu-specific Option class
//require_once( 'demo.php' );

/* The Meat */
class UberMenu{
	
	private $settings;
	private $pluginURL;
	private $tour;
	private $stylePresets;
	
	function __construct(){
		
		$this->pluginURL = plugins_url().'/'.str_replace(basename( __FILE__ ),"",plugin_basename( __FILE__ ) );
		
		$this->registerStylePresets();
		$this->settings = $this->optionsMenu();
		
		//ADMIN
		if( is_admin() ){
			add_action( 'admin_menu' , array( $this , 'adminInit' ) );
			add_action( 'wp_ajax_megaMenu_updateNavLocs', array( $this , 'updateNavLocs_callback' ) );			//For logged in users
			add_action( 'wp_ajax_wpmega-add-menu-item', array( $this , 'addMenuItem_callback' ) );
			add_action( 'wp_ajax_ubermenu_getPreview', array( $this ,  'getPreview_callback' ) );
			
			add_action( 'ubermenu_menu_item_options', array( $this , 'menuItemCustomOptions' ), 10, 1);		//Must go here for AJAX purposes
			
			//Add "Settings" and "Support Guide" links to the Plugins page
			add_filter( 'plugin_action_links', array( $this , 'pluginActionLinks' ), 10, 2);
			
			//AJAX clear of show thanks box
			add_action( 'wp_ajax_ubermenu_showThanksCleared', array( $this , 'showThanksCleared_callback' ) );
			
			//UberMenu Thank You panel
			add_action( 'sparkoptions_before_settings_panel_'. UBERMENU_SETTINGS , array( $this , 'showThanks' ) );
			
			//AJAX Load Image
			add_action( 'wp_ajax_ubermenu_getMenuImage', array( $this, 'getMenuImage_callback' ) );
			
			//Appearance > UberMenu Preview
			add_filter( 'wp_nav_menu_args' , array( $this , 'megaMenuFilter' ), 2000 );  	//filters arguments passed to wp_nav_menu
			
			//Save Style Generator
			add_action( 'sparkoptions_update_settings_'.UBERMENU_SETTINGS , array( $this , 'saveStyleGenerator' ) , 10 , 1 );
			
			//Create the welcome tour
			$this->createTour();
			
		}
		//FRONT END
		else{
			add_action( 'plugins_loaded' , array( $this , 'init' ) );
		}
		
		//Add Thumbnail Support
		add_action( 'after_setup_theme', array( $this , 'addThumbnailSupport' ), 500 );	//go near the end, so we don't get overridden
		
		//Add Sidebars
		//add_action( 'widgets_init', array( $this , 'registerSidebars' ), 500);
		add_action( 'init', array( $this , 'registerSidebars' ), 500);	//Note that on the admin side, this runs before settings are updated
		
		
		//UberMenu Easy Integration
		add_shortcode( 'uberMenu_easyIntegrate' , array( 'UberMenu' , 'easyIntegrate' ) );
		
		if( $this->settings->op( 'wpmega-easyintegrate' ) ){ 
			add_action( 'init', array( $this , 'registerThemeLocation' ) );
		}

	}
	
	
	function init(){
			
		$this->loadAssets();
		
		//Filters
		add_filter( 'wp_nav_menu_args' , array( $this , 'megaMenuFilter' ), 2000 );  	//filters arguments passed to wp_nav_menu
		
		do_action( 'uberMenu_register_styles' );
	}
	
	function loadAssets(){
		
		//Load on front end, as well as on login and registration pages if setting is enabled
		if( !is_admin() && 
		  ( $this->settings->op( 'wpmega-load-on-login' ) || !in_array( $GLOBALS['pagenow'], array( 'wp-login.php', 'wp-register.php' ) ) ) ) {
			
			//Actions
			add_action( 'wp_print_styles' , array( $this , 'loadCSS' ) );
			add_action( 'wp_head', array( $this  , 'insertCSS' ), 100 );
			//add_action( 'wp_head', 'wpmega_inline', 101);
			
			//IE Fix
			if( $this->settings->op( 'wpmega-iefix' ) ) add_action( 'wp_head', array( $this , 'ieFix' ) );	//You can safely disable this if you are including it elsewhere
			
			//Load Javascript unless disabled
			if( $this->settings->op( 'wpmega-jquery' ) ) add_action( 'init', array( $this , 'loadJS' ), 500);
			
		}

	}
	
	
	
	function loadCSS(){
		
		//Load Structural CSS
		if( $this->settings->op( 'include-basic-css' ) ) {
			wp_enqueue_style( 'ubermenu-basic', $this->pluginURL.'styles/basic.css', false, UBERMENU_VERSION, 'all' );
		}
		
		//Load Preset
		if( $this->settings->op( 'wpmega-style' ) == 'preset' || !$this->settings->op( 'wpmega-style' ) ) {				
		
			$id = 
				!$this->settings->op( 'wpmega-style-preset' )  
				? 'grey-white' 
				: $this->settings->op( 'wpmega-style-preset' );		
	
			if( !empty( $id ) ) {
				$href = $this->stylePresets[$id]['path'];
				wp_enqueue_style('ubermenu-'.$id, $href, false, UBERMENU_VERSION, 'all'); 
			}

		}		
		//Load Custom stylesheet
		else if( $this->settings->op( 'wpmega-style' ) == 'custom' ){
			wp_enqueue_style('ubermenu-custom', $this->pluginURL.'styles/custom.css', false, UBERMENU_VERSION, 'all');
		}		
		// Load Generated Stylesheet 
		else if(   $this->settings->op( 'wpmega-style' ) == 'inline'	//Using Generator 
				&& $this->settings->op( 'save-style-gen-to-file' )		//Saving to file
				&& $this->settings->op( 'use-gen-skin' ) ){				//File Generated Successfully
		
			wp_enqueue_style('ubermenu-generated-skin', $this->pluginURL.'stylegenerator/skin.css', false, UBERMENU_VERSION, 'all');
		}
	}
	
	/*
	 * Insert StyleGenerator-generated CSS in the site head
	 */
	function insertCSS(){
		
		$css = '';
		$from = array();
	
		//Gather special CSS settings
		
		$menuW = $this->settings->op( 'wpmega-container-w' );
		if( !empty( $menuW ) ) {
			$css.= "\n\n/* Menu Width - UberMenu Advanced Settings */\n";
			$css.= '#megaMenu{ width: '.$menuW.'px; max-width:100%; }';
		}
		
		$innerMenuW = $this->settings->op( 'inner-menu-width' );
		if( !empty( $innerMenuW ) ){
			$css.= "\n\n/* Inner Menu Width - used for centering - UberMenu Advanced Settings */\n";
			$css.= '#megaMenu ul.megaMenu{ max-width: '.$innerMenuW.'px; }';
		}
		
		
		$verticalSubmenuWidth = $this->settings->op( 'vertical-submenu-w');
		if( !empty( $verticalSubmenuWidth ) ){
			$css.= "/* Vertical Submenu Width */\n";
			$css.= '#megaMenu.megaMenuVertical ul.megaMenu li.ss-nav-menu-mega.ss-nav-menu-item-depth-0 ul.sub-menu-1{ width: '.$verticalSubmenuWidth.'px; max-width: '.$verticalSubmenuWidth. 'px; }';
		}
		
		$customTweaks = $this->settings->op( 'wpmega-css-tweaks' );
		if( !empty( $customTweaks ) ) {
			$css.= "\n\n/* Custom Tweaks - UberMenu Style Configuration Settings */\n";
			$css.= stripslashes( $customTweaks );
		}
	
		//Append CSS from Generator, if using inline style & no external stylesheet
		if( $this->settings->op( 'wpmega-style' ) == 'inline'
			&& !$this->settings->op( 'use-gen-skin' ) ){
			$css = "/* Menu Width - UberMenu Advanced Settings */\n". $this->getGeneratorCSS() . "\n\n".$css;
		}
		
		$css = trim( $css );
	
		//If we've got anything to print, print it!
		if( !empty($css) ){
			?>

<!-- UberMenu CSS - Controlled through UberMenu Options Panel 
================================================================ -->
<style type="text/css" id="ubermenu-style-generator-css">
<?php echo $css; ?>
	
</style>
<!-- end UberMenu CSS -->
		
			<?php
		}
		
	}
	
	function loadJS(){
		
		// Load jQuery - optionally disable for when dumb themes don't include jquery properly
		if( $this->settings->op( 'wpmega-include-jquery' ) ) wp_enqueue_script( 'jquery' );

		// Load Hover Intent
		if( $this->settings->op( 'wpmega-include-hoverintent' ) )
			wp_enqueue_script( 'hoverintent' , $this->pluginURL.'js/hoverIntent.js', array( 'jquery' ), false, true );

		if( $this->settings->op( 'load-google-maps') )
			wp_enqueue_script( 'google-maps', 'http://maps.googleapis.com/maps/api/js?sensor=false' , array( 'jquery' ), false, true ); 
	
		if($this->settings->op( 'wpmega-debug' ) == 'on') 	wp_enqueue_script( 'ubermenu', $this->pluginURL.'js/ubermenu.dev.js', array(), false, true );		
		else 												wp_enqueue_script( 'ubermenu', $this->pluginURL.'js/ubermenu.min.js', array(), false, true );
	
	
		$this->loadJSsettings();
	
	}
	
	function loadJSsettings(){
	
		wp_localize_script( 'ubermenu', 'uberMenuSettings', array(
			'speed'				=>	$this->settings->op( 'wpmega-animation-time' ),
			'trigger'			=>	$this->settings->op( 'wpmega-trigger' ),
			'orientation'		=>	$this->settings->op( 'wpmega-orientation' ),
			'transition'		=>	$this->settings->op( 'wpmega-transition' ),
			'hoverInterval'		=>	$this->settings->op( 'wpmega-hover-interval' ),
			'hoverTimeout'		=>	$this->settings->op( 'wpmega-hover-timeout' ),
			
			//turn booleans to strings, since wp_localize script can handle booleans - converted back in JS
			'removeConflicts'	=>	$this->settings->op( 'wpmega-remove-conflicts' ) ? 'on' : 'off',
			'autoAlign'			=>	$this->settings->op( 'wpmega-autoAlign' ) ? 'on' : 'off',			
			'noconflict'		=>	$this->settings->op( 'wpmega-jquery-noconflict' ) ? 'on' : 'off',
			'fullWidthSubs'		=>	$this->settings->op( 'wpmega-submenu-full' ) ? 'on' : 'off',
			'androidClick'		=>	$this->settings->op( 'android-click' ) ? 'on' : 'off',
			
			'loadGoogleMaps'	=>	$this->settings->op( 'load-google-maps' ) ? 'on' : 'off',
		));
		
	}
	
	function ieFix(){
		?>
		<!--[if lt IE 8]>
		<script src="http://ie7-js.googlecode.com/svn/version/2.1(beta4)/IE8.js"></script>
		<![endif]-->
		<?php 
	}
	
	function registerThemeLocation(){
		register_nav_menu('ubermenu' , __( 'UberMenu' ));
	}
	
	static function easyIntegrate($atts = array(), $data = ''){
		extract(shortcode_atts(array(
			'echo'	=>	'true',
		), $atts));
		
		$echo = $echo == 'false' ? false : true;
		
		$menu = wp_nav_menu( array( 'theme_location' => 'ubermenu' , 'megaMenu' => true , 'echo' => $echo ) );
		
		if( !$echo ) return $menu;
	}

	/*
	 * Add Support for Thumbnails on Menu Items
	 *
	 * This function adds support without override the theme's support for thumbnails
	 * Note we could just call add_theme_support('post-thumbnails') without specifying a post type,
	 * but this would make it look like users could set featured images on themes that don't support it
	 * so we don't want that.
	 */
	function addThumbnailSupport(){
	
		global $_wp_theme_features;
		$post_types = array( 'nav_menu_item' );
	
		$alreadySet = false;
	
		//Check to see if some features are already supported so that we don't override anything
		if( isset( $_wp_theme_features['post-thumbnails'] ) && is_array( $_wp_theme_features['post-thumbnails'][0] ) ){
			$post_types = array_merge($post_types, $_wp_theme_features['post-thumbnails'][0]);
		}
		//If they already tuned it on for EVERY type, then we don't need to do anything more
		elseif( isset( $_wp_theme_features['post-thumbnails'] ) && $_wp_theme_features['post-thumbnails'] == 1 ){
			$alreadySet = true;
		}
	
		if(!$alreadySet) add_theme_support( 'post-thumbnails' , $post_types );
	
		add_post_type_support( 'nav_menu_item' , 'thumbnail' ); //wp33
	}


	function getGeneratorCSS(){
		return stripslashes( get_option( UBERMENU_STYLES ) );
	}
	
	
	/*
	 * Apply options to the Menu via the filter
	 */
	function megaMenuFilter( $args ){
		
		if( isset( $args['responsiveSelectMenu'] ) ) return $args;
		
		//Don't do anything in IE6
		if( $this->settings->op( 'no-ie6' ) && 
			strpos( $_SERVER['HTTP_USER_AGENT'], 'MSIE 6') !== false ) return $args;
	
		//Check to See if this Menu Should be Megafied
		$location = $args['theme_location'];
		$activeLocs = get_option( UBERMENU_NAV_LOCS, array() ); 
		
		if( !isset( $args['preview'] ) ){	//preview automatically passes through
			//STRICT
			if( $this->settings->op( 'wpmega-strict' ) ) {
				//Strict Mode requires the location to be set and for that location to be activated
				//If location is empty or location is not activated, return
				if( ( empty( $location ) || !in_array( $location, $activeLocs ) ) && ( !isset( $args['megaMenu'] ) || $args['megaMenu'] != true ) ){
					return $args;
				} 
			
				//Check to make sure the menu exists
				$nav_menu_locations = get_nav_menu_locations();
				if( !isset( $nav_menu_locations[ $location ] ) ){
					//If this was supposed to be a Mega Menu, explain the problem to the user.
					if( isset( $args['megaMenu'] ) && $args['megaMenu'] == true ) echo "Please activate UberMenu Easy Integration and set a menu in the $location theme location in Appearance > Nav Menus";
					return $args;
				}
			}
			//LENIENT
			else{
				//In the Event that the LOCATION is empty, that means the theme author has not 
				//created the menu using the theme_location parameter properly, so we'll go ahead and megafy the menu
				if( $args['megaMenu'] != true && !empty( $location ) && !in_array( $location, $activeLocs ) ){
					return $args;	//megaMenu setting for manual wp_nav_menu
				}
			}
		}
		
		$args['walker'] 			= new UberMenuWalker();
		$args['container_id'] 		= 'megaMenu';
		$args['container_class'] 	= 'megaMenuContainer megaMenu-nojs';
		$args['menu_class']			= 'megaMenu';
		$args['depth']				= 0;
		$args['items_wrap']			= '<ul id="%1$s" class="%2$s">%3$s</ul>'; //This is the default, to override any stupidity
		
		if( $this->settings->op( 'wpmega-html5' ) )						$args['container'] 		= 'nav';
		else 															$args['container'] 		= 'div';	
		
		if( $this->settings->op( 'responsive-menu' ) )					$args['container_class'].= ' megaResponsive';
		
		if( $this->settings->op( 'wpmega-menubar-full' ) )				$args['container_class'].= ' megaFullWidth';
		
		if( $this->settings->op( 'wpmega-submenu-full' ) )				$args['container_class'].= ' megaFullWidthSubs';  
		
		if( $this->settings->op( 'wpmega-style' ) == 'preset' )			$args['container_class'].= ' wpmega-preset-'.$this->settings->op( 'wpmega-style-preset' );
		
		if( $this->settings->op( 'wpmega-orientation' ) == 'vertical' )	$args['container_class'].= ' megaMenuVertical';
		else 															$args['container_class'].= ' megaMenuHorizontal';
		
		if( $this->settings->op( 'wpmega-transition' ) == 'fade' )		$args['container_class'].= ' megaMenuFade';
		
		if( $this->settings->op( 'wpmega-trigger' ) == 'click' )		$args['container_class'].= ' megaMenuOnClick';
		else															$args['container_class'].= ' megaMenuOnHover';
	
		if( $this->settings->op( 'wpmega-autoAlign' ) )					$args['container_class'].= ' wpmega-autoAlign';
		
		if( $this->settings->op( 'wpmega-jquery' )	)					$args['container_class'].= ' wpmega-withjs';
		else 															$args['container_class'].= ' wpmega-nojs';
		
		if( $this->settings->op( 'wpmega-remove-conflicts' ) )			$args['container_class'].= ' wpmega-noconflict';
		
		if( $this->settings->op( 'center-menubar' ) )					$args['container_class'].= ' megaCenterMenubar';
		if( $this->settings->op( 'enable-clearfix' ) )					$args['container_class'].= ' megaClear';
		if( $this->settings->op( 'center-inner-menu' ) )				$args['container_class'].= ' megaCenterInner';
		
		if( $this->settings->op( 'wpmega-minimizeresidual' ) )			$args['menu_id'] = 'megaUber';
		
		return $args;
	}
	

	
	function getSettings(){
		return $this->settings;
	}
	
	
	
	function getImage( $id, $w = 25, $h = 25 ){
	
		if( empty( $w ) ) $w = 25; 
		if( empty( $h ) ) $h = 25;
	
		if( has_post_thumbnail( $id ) ){
			$img_id = get_post_thumbnail_id( $id );
			$attachment =& get_post( $img_id );
			
			$image = wp_get_attachment_image_src( $img_id, 'single-post-thumbnail' );
			$src = $image[0];
			
			if( is_ssl() ) $src = str_replace('http://', 'https://', $src);
			
			$alt = get_post_meta( $img_id, '_wp_attachment_image_alt', true );
			$title = trim( strip_tags( $attachment->post_title ) );
			if( empty( $alt ) ) $alt = $title;
			
			if( $this->settings->op( 'wpmega-resizeimages' ) ){
				if( $this->settings->op( 'wpmega-usetimthumb' ) ){	
					return $this->timthumb($src, $w, $h, $title, $alt);
				}
				else return '<img height="'.$h.'" width="'.$w.'" src="'.$src.'" alt="'.$alt.'" title="'.$title.'" />';
			}
			else return '<img src="'.$src.'" alt="'.$alt.'" title="'.$title.'" />';
			
		}
		return '';
	}
	
	/*
	 * TimThumb function
	 */
	function timthumb( $src, $w, $h, $title = '', $alt = '', $zc = 1 ){  //, $rel=''){
		
		if( stristr( trim( $src ), 'http://' ) != 0){
			$src = get_bloginfo('url') . trim($src);
		}
		
		$ttsrc = UBERMENU_TT;
		
		if( is_ssl() ) $ttsrc = str_replace( 'http://', 'https://', $ttsrc );
	
		$img = '<img src="'.$ttsrc.
					'?src='.$src.
					'&amp;w='.$w.
					'&amp;h='.$h.
					'&amp;zc='.$zc.
					'" alt="'.$alt.'" title="'.$title.'"';
		$img.= '/>';
		return $img;
	}

	/*
	 * Get the Post Thumbnail Image
	 */
	function getPostImage( $id, $w=30, $h=30, $default_img = false ){
		
		if( empty( $w ) ) $w = 30; if( empty( $h ) ) $h = 30;
		
		if ( has_post_thumbnail( $id ) ){
			$image = wp_get_attachment_image_src( get_post_thumbnail_id( $id ), 'single-post-thumbnail' );
			$src = $image[0];
					
			return $this->buildImg( $src, $w, $h );
		}
		else if($default_img){
			//Use Default Image if Post does not have featured image
			return $this->buildImg( $default_img, $w, $h );
		}
		return '';
	}
	
	function buildImg($src, $w, $h){
	
		if( is_ssl() ) $src = str_replace('http://', 'https://', $src);
		
		if( $this->settings->op( 'wpmega-usetimthumb' ) ){
			return $this->timthumb( $src, $w, $h );
		}
		else return '<img height="'.$h.'" width="'.$w.'" src="'.$src.'" alt="" />';
	}
	
	
	
	
	
	
	
	
	
	
	
	
	
	
	
	
	
	
	/* ADMIN */
	
	function adminInit(){
						
		add_action( 'admin_head', array( $this , 'addActivationMetaBox' ) );
	
		//Appearance > Menus : load additional styles and scripts
		add_action( 'admin_print_styles-nav-menus.php', array( $this , 'loadAdminNavMenuJS' ) ); 
		add_action( 'admin_print_styles-nav-menus.php', array( $this , 'loadAdminNavMenuCSS' )); 
		
		//Appearance > Menus : modify menu item options
		add_filter( 'wp_edit_nav_menu_walker', array( $this , 'editWalker' ) , 2000);
		
		//Appearance > Menus : save custom menu options
		add_action( 'wp_update_nav_menu_item', array( $this , 'updateNavMenuItem' ), 10, 3); //, $menu_id, $menu_item_db_id, $args;
		
		
		//Appearance > UberMenu load additional styles and scripts
		add_action( 'sparkoptions_load_js_'.UBERMENU_SETTINGS , array( $this , 'loadAdminNavMenuJS' ) );
		add_action( 'sparkoptions_load_css_'.UBERMENU_SETTINGS , array( $this , 'loadAdminNavMenuCSS' ) );
		
		do_action( 'uberMenu_register_styles' );
				
		//For extensibility
		do_action( 'uberMenu_after_init' );
		
	}
	
	function loadAdminNavMenuJS(){
		
		wp_enqueue_script('jquery');	// Load jQuery
	
		wp_enqueue_script('ubermenu', $this->pluginURL.'js/ubermenu.dev.js', array(), UBERMENU_VERSION, true);
		$this->loadJSsettings();
	
		wp_enqueue_script('hoverintent', $this->pluginURL.'js/hoverIntent.js', array( 'jquery' ), UBERMENU_VERSION, true);
		wp_enqueue_script('thickbox');
	
		//Admin Extras	
		wp_enqueue_script('ubermenu-admin-js', $this->pluginURL.'js/ubermenu.admin.js', array(), UBERMENU_VERSION, true);	
		wp_enqueue_script('colorpicker-js', $this->pluginURL.'js/colorpicker/js/colorpicker.js', array(), UBERMENU_VERSION, true);
	
		add_action( 'admin_head', array( $this  , 'insertCSS' ), 100 );
	}
	
	function loadAdminNavMenuCSS(){
		wp_enqueue_style('ubermenu-admin-css', 	$this->pluginURL.'styles/admin.css', 	false, UBERMENU_VERSION, 'all');
		wp_enqueue_style('thickbox');
	
		wp_enqueue_style('wpmega-basic', 	$this->pluginURL.'styles/basic.css', 					false, UBERMENU_VERSION, 'all');
		wp_enqueue_style('colorpicker',		$this->pluginURL.'js/colorpicker/css/colorpicker.css', false, UBERMENU_VERSION, 'all');
		
		//Really for UberMenu settings page (Preview)
		wp_enqueue_style( 'ubermenu-basic', $this->pluginURL.'styles/basic.css', false, UBERMENU_VERSION, 'all' );
	}
	
	/*
	 * Add the Activate Uber Menu Locations Meta Box to the Appearance > Menus Control Panel
	 */
	function addActivationMetaBox(){
		if ( wp_get_nav_menus() )
			add_meta_box( 'nav-menu-theme-megamenus', __( 'Activate Uber Menu Locations' ), array( $this , 'showActivationMetaBox' ) , 'nav-menus', 'side', 'high' );
	}
	
	/*
	 * Generates the Activate Uber Menu Locations Meta Box
	 */
	function showActivationMetaBox(){
	
		/* This is just in case JS is not working.  It'll only save the last checked box */
		if( isset( $_POST['megaMenu-locations'] ) && $_POST['megaMenu-locations'] == 'Save'){
			$data = $_POST['wp-mega-menu-nav-loc'];
			$data = explode(',', $data);		
			update_option( UBERMENU_NAV_LOCS, $data );
			echo 'Saved Changes';
		}
		
		$active = get_option( UBERMENU_NAV_LOCS, array());
		
		echo '<div class="megaMenu-metaBox">';	
		echo '<p class="howto">Select the Menu Locations to Megafy.  This must be activated for any Mega Menu Options to affect that Menu Location.</p>';
		
		echo '<form>';
		
		$locs = get_registered_nav_menus();
		
		foreach($locs as $slug => $desc){		
			echo '<label class="menu-item-title" for="megaMenuThemeLoc-'.$slug.'">'.
					'<input class="menu-item-checkbox" type="checkbox" value="'.$slug.'" id="megaMenuThemeLoc-'.$slug.'" name="wp-mega-menu-nav-loc" '.
					checked( in_array( $slug, $active ), true, false).'/>'.
					$desc.'</label>';
		}
		
		echo '<p class="button-controls">'.
				'<img class="waiting" src="'.esc_url( admin_url( 'images/wpspin_light.gif' ) ).'" alt="" />'.
				'<input id="wp-mega-menu-navlocs-submit" type="submit" class="button-primary" name="megaMenu-locations" value="Save" />'.
				'</p>';
		
		echo '</form>';
		
		if( !$this->settings->op( 'wpmega-strict' ) ){
			echo '<p class="howto">If more than 1 menu is being megafied in your theme, turn on Strict Mode in Appearance > UberMenu > '.
					'Theme Integration.</p>';
		}

		echo '<p>Note you can only have 1 UberMenu per page.</p>';

		echo '</div>';
	}

	/*
	 * Update the Locations when the Activate Uber Menu Locations Meta Box is Submitted
	 */
	function updateNavLocs_callback(){
		
		$data = $_POST['data'];	
		$data = explode(',', $data);
		
		update_option( UBERMENU_NAV_LOCS, $data);
		
		echo $data;		
		die();		
	}


	
	
	/*
	 * Custom Walker Name
	 */
	function editWalker( $className ){
		return 'UberMenuWalkerEdit';
	}
	
	
	/*
	 * Get the Image for a Menu Item via AJAX
	 */
	function getMenuImage_callback(){
		
		$id = $_POST['id'];
		
		$id = substr($id, (strrpos($id, '-')+1));
		
		$data = array();
		
		$ajax_nonce = wp_create_nonce( "set_post_thumbnail-$id" );
		$rmvBtn = '<div class="remove-item-thumb" id="remove-item-thumb-'.$id.
					'"><a href="#" id="remove-post-thumbnail-'.$id.
					'" onclick="wpmega_remove_thumb(\'' . $ajax_nonce . '\', '.
					$id.');return false;">' . esc_html__( 'Remove image' ) . '</a></div>';
		
		$data['remove_nonce'] = $ajax_nonce;// $rmvBtn;
		$data['id'] = $id;
		
		$data['image'] = $this->getImage( $id );
		$this->JSONresponse( $data );
	}
	
	
	
	
	
	
	/* Registering Sidebars */
	function registerSidebars(){
		
		if(function_exists('register_sidebars')){
			
			$numSidebars = $this->settings->op( 'wpmega-sidebars' );
			if(!empty($numSidebars)){
				if($numSidebars == 1){
					register_sidebar(array(
						'name'          => __('UberMenu Widget Area 1'),
						'id'            => 'wpmega-sidebar',
						'before_title'  => '<h2 class="widgettitle">',
						'after_title'   => '</h2>',
						'description'	=> 'Select "UberMeu Widget Area 1" in your Menu Item under Appearance > Menus to add this widget area to your menu.'
					));				
				}
				else{
					register_sidebars( $numSidebars, array(
						'name'          => __('UberMenu Widget Area %d'),
						'id'            => 'wpmega-sidebar',
						'before_title'  => '<h2 class="widgettitle">',
						'after_title'   => '</h2>',
						'description'	=> __('Select this widget area in your Menu Item\'s "Display a Widget Area" option under Appearance > Menus to add this widget area to your menu.')
					));
				}
			}
		}
	}
	
	
	/*
	 * Show a sidebar select box
	 */
	function sidebarSelect($id){
		
		$fid = 'edit-menu-item-sidebars-'.$id;
		$name = 'menu-item-sidebars['.$id.']';
		$selection = get_post_meta( $id, '_menu_item_sidebars', true);
		
		$ops = $this->sidebarList();
		if( empty( $ops ) ) return '';
		
		$html = '<select id="'.$fid.'" name="'.$name.'" class="edit-menu-item-sidebars">';
		
		$html.= '<option value=""></option>';
		foreach( $ops as $opVal => $op ){
			$selected = $opVal == $selection ? 'selected="selected"' : '';
			$html.= '<option value="'.$opVal.'" '.$selected.' >'.$op.'</option>';
		}
				
		$html.= '</select>';
		
		return $html;
	}

	/*
	 * List the available sidebars
	 */
	function sidebarList(){
		
		$sb = array();
		
		$numSidebars = $this->settings->op( 'wpmega-sidebars' );
		
		for( $k = 0; $k < $numSidebars; $k++ ){
			$val = 'UberMenu Widget Area '.($k+1);
			$sb[$val] = $val;
		}
		return $sb;
	}

	/* 
	 * Show a sidebar
	 */
	function sidebar($name){
		
		if(function_exists('dynamic_sidebar')){
			ob_start();
			echo '<ul id="wpmega-'.sanitize_title($name).'">';
			dynamic_sidebar($name);		
			echo '</ul>';
			return ob_get_clean();
		}
		return 'none';
	}

	/*
	 * Count the number of widgets in a sidebar area
	 */
	function sidebarCount($index){
		
		global $wp_registered_sidebars, $wp_registered_widgets;
	
		if ( is_int($index) ) {
			$index = "sidebar-$index";
		} else {
			$index = sanitize_title($index);
			foreach ( (array) $wp_registered_sidebars as $key => $value ) {
				if ( sanitize_title($value['name']) == $index ) {
					$index = $key;
					break;
				}
			}
		}
	
		$sidebars_widgets = wp_get_sidebars_widgets();
	
		if ( empty($wp_registered_sidebars[$index]) || !array_key_exists($index, $sidebars_widgets) || !is_array($sidebars_widgets[$index]) || empty($sidebars_widgets[$index]) )
			return false;
	
		$sidebar = $wp_registered_sidebars[$index];
		
		return count($sidebars_widgets[$index]);
	}



	
	/*
	 * Setup the ToolTip Tour for UberMenu
	 */
	function createTour(){
		global $pagenow;
		$this->tour = $uberTour = new TipTour( 'uberMenu' );
				
		if( $uberTour->tourOn() ){
			
			//build & load		
			
			$page_slug = '_';
			if ( isset($_GET['page']) ) $page_slug = $_GET['page'];
			
			$uberTour->addStep( new TipTourStep( 
				$pagenow,	//load anywhere
				$page_slug,
				'#menu-appearance > a.menu-top',
				__( 'Welcome to UberMenu!', 'ubermenu' ),
				'<p>'.__( 'Thank you for installing UberMenu - WordPress Mega Menu Plugin by SevenSpark!  Click "Start Tour" to view a quick introduction', 'ubermenu').'</p>',
				'top',
				'0 0',
				'Start Tour'
			));
			
			
			$uberTour->addStep( new TipTourStep( 
				'nav-menus.php',
				'',
				'#nav-menu-header',
				__( '1. Create a Menu', 'ubermenu' ),
				'<p>'.__( 'Start off by creating a menu using the WordPress 3 Menu System.  Each menu item has new options based on its level.  To create a mega menu drop down, be sure to check "Activate Mega Menu" in the UberMenu Options', 'ubermenu').'</p>',
				'top'
			));
			
			$uberTour->addStep( new TipTourStep( 
				'nav-menus.php',	//load anywhere
				'',
				'#nav-menu-theme-locations',
				__( '2. Set Theme Location', 'ubermenu' ),
				'<p>'.__( 'Next, set your menu in the appropriate theme location.  If your theme does not support theme locations, you can use UberMenu Easy Integration instead.', 'ubermenu').'</p>',
				'left'
			));
			
			
			$uberTour->addStep( new TipTourStep( 
				'nav-menus.php',	//load anywhere
				'',
				'#nav-menu-theme-megamenus',
				__( '3. Activate UberMenu Theme Locations', 'ubermenu' ),
				'<p>'.__( 'Now, activate UberMenu on the appropriate theme location.  This tells UberMenu which menus is should affect, so you can have 1 UberMenu and multiple non-UberMenus.', 'ubermenu').'</p>',
				'left'
			));
			
			
			$uberTour->addStep( new TipTourStep( 
				'themes.php',	//load anywhere
				'uber-menu',
				'#container-wpmega-orientation',
				__( '4. Pick your Orientation', 'ubermenu' ),
				'<p>'.__( 'Decide whether your menu should be vertically or horizontally aligned.', 'ubermenu').'</p>',
				'left top',
				'0 -50'
			));
			
			$uberTour->addStep( new TipTourStep( 
				'themes.php',	//load anywhere
				'uber-menu',
				'#container-wpmega-transition',
				__( '5. jQuery', 'ubermenu' ),
				'<p>'.__( 'Decide whether you want your menu to be jQuery-Enhanced or pure CSS.  Pure CSS mega submenus will all be full-width unless customized with CSS.', 'ubermenu').'</p>',
				'left top',
				'0 -50'
			));
			
			
			
			
			$uberTour->addStep( new TipTourStep( 
				'themes.php',	//load anywhere
				'uber-menu',
				'.spark-nav-footer a:first',
				__( 'Have a question?', 'ubermenu' ),
				'<p>'.__( 'You can always access the latest version of the support manual by clicking this link.', 'ubermenu').'</p><p>Thank you for your purchase.  Enjoy UberMenu!</p>',
				'left',
				'0 -75'
			));
			
			$uberTour->loadTour();
			
		}
	
		$this->settings->addTour( $this->tour );
		
	}
	
	
	
	
	
	
	
	
	/*
	 * Save the Menu Item Options for UberMenu
	 */
	function updateNavMenuItem( $menu_id, $menu_item_db_id, $args ){
	
		$isTopLevel = $args['menu-item-parent-id'] == 0 ? true : false;
		
		/* For All Levels */
		
		//shortcode
		$shortcode = isset( $_POST['menu-item-shortcode'][$menu_item_db_id] ) ? $_POST['menu-item-shortcode'][$menu_item_db_id] : '';
		update_post_meta( $menu_item_db_id, '_menu_item_shortcode', $shortcode );
		
		//widget area sidebars
		$sidebars = isset( $_POST['menu-item-sidebars'][$menu_item_db_id] ) ? $_POST['menu-item-sidebars'][$menu_item_db_id] : '';
		update_post_meta( $menu_item_db_id, '_menu_item_sidebars', $sidebars );
		
		//highlight
		$highlight = 'off';
		if( isset( $_POST['menu-item-highlight'][$menu_item_db_id] ) && $_POST['menu-item-highlight'][$menu_item_db_id] == 'on'){
			$highlight = 'on';
		}
		update_post_meta( $menu_item_db_id, '_menu_item_highlight', $highlight );
		
		//notext
		$notext = 'off';
		if( isset( $_POST['menu-item-notext'][$menu_item_db_id] ) && $_POST['menu-item-notext'][$menu_item_db_id] == 'on'){
			$notext = 'on';
		}
		update_post_meta( $menu_item_db_id, '_menu_item_notext', $notext );
			
		//nolink
		$nolink = 'off';
		if( isset( $_POST['menu-item-nolink'][$menu_item_db_id] ) && $_POST['menu-item-nolink'][$menu_item_db_id] == 'on'){
			$nolink = 'on';
		}
		update_post_meta( $menu_item_db_id, '_menu_item_nolink', $nolink );
		
		
		
		/* Sub Levels Only */
		
		if( !$isTopLevel ){
			//isheader
			$isheader = 'off';
			if( isset( $_POST['menu-item-isheader'][$menu_item_db_id] ) && $_POST['menu-item-isheader'][$menu_item_db_id] == 'on'){
				$isheader = 'on';
			}
			update_post_meta( $menu_item_db_id, '_menu_item_isheader', $isheader );
			
			//verticaldivision
			$verticaldivision = 'off';
			if( isset( $_POST['menu-item-verticaldivision'][$menu_item_db_id] ) && $_POST['menu-item-verticaldivision'][$menu_item_db_id] == 'on'){
				$verticaldivision = 'on';
			}
			update_post_meta( $menu_item_db_id, '_menu_item_verticaldivision', $verticaldivision );
			
			//newcol
			$newcol = 'off';
			if( isset( $_POST['menu-item-newcol'][$menu_item_db_id] ) && $_POST['menu-item-newcol'][$menu_item_db_id] == 'on'){
				$newcol = 'on';
			}
			update_post_meta( $menu_item_db_id, '_menu_item_newcol', $newcol );
		}
		
		/* Top Level Only */
		
		if( $isTopLevel ){
		
			//isMega
			$isMega = 'off';
			if( isset( $_POST['menu-item-isMega'][$menu_item_db_id] ) && $_POST['menu-item-isMega'][$menu_item_db_id] == 'on'){
				$isMega = 'on';
			}
			update_post_meta( $menu_item_db_id, '_menu_item_isMega', $isMega );
			
			//alignRight - defunct
			//alignSubmenu
			$alignSubmenu = isset( $_POST['menu-item-alignSubmenu'][$menu_item_db_id] ) ? $_POST['menu-item-alignSubmenu'][$menu_item_db_id] : 'center';
			update_post_meta( $menu_item_db_id, '_menu_item_alignSubmenu', $alignSubmenu );
			
			//floatRight
			$floatRight = 'off';
			if( isset( $_POST['menu-item-floatRight'][$menu_item_db_id] ) && $_POST['menu-item-floatRight'][$menu_item_db_id] == 'on'){
				$floatRight = 'on';
			}
			update_post_meta( $menu_item_db_id, '_menu_item_floatRight', $floatRight );
			
			//fullWidth
			$fullWidth = 'off';
			if( isset( $_POST['menu-item-fullWidth'][$menu_item_db_id] ) && $_POST['menu-item-fullWidth'][$menu_item_db_id] == 'on'){
				$fullWidth = 'on';
			}
			update_post_meta( $menu_item_db_id, '_menu_item_fullWidth', $fullWidth );
			
			$numCols = 'auto';
			if( isset( $_POST['menu-item-numCols'][$menu_item_db_id] ) ){
				$numCols = $_POST['menu-item-numCols'][$menu_item_db_id];
			}
			update_post_meta( $menu_item_db_id, '_menu_item_numCols', $numCols );
		
		}
	
	}


	/**
	 * This function is paired with a JavaScript Override Function so that we can use our custom Walker rather
	 * than the built-in version.  This allows us to include the UberMenu Options as soon as an item is added to the menu,
	 * 
	 * This is a slightly edited version of case 'add-menu-item' : located in wp-admin/admin-ajax.php
	 * 
	 * In the future, if WordPress provides a hook or filter, this should be updated to use that instead.
	 * 
	 */
	function addMenuItem_callback(){
		
		if ( ! current_user_can( 'edit_theme_options' ) )
		die('-1');

		check_ajax_referer( 'add-menu_item', 'menu-settings-column-nonce' );
	
		require_once ABSPATH . 'wp-admin/includes/nav-menu.php';
	
		// For performance reasons, we omit some object properties from the checklist.
		// The following is a hacky way to restore them when adding non-custom items.
	
		$menu_items_data = array();
		foreach ( (array) $_POST['menu-item'] as $menu_item_data ) {
			if (
				! empty( $menu_item_data['menu-item-type'] ) &&
				'custom' != $menu_item_data['menu-item-type'] &&
				! empty( $menu_item_data['menu-item-object-id'] )
			) {
				switch( $menu_item_data['menu-item-type'] ) {
					case 'post_type' :
						$_object = get_post( $menu_item_data['menu-item-object-id'] );
					break;
	
					case 'taxonomy' :
						$_object = get_term( $menu_item_data['menu-item-object-id'], $menu_item_data['menu-item-object'] );
					break;
				}
	
				$_menu_items = array_map( 'wp_setup_nav_menu_item', array( $_object ) );
				$_menu_item = array_shift( $_menu_items );
	
				// Restore the missing menu item properties
				$menu_item_data['menu-item-description'] = $_menu_item->description;
			}
	
			$menu_items_data[] = $menu_item_data;
		}
	
		$item_ids = wp_save_nav_menu_items( 0, $menu_items_data );
		if ( is_wp_error( $item_ids ) )
			die('-1');
	
		foreach ( (array) $item_ids as $menu_item_id ) {
			$menu_obj = get_post( $menu_item_id );
			if ( ! empty( $menu_obj->ID ) ) {
				$menu_obj = wp_setup_nav_menu_item( $menu_obj );
				$menu_obj->label = $menu_obj->title; // don't show "(pending)" in ajax-added items
				$menu_items[] = $menu_obj;
			}
		}
	
		if ( ! empty( $menu_items ) ) {
			$args = array(
				'after' => '',
				'before' => '',
				'link_after' => '',
				'link_before' => '',
				//'walker' => new Walker_Nav_Menu_Edit,
				'walker' =>	new UberMenuWalkerEdit,			//EDIT FOR UBERMENU
			);
			echo walk_nav_menu_tree( $menu_items, 0, (object) $args );
		}
	}
	
	
	/*
	 * Create the UberMenu SparkOptions Panel and Settings object
	 */
	function optionsMenu(){
	
		$sparkOps = new UberOptions( 
							UBERMENU_SETTINGS, 
							
							//Menu Page
							array(
								'parent_slug' 	=> 'themes.php',
								'page_title'	=> 'UberMenu Options',
								'menu_title'	=> 'UberMenu',
								'menu_slug'		=> 'uber-menu',
							),
							
							//Links
							array(

								1	=>	array(
									'href'	=>	'http://j.mp/dPmJ8m',
									'class'	=>	'spark-outlink-hl',
									'title'	=>	'Read the Support Manual',
									'text'	=>	'Support Manual &rarr;',							
								),
								
								2	=>	array(
									'href'	=>	'http://j.mp/ekannC',
									'class'	=>	'spark-outlink',
									'title'	=>	'Frequently Asked Questions',
									'text'	=>	'FAQs  &rarr;',							
								),
								
								3	=>	array(
									'href'	=>	'http://sevenspark.com/out/support',
									'class'	=>	'spark-outlink',
									'title'	=>	'Submit a support request',
									'text'	=>	'Get Support &rarr;',							
								),
								
								4	=>	array(
									'href'	=>	'http://j.mp/fDpVkP',
									'class'	=>	'spark-outlink',
									'title'	=>	'Purchase a license for use on a second installation',
									'text'	=>	'Additional License &rarr;'
								)
							)
							
						);
		
		
		
		/*
		 * Basic Config Panel
		 */
		$basic = 'basic-config';
		$sparkOps->registerPanel( $basic, 'Basic Configuration' );
		
		$sparkOps->addHidden( $basic , 'current-panel-id' , $basic );
		
		$sparkOps->addRadio( $basic, 
					'wpmega-orientation',
					'Orientation',
					'Include an "Add to Wishlist" button on your individual product pages',
					array(
						'horizontal'	=>	'Horizontal',
						'vertical'		=>	'Vertical'
					),
					'horizontal'
					);
		
		$sparkOps->addCheckbox( $basic,
					'wpmega-menubar-full',
					'Expand Menu Bar Full Width?',
					'Enable to have the menu bar fill its container.  Disable to size the menu bar to its contents. ',
					'on'
					);
					
		$sparkOps->addCheckbox( $basic,
					'responsive-menu',
					'Responsive Mega Menu',
					'Enable UberMenu\'s responsive properties. Requires a responsive theme, otherwise you may have '.
					'strange results if your theme is fixed-width.',
					'off'
					);
			
		/* ANIMATIONS */
				
		$sparkOps->addSubHeader( $basic, 
					'wpmega-animation-header',
					'jQuery Animations &amp; Auto-positioning'
					);
		
		$sparkOps->addCheckbox( $basic,
					'wpmega-jquery',
					'jQuery Enhanced',
					'Disable to use UberMenu without jQuery enhancement.  All submenus will be full-width by default.',
					'on'
					);
		
		$sparkOps->addRadio( $basic,
					'wpmega-transition',
					'Animation',
					'',
					array(
						'slide'	=>	'Slide',
						'fade'	=>	'Fade',
						'none'	=>	'None',
					),
					'slide'
					);
					
		$sparkOps->addTextInput( $basic,
					'wpmega-animation-time',
					'Animation Time',
					'Set how long the animation should take in milliseconds',
					300,
					'spark-minitext',
					'ms'	
					);
					
		$sparkOps->addRadio( $basic,
					'wpmega-trigger',
					'Trigger',
					'',
					array(
							'hover'			=>	'Hover',
							'hoverIntent'	=>	'Hover Intent',
							'click'			=>	'Click'
						),
					'hoverIntent'
					);
		
		
		$sparkOps->addTextInput( $basic,
					'wpmega-hover-interval',
					'Hover Intent Interval',
					'The number of milliseconds before the hover event is triggered.  Defaults to 100. [Applies to trigger: Hover Intent]',
					20,
					'spark-minitext',
					'ms'	
					);
					
		$sparkOps->addTextInput( $basic,
					'wpmega-hover-timeout',
					'Hover Intent Timeout',
					'The number of milliseconds before the out event is triggered (milliseconds until the submenu closes after hover-off).  Defaults to 400. [Applies to trigger: Hover Intent]',
					400,
					'spark-minitext',
					'ms'	
					);			
					
		$sparkOps->addCheckbox( $basic,
					'wpmega-submenu-full',
					'Full Width Submenus',
					'Enable to force all submenus to be full-width, regardless of their content.  Disable '.
						'to size submenus to their content and position with Javascript.',
					'off'
					);			
					
		$sparkOps->addCheckbox( $basic,
					'wpmega-autoAlign',
					'Auto Align',
					'Automatically align the second-level menu items by setting all widths the width of the widest item in each submenu. '.
						'<div class="spark-infobox spark-infobox-warning">This feature remains for legacy use.  A better way to accomplish column alignment is to choose the "Full Width" option and set the number of columns in a menu item\'s settings</div>',
					'off'
					);
		
		
		$sparkOps->addSubHeader( $basic, 
					'wpmega-vertical-header',
					'Vertical Menu Sizing'
					);
		
		$sparkOps->addTextInput( $basic,			
					'vertical-submenu-w',
					'Vertical Mega Submenu Width',
					'Since the width of a submenu is constrained by the container that the UberMenu is placed in, you must set the width manually if you want it to be wider.',
					'',
					'spark-minitext',
					'px'
					);
					
		/* Mobile Settings */
		$sparkOps->addSubHeader( $basic, 
					'mobile-settings-header',
					'Mobile Settings'
					);
					
		$sparkOps->addCheckbox( $basic,
					'android-click',
					'Force Click Trigger on Android',
					'<em>Makes Android interface act more like iOS</em>.  By default, Android will redirect to the top level menu item link URL as soon as it is tapped.  To display a submenu, users must '.
					'tap, and without lifting their finger slide off of the menu item.  By enabling Force-Click, the menu touch interface on Android will '.
					'work similarly to iOS: a tap will open the sub menu.  Tap + hold will follow the link.',
					'off'
					);	
		
		
		/* DESCRIPTION, SHORTCODE, WIDGET SETTINGS */
		$descConfig = 'description-config';
		$sparkOps->registerPanel( $descConfig, 'Descriptions, Shortcodes, Widgets' );
		
		$sparkOps->addSubHeader( $descConfig, 
					'wpmega-desc-header',
					'Descriptions'
					);
		
		$sparkOps->addInfobox( $descConfig,
					'wpmega-descriptions',
					'',
					'You can turn on and off menu item descriptions on a per-level basis.'
					);
		
		$sparkOps->addCheckbox( $descConfig,
					'wpmega-description-0',
					'Display Top-Level Descriptions'
					);
					
		$sparkOps->addCheckbox( $descConfig,
					'wpmega-description-1',
					'Display Sub-Header Descriptions'
					);
					
		$sparkOps->addCheckbox( $descConfig,
					'wpmega-description-2',
					'Display Sub-Menu Item Descriptions'
					);	
					
		
		//ADVANCED
		$sparkOps->addSubHeader( $descConfig,
					'wpmega-othercontent-header',
					'Advanced Content Capabilities: Content Overrides, Shortcodes, Widgets'
					);
		
		$sparkOps->addCheckbox( $descConfig,
					'wpmega-shortcodes',
					'Allow Content Overrides',
					'Content Overrides allow you to include non-links in the Mega Menu.  You can use shortcodes, which will allow you to put items like contact forms, search boxes, or galleries in your Menus',
					'off'
					);	
		
		$sparkOps->addTextInput( $descConfig,	//panel_id 
					'wpmega-sidebars',
					'Number of Widget Areas',
					'Enter the number of widget areas/sidebars that should be generated for the UberMenu.  You can then add widgets through the normal means. <span class="ss-infobox ss-infobox-warning">This value must be 1 or greater to use widgets</span>',
					'0',
					'spark-minitext'
					);		
		
		$sparkOps->addCheckbox( $descConfig,
					'wpmega-top-level-widgets',
					'Allow Top-Level Widgets',
					'Turn this on to allow placing widgets in the top level, in addition to the submenu.  Remember, if you place widgets in the top level, they will always be visible.  In many cases, this will break your layout.',
					'off'
					);	
		
		$sparkOps->addCheckbox( $descConfig,
					'load-google-maps',
					'Load Google Maps',
					'Loads the Google Maps API so you can use maps in your menu with the provided shortcode.  Note that jQuery Enhancements must be enabled in order to utilize the map shortcode.',
					'off'
					);	
							
							
							
		
		/*
		 * IMAGE SETTINGS
		 */
		$imageConfig = 'image-config';
		$sparkOps->registerPanel( $imageConfig, 'Images' );			
		
		
		$sparkOps->addInfobox( $imageConfig,
						'wpmega-images',
						'',
						'Control how your images are sized and processed.'				
					);
		
		$sparkOps->addCheckbox( $imageConfig,			
					'wpmega-resizeimages',
					'Resize Images',
					'Should the images be all sized the same?  <span class="spark-infobox">This must be enabled if you wish to use Timthumb.</span>',
					'on'
					);
					
		$sparkOps->addTextInput( $imageConfig,
					'wpmega-image-width',
					'Image Width',
					'Setting this value is important for proper spacing in the menu, even if you aren\'t resizing your images.',
					'25',
					'spark-minitext',
					'px'
					);
		
		$sparkOps->addTextInput( $imageConfig,		
					'wpmega-image-height',
					'Image Height',
					'',
					'25',
					'spark-minitext',
					'px'
					);
		
		$sparkOps->addCheckbox( $imageConfig,
					'wpmega-usetimthumb',
					'Use Timthumb',
					'Use TimThumb to automatically scale and crop images to the preferred width and height.  <span class="spark-infobox">You must follow the instructions in the UberMenu Support Guide to setup TimThumb for use.  Note that some hosts, especially shared hosting, will not allow you to use timthumb on their servers.</span>',
					'off'
					);		
	
	
	
	
	
		/* THEME INTEGRATION */
		$themeIntegration = 'theme-config';
		$sparkOps->registerPanel( $themeIntegration, 'Theme Integration' );	
		
		$sparkOps->addInfobox( $themeIntegration,
					'wpmega-themeintegration',
					'',
					'Help with integrating UberMenu with complex or misbehaving themes.'		
					);
					
		$sparkOps->addCheckbox( $themeIntegration,
					'wpmega-strict',
					'Strict Mode',
					'Disable Strict Mode if you have only one menu on your site, and it should be a mega menu, and your theme does not support Theme Locations.  Otherwise, leave this on or you may end up with multiple UberMenus.',
					'on'
					);
		
		$sparkOps->addCheckbox( $themeIntegration,
					'wpmega-easyintegrate',
					'Easy Integration',
					'For themes that don\'t properly support WordPress 3 Menus.  Just turn this on and place '.
							'<code>&lt;?php uberMenu_easyIntegrate(); ?&gt;</code> in your header.php file, or <code>[uberMenu_easyIntegrate]</code> in a text widget to place your menu.',
					'off'
					);
		
		$sparkOps->addCheckbox( $themeIntegration,			
					'wpmega-remove-conflicts',
					'Remove Theme Conflicts',
					'This attempts to remove previously registered javascript acting on the menu.',
					'on'
					);
					
		$sparkOps->addCheckbox( $themeIntegration,
					'wpmega-minimizeresidual',
					'Minimize Residual Styling',
					'This will change the ID of the menu\'s top level UL.  If you still have residual styling, you likely need 
						to manually edit the ID or class of the elements surrounding the <code>wp_nav_menu</code> call in your header.php.',
					'on'
					);
					
		$sparkOps->addCheckbox( $themeIntegration,
					'wpmega-iefix',
					'Use IE Fix Script',
					//'Disable this only if it is causing problems or if you are already including it elsewhere.',
					'Depending on how your theme is coded, some themes will require this for the menu to work properly while others will not.  IE is a fickle bitch, so try it both ways and see which works better for your theme.',
					'off'
					);
					
		$sparkOps->addCheckbox( $themeIntegration,
					'wpmega-jquery-noconflict',
					'Run jQuery in noConflict Mode',
					'If your theme does not load the default WordPress jQuery library, turn this on.',
					'off'
					);
					
		$sparkOps->addCheckbox( $themeIntegration,
					'wpmega-load-on-login',
					'Load UberMenu on Login and Registration Pages',
					'Since WordPress login and registration pages do not include a menu by default, UberMenu won\'t load there.  If you are styling your login/registration page, turn this on - otherwise UberMenu will be completely unstyled.',
					'off'
					);
				
		$sparkOps->addCheckbox( $themeIntegration,	
					'wpmega-include-jquery',
					'Include jQuery',
					'This is REQUIRED.  Only disable this if your theme or another plugin already includes hoverIntent.js.  <span class="spark-infobox spark-infobox-warning"><strong>DO NOT DISABLE UNLESS YOU KNOW WHAT YOU ARE DOING!</strong></span>',
					'on'
					);
					
		$sparkOps->addCheckbox( $themeIntegration,
					'wpmega-include-hoverintent',
					'Include Hover Intent',
					'This is REQUIRED.  Only this if your theme or another plugin already includes hoverIntent.js.  <span class="spark-infobox spark-infobox-warning"><strong>DO NOT DISABLE UNLESS YOU ARE CERTAIN!</strong></span>',
					'on'
					);	
		
		$sparkOps->addCheckbox( $themeIntegration,
					'no-ie6',
					'Default to theme menu in IE6',
					'UberMenu is not compatible with IE6, as noted in the product description.  By default, UberMenu will not load in IE6, in an effort to fall back to the default theme menu, which may or may not be IE6-compatible.  If for some reason you want to disable this feature, you can do so here.',
					'on'
					);
		
		
				
	
		/* ADVANCED */
		$advanced = 'advanced-config';
		$sparkOps->registerPanel( $advanced, 'Advanced Settings' );
		
		$sparkOps->addInfobox( $advanced , 
					'advanced-panel-warning' , 
					'Warning' ,
					'Do not change advanced settings unless you are certain of what you are doing.',
					'spark-infobox-warning' 
					);
					
		$sparkOps->addTextInput( $advanced,
					'wpmega-container-w',
					'Menu Bar Width',
					'Enter a width in pixels.  UberMenu automatically sizes to its container, so you only need to use this if you want UberMenu '.
						'to be a different size.  It will automatically be centered when possible.'.
						'<span class="spark-infobox spark-infobox-warning">Remember, your submenu\'s maximum width is restricted to the width of your menu bar by default.</span>'.
						'<span class="spark-infobox spark-infobox-warning">Be sure to disable the Expand Menu Bar Full Width option in basic settings if you intend to use this</span>',
					'',
					'spark-minitext',
					'px'
					);
					
		$sparkOps->addCheckbox( $advanced,
					'center-menubar',
					'Center Menu Bar',
					'Coupled with the width above, this will center your menu within its container.',
					'off'
					);
					
		$sparkOps->addCheckbox( $advanced,
					'enable-clearfix',
					'Enable Clearfix',
					'Important for centering.',
					'off'
					);
					
		$sparkOps->addTextInput( $advanced,
					'inner-menu-width',
					'Inner Menu Width',
					'Enter a width in pixels (most common: 960).  This is useful for centering the menu items while allowing the menu bar to span the entire window.',
					'',
					'spark-minitext',
					'px'
					);
					
		$sparkOps->addCheckbox( $advanced,
					'center-inner-menu',
					'Center Inner Menu',
					'Coupled with the width above, this will center your inner menu within the menu bar.'.
					'<span class="spark-infobox spark-infobox-warning">You must set an explicit width above to center the menu.</span>',
					'off'
					);
					
		/*$sparkOps->addTextInput( $advanced,			
					'wpmega-max-submenu-w',
					'Maximum Submenu Width',
					'Normally, a submenu can only be as wide as the top level menu bar.  If you want it to be wider, set the value here.',
					'',
					'spark-minitext',
					'px'
					);*/
		
		$sparkOps->addCheckbox( $advanced,
					'wpmega-html5',
					'HTML5',
					'Use the HTML5 <code>&lt;nav&gt;</code> element as the menu container.  <span class="spark-infobox spark-infobox-warning">Only enable this if your theme supports HTML5!  Otherwise it\'ll break Internet Explorer</span>'
					);
					
		$sparkOps->addCheckbox( $advanced,
					'include-basic-css',
					'Include basic.css',
					'UberMenu\'s basic.css contains all the structural code for the menu.  <span class="spark-infobox spark-infobox-warning">Without this code, the menu will not work.  Only disable this if you are going to manually include it elsewhere.</span>',
					'on'
					);
	
		$sparkOps->addCheckbox( $advanced,
					'wpmega-debug',		//'Debug' is legacy
					'Development Mode',
					'Run in Development mode.  This will load the .dev.js ubermenu script rather than the minified version.  Easier for testing customizations and debugging.'
					);
					
		$sparkOps->addResetButton( $advanced,
					'wpmega-reset-options',
					'Reset Options',
					'Reset UberMenu Settings to the factory defaults.'
					);
					
					
		
		/* STYLE SETTINGS */
		$styleConfig = 'style-config';
		$sparkOps->registerPanel( $styleConfig, 'Style Configuration' );
		
		$sparkOps->addInfobox( $styleConfig, 
					'wpmega-style-settings', 
					'',
					'Configure how you want to apply styles to the menu'
					);
		
		$sparkOps->addRadio( $styleConfig,
					'wpmega-style',
					'Style Application',
					'',
					array(
						'preset'	=>	'Use a Preset'.
										'<span class="spark-admin-op-radio-desc">Select from the Preset Styles below</span>',
						
						'inline'	=>	'Style Generator'.
										'<span class="spark-admin-op-radio-desc">If custom file is not writable, adds <code>&lt;style&gt;</code> tags to <code>&lt;head&gt;</code></span>',
						
						'custom'	=>	'Custom'.
										'<span class="spark-admin-op-radio-desc">Load <code>ubermenu/styles/custom.css</code>.  Always use this file for customizations!</span>',
						
						'none'		=>	'Do Nothing '.
										'<span class="spark-admin-op-radio-desc">I will manually include the styles elsewhere, like in my <code>style.css</code>.</span>',
					),
					'preset'
					);
					
		$sparkOps->addSelect( $styleConfig,
					'wpmega-style-preset',	
					'Style Preset',
					'',
					'ubermenu_getStylePresetOps',
					'grey-white'
					);
					
		$sparkOps->addTextarea( $styleConfig,
					'wpmega-css-tweaks',
					'Custom CSS',
					'Best for minor CSS tweaks.  Want to write a custom style?  Use custom.css.',
					''
					);
					
				
				
				
		/* STYLE GENERATOR */
		$styleGen = 'style-gen';
		
		$sparkOps->registerPanel( $styleGen, 'Style Generator' );
		
		$sparkOps->addCheckbox( $styleGen,
					'save-style-gen-to-file',
					'Save Custom CSS to file',
					'Will attempt to save to a file, rather than including in site head.'
					);
		
		$sparkOps->addHidden( $styleGen , 
					'use-gen-skin',
					'off' 
					);	
		
		//Menu Bar
		$sparkOps->addSubHeader( $styleGen,
					'menu-bar-header',
					'Menu Bar'
					);
		
		//@menubarColorStart, @menubarColorEnd
		$sparkOps->addColorPicker( $styleGen,
					'menu-bar-background',
					'Menu Bar Background',
					'',
					true
					);
		
		//@menubarBorderColor
		$sparkOps->addColorPicker( $styleGen,
					'menu-bar-border-color',
					'Menu Bar Border',
					'',
					false
					);
		
		//@menubarRadius
		$sparkOps->addTextInput( $styleGen, 
					'menu-bar-border-radius',
					'Menu Bar Border Radius',
					'CSS3: only works in modern browsers.',
					'0',
					'minitext',
					'px'
					);
					
		//Top Level
		
		$sparkOps->addSubHeader( $styleGen,
					'top-level-header',
					'Top Level'
					);
		
		//@topLevelFontSize
		$sparkOps->addTextInput( $styleGen, 
					'top-level-item-font-size',
					'Font Size',
					'Example: <em>12px</em> or <em>1.5em</em>',
					'12px',
					'minitext'
					);	
		 
		//@topLevelColor
		$sparkOps->addColorPicker( $styleGen,
					'top-level-item-font-color',
					'Font Color',
					'',
					false
					);
		
		//@topLevelColorHover
		$sparkOps->addColorPicker( $styleGen,
					'top-level-item-font-color-hover',
					'Font Color [Hover]',
					'',
					false
					);
		
		//@currentColor
		$sparkOps->addColorPicker( $styleGen,
					'top-level-item-font-color-current',
					'Font Color [Current Menu Item]',
					'',
					false
					);
		
		//@topLevelTextShadow
		$sparkOps->addColorPicker( $styleGen,
					'top-level-item-text-shadow',
					'Text Shadow',
					'',
					false
					);
	
		//@topLevelTextShadowHover
		$sparkOps->addColorPicker( $styleGen,
					'top-level-item-text-shadow-hover',
					'Text Shadow [Hover]',
					'',
					false
					);
					
					
		//@topLevelTextTransform
		$sparkOps->addSelect( $styleGen,			
					'top-level-text-transform',
					'Text Transform',
					'',
					array(
						'uppercase'	=>	'uppercase',
						'capitalize'=>	'capitalize',
						'lowercase'	=>	'lowercase',
						'none'		=>	'none'
					),
					'none'
					);
					
		//@topLevelTextWeight
		$sparkOps->addSelect( $styleGen,
					'top-level-text-weight',		
					'Font Weight',
					'',
					array(
						'normal'	=>	'normal',
						'bold'		=>	'bold',
					),
					'bold'
					);
					
	
		//@topLevelDividerColor
		$sparkOps->addColorPicker( $styleGen,
					'top-level-item-border',
					'Item Divider Color',
					'',
					false
					);
	
		//@topLevelPaddingX
		$sparkOps->addTextInput( $styleGen, 
					'top-level-item-padding-x',
					'Horizontal Padding',
					'',
					'15',
					'minitext',
					'px'
					);	
					
		//@topLevelPaddingY
		$sparkOps->addTextInput( $styleGen, 
					'top-level-item-padding-y',
					'Vertical Padding',
					'',
					'12',
					'minitext',
					'px'
					);	
	
		//@topLevelGlowOpacity
		$sparkOps->addTextInput( $styleGen,
					'top-level-item-glow-opacity',
					'Glow Opacity',
					'The top and left edge are given a lighter glow to add depth.  Set a decimal between 0 and 1. For lighter menus, set a value closer to 1.  For darker menus, set a number closer to 0.',
					'.9',
					'minitext'
					);
		
		//@topLevelBackgroundHoverStart, @topLevelBackgroundHoverEnd
		$sparkOps->addColorPicker( $styleGen,
					'top-level-item-background-hover',
					'Background Color [Hover]',
					'',
					true
					);
		
		//@topLevelGlowOpacityHover
		$sparkOps->addTextInput( $styleGen,
					'top-level-item-glow-opacity-hover',
					'Glow Opacity [Hover]',
					'The top and left edge are given a lighter glow to add depth.  Set a decimal between 0 and 1. For lighter menus, set a value closer to 1.  For darker menus, set a number closer to 0.',
					'.9',
					'minitext'
					);
	
		/*$sparkOps->addColorPicker( $styleGen,
					'top-level-item-border-hover',
					'Tab and Dropdown Border Color [Hover]',
					'',
					false
					);*/
	
		/*$sparkOps->addTextInput( $styleGen,
					'top-level-item-border-radius',
					'Tab and Dropdown Border Radius [Hover]',
					'',
					'0',
					'minitext',
					'px'
					);*/
	
		
		//Sub Menus
		$sparkOps->addSubHeader( $styleGen,
					'sub-level-header',
					'Sub Menu Level'		
		);
		
		//@subMenuBorderColor
		$sparkOps->addColorPicker( $styleGen,
					'sub-menu-border',
					'Submenu Border Color',
					'',
					false
					);
		
		//@submenuColorStart
		$sparkOps->addColorPicker( $styleGen,
					'sub-level-background',
					'Dropdown Background Color',
					'Set the second color to create a vertical gradient.  Leave blank for a flat color.',
					true
					);
	
		//@subMenuColor
		$sparkOps->addColorPicker( $styleGen,
					'sub-level-item-font-color',
					'Submenu Font Color',
					'The default font color for the submenus - overridden by header and item colors',
					false
					);	

		//@subMenuTextShadow
		$sparkOps->addColorPicker( $styleGen,
					'sub-level-text-shadow',
					'Submenu Text Shadow Color',
					'',
					false
					);	

		//@subMenuBoxShadow
		$sparkOps->addColorPicker( $styleGen,
					'sub-level-box-shadow',
					'Drop Shadow Color',
					'',
					false
					);
					
		//@SubMenuMinColWidth
		$sparkOps->addTextInput( $styleGen,
					'sub-level-column-width',
					'Minimum Column Width',
					'You can set the minimum width of the columns in the dropdown.  Useful to align columns in multi-row layouts.',
					'100',
					'minitext',
					'px'
					);
					

		
		//Submenu Headers
		$sparkOps->addSubHeader( $styleGen,
					'sub-level-header-headers',
					'Sub Menu Headers'		
		);
		
		//@subHeaderColor
		$sparkOps->addColorPicker( $styleGen,
					'sub-level-header-font-color',
					'Header Font Color',
					'',
					false
					);	
		
		//@subHeaderSize
		$sparkOps->addTextInput( $styleGen,
					'sub-level-header-font-size',
					'Header Font Size',
					'Example: 12px or 1.5em',
					'12px',
					'minitext',
					''
					);
		
		//@subHeaderWeight
		$sparkOps->addSelect( $styleGen,
					'sub-level-header-font-weight',		
					'Header Font Weight',
					'',
					array(
						'normal'	=>	'normal',
						'bold'		=>	'bold',
					),
					'bold'
					);

		//@subHeaderBorderBottom (1)					
		$sparkOps->addSelect( $styleGen,			
					'sub-level-header-border-style',
					'Header Underline Style',
					'',
					array(
						'dotted'	=>	'dotted',
						'dashed'	=>	'dashed',
						'solid'		=>	'solid',
						'none'		=>	'none',
					),
					'dotted'
					);
		
		//@subHeaderBorderBottom (2)					
		$sparkOps->addColorPicker( $styleGen,			
					'sub-level-header-border-color',
					'Header Underline Color',
					'',
					false
					);



		//Submenu Links
		$sparkOps->addSubHeader( $styleGen,
					'sub-level-header-links',
					'Sub Menu Links'		
					);
		
		//@subLinkColor
		$sparkOps->addColorPicker( $styleGen,
					'sub-level-link-font-color',
					'Submenu Link Font Color',
					'',
					false
					);	
		
		//@subLinkColorHover
		$sparkOps->addColorPicker( $styleGen,
					'sub-level-link-font-color-hover',
					'Submenu Link Font Color [Hover]',
					'',
					false
					);
		
		//@subLinkSize
		$sparkOps->addTextInput( $styleGen,
					'sub-level-link-font-size',
					'Submenu Link Font Size',
					'Example: 12px or 1.5em',
					'12px',
					'minitext',
					''
					);
		
		//@subLinkBackground
		$sparkOps->addColorPicker( $styleGen,
					'sub-level-link-background',
					'Item Background Color',
					'',
					false
					);	
		
		//@subLinkBackgroundHover
		$sparkOps->addColorPicker( $styleGen,
					'sub-level-link-background-hover',
					'Item Background Color [Hover]',
					'',
					false
					);	
		
		
							
		//Miscellaneous
		$sparkOps->addSubHeader( $styleGen,
					'sub-level-other-header',
					'Miscellaneous'	
		);
		
		//@highlightColor
		$sparkOps->addColorPicker( $styleGen,
					'sub-level-highlight-color',
					'Highlight Color',
					'Items that are highlighted will be displayed this color',
					false
					);
		
		//@descriptionSize
		$sparkOps->addTextInput( $styleGen,
					'menu-description-size',
					'Description Font Size',
					'Example: 12px or 1.5em',
					'9px',
					'minitext',
					''
					);
		
		//@descriptionColor: #bbb;
		$sparkOps->addColorPicker( $styleGen,
					'menu-description-color',
					'Description Font Color',
					'',
					false
					);
			
		//@descriptionTransform
		$sparkOps->addSelect( $styleGen,			
					'description-transform',
					'Description Transform',
					'',
					array(
						'uppercase'	=>	'uppercase',
						'capitalize'=>	'capitalize',
						'lowercase'	=>	'lowercase',
						'none'		=>	'none'
					),
					'none'
					);
		
		//@topLevelArrowColor
		$sparkOps->addColorPicker( $styleGen,
					'top-level-arrow-color',
					'Top Level Arrow Color',
					'',
					false
					);
		
		//@flyoutArrowColor
		$sparkOps->addColorPicker( $styleGen,
					'sub-level-arrow-color',
					'Flyout Sub Level Arrow Color',
					'',
					false
					);
		
					
					
		
		//PREVIEW
		$sparkOps->addSubHeader( $styleGen,
					'stylegen-preview',
					'Preview'	
					);
		
		$sparkOps->addSelect( $styleGen,
					'menu-preview',
					'Menu',
					'Select the menu you\'d like to preview',
					$this->getNavMenus()
					);
		
		$sparkOps->addCustomField( $styleGen,
					'ubermenu-preview-button',
					'previewButton'
					);
		
		
		
		return $sparkOps;
		
	}

	function getNavMenus(){
		$menus = array();
		foreach( wp_get_nav_menus() as $m ){
			$menus[$m->slug] = $m->name;
		}
		return $menus;
	}


	function menuItemCustomOptions( $item_id ){

		//$settings = wpmega_getSettings();
		global $uberMenu;
		$settings = $uberMenu->getSettings();

		$minSidebarLevel = 1;
		if( $settings->op( 'wpmega-top-level-widgets' ) ){
			$minSidebarLevel = 0;
		}
		?>
	
			<!--  START MEGAWALKER ATTS -->
			<div class="clear">
				<a href="#" class="wpmega-showhide-menu-ops">Show/Hide UberMenu Options</span></a>
				<span class="ss-info-container">?<span class="ss-info">UberMenu options set here only apply to menus that have been activated in the "Activate UberMenu Locations" meta box.</span></span> 
					
				<div class="wpmega-atts">
					
					<?php
						
						
						$this->showCustomMenuOption(
							'isMega', 
							$item_id, 
							array(
								'level'	=> '0', 
								'title' => __('Make this item\'s submenu a mega menu.  Leave unchecked to use a flyout menu'), 
								'label' => __('Activate Mega Menu'), 
								'type' 	=> 'checkbox', 
							)
						);
						
						$this->showCustomMenuOption(
							'notext', 
							$item_id, 
							array(
								'level' => '0-plus', 
								'title' => __('Remove the Navigation Label text from the link.  Can be used, for example, with image-only links.'), 
								'label' => __('Disable Text'), 
								'type' 	=> 'checkbox', 
							)
						);

						$this->showCustomMenuOption(
							'nolink', 
							$item_id, 
							array(
								'level' => '0-plus', 
								'title' => __('Remove the link altogether.  Can be used, for example, with content overrides or widgets.'), 
								'label' => __('Disable Link'), 
								'type' 	=> 'checkbox', 
							)
						);

						$this->showCustomMenuOption(
							'floatRight', 
							$item_id, 
							array(
								'level' => '0', 
								'title' => __('Float the menu item to the right edge of the menu bar.'), 
								'label' => __('Align Menu Item to Right Edge'), 
								'type' 	=> 'checkbox', 
							)
						);
						
						$this->showCustomMenuOption(
							'fullWidth', 
							$item_id, 
							array(
								'level' => '0', 
								'title' => __('Make this item\'s submenu the full width of the menu bar.  Note that with javascript disabled, submenus are full-width by default.  This is horizontal-orientation specific.  To make a vertical menu full-width, set the width appropriately in the Basic Configuration Options.'), 
								'label' => __('Full Width Submenu'), 
								'type' 	=> 'checkbox', 
							)
						);

						$this->showCustomMenuOption(
							'alignSubmenu', 
							$item_id, 
							array(
								'level' => '0', 
								'title' => __('Select where to align the submenu.  Note that submenus can only be centered if jQuery Enhancements are enabled.  Horizontal-orientation specific.'), 
								'label' => __('Align Mega Submenu'), 
								'type' 	=> 'select', // 'checkbox',
								'ops'	=>	array(
									'left'	=>	'Left',
									'center'=>	'Center',
									'right'	=>	'Right',
								),
								'default'	=>	'center'
							)
						);

						
						$this->showCustomMenuOption(
							'numCols', 
							$item_id, 
							array(
								'level' => '0', 
								'title' => __('<strong>Only valid for full-width submenus</strong>.  Set how many columns should be in each row in the submenu.  Columns will be sized evenly.'), 
								'label' => __('Submenu Columns [FullWidth]'), 
								'type' 	=> 'select', // 'checkbox',
								'ops'	=>	array(
									'auto'	=> 'Automatic',
									'1'		=>	'1',
									'2'		=>	'2',
									'3'		=>	'3',
									'4'		=>	'4',
									'5'		=>	'5',
									'6'		=>	'6',
									'7'		=>	'7',
								),
								'default'	=>	'auto'
							)
						);
						
						
						//here
						
						$this->showCustomMenuOption(
							'isheader', 
							$item_id, 
							array(
								'level' => '2-plus', 
								'title' => __('Display this item as a header, like second-level menu items.  Good for splitting columns vertically without starting a new row'), 
								'label' => __('Header Display'), 
								'type' => 'checkbox', 
							)
						);

						$this->showCustomMenuOption(
							'highlight', 
							$item_id, 
							array(
								'level' => '0-plus', 
								'title' => __('Make this item stand out.'), 
								'label' => __('Highlight this item'), 
								'type' => 'checkbox', 
							)
						);

						$this->showCustomMenuOption(
							'newcol', 
							$item_id, 
							array(
								'level' => '2-plus', 
								'title' => __('Use this on the item that should start the second column under the same header - for example, have two columns under "Sports"'), 
								'label' => __('Start a new column (under same header)?'), 
								'type' => 'checkbox', 
							)
						);

						$this->showCustomMenuOption(
							'verticaldivision', 
							$item_id, 
							array(
								'level' => '1', 
								'title' => __('Begin a new row with this item.  You should always check this on the first item in the second row of your submenu.'), 
								'label' => __('New Row (Vertical Division)'), 
								'type' => 'checkbox', 
							)
						);
						
						
						
						
						//CONTENT OVERRIDES AND WIDGET AREAS
						
						if ( $settings->op( 'wpmega-shortcodes' ) ) {
							$this->showCustomMenuOption(
								'shortcode', 
								$item_id, 
								array(
									'level' => '0-plus', 
									'title' => __('Display custom content in this menu item.  This input accepts shortcodes so you can display things like contact forms, search boxes, or galleries.  Check "Disable Link" above to display only this content, instead of a link.'), 
									'label' => __('Content Override'), 
									'type' 	=> 'textarea', 
								)
							);
						}
						
						$this->showCustomMenuOption(
							'sidebars', 
							$item_id, 
							array(
								'level' => $minSidebarLevel . '-plus', 
								'title' => __('Select the widget area to display'), 
								'label' => __('Display a Widget Area'), 
								'type' => 'sidebarselect', 
							)
						);


						//global $temp_ID;
						//$temp_ID = $item_id;
						global $post_ID;
						//wp33
						$post_ID = $item_id;
						//wp33

						$iframeSrc = get_upload_iframe_src('image') . '&amp;tab=type&amp;width=640&amp;height=589';
						//media-upload.php?post_id=<?php echo $item_id; &amp;type=image&amp;TB_iframe=1&amp;width=640&amp;height=589
						$wp_mega_link = "Set Thumbnail";
						$wp_mega_img = $uberMenu->getImage( $item_id );
						if (!empty($wp_mega_img)) {
							$wp_mega_link = $wp_mega_img;
							$ajax_nonce = wp_create_nonce("set_post_thumbnail-$item_id");
							$wp_mega_link .= '<div class="remove-item-thumb" id="remove-item-thumb-' . $item_id . '"><a href="#" id="remove-post-thumbnail-' . $item_id . '" onclick="wpmega_remove_thumb(\'' . $ajax_nonce . '\', ' . $item_id . ');return false;">' . esc_html__('Remove image') . '</a></div>';
						}
					?>
					<p class="wpmega-custom-all"><a class="thickbox set-menu-item-thumb button clear" id="set-post-thumbnail-<?php echo $item_id;?>" href="<?php echo $iframeSrc;?>" title="Set Thumbnail"><?php
						echo $wp_mega_link;
					?></a></p>
					
				</div>
				<!--  END MEGAWALKER ATTS -->
			</div>
	<?php
	}

	function showCustomMenuOption( $id, $item_id, $args ){
		extract( wp_parse_args(
			$args, array(
				'level'	=> '0-plus',
				'title' => '',
				'label' => '',
				'type'	=> 'text',
				'ops'	=>	array(),
				'default'=> '',
			) )
		);
	
		global $uberMenu;
		$settings = $uberMenu->getSettings();
		
		$desc = '<span class="ss-desc">'.$label.'<span class="ss-info-container">?<span class="ss-info">'.$title.'</span></span></span>';
		?>
				<p class="field-description description description-wide wpmega-custom wpmega-l<?php echo $level;?> wpmega-<?php echo $id;?>">
					<label for="edit-menu-item-<?php echo $id;?>-<?php echo $item_id;?>">
						
						<?php
						
						switch($type) {
							
							case 'text': 
								?>						
								<input type="text" id="edit-menu-item-<?php echo $id;?>-<?php echo $item_id;?>" 
									class="edit-menu-item-<?php echo $id;?>" 
									name="menu-item-<?php echo $id;?>[<?php echo $item_id;?>]" 
									size="30" 
									value="<?php echo htmlspecialchars(get_post_meta($item_id, '_menu_item_' . $id, true));?>" />
								<?php
								echo $desc;
								break;

							case 'textarea':
								echo $desc;
								?>
								<textarea id="edit-menu-item-<?php echo $id;?>-<?php echo $item_id;?>"
									 class="edit-menu-item-<?php echo $id;?>"
									 name="menu-item-<?php echo $id;?>[<?php echo $item_id;?>]" ><?php
										echo htmlspecialchars(get_post_meta($item_id, '_menu_item_' . $id, true));
									 ?></textarea>
								<?php
								break;

							case 'checkbox':
								?>
								<input type="checkbox" 
									id="edit-menu-item-<?php echo $id;?>-<?php echo $item_id;?>" 
									class="edit-menu-item-<?php echo $id;?>" 
									name="menu-item-<?php echo $id;?>[<?php echo $item_id;?>]" 
									<?php
										if (get_post_meta($item_id, '_menu_item_' . $id, true) == 'on')
											echo 'checked="checked"';
									?> />
								<?php
								echo $desc;
								break;
								
							case 'select':
								echo $desc;
								$_val = get_post_meta($item_id, '_menu_item_' . $id, true);
								if( empty($_val) ) $_val = $default;
								?>
								<select 
									id="edit-menu-item-<?php echo $id; ?>-<?php echo $item_id; ?>"
									class="edit-menu-item-<?php echo $id; ?>"
									name="menu-item-<?php echo $id;?>[<?php echo $item_id;?>]">
									<?php foreach( $ops as $opval => $optitle ): ?>
										<option value="<?php echo $opval; ?>" <?php if( $_val == $opval ) echo 'selected="selected"'; ?> ><?php echo $optitle; ?></option>
									<?php endforeach; ?>
								</select>
								<?php
								break;
								
							case 'sidebarselect':
								echo $desc;
								if( $settings->op( 'wpmega-sidebars' ) > 0){
									echo $uberMenu->sidebarSelect( $item_id );
								}
								else echo '<div><small class="clear">You currently have 0 widget areas set in your UberMenu options.</small></div>';
								break;
	
						}
 						?>
						
					</label>
				</p>
	<?php
	}


	function pluginActionLinks( $links, $file ) {
		if ( $file == 'ubermenu/ubermenu.php' ){
			$links[] = '<a href="' . admin_url( 'themes.php?page=uber-menu' ) . '">Settings</a>';
			$links[] = '<a href="http://bit.ly/eR0cvC" target="_blank">Support Manual</a>';
		}
		return $links;
	}
	
	
	
	
	
	
	
	
	
	
	function showThanks(){
	
		if( isset($_GET['cleared'] ) ){
			update_option( 'ubermenu-thanks', 2 );
		}
		if( isset($_GET['reset'] ) ){
			update_option( 'ubermenu-thanks', 1 );
		}
		//Pre //Done //Kill
		$status = get_option( 'ubermenu-thanks', 1 );
		
		if($status == 2) return;
		
		?>
		<div class="ubermenu-thanks">
			<h3>Thank you for installing UberMenu - WordPress Mega Menu Plugin, available exclusively from CodeCanyon!</h3>
			<p>This license entitles you to use UberMenu on a single WordPress instance (and one private development server).</p>
						
			<p>To get started, take a look at the <a href="http://bit.ly/eR0cvC" target="_blank">UberMenu Support Manual</a>, or watch the
				<a href="http://bit.ly/dQaVPJ" target="_blank">screencast</a>.  If you'd like to keep up with UberMenu updates, you can
				follow <a href="http://bit.ly/i1j6wb" target="_blank">@sevenspark</a> on Twitter or fan on <a href="http://www.facebook.com/sevensparklabs">Facebook</a>.</p>
				
		
			<div class="ops">
				<a class="button button-good" id="ubermenu-thanks-cleared" href="<?php echo $_SERVER["REQUEST_URI"].'&cleared=yup';?>">I purchased UberMenu from CodeCanyon</a>
				<a class="button button-bad" href="http://bit.ly/grEsDs">I need a license</a>
			</div>
					
			<div class="clear"></div>
		</div>
		<?php 
		
	}
	
	function showThanksCleared_callback(){
		
		if(isset($_GET['cleared'])){
			update_option('ubermenu-thanks', 2);
		}
			
		$data = array();
		
		$ajax_nonce = wp_create_nonce( "thanks-cleared" );
		
		$data['remove_nonce'] = $ajax_nonce;// $rmvBtn;
		$data['response'] = "<h3 style='display:inline-block'>Thank you for your purchase!</h3>";
		$this->JSONresponse($data);	
	}
	
	
	/*
	 * Generates a Preview Menu for display in the control panel
	 */
	function getPreview_callback(){
				
		$d = $_POST['data'];
		wp_parse_str($d, $data);
		
		$style_source = $data['wpmega-style'];
		
		$style = '';
		$link = '';

		
		// Generate CSS
		$settings = $this->getStyleSettings( $data );
		$gen = new StyleGenerator( UBERMENU_LESS );
		$style = $gen->generateCSS( $settings );
			
		$html = '<h3>Menu Preview <span class="spark-preview-close"></span></h3>';
		$html.= '<div class="ss-preview-note spark-infobox spark-infobox-warning">Note: The menu preview gives a general impression of colors and styles, but will not give an exact representation of '.
				'the menu in all cases - especially when using advanced methods like widgets and shortcodes.</div>';
		
		$html.= wp_nav_menu( array( 'menu' => $data['menu-preview'], 'megaMenu' => TRUE, 'echo' => false , 'preview' => true ) );
	
		$json 			= array();
		$json['content']= $this->escapeNewlines( $html );
		$json['style']	= $this->escapeNewlines( $style );
		$json['link'] 	= $link;
		
		$this->JSONresponse($json);
	}
	
	
	/*
	 * Style Generator - LESS PHP
	 */
	function getStyleSettings( $data ){
		
		$settings = array();
		
		
		//Menu Bar
		
		//Background Gradient
		$settings['menubarColorStart'] 		= 	$this->colorOrTransparent( $data['menu-bar-background'] );
		$settings['menubarColorEnd']		=	$this->colorOrTransparent( $data['menu-bar-background-color2'], $settings['menubarColorStart'] );
		$settings['menubarBorder']			=	empty( $data['menu-bar-border-color'] ) ? 'none' : '1px solid '.$this->colorOrTransparent( $data['menu-bar-border-color'] );
		$settings['menubarRadius']			=	$this->orDefault( $data['menu-bar-border-radius'], 0 ).'px';
				
		//Top Level
		$settings['topLevelFontSize'] 		= 	$this->orDefault( $data['top-level-item-font-size'] , '12px' );
		$settings['topLevelColor']			=	$this->colorOrTransparent( $data['top-level-item-font-color'] , '#333' );
		$settings['topLevelColorHover']		=	$this->colorOrTransparent( $data['top-level-item-font-color-hover'] , $settings['topLevelColor'] );
		$settings['topLevelTextShadow']		=	'0 -1px 1px '.$this->colorOrTransparent( $data['top-level-item-text-shadow'] );
		$settings['topLevelTextShadowHover']=	'0 -1px 1px '.$this->colorOrTransparent( $data['top-level-item-text-shadow-hover'] );
		$settings['topLevelTextTransform']	=	$this->orDefault( $data['top-level-text-transform'], 'none' );
		$settings['topLevelTextWeight']		=	$this->orDefault( $data['top-level-text-weight'] , 'normal' );
		
		$settings['currentColor']			=	$this->colorOrTransparent( $data['top-level-item-font-color-current'] , '#000' );
		$settings['topLevelPaddingX']		=	$this->orDefault( $data['top-level-item-padding-x'].'px' , '15px' );
		$settings['topLevelPaddingY']		=	$this->orDefault( $data['top-level-item-padding-y'].'px' , '12px' );
		$settings['topLevelDividerColor'] 	=	$this->colorOrTransparent( $data['top-level-item-border'] ); //, '#e0e0e0'
		$settings['topLevelGlowOpacity'] 	=	$this->orDefault( $data['top-level-item-glow-opacity'], '.9' , true );

		$settings['topLevelBackgroundHoverStart'] 	=  $this->colorOrTransparent( $data['top-level-item-background-hover'] );
		$settings['topLevelBackgroundHoverEnd'] 	= $this->colorOrTransparent( $data['top-level-item-background-hover-color2'], $settings['topLevelBackgroundHoverStart'] );
		$settings['topLevelGlowOpacityHover'] 		=	$this->orDefault( $data['top-level-item-glow-opacity-hover'], '.9' , true );

		
		//Submenu
		$settings['subMenuBorderColor']		=	$this->colorOrTransparent( $data['sub-menu-border'] );
		//$settings['subMenuMarginTop']		=	'0px'; //$settings['menubarBorder'] == 'none' ? '0' : '1px';
		$settings['submenuColorStart']		=	$this->colorOrTransparent( $data['sub-level-background'] );
		$settings['submenuColorEnd']		=	$this->colorOrTransparent( $data['sub-level-background-color2'] , $settings['submenuColorStart'] );
		
		$settings['subMenuColor']			=	$this->colorOrTransparent( $data['sub-level-item-font-color'] , '#000' );
		$settings['subMenuTextShadow']		=	'0px 1px 1px '.$this->colorOrTransparent( $data['sub-level-text-shadow'] );
		$settings['subMenuBoxShadow']		=	'1px 1px 1px '.$this->colorOrTransparent( $data['sub-level-box-shadow'] );
		$settings['subMenuMinColWidth']		=	$this->orDefault( $data['sub-level-column-width'].'px', '120px' );
		

		//Submenu Headers
		$settings['subHeaderColor']			=	$this->colorOrTransparent( $data['sub-level-header-font-color'] , '#777' );
		$settings['subHeaderSize']			=	$this->orDefault( $data['sub-level-header-font-size'] , '12px' );
		$settings['subHeaderWeight']		=	$this->orDefault( $data['sub-level-header-font-weight'] , 'normal' );
		$settings['subHeaderBorderBottom']	=	$data['sub-level-header-border-style'] == 'none' 
													? 'none' 
													: '1px '.$this->orDefault( $data['sub-level-header-border-style'], 'none' ).' '.$this->colorOrTransparent( $data['sub-level-header-border-color'] );
		$settings['subHeaderMarginBottom']	=	$settings['subHeaderBorderBottom'] == 'none' ? '.4em' : '.6em';
		
		//Submenu Links
		$settings['subLinkColor']			=	$this->colorOrTransparent( $data['sub-level-link-font-color'] , '#888' );
		$settings['subLinkColorHover']		=	$this->colorOrTransparent( $data['sub-level-link-font-color-hover'] , '#000' );
		$settings['subLinkSize']			=	$this->orDefault( $data['sub-level-link-font-size'] , '12px' );
		$settings['subLinkBackground']		=	$this->colorOrTransparent( $data['sub-level-link-background'] );
		$settings['subLinkBackgroundHover']	=	$this->colorOrTransparent( $data['sub-level-link-background-hover'] );
 		
		//Misc
		$settings['highlightColor']			=	$this->colorOrTransparent( $data['sub-level-highlight-color'] , $settings['subLinkColor'] );
		$settings['descriptionSize']		=	$this->orDefault( $data['menu-description-size'], '9px' );
		$settings['descriptionColor']		=	$this->colorOrTransparent( $data['menu-description-color'] , '#bbb' );
		$settings['descriptionTransform']	=	$this->orDefault( $data['description-transform'], 'none' );
 		
		//Arrows
		$settings['topLevelArrowColor']		= 	$this->colorOrTransparent( $data['top-level-arrow-color'] );
		$settings['flyoutArrowColor'] 		=	$this->colorOrTransparent( $data['sub-level-arrow-color'] );
		
		//Images
		$settings['imageWidth']				=	$this->orDefault( $data['wpmega-image-width'].'px' , '25px' );
		$settings['imageHeight']			=	$this->orDefault( $data['wpmega-image-height'].'px' , '25px' );
		
		// @imageWidth: 15px;
		// @imageHeight: 15px;

		
		return $settings;		
	}
	
	//utility helper
	function orDefault( $val , $default , $zeroValid = false ){	
		if( $zeroValid ){
			if( $val === 0 || $val === '0' ) return $val;
		}
		return empty( $val ) ? $default : $val;
	}
	//utility helper
	function colorOrTransparent( $val, $default = 'transparent' ){
		return empty( $val ) ? $default : '#'.$val;
	}
	
	function saveStyleGenerator( $saveOps ){
		
		$sheetWritten = 'off';
		
		//If we're using Style Generator Styles ('inline' is legacy)
		if( $saveOps['wpmega-style'] == 'inline' ){
		
			$styleSettings = $this->getStyleSettings( $saveOps );
			$gen = new StyleGenerator( UBERMENU_LESS );
			$style = $gen->generateCSS( $styleSettings );
			
			//Save the CSS to the DB
			update_option( UBERMENU_STYLES , $style );
			
			//Write File (if option set)
			if( $saveOps['save-style-gen-to-file'] == 'on' ){
				
				//Append a Comment to the beginning of the style /* Generated for ___ on ___ date - if you want to customize, copy to custom.css */
				//Set a message if file could not be written
				
				$site = get_bloginfo('wpurl');
				$date = date('F j, Y H:i:s');
				$prepend = "/*\n * Generated for $site on $date by UberMenu Style Generator \n * To customize this file, copy it to custom.css and have at it! \n */ \n\n";
				
				$sheetWritten = 'on';
				if( $gen->writeStylesheet( UBERMENU_GEN_SKIN , $prepend ) === false ){
					//write failed
					$sheetWritten = 'off';
					$this->settings->warning = 'The stylesheet '.UBERMENU_GEN_SKIN.' could not be written.  Styles will be loaded in the site &lt;head&gt; instead.';
				}	
			}
		}
		$this->settings->settings['use-gen-skin'] = $sheetWritten;
	}
	
	/*
	 * Escape newlines, tabs, and carriage returns
	 */
	function escapeNewlines($html){
		
		$html = str_replace("\n", '\\n', $html);
		$html = str_replace("\t", '\\t', $html);
		$html = str_replace("\r", '\\r', $html);
		
		return $html;
		
	}
	
	
	/*
	 * Prints a json response
	 */
	function JSONresponse($data){
			
		header('Content-Type: application/json; charset=UTF-8');	//Set the JSON header so that JSON will validate Client-Side
		
		echo '{ '.$this->buildJSON($data).' }';					//Send the response
			
		die();
	}

	/*
	 * Builds a json object from an array
	 */
	function buildJSON($ar){
		if($ar == null) return '';
		$txt = '';
		$count = count($ar);
		$k = 1;
		foreach($ar as $key=>$val){	
			$comma = ',';
			if($count == 1 || $count == $k) $comma = '';
			
			if(is_array($val)){
				$txt.= '"'.$key.'" : { ';
				$txt.= $this->buildJSON($val);	//recurse
				$txt.= ' }'.$comma."\n ";
			}
			else{
				$quotes = is_numeric($val) ? FALSE : TRUE;	
				$txt.= '"' . str_replace('-', '_', $key).'" : ';
				if($quotes) $txt.= '"';
				$txt.= str_replace('"','\'', $val);
				if($quotes) $txt.= '"';
				$txt.= $comma."\n";			
			}
			$k++;
		}
		return $txt;
	}
	
	
	
	
	
	/*
	 * Register a Style Preset Option
	 */
	function registerStylePreset($id, $name, $path, $top=false){
		$this->stylePresets[$id] = array(
			'name'	=>	$name,
			'path'	=>	$path,
			'top'	=>	$top,
		);
	}
	
	/*
	 * Get the registered Style Presets as an array of options
	 */
	function getStylePresetOps(){		
		$ops = array( 'none' => '&nbsp;');
		$tops = array();
		
		foreach( $this->stylePresets as $id => $s ){
			if( $s['top'] ){
				$tops[$id] = $s['name'];
			}
			else $ops[$id] = $s['name'];
		}
		
		if( !empty( $tops ) ){
			$ops = array_merge( $tops, $ops );
		}
		
		return $ops;	
	}
	
	/* Here is where the Presets are registered */
	function registerStylePresets(){
		
		$this->stylePresets = array();
		
		$this->registerStylePreset('grey-white', 		'Black and White', 				UBERMENU_ADMIN_PATH.'/styles/skins/blackwhite.css');
		$this->registerStylePreset('black-white-2',		'Black and White 2.0',			UBERMENU_ADMIN_PATH.'/styles/skins/blackwhite2.css');
		$this->registerStylePreset('vanilla', 			'Vanilla', 						UBERMENU_ADMIN_PATH.'/styles/skins/vanilla.css');
		$this->registerStylePreset('vanilla-bar', 		'Vanilla Bar', 					UBERMENU_ADMIN_PATH.'/styles/skins/vanilla_bar.css');
		$this->registerStylePreset('shiny-black', 		'Shiny Black', 					UBERMENU_ADMIN_PATH.'/styles/skins/shinyblack.css');
		$this->registerStylePreset('simple-green', 		'Simple Green',					UBERMENU_ADMIN_PATH.'/styles/skins/simplegreen.css');
		$this->registerStylePreset('earthy', 			'Earthy', 						UBERMENU_ADMIN_PATH.'/styles/skins/earthy.css');
		$this->registerStylePreset('silver-tabs', 		'Silver Tabs',					UBERMENU_ADMIN_PATH.'/styles/skins/silvertabs.css');
		$this->registerStylePreset('black-silver', 		'Black and Silver', 			UBERMENU_ADMIN_PATH.'/styles/skins/blacksilver.css');
		$this->registerStylePreset('blue-silver', 		'Blue and Silver', 				UBERMENU_ADMIN_PATH.'/styles/skins/bluesilver.css');
		$this->registerStylePreset('red-black', 		'Red and Black', 				UBERMENU_ADMIN_PATH.'/styles/skins/redblack.css');
		$this->registerStylePreset('orange', 			'Burnt Orange', 				UBERMENU_ADMIN_PATH.'/styles/skins/orange.css');
		$this->registerStylePreset('clean-white', 		'Clean White', 					UBERMENU_ADMIN_PATH.'/styles/skins/cleanwhite.css');
		$this->registerStylePreset('trans-black', 		'Transparent Black',			UBERMENU_ADMIN_PATH.'/styles/skins/trans_black.css');
		$this->registerStylePreset('trans-black-hov',	'Transparent Black Hover',		UBERMENU_ADMIN_PATH.'/styles/skins/trans_black_hover.css');
		$this->registerStylePreset('tt-silver', 		'Two Tone Silver & Black',		UBERMENU_ADMIN_PATH.'/styles/skins/twotone_silver_black.css');
		$this->registerStylePreset('tt-black', 			'Two Tone Black & Black',		UBERMENU_ADMIN_PATH.'/styles/skins/twotone_black_black.css');
		$this->registerStylePreset('tt-red', 			'Two Tone Red & Black',			UBERMENU_ADMIN_PATH.'/styles/skins/twotone_red_black.css');
		$this->registerStylePreset('tt-blue', 			'Two Tone Blue & Black',		UBERMENU_ADMIN_PATH.'/styles/skins/twotone_blue_black.css');
		$this->registerStylePreset('tt-green', 			'Two Tone Green & Black',		UBERMENU_ADMIN_PATH.'/styles/skins/twotone_green_black.css');
		$this->registerStylePreset('tt-purple', 		'Two Tone Purple & Black',		UBERMENU_ADMIN_PATH.'/styles/skins/twotone_purple_black.css');
		$this->registerStylePreset('tt-orange', 		'Two Tone Orange & Black',		UBERMENU_ADMIN_PATH.'/styles/skins/twotone_orange_black.css');
		$this->registerStylePreset('tt-silver-s',		'Two Tone Silver & Silver',		UBERMENU_ADMIN_PATH.'/styles/skins/twotone_silver_silver.css');
		
		$this->registerStylePreset('custom', 			'Custom - (Legacy Use)', 		UBERMENU_ADMIN_PATH.'/styles/custom.css');

	}
	
}

global $uberMenu;
$uberMenu = new UberMenu();

/*
 * For backwards compatibility
 */
function uberMenu_easyIntegrate(){
	UberMenu::easyIntegrate();	
}




/*
 * Get the registered Style Presets as an array of options - for use in callbacks
 */
function ubermenu_getStylePresetOps(){
	global $uberMenu;	
	return $uberMenu->getStylePresetOps();
}

/*
 * Register a style preset with UberMenu - to be called by outside plugins in the uberMenu_register_styles action hook
 */
function ubermenu_registerStylePreset($id, $name, $path){
	global $uberMenu;
	$uberMenu->registerStylePreset( $id, $name, $path, true );
}


/*
 * Example of how to register an UberMenu preset externally
 * 
	function uberMenu_register_styles_example(){
		ubermenu_registerStylePreset( 'my-preset', 'SupaSlammin', 'path/to/stylesheet.css' );
	} 
	add_action( 'uberMenu_register_styles', 'uberMenu_register_styles_example' , 10 , 0 );
 */

