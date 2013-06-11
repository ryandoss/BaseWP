<?php
if(preg_match('#' . basename(__FILE__) . '#', $_SERVER['PHP_SELF'])) { die('You are not allowed to call this page directly.'); }

/*
Plugin Name: Business Directory Plugin
Plugin URI: http://www.businessdirectoryplugin.com
Description: Provides the ability to maintain a free or paid business directory on your WordPress powered site.
Version: 3.0.2
Author: D. Rodenbaugh
Author URI: http://businessdirectoryplugin.com
License: GPLv2 or any later version
*/

/*  Copyright 2009-2013, Skyline Consulting and D. Rodenbaugh

    This program is free software; you can redistribute it and/or modify
    it under the terms of the GNU General Public License, version 2 or later, as
    published by the Free Software Foundation.

    This program is distributed in the hope that it will be useful,
    but WITHOUT ANY WARRANTY; without even the implied warranty of
    MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
    GNU General Public License for more details.

    You should have received a copy of the GNU General Public License
    along with this program; if not, write to the Free Software
    Foundation, Inc., 51 Franklin St, Fifth Floor, Boston, MA  02110-1301  USA
    reCAPTCHA used with permission of Mike Crawford & Ben Maurer, http://recaptcha.net
*/

define ( 'WPBDP_VERSION', '3.0.2' );

define( 'WPBDP_PATH', plugin_dir_path( __FILE__ ) );
define( 'WPBDP_URL', plugins_url( '/', __FILE__ ) );
define( 'WPBDP_TEMPLATES_PATH', WPBDP_PATH . 'templates' );

define( 'WPBDP_POST_TYPE', 'wpbdp_listing' );
define( 'WPBDP_CATEGORY_TAX', 'wpbdm-category' );
define( 'WPBDP_TAGS_TAX', 'wpbdm-tags' );

require_once( WPBDP_PATH . 'api/api.php' );
require_once( WPBDP_PATH . 'deprecated/deprecated.php' );
include_once( WPBDP_PATH . 'gateways-googlecheckout.php' );
require_once( WPBDP_PATH . 'utils.php' );
require_once( WPBDP_PATH . 'admin/wpbdp-admin.class.php' );
require_once( WPBDP_PATH . 'api/wpbdp-settings.class.php' );
require_once( WPBDP_PATH . 'api/form-fields.php' );
require_once( WPBDP_PATH . 'api/payment.php' );
require_once( WPBDP_PATH . 'api/listings.php' );
require_once( WPBDP_PATH . 'api/templates-ui.php' );
require_once( WPBDP_PATH . 'installer.php' );
require_once( WPBDP_PATH . 'views.php' );
require_once( WPBDP_PATH . 'widgets.php' );


global $wpbdp;


class WPBDP_Plugin {

    public function __construct() {
        register_activation_hook(__FILE__, array($this, 'plugin_activation'));
        register_deactivation_hook(__FILE__, array($this, 'plugin_deactivation'));      
    }

    public function _listing_expirations() {
        global $wpdb;

        wpbdp_log('Running expirations hook.');

        if ( !wpbdp_get_option( 'listing-renewal' ) )
            return;

        $current_date = current_time('mysql');

        $posts_to_check = $wpdb->get_results($wpdb->prepare(
                "SELECT * FROM {$wpdb->prefix}wpbdp_listing_fees WHERE expires_on IS NOT NULL AND expires_on < %s AND email_sent = %d LIMIT 100", $current_date, 0) );

        foreach ( $posts_to_check as $p ) {
            $listing = get_post( $p->listing_id );

            if ( !$listing || $listing->post_type != wpbdp_post_type() ) {
                continue;
            }

            if ( !has_term( intval( $p->category_id ), wpbdp_categories_taxonomy(), $p->listing_id ) ) {
                $wpdb->query( $wpdb->prepare( "DELETE FROM {$wpdb->prefix}wpbdp_listing_fees WHERE id = %d", $p->id ) );
                continue;
            }

            // Remove expired category from post
            $listing_terms = wp_get_post_terms( $p->listing_id, wpbdp_categories_taxonomy(), array('fields' => 'ids') );
            wpbdp_array_remove_value( $listing_terms, $p->category_id );
            wp_set_post_terms( $p->listing_id, $listing_terms, wpbdp_categories_taxonomy() );

            if ( !$listing_terms ) {
                $wpdb->query( $wpdb->prepare( "UPDATE {$wpdb->posts} SET post_status = %s WHERE ID = %d", wpbdp_get_option( 'deleted-status' ), $p->listing_id ) );
            }

            // Send email to listing owner
            $headers = sprintf("MIME-Version: 1.0\n" .
                               "From: %s <%s>\n" . 
                               "Reply-To: %s\n" . 
                               "Content-Type: text/html; charset=\"%s\"\n",
                                get_option('blogname'),
                                get_option('admin_email'),
                                get_option('admin_email'),
                                get_option('blog_charset'));
            $subject = sprintf('[%s] %s', get_option('blogname'), wp_kses($listing->post_title, array()));
            
            $message = nl2br(wpbdp_get_option('listing-renewal-message'));
            $message = str_replace('[listing]', esc_attr($listing->post_title), $message);
            $message = str_replace('[category]', get_term($p->category_id, WPBDP_CATEGORY_TAX )->name, $message);
            $message = str_replace('[expiration]', date_i18n(get_option('date_format') . ' ' . get_option('time_format'), strtotime($p->expires_on)), $message);
            $message = str_replace('[link]', sprintf('<a href="%1$s">%1$s</a>', add_query_arg(array('action' => 'renewlisting', 'renewal_id' => $p->id), wpbdp_get_page_link('main')) ), $message);

            wpbdp_log(sprintf('Listing "%s" expired on category %s. Email sent.', $listing->post_title, $p->category_id));
            @wp_mail(wpbusdirman_get_the_business_email($listing->ID), $subject, $message, $headers ); // TODO: move this to use WPBDP_Email

            $wpdb->update( "{$wpdb->prefix}wpbdp_listing_fees", array('email_sent' => 1), array('id' => $p->id) );
        }
    }

    public function _pre_get_posts(&$query) {
        global $wpdb;

        if (!$query->is_admin && $query->is_archive && $query->get(WPBDP_CATEGORY_TAX)) {
            // category page query
            $query->set('post_status', 'publish');
            $query->set('post_type', WPBDP_POST_TYPE);
            $query->set('posts_per_page', 0);
            $query->set('orderby', wpbdp_get_option('listings-order-by', 'date'));
            $query->set('order', wpbdp_get_option('listings-sort', 'ASC'));
        }
    }

    public function _posts_fields($fields, $query) {
        global $wpdb;

        if (!is_admin() && isset($query->query_vars['post_type']) && $query->query_vars['post_type'] == WPBDP_POST_TYPE) {
            $is_sticky_query = $wpdb->prepare("(SELECT 1 FROM {$wpdb->postmeta} WHERE {$wpdb->postmeta}.post_id = {$wpdb->posts}.ID AND {$wpdb->postmeta}.meta_key = %s AND {$wpdb->postmeta}.meta_value = %s) AS wpbdp_is_sticky",
                                               '_wpbdp[sticky]', 'sticky');

            $fields = $fields . ', ' . $is_sticky_query;
            $fields = apply_filters('wpbdp_query_fields', $fields);
        }

        return $fields;
    }

    public function _posts_orderby($orderby, $query) {
        if (!is_admin() && isset($query->query_vars['post_type']) && $query->query_vars['post_type'] == WPBDP_POST_TYPE) {
            $wpbdp_orderby = apply_filters('wpbdp_query_orderby', '');
            $orderby = 'wpbdp_is_sticky DESC' . $wpbdp_orderby . ', ' . $orderby;
        }

        return $orderby;
    }

    private function get_rewrite_rules() {
        global $wpdb;

        $rules = array();

        if ($page_id = wpbdp_get_page_id('main')) {
            global $wp_rewrite;

            $page_link = wpbdp_get_page_link('main');
            $rewrite_base = str_replace('index.php/', '', rtrim(str_replace(home_url() . '/', '', $page_link), '/'));
            
            $rules['(' . $rewrite_base . ')/' . $wp_rewrite->pagination_base . '/?([0-9]{1,})/?$'] = 'index.php?page_id=' . $page_id . '&paged=$matches[2]';
            $rules['(' . $rewrite_base . ')/([0-9]{1,})/?(.*)/?$'] = 'index.php?page_id=' . $page_id . '&id=$matches[2]';
            
            $rules['(' . $rewrite_base . ')/' . wpbdp_get_option('permalinks-category-slug') . '/(.+?)/' . $wp_rewrite->pagination_base . '/?([0-9]{1,})/?$'] = 'index.php?page_id=' . $page_id . '&category=$matches[2]&paged=$matches[3]';
            $rules['(' . $rewrite_base . ')/' . wpbdp_get_option('permalinks-category-slug') . '/(.+?)/?$'] = 'index.php?page_id=' . $page_id . '&category=$matches[2]';
            $rules['(' . $rewrite_base . ')/' . wpbdp_get_option('permalinks-tags-slug') . '/(.+?)/' . $wp_rewrite->pagination_base . '/?([0-9]{1,})/?$'] = 'index.php?page_id=' . $page_id . '&tag=$matches[2]&paged=$matches[3]';
            $rules['(' . $rewrite_base . ')/' . wpbdp_get_option('permalinks-tags-slug') . '/(.+?)$'] = 'index.php?page_id=' . $page_id . '&tag=$matches[2]';
        }

        return $rules;
    }

    public function _rewrite_rules($rules) {
        $newrules = $this->get_rewrite_rules();
        return $newrules + $rules;
    }

    public function _wp_loaded() {
        if ($rules = get_option( 'rewrite_rules' )) {
            // wpbdp_debug_e($this->get_rewrite_rules());
            foreach ($this->get_rewrite_rules() as $k => $v) {
                if (!isset($rules[$k]) || $rules[$k] != $v) {
                    global $wp_rewrite;
                    $wp_rewrite->flush_rules();
                    return;
                }
            }
        }
    }

    public function _query_vars($vars) {
        array_push($vars, 'id');
        array_push($vars, 'listing');
        array_push($vars, 'category_id'); // TODO: are we really using this var?
        array_push($vars, 'category');
        array_push($vars, 'action'); // TODO: are we really using this var?

        return $vars;
    }

    /**
     * Workaround for issue WP bug #16373.
     * See http://wordpress.stackexchange.com/questions/51530/rewrite-rules-problem-when-rule-includes-homepage-slug.
     */
    public function _redirect_canonical( $redirect_url, $requested_url ) {
        global $wp_query;

        if ( $main_page_id = wpbdp_get_page_id( 'main' ) ) {
            if ( is_page() && !is_feed() && isset( $wp_query->queried_object ) &&
                 get_option( 'show_on_front' ) == 'page' &&
                 get_option( 'page_on_front' ) == $wp_query->queried_object->ID ) {
                return $requested_url;
            }
        }

        return $redirect_url;
    }

    public function _template_redirect() {
        global $wp_query;

        if ( is_feed() )
            return;

        // handle some deprecated stuff
        if ( is_search() && isset( $_REQUEST['post_type'] ) && $_REQUEST['post_type'] == wpbdp_post_type() ) {
            $url = add_query_arg( array( 'action' => 'search',
                                         'dosrch' => 1,
                                         'q' => wpbdp_getv( $_REQUEST, 's', '' ) ), wpbdp_get_page_link( 'main' ) );
            wp_redirect( $url ); exit;
        }

        if ( (get_query_var('taxonomy') == WPBDP_CATEGORY_TAX) && (_wpbdp_template_mode('category') == 'page') ) {
            wp_redirect( add_query_arg('category', get_query_var('term'), wpbdp_get_page_link('main')) ); // XXX
            exit;
        }

        if ( (get_query_var('taxonomy') == WPBDP_TAGS_TAX) && (_wpbdp_template_mode('category') == 'page') ) {
            wp_redirect( add_query_arg('tag', get_query_var('term'), wpbdp_get_page_link('main')) ); // XXX
            exit;
        }

        if ( is_single() && (get_query_var('post_type') == WPBDP_POST_TYPE) && (_wpbdp_template_mode('single') == 'page') ) {
            if (get_query_var('name')) {
                wp_redirect( add_query_arg('listing', get_query_var('name'), wpbdp_get_page_link('main')) ); // XXX
            } else {
                wp_redirect( add_query_arg('id', get_query_var('p'), wpbdp_get_page_link('main')) ); // XXX
            }
            
            exit;
        }

        if ( (get_query_var('p') == wpbdp_get_page_id('main')) && (get_query_var('id')) ) {
            $id = get_query_var('id');
            $post = get_post($id);

            if (!$post || $post->post_type != wpbdp_post_type() || $post->post_status != 'publish') {
                status_header(404);
                nocache_headers();
                include( get_404_template() );
                exit;
            }
        }
        
    }

    public function _plugin_initialization() {
        $this->_config['main_page'] = wpbdp_get_page_id('main');
    }

    public function plugin_activation() {
        add_action('init', array($this, 'flush_rules'), 11);
    }

    public function plugin_deactivation() {
        wp_clear_scheduled_hook('wpbdp_listings_expiration_check');
    }

    public function flush_rules() {
        if (function_exists('flush_rewrite_rules'))
            flush_rewrite_rules(false);
    }

    public function init() {
        // add_option('wpbdp-debug-on', true);
        if (get_option('wpbdp-debug-on', false)) $this->debug_on();

        wpbdp_log('WPBDP_Plugin::init()');

        $this->settings = new WPBDP_Settings();
        $this->formfields = WPBDP_FormFields::instance();
        $this->fees = new WPBDP_FeesAPI();
        $this->payments = new WPBDP_PaymentsAPI();
        $this->listings = new WPBDP_ListingsAPI();

        $this->_config = array('main_page' => 0); // some stuff we can know from the start and cache

        if (is_admin()) {
            $this->admin = new WPBDP_Admin();
        }
        
        $this->controller = new WPBDP_DirectoryController();

        add_action('init', array($this, 'install_or_update_plugin'), 1);
        add_action('init', array($this, '_register_post_type'), 0);

        add_action('init', array($this, '_plugin_initialization'));
        add_action('init', array($this, '_session_start'));
        add_action('init', array($this, '_register_image_sizes'));

        // add_action('init', create_function('', 'do_action("wpbdp_listings_expiration_check");'), 20); // XXX For testing only

        add_filter('posts_request', create_function('$x', 'wpbdp_debug($x); return $x;')); // used for debugging

        add_filter('rewrite_rules_array', array($this, '_rewrite_rules'));
        add_filter('query_vars', array($this, '_query_vars'));
        add_filter( 'redirect_canonical', array( $this, '_redirect_canonical' ), 10, 2 );
        add_action('template_redirect', array($this, '_template_redirect'));
        add_action('wp_loaded', array($this, '_wp_loaded'));

        add_action('pre_get_posts', array($this, '_pre_get_posts'));
        add_action('posts_fields', array($this, '_posts_fields'), 10, 2);
        add_action('posts_orderby', array($this, '_posts_orderby'), 10, 2);

        add_filter('comments_template', array($this, '_comments_template'));
        add_filter('taxonomy_template', array($this, '_category_template'));
        add_filter('single_template', array($this, '_single_template'));

        add_action( 'wp', array( $this, '_meta_setup' ) );
        add_action( 'wp', array( $this, '_jetpack_compat' ), 11, 1 );
        add_action( 'wp_head', array( $this, '_handle_broken_plugin_filters' ), 99 );

        add_filter( 'wp_title', array( $this, '_meta_title' ), 10, 3 );        

        add_action( 'wp_head', array( $this, '_rss_feed' ) );
        add_action('wp_footer', array($this, '_credits_footer'));

        add_action('widgets_init', array($this, '_register_widgets'));

        /* Shortcodes */
        add_shortcode('WPBUSDIRMANADDLISTING', array($this->controller, 'submit_listing'));
        add_shortcode('businessdirectory-submitlisting', array($this->controller, 'submit_listing'));
        add_shortcode('WPBUSDIRMANMANAGELISTING', array($this->controller, 'manage_listings'));
        add_shortcode('businessdirectory-managelistings', array($this->controller, 'manage_listings'));
        add_shortcode('WPBUSDIRMANMVIEWLISTINGS', array($this, '_listings_shortcode'));
        add_shortcode('businessdirectory-viewlistings', array($this, '_listings_shortcode'));
        add_shortcode('businessdirectory-listings', array($this, '_listings_shortcode'));        
        add_shortcode('WPBUSDIRMANUI', array($this->controller, 'dispatch'));
        add_shortcode('businessdirectory', array($this->controller, 'dispatch'));
        add_shortcode('business-directory', array($this->controller, 'dispatch'));

        /* Expiration hook */
        add_action('wpbdp_listings_expiration_check', array($this, '_listing_expirations'), 0);

        $this->controller->init();

        /* scripts & styles */
        add_action('wp_enqueue_scripts', array($this, '_enqueue_scripts'));

        add_action('init', array($this, '_init_modules'));
        add_action('wp_ajax_wpbdp-ajax', array($this, '_handle_ajax'));
    }

    public function _init_modules() {
        do_action('wpbdp_modules_loaded');
        // do_action( 'wpbdp_register_settings', $this->settings );
        do_action_ref_array( 'wpbdp_register_settings', array( &$this->settings ) );
        do_action('wpbdp_register_fields', $this->formfields);

        do_action('wpbdp_modules_init');
    }

    public function get_post_type() {
        return WPBDP_POST_TYPE;
    }

    public function get_post_type_category() {
        return WPBDP_CATEGORY_TAX;
    }

    public function get_post_type_tags() {
        return WPBDP_TAGS_TAX;
    }

    public function install_or_update_plugin() {
        $installer = new WPBDP_Installer();
        $installer->install();
        
        $plugin_dir = basename( dirname( __FILE__ ) );
        $languages_dir = trailingslashit( $plugin_dir . '/languages' );
        load_plugin_textdomain( 'WPBDM', false, $languages_dir );
    }

    function _session_start() {
        if (session_id() == '') {
            session_start();
        }
    }

    function _register_post_type() {
        $post_type_slug = $this->settings->get('permalinks-directory-slug', WPBDP_POST_TYPE);
        $category_slug = $this->settings->get('permalinks-category-slug', WPBDP_CATEGORY_TAX);
        $tags_slug = $this->settings->get('permalinks-tags-slug', WPBDP_TAGS_TAX);

        $labels = array(
            'name' => _x('Directory', 'post type general name', 'WPBDM'),
            'singular_name' => _x('Directory', 'post type singular name', 'WPBDM'),
            'add_new' => _x('Add New Listing', 'listing', 'WPBDM'),
            'add_new_item' => _x('Add New Listing', 'post type', 'WPBDM'),
            'edit_item' => __('Edit Listing', 'WPBDM'),
            'new_item' => __('New Listing', 'WPBDM'),
            'view_item' => __('View Listing', 'WPBDM'),
            'search_items' => __('Search Listings', 'WPBDM'),
            'not_found' =>  __('No listings found', 'WPBDM'),
            'not_found_in_trash' => __('No listings found in trash', 'WPBDM'),
            'parent_item_colon' => ''
            );

        $args = array(
            'labels' => $labels,
            'public' => true,
            'publicly_queryable' => true,
            'show_ui' => true,
            'query_var' => true,
            'rewrite' => array('slug'=> $post_type_slug, 'with_front' => false, 'feeds' => true),
            'capability_type' => 'post',
            'hierarchical' => false,
            'menu_position' => null,
            'menu_icon' => WPBDP_URL . 'resources/images/menuico.png',
            'supports' => array('title','editor','author','categories','tags','thumbnail','excerpt','comments','custom-fields','trackbacks')
        );

        register_post_type(WPBDP_POST_TYPE, $args);

        register_taxonomy(WPBDP_CATEGORY_TAX, WPBDP_POST_TYPE, array( 'hierarchical' => true, 'label' => 'Directory Categories', 'singular_name' => 'Directory Category', 'show_in_nav_menus' => true, 'update_count_callback' => '_update_post_term_count','query_var' => true, 'rewrite' => array('slug' => $category_slug) ) );
        register_taxonomy(WPBDP_TAGS_TAX, WPBDP_POST_TYPE, array( 'hierarchical' => false, 'label' => 'Directory Tags', 'singular_name' => 'Directory Tag', 'show_in_nav_menus' => true, 'update_count_callback' => '_update_post_term_count', 'query_var' => true, 'rewrite' => array('slug' => $tags_slug) ) );
    }

    public function _register_image_sizes() {
        $thumbnail_width = intval( wpbdp_get_option( 'thumbnail-width' ) );

        $max_width = intval( wpbdp_get_option('image-max-width') );
        $max_height = intval( wpbdp_get_option('image-max-height') );

        // thumbnail size
        add_image_size( 'wpbdp-thumb', $thumbnail_width, 0, false );
        add_image_size( 'wpbdp-large', $max_width, $max_height, false );
    }

    public function debug_on() {
        WPBDP_Debugging::debug_on();
    }

    public function debug_off() {
        WPBDP_Debugging::debug_off();
    }

    public function has_module($name) {
        switch (strtolower($name)) {
            default:
                break;
            case 'paypal':
                return wpbdp_payments_api()->has_gateway('paypal');
                break;
            case '2checkout':
            case 'twocheckout':
                return wpbdp_payments_api()->has_gateway('2checkout');
                break;
            case 'googlecheckout':
                return wpbdp_payments_api()->has_gateway('googlecheckout');
                break;
            case 'googlemaps':
                return class_exists('BusinessDirectory_GoogleMapsPlugin');
                break;
            case 'ratings':
                return class_exists('BusinessDirectory_RatingsModule');
                break;
            case 'regions':
                return class_exists('WPBDP_RegionsPlugin');
                break;
            case 'attachments':
                return class_exists( 'WPBDP_ListingAttachmentsModule' );
                break;
            case 'zipcodesearch':
                return class_exists( 'WPBDP_ZIPShortcodeModule' );
                break;
            case 'featuredlevels':
                return class_exists( 'WPBDP_FeaturedLevelsModule' );
                break;
            case 'categories':
                return class_exists( 'WPBDP_CategoriesModule' );
                break;
        }

        return false;
    }

    public function _rss_feed() {
        $action = $this->controller->get_current_action();
        $main_page_id = wpbdp_get_page_id( 'main' );

        if ( !$action || !$main_page_id )
            return;

        echo "\n<!-- Business Directory RSS feed -->\n";
        echo sprintf( '<link rel="alternate" type="application/rss+xml" title="%s" href="%s" /> ',
                      sprintf( _x( '%s Feed', 'rss feed', 'WPBDM'), get_the_title( $main_page_id ) ),
                      add_query_arg( 'post_type', WPBDP_POST_TYPE,  get_bloginfo( 'rss2_url' ) )
                    );

        if ( $action == 'browsetag' || $action == 'browsecategory' ) {
            echo "\n";
            echo sprintf( '<link rel="alternate" type="application/rss+xml" title="%s" href="%s" /> ',
                          sprintf( _x( '%s Feed', 'rss feed', 'WPBDM'), get_the_title( $main_page_id ) ),
                          add_query_arg( array( 'post_type' => WPBDP_POST_TYPE, WPBDP_CATEGORY_TAX => get_query_var( 'category' ) ),  get_bloginfo( 'rss2_url' ) )
                        );
        }

        echo "\n";
    }

    public function _credits_footer() {
        $html = '';

        if(wpbdp_get_option('credit-author')) {
            $html .= '<div class="wpbdmac">Directory powered by <a href="http://businessdirectoryplugin.com/">Business Directory Plugin</a></div>';
        }

        echo $html;
    }

    public function _register_widgets() {
        register_widget('WPBDP_LatestListingsWidget');
        register_widget('WPBDP_FeaturedListingsWidget');
        register_widget('WPBDP_RandomListingsWidget');
        register_widget('WPBDP_SearchWidget');
    }

    public function _listings_shortcode($atts) {
        if (!$this->controller->check_main_page($msg)) return $msg;

        $atts = shortcode_atts(array('category' => null), $atts);

        if ($atts['category']) {
            return $this->controller->browse_category($atts['category']);
        } else {
            return $this->controller->view_listings(true);
        }

    }

    /* theme filters */
    public function _comments_template($template) {
        // disable comments in WPBDP pages or if comments are disabled for listings
        if ( (is_single() && get_post_type() == WPBDP_POST_TYPE && !$this->settings->get('show-comment-form')) || 
              (get_post_type() == 'page' && get_the_ID() == wpbdp_get_page_id('main') )  ) {
            return WPBDP_TEMPLATES_PATH . '/empty-template.php';
        }

        return $template;
    }

    public function _category_template($template) {
        if (get_query_var(WPBDP_CATEGORY_TAX) && taxonomy_exists(WPBDP_CATEGORY_TAX)) {
            return wpbdp_locate_template(array('businessdirectory-category', 'wpbusdirman-category'));
        }

        return $template;
    }

    public function _single_template($template) {
        if (is_single() && get_post_type() == WPBDP_POST_TYPE) {
            return wpbdp_locate_template(array('businessdirectory-single', 'wpbusdirman-single'));
        }
        
        return $template;
    }

    /* scripts & styles */
    public function _enqueue_scripts() {
        wp_enqueue_style('wpbdp-base-css', WPBDP_URL . 'resources/css/wpbdp.css');
        wp_enqueue_script('wpbdp-js', WPBDP_URL . 'resources/js/wpbdp.js', array('jquery'));

        // enable legacy css (should be removed in a future release) XXX
        if (_wpbdp_template_mode('single') == 'template' || _wpbdp_template_mode('category') == 'template' ||  wpbdp_get_page_id('main') == get_option('page_on_front') )
            wp_enqueue_style('wpbdp-legacy-css', WPBDP_URL . '/resources/css/wpbdp-legacy.css');

        $counter = 0;
        foreach (array('wpbdp.css', 'wpbusdirman.css', 'wpbdp_custom_style.css', 'wpbdp_custom_styles.css', 'wpbdm_custom_style.css', 'wpbdm_custom_styles.css') as $stylesheet) {
            if (file_exists( get_stylesheet_directory() . '/' . $stylesheet )) {
                wp_enqueue_style('wpbdp-custom-css-' . $counter, get_stylesheet_directory_uri() . '/' . $stylesheet);
                $counter++;
            }

            if (file_exists( get_stylesheet_directory() . '/css/' . $stylesheet )) {
                wp_enqueue_style('wpbdp-custom-css-' . $counter, get_stylesheet_directory_uri() . '/css/' . $stylesheet);
                $counter++;
            }

            if (get_template_directory() != get_stylesheet_directory()) {
                if (file_exists( get_template_directory() . '/' . $stylesheet )) {
                    wp_enqueue_style('wpbdp-custom-css-' . $counter, get_template_directory_uri() . '/' . $stylesheet);
                    $counter++;
                }

                if (file_exists( get_template_directory() . '/css/' . $stylesheet )) {
                    wp_enqueue_style('wpbdp-custom-css-' . $counter, get_template_directory_uri() . '/css/' . $stylesheet);
                    $counter++;
                }
            }

            if (file_exists(WP_PLUGIN_DIR . '/' . $stylesheet)) {
                wp_enqueue_style('wpbdp-custom-css-' . $counter, WP_PLUGIN_URL . '/' . $stylesheet);
                $counter++;
            }
        }

        if ( wpbdp_get_option( 'use-thickbox' ) ) {
            add_thickbox();
        }
    }

    /*
     * Page metadata
     */
    public function _meta_setup() {
        $action = $this->controller->get_current_action();

        if ( !$action )
            return;

        $this->_do_wpseo = defined( 'WPSEO_VERSION' ) ? true : false;

        if ( $this->_do_wpseo ) {
            global $wpseo_front;

            remove_filter( 'wp_title', array( $this, '_meta_title' ), 10, 3 );
            add_filter( 'wp_title', array( $this, '_meta_title' ), 16, 3 );

            remove_filter( 'wp_title', array( &$wpseo_front, 'title' ), 15, 3 );
            remove_action( 'wp_head', array( &$wpseo_front, 'head' ), 1, 1 );
            add_action( 'wp_head', array( $this, '_meta_keywords' ) );
        }

        remove_filter( 'wp_head', 'rel_canonical' );
        add_filter( 'wp_head', array( $this, '_meta_rel_canonical' ) );
    }

    /*
     * Fix issues with Jetpack.
     */
    public function _jetpack_compat( &$wp ) {
        static $incompatible_actions = array( 'submitlisting', 'editlisting', 'upgradetostickylisting' );

        $action = $this->controller->get_current_action();

        if ( !$action )
            return;

        if ( defined( 'JETPACK__VERSION' ) && in_array( $action, $incompatible_actions ) ) {
            add_filter( 'jetpack_enable_opengraph', '__return_false', 99 );
            remove_action( 'wp_head', 'jetpack_og_tags' );
        }
    }

    public function _handle_broken_plugin_filters() {
        $action = $this->controller->get_current_action();

        if ( !$action )
            return;

        $bad_filters = array( 'get_the_excerpt' => array(), 'the_excerpt' => array(), 'the_content' => array() );

        // AddThis Social Bookmarking Widget - http://www.addthis.com/
        if ( defined( 'ADDTHIS_PLUGIN_VERSION' ) ) {
            $bad_filters['get_the_excerpt'][] = array( 'addthis_display_social_widget_excerpt', 11);
            $bad_filters['get_the_excerpt'][] = array( 'addthis_display_social_widget', 15 );
            $bad_filters['the_content'][] = array( 'addthis_display_social_widget', 15 );
        }

        // Jamie Social Icons - http://wordpress.org/extend/plugins/jamie-social-icons/ 
        if ( function_exists( 'jamiesocial' ) ) {
            $bad_filters['the_content'][] = 'add_post_topbot_content';
            $bad_filters['the_content'][] = 'add_post_bot_content';
            $bad_filters['the_content'][] = 'add_page_topbot_content';
            $bad_filters['the_content'][] = 'add_page_top_content';
            $bad_filters['the_content'][] = 'add_page_bot_content';
        }

        // TF Social Share - http://www.searchtechword.com/2011/06/wordpress-plugin-add-twitter-facebook-google-plus-one-share
        if ( function_exists( 'kc_twitter_facebook_excerpt' ) ) {
            $bad_filters['the_excerpt'][] = 'kc_twitter_facebook_excerpt';
            $bad_filters['the_content'][] = 'kc_twitter_facebook_contents';
        }

        // Shareaholic - https://shareaholic.com/publishers/
        if ( defined( 'SHRSB_vNum' ) ) {
            $bad_filters['the_content'][] = 'shrsb_position_menu';
            $bad_filters['the_content'][] = 'shrsb_get_recommendations';
            $bad_filters['the_content'][] = 'shrsb_get_cb';
        }

        // Quick AdSense - http://quicksense.net/
        global $QData;
        if ( isset( $QData ) ) {
            $bad_filters['the_content'][] = 'process_content';
        }        

        foreach ( $bad_filters as $filter => &$callbacks ) {
            foreach ( $callbacks as &$callback_info ) {
                if ( has_filter( $filter, is_array( $callback_info ) ? $callback_info[0] : $callback_info ) ) {
                    remove_filter( $filter, is_array( $callback_info ) ? $callback_info[0] : $callback_info, is_array( $callback_info ) ? $callback_info[1] : 10 );
                }
            }
        }

    }

    public function _meta_title($title, $sep, $seplocation) {
        $action = $this->controller->get_current_action();

        switch ($action) {
            case 'browsetag':
                $term = get_term_by('slug', get_query_var('tag'), WPBDP_TAGS_TAX);

                if ( $this->_do_wpseo ) {
                    global $wpseo_front;
                    
                    $title = trim( wpseo_get_term_meta( $term, $term->taxonomy, 'title' ) );

                    if ( !empty( $title ) )
                        return wpseo_replace_vars( $title, (array) $term );

                    return $wpseo_front->get_title_from_options( 'title-' . $term->taxonomy, $term );
                }

                return sprintf( _x( 'Listings tagged: %s', 'title', 'WPBDM' ), $term->name ) . ' ' . $sep . ' ';

                break;

            case 'browsecategory':
                $term = get_term_by('slug', get_query_var('category'), wpbdp_categories_taxonomy());
                if (!$term && get_query_var('category_id')) $term = get_term_by('id', get_query_var('category_id'), wpbdp_categories_taxonomy());

                if ( $this->_do_wpseo ) {
                    global $wpseo_front;

                    $title = trim( wpseo_get_term_meta( $term, $term->taxonomy, 'title' ) );

                    if ( !empty( $title ) )
                        return wpseo_replace_vars( $title, (array) $term );

                    return $wpseo_front->get_title_from_options( 'title-' . $term->taxonomy, $term );
                }

                return $term->name . ' ' . $sep . ' ';

                break;

            case 'showlisting':
                $listing_id = get_query_var('listing') ? wpbdp_get_post_by_slug(get_query_var('listing'))->ID : wpbdp_getv($_GET, 'id', get_query_var('id'));

                if ( $this->_do_wpseo ) {
                    global $wpseo_front;

                    $title = $wpseo_front->get_content_title( get_post( $listing_id ) );
                    $title = esc_html( strip_tags( stripslashes( apply_filters( 'wpseo_title', $title ) ) ) );
                    
                    return $title;
                    break;
                } else {
                    $post_title = get_the_title($listing_id);
                }

                return $post_title . ' '.  $sep . ' ';
                break;

            default:
                break;
        }

        return $title;
    }

    public function _meta_keywords() {
        global $wpseo_front;
        global $post;

        $listing_id = get_query_var('listing') ? wpbdp_get_post_by_slug(get_query_var('listing'))->ID : wpbdp_getv($_GET, 'id', get_query_var('id'));

        $prev_post = $post;
        $post = get_post( $listing_id );

        $wpseo_front->metadesc();
        $wpseo_front->metakeywords();

        $post = $prev_post;
    }

    public function _meta_rel_canonical() {
        $action = $this->controller->get_current_action();

        if ( !$action )
            return rel_canonical();

        if ( in_array( $action, array( 'editlisting', 'submitlisting', 'sendcontactmessage', 'deletelisting', 'upgradetostickylisting', 'renewlisting', 'payment-process' ) ) )
            return;        

        $link = $_SERVER['REQUEST_URI'];
        echo sprintf( '<link rel="canonical" href="%s" />', $link );
    }

    public function get_ajax_url($action, $args=array()) {
        $args = array_merge( array('action' => 'wpbdp-ajax', 'wpbdp_action' => $action), $args );
        return add_query_arg( $args, admin_url( 'admin-ajax.php' ) );
    }

    public function _handle_ajax() {
        $action = wpbdp_getv($_REQUEST, 'wpbdp_action', null);

        switch ($action) {
            case 'file-upload':
                return $this->_handle_ajax_file_upload();
                break;
        }

        exit;
    }

    private function _handle_ajax_file_upload() {
        echo '<form action="" method="POST" enctype="multipart/form-data">';
        echo '<input type="file" name="file" class="file-upload" onchange="return window.parent.WPBDP.fileUpload.handleUpload(this);"/>';
        echo '</form>';

        if ( isset($_FILES['file']) && $_FILES['file']['error'] == 0 ) {
            // TODO: we support only images for now but we could use this for anything later
            if ( $media_id = wpbdp_media_upload( $_FILES['file'], true, true, array(), $errors ) ) {
                echo '<div class="preview" style="display: none;">';
                echo wp_get_attachment_image( $media_id, 'thumb', false );
                echo '</div>';

                echo '<script type="text/javascript">';
                echo sprintf( 'window.parent.WPBDP.fileUpload.finishUpload(%d, %d);', $_REQUEST['field_id'], $media_id );
                echo '</script>';
            } else {
                print $errors;
            }
        }

        echo sprintf( '<script type="text/javascript">window.parent.WPBDP.fileUpload.resizeIFrame(%d);</script>', $_REQUEST['field_id'] );
        
        exit;
    }    


}

$wpbdp = new WPBDP_Plugin();
$wpbdp->init();
