<?php 

/*
Plugin Name: Responsive Select Menu
Plugin URI: http://wpmegamenu.com/responsive-select-menu
Description: Turn your menu into a select box at small viewport sizes
Version: 1.4
Author: Chris Mavricos, SevenSpark
Author URI: http://sevenspark.com
Copyright 2011-2012  Chris Mavricos, SevenSpark http://sevenspark.com (email : chris@sevenspark.com) 
*/

define( 'RESPONSIVE_SELECT_MENU_VERSION', '1.4' );
define( 'RESPONSIVE_SELECT_MENU_SETTINGS', 'responsive-select-menu' );

require_once( 'sparkoptions/SparkOptions.class.php' );		//SevenSpark Options Panel

class ResponsiveMenuSelect{

	private $enabled;
	private $enabled_determined;
		
	function __construct(){

		$this->settings = $this->optionsMenu();
		$this->enabled_determined = false;
		
		if( is_admin() ){
			
		}
		else{
			add_action( 'plugins_loaded' , array( $this , 'init' ) );
		}
	}
	
	function init(){

		$this->loadAssets();
		
		//Filters
		add_filter( 'wp_nav_menu_args' , array( $this , 'responsiveSelectAddFilter' ), 2100 );  	//filters arguments passed to wp_nav_menu
		
		add_filter( 'wp_nav_menu_args' , array( $this , 'responsiveSelectFilter' ), 2200 );			//second call, to print select menu
		
	}

	/**
	 * Determine whether we should load the responsive select on these pages 
	 * and cache the result.
	 */
	function isEnabled(){

		if( $this->enabled_determined ) return $this->enabled;

		$this->enabled_determined = true;
		$this->enabled = false;

		if( !$this->settings->op( 'display_only' ) ){
			$this->enabled = true;
		}
		else{
			$list = $this->settings->op( 'display_only' );
			$list = str_replace( ' ', '', $list );
			$ids = explode( ',' , $list );

			global $post;
			if( $post && in_array( $post->ID , $ids ) ) $this->enabled = true;
			else $this->enabled = false;
		}
		return $this->enabled;
	}

	/**
	 * Determine whether this particular menu location should be activated
	 */
	function isActivated( $args ){

		//Activate All?
		if( $this->settings->op( 'activate_theme_locations_all' ) ){
			return true;
		}

		//Activate this theme_location specifically?
		if( isset( $args['theme_location'] ) ){
			$location = $args['theme_location'];
			$active_theme_locations = $this->settings->op( 'active_theme_locations' );

			if( is_array( $active_theme_locations ) && in_array( $location, $active_theme_locations ) ){
				return true;
			}
		}
		return false;
	}


	function loadAssets(){
		
		if( !is_admin() ){
			add_action( 'wp_print_styles' , array( $this , 'loadCSS' ) );		
			add_action( 'wp_head', array( $this  , 'insertHeaderCode' ), 110 );			
		}
				
	}

	function loadCSS(){
		if( $this->isEnabled() ) wp_enqueue_script( 'jquery' );	
	}
	
	function insertHeaderCode(){
		if( $this->isEnabled() ){
		?>
		
<!-- Responsive Select CSS 
================================================================ -->
<style type="text/css" id="responsive-select-css">
.responsiveSelectContainer select.responsiveMenuSelect, select.responsiveMenuSelect{
	display:none;
}

@media (max-width: <?php echo $this->settings->op( 'max-menu-width' ); ?>px) {
	.responsiveSelectContainer{
		border:none !important;
		background:none !important;
		box-shadow:none !important;
	}
	.responsiveSelectContainer ul, ul.responsiveSelectFullMenu, #megaMenu ul.megaMenu.responsiveSelectFullMenu{
		display: none !important;
	}
	.responsiveSelectContainer select.responsiveMenuSelect, select.responsiveMenuSelect { 
		display: inline-block; 
		width:100%;
	}
}	
</style>
<!-- end Responsive Select CSS -->

<!-- Responsive Select JS
================================================================ -->
<script type="text/javascript">
jQuery(document).ready( function($){
	$( '.responsiveMenuSelect' ).change(function() {
		var loc = $(this).find( 'option:selected' ).val();
		if( loc != '' && loc != '#' ) window.location = loc;
	});
});
</script>
<!-- end Responsive Select JS -->
		
<?php
		}
	}

	
	function responsiveSelectAddFilter( $args ){

		if( $this->isEnabled() && $this->isActivated( $args ) ){
		
			//Don't add it twice (when it gets called again by selectNavMenu() )
			if( isset( $args['responsiveMenuSelect'] ) && $args['responsiveMenuSelect'] == true ) {
				return $args;
			}
			
			$selectNav = $this->selectNavMenu( $args );
			
			$args['container_class'].= ' responsiveSelectContainer';	
			$args['menu_class'].= ' responsiveSelectFullMenu';

			//This line would add a container if it doesn't exist, but has the potential to break certain theme menus
			//if( $args['container'] != 'nav' ) $args['container'] = 'div';	//make sure there's a container to add class to
			
			$args['items_wrap']	= '<ul id="%1$s" class="%2$s">%3$s</ul>'.$selectNav;

		}

		return $args;

	}
	
	function selectNavMenu( $args ){
		
		$args['responsiveMenuSelect'] = true;
		
		$select = wp_nav_menu( $args );
		
		return $select;
	}
	
	function responsiveSelectFilter( $args ){

		if( $this->isEnabled() ){

			if( !isset( $args['responsiveMenuSelect'] ) ) return $args;

			$itemName = $this->settings->op( 'first_item' );
			$selected = $this->settings->op( 'current_selected' ) ? '' : 'selected="selected"';
			$firstOp = '<option value="" '.$selected.'>'.$itemName.'</option>';

			$args['container'] = false;
			$args['menu_class'] = 'responsiveMenuSelect';
			$args['menu_id'] = '';
			$args['walker'] = new ResponsiveSelectWalker();
			$args['echo'] = false;
			$args['items_wrap'] = '<select class="%2$s">'.$firstOp.'%3$s</select>';
			
			$args['depth'] = $this->settings->op( 'max-menu-depth' );

		}
		
		return $args;
		
	}

	/*
	 * Create the UberMenu SparkOptions Panel and Settings object
	 */
	function optionsMenu(){
	
		$sparkOps = new ResponsiveMenuSelectOptions( 
			RESPONSIVE_SELECT_MENU_SETTINGS, 
			
			//Menu Page
			array(
				'parent_slug' 	=> 'themes.php',
				'page_title'	=> 'Responsive Select Menu',
				'menu_title'	=> 'Responsive Select',
				'menu_slug'		=> 'responsive-select',
			),
			
			//Links
			array()
			
		);
		
		
		
		/*
		 * Basic Config Panel
		 */
		$basic = 'basic-config';
		$sparkOps->registerPanel( $basic, 'Basic Configuration' );
		
		$sparkOps->addHidden( $basic , 'current-panel-id' , $basic );


		$sparkOps->addTextInput( $basic,
					'max-menu-width',
					'Maximum Menu Width',
					'Show the select box when the viewport is less than this width',
					960,
					'spark-minitext',
					'px'
					);

		$sparkOps->addTextInput( $basic,
					'max-menu-depth',
					'Menu Depth Limit',
					'The maximum number of levels of menu items to include in the select menu.  Set to 0 for no limit.',
					0,
					'spark-minitext',
					''
					);

		$sparkOps->addTextInput( $basic,
					'spacer',
					'Sub Item Spacer',
					'The character to use to indent sub items.',
					'&ndash; ',
					'spark-minitext',
					''
					);

		$sparkOps->addCheckbox( $basic,
					'exclude-hashes',
					'Exclude Items Without Links',
					'Exclude any items where the URL is set to "#" or blank',
					'on'
					);

		$sparkOps->addTextInput( $basic,
					'first_item',
					'First Item Name',
					'Text to display for the first "dummy" item.',
					'&rArr; Navigate',
					'',
					''
					);

		$sparkOps->addCheckbox( $basic,
					'current_selected',
					'Show currently selected item',
					'Enable to show the currently selected item, rather than the first "dummy" item, when the page loads.',
					'off'
					);

		$sparkOps->addSubHeader( $basic, 
					'activate_theme_locations_header',
					'Activate Theme Locations'
					);

		$sparkOps->addCheckbox( $basic,
					'activate_theme_locations_all',
					'Activate All Theme Locations',
					'Apply the responsive select menu to all menus',
					'on'
					);

		$sparkOps->addChecklist( $basic,
					'active_theme_locations', 
					'Selectively Activate Theme Locations',
					'Disable the above and activate only the theme locations you want.  These theme locations correspond to the Theme Locations Meta Box in Appearance > Menus',
					'get_registered_nav_menus'
					);



		$advanced = 'advanced-config';
		$sparkOps->registerPanel( $advanced, 'Advanced Settings' );

		$sparkOps->addTextInput( $advanced,
					'display_only',
					'Enable only on',
					'IDs of pages to enable responsive select menu on.  Other pages will use the standard theme menu.  Enter as a comma-separated list.',
					'',
					'',
					''
					);



		$uber = 'uber-config';
		$sparkOps->registerPanel( $uber, 'UberMenu Configuration' );

		$sparkOps->addInfobox( $uber,
					'uber-info',
					'UberMenu is NOT required in order to use this plugin',
					
					'<p class="cf">UberMenu is a user-friendly, highly customizable responsive Mega Menu WordPress plugin. '.
					'It works out of the box with the WordPress 3 Menu System, making it simple to get started '.
					'but powerful enough to create highly customized and creative mega menu configurations.<br/><br/>'						.
					'If you are not using UberMenu, you can ignore these settings.  '.
					'<a href="http://wpmegamenu.com" class="button save-button" target="_blank">More about UberMenu &rarr;</a></p>'
					
				);

		$sparkOps->addCheckbox( $uber,
					'uber-enabled',
					'Activate UberMenu Options',
					'If you are using UberMenu, enable this setting to make use of the UberMenu-specific options.',
					'off'
					);

		$sparkOps->addCheckbox( $uber,
					'uber-exclude-nonlinks',
					'Exclude Non-links',
					'Exclude any items where the link is disabled',
					'on'
					);

		$sparkOps->addCheckbox( $uber,
					'uber-exclude-notext',
					'Exclude No-text',
					'Exclude any items where the text is disabled',
					'on'
					);

		$sparkOps->addCheckbox( $uber,
					'uber-exclude-sidebar',
					'Exclude Widgets',
					'Exclude any items with attached widgets',
					'on'
					);

		$sparkOps->addCheckbox( $uber,
					'uber-exclude-content-overrides',
					'Exclude Content Overrides',
					'Exclude any items with content overrides',
					'on'
					);

		$ss = 'ss-config';
		$sparkOps->registerPanel( $ss, 'More from SevenSpark' );

		$sparkOps->addCustom( $ss, 'ss_products' , 'ResponsiveMenuSelect::sevenspark_showcase' );


		return $sparkOps;
	}


	function getSettings(){
		return $this->settings;
	}

	static function sevenspark_showcase(){
	
		$html = '
			<div class="social_media">
				<a target="_blank" href="https://twitter.com/#!/sevenspark" class="ss-twitter"></a> 
				<a target="_blank" href="http://www.facebook.com/sevensparklabs" class="ss-facebook"></a> 
				<a target="_blank" href="http://dribbble.com/sevenspark" class="ss-dribbble"></a>
			</div>

			<div class="ss-infobox spark-infobox">
				Like this plugin?  Check out even more from SevenSpark
			</div>

			<div class="cf">
				<h4>Contact Form 7 - Dynamic Text Extension</h4>
				<p>Contact Form 7 DTX allows you to pre-populate your Contact Form 7 fields with dynamic values</p>

				<a href="http://wordpress.org/extend/plugins/contact-form-7-dynamic-text-extension/" target="_blank" class="button save-button">Download 
				CF7 DTX for Free &rarr;</a>
			</div>


			<div class="cf">
				<h4>UberMenu - Responsive WordPress Mega Menu Plugin</h4>

				<a href="http://wpmegamenu.com"><img src="http://2.s3.envato.com/files/43473217/UberMenu_packaging_main_2.2.png" alt="UberMenu" /></a>

				<p>UberMenu is a user-friendly, highly customizable responsive Mega Menu WordPress plugin. 
				It works out of the box with the WordPress 3 Menu System, making it simple to get started 
				but powerful enough to create highly customized and creative mega menu configurations.</p>

				<a href="http://wpmegamenu.com" class="button save-button" target="_blank">Check out the UberMenu demo &rarr;</a>

			</div>

			<div class="cf">
				<h4>UberMenu - Sticky Menu Extension</h4>

				<a href="http://wpmegamenu.com/sticky"><img src="http://2.s3.envato.com/files/46737754/UberMenuSticky_packaging_main_1.0.png" alt="UberMenu Sticky Menu" /></a>

				<p>Turn your UberMenu into a Sticky Menu as your users scroll.</p>

				<a href="http://wpmegamenu.com/sticky" class="button save-button" target="_blank">Check out the UberMenu Sticky demo &rarr;</a>

			</div>

			<div class="cf">
				<h4>UberMenu - Conditionals Extension</h4>

				<a href="http://wpmegamenu.com/conditionals"><img src="http://0.s3.envato.com/files/35005553/UberMenu_Conditionals_packaging_main.png" alt="UberMenu Conditionals" /></a>

				<p>Display or hide your menu items based on preset conditions.</p>

				<a href="http://labs.sevenspark.com/UberMenuConditionals" class="button save-button" target="_blank">Check out the UberMenu Conditionals demo &rarr;</a>

			</div>

			<div class="cf">
				<h4>Agility - Responsive HTML5 WordPress Theme</h4>

				<img src="http://1.s3.envato.com/files/26983727/01_Agility_Responsive_WordPress_theme.__large_preview.png" alt="Agility" />

				<a href="http://agility.sevenspark.com" class="button save-button" target="_blank">View the demo &rarr;</a>
			</div>

			<div class="cf">
				<h4>WordPress Menu Management Enhancer</h4>

				<img src="http://3.s3.envato.com/files/6124310/MenuManager_packaging_main.jpg" alt="Agility" />

				<a href="http://codecanyon.net/item/menu-management-enhancer-for-wordpress/529353?ref=sevenspark" class="button save-button" target="_blank">View the demo &rarr;</a>
			</div>

		';

		return $html;

	}
	

}

$responsiveMenuSelect = new ResponsiveMenuSelect();





class ResponsiveSelectWalker extends Walker_Nav_Menu{

	private $index = 0;
	
	function start_lvl( &$output, $depth ) {
		$indent = str_repeat( "\t", $depth );
		//$output .= "\n$indent<ul class=\"sub-menu sub-menu-".($depth+1)."\">\n";
	}
	
	function end_lvl(&$output, $depth) {
		$indent = str_repeat("\t", $depth);
		//$output .= "$indent</ul>\n";
	}
	
	function start_el( &$output, $item, $depth, $args ){
		
		global $responsiveMenuSelect;
		global $wp_query;
		$indent = ( $depth ) ? str_repeat( "\t", $depth ) : '';
		$dashes = ( $depth ) ? str_repeat( $responsiveMenuSelect->getSettings()->op( 'spacer' ), $depth ) : '';	//"&ndash; "

		$class_names = $value = '';

		$classes = empty( $item->classes ) ? array() : (array) $item->classes;
		$classes[] = 'menu-item-' . $item->ID;

		$class_names = join( ' ', apply_filters( 'nav_menu_css_class', array_filter( $classes ), $item, $args ) );
		$class_names = ' class="' . esc_attr( $class_names ) . '"';

		$id = apply_filters( 'nav_menu_item_id', 'menu-item-'. $item->ID, $item, $args );
		$id = strlen( $id ) ? ' id="' . esc_attr( $id ) . '"' : '';

		if( ( $item->url == '#' || $item->url == '' ) && $responsiveMenuSelect->getSettings()->op( 'exclude-hashes' ) ){
			return;
		}

		//IF UBERMENU
		if( $responsiveMenuSelect->getSettings()->op( 'uber-enabled' ) ){

			global $uberMenu;
			$settings = $uberMenu->getSettings();

			//Test override settings
			$override = get_post_meta( $item->ID, '_menu_item_shortcode', true);
			$overrideOn = /*$depth > 0  && */ $settings->op( 'wpmega-shortcodes' ) && !empty( $override ) ? true : false;
			
			//Test sidebar settings
			$sidebar = get_post_meta( $item->ID, '_menu_item_sidebars', true);
			$sidebarOn = ( $settings->op( 'wpmega-top-level-widgets' ) || $depth > 0 ) && $settings->op( 'wpmega-sidebars' ) && !empty( $sidebar ) ? true : false;

			$notext = get_post_meta( $item->ID, '_menu_item_notext', true ) == 'on' || $item->title == UBERMENU_NOTEXT ? true : false;
			$nolink = get_post_meta( $item->ID, '_menu_item_nolink', true ) == 'on' ? true : false;
				
			if( $nolink && $responsiveMenuSelect->getSettings()->op( 'uber-exclude-nonlinks' ) ){
				return;
			}
			if( $notext && $responsiveMenuSelect->getSettings()->op( 'uber-exclude-notext' ) ){
				return;
			}
			if( $sidebarOn && $responsiveMenuSelect->getSettings()->op( 'uber-exclude-sidebar' ) ){
				return;
			}
			if( $overrideOn && $responsiveMenuSelect->getSettings()->op( 'uber-exclude-content-overrides' ) ){
				return;
			}					

		}

		//$attributes = ! empty( $item->url )        ? ' value="'   . esc_attr( $item->url        ) .'"' : '';
		$attributes = ' value="'   . esc_attr( $item->url        ) .'"';
		
		if( $responsiveMenuSelect->getSettings()->op( 'current_selected' ) && strpos( $class_names , 'current-menu-item' ) > 0 ){
			$attributes.= ' selected="selected"';
		}
		
		$output .= $indent . '<option ' . $id . $attributes . '>';

		$item_output = $args->before;
		$item_output .= $dashes . $args->link_before . apply_filters( 'the_title', $item->title, $item->ID ) . $args->link_after;
		$item_output .= $args->after;

		$output.= $item_output;

		$output .= "</option>\n";
	}
	
	function end_el(&$output, $item, $depth) {
		return;		
	}
}
