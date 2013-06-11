<?php

class GFUserSignups {

    public static function create_signups_table() {
        require_once(ABSPATH . "wp-admin/includes/upgrade.php");
        
        global $wpdb, $charset_collate;
        
        self::add_signups_to_wpdb();
        
        $ms_queries = "CREATE TABLE $wpdb->signups (
                domain varchar(200) NOT NULL default '',
                path varchar(100) NOT NULL default '',
                title longtext NOT NULL,
                user_login varchar(60) NOT NULL default '',
                user_email varchar(100) NOT NULL default '',
                registered datetime NOT NULL default '0000-00-00 00:00:00',
                activated datetime NOT NULL default '0000-00-00 00:00:00',
                active tinyint(1) NOT NULL default '0',
                activation_key varchar(50) NOT NULL default '',
                meta longtext,
                KEY activation_key (activation_key),
                KEY domain (domain)
                ) $charset_collate;";
            
        // now create table
        dbDelta($ms_queries);
    }
    
    /**
    * Add signups property to $wpdb object. Used by several MS functions.
    */
    private static function add_signups_to_wpdb() {
        global $wpdb;
        $wpdb->signups = $wpdb->prefix . 'signups';
    }
    
    public static function prep_signups_functionality() {
        
        if(!is_multisite()) {
                
            // require MS functions
            require_once(ABSPATH . 'wp-includes/ms-functions.php');
            
            // add $wpdb->signups property (accessed in various MS functions)
            self::add_signups_to_wpdb();
            
            // remove filter which checks for Network setting (not active on non-ms install)
            remove_filter('option_users_can_register', 'users_can_register_signup_filter');
            
        }
        
        // signup: update the signup URL to GF's custom activation page
        add_filter('wpmu_signup_user_notification_email', array('GFUserSignups', 'modify_signup_user_notification_message'), 10, 4);
        add_filter('wpmu_signup_blog_notification_email', array('GFUserSignups', 'modify_signup_blog_notification_message'), 10, 7);
        
        // signup: BP cancels default MS signup notification and replaces with its own; hook up to BP's custom notification hook
        if(GFUser::is_bp_active()) {
            add_filter('bp_core_activation_signup_user_notification_message', array('GFUserSignups', 'modify_signup_user_notification_message'), 10, 4);
            add_filter('bp_core_activation_signup_blog_notification_message', array('GFUserSignups', 'modify_signup_blog_notification_message'), 10, 7);
        }
        
    }
    
    public static function modify_signup_user_notification_message($message, $user, $user_email, $key) {
        
        $signup = GFSignup::get( $key );
        
        // if no signup or config is set for manual activation, return false preventing signup notification from being sent to user
        if( is_wp_error( $signup ) || $signup->get_activation_type() == 'manual' )
            return false;
        
        $url = add_query_arg(array('page' => 'gf_activation', 'key' => $key), get_site_url() . '/' );
        
        // BP replaces URL before passing the message, get the BP activation URL and replace
        if( GFUser::is_bp_active() ) {
            $activate_url = esc_url(bp_get_activation_page() . "?key=$key");
            $message = str_replace($activate_url, '%s', $message);
        }
        
        return sprintf($message, $url);
    }
    
    public static function modify_signup_blog_notification_message($message, $domain, $path, $title, $user, $user_email, $key) {
        
        $signup = GFSignup::get( $key );
        
        // if no signup or config is set for manual activation, return false preventing signup notification from being sent to user
        if( is_wp_error( $signup ) || $signup->get_activation_type() == 'manual' )
            return false;
        
        $url = add_query_arg(array('page' => 'gf_activation', 'key' => $key), get_site_url());
        
        // BP replaces URL before passing the message, get the BP activation URL and replace
        if(GFUser::is_bp_active()) {
            $activate_url = esc_url(bp_get_activation_page() . "?key=$key");
            $message = str_replace($activate_url, '%s', $message);
        }
        
        return sprintf($message, $url, esc_url("http://{$domain}{$path}"), $key);
    }
    
    public static function add_signup_meta($lead_id, $activation_key) {
        gform_update_meta($lead_id, 'activation_key', $activation_key);
    }
    
    public static function get_lead_activation_key($lead_id) {
        global $wpdb;
        return $wpdb->get_var($wpdb->prepare("SELECT meta_value FROM {$wpdb->prefix}rg_lead_meta WHERE lead_id = %d AND meta_key = 'activation_key'", $lead_id));
    }
    
    /**
     * Activate a signup.
     *
     */
    public static function activate_signup($key) {
        global $wpdb, $current_site;
        
        $blog_id = is_object($current_site) ? $current_site->id : false;
        $signup = GFSignup::get( $key );
        
        if( is_wp_error( $signup ) )
            return $signup;
        
        $user_login = $wpdb->escape($signup->user_login);
        $user_email = $wpdb->escape($signup->user_email);
        $user_id = username_exists($user_login);

        if(!$user_id) {
            
            // unbind site creation from gform_user_registered hook, run it manually below
            if(is_multisite())
                remove_action( 'gform_user_registered' , array( 'GFUser', 'create_new_multisite' ) );
            
            $user_data = GFUser::create_user( $signup->lead, $signup->form, $signup->config);
            $user_id = rgar($user_data, 'user_id');
            
        } else {
            $user_already_exists = true;
        }

        if(!$user_id)
            return new WP_Error('create_user', __('Could not create user'), $signup);

        $signup->set_as_activated();
        
        if(isset($user_already_exists))
            return new WP_Error('user_already_exists', __( 'That username is already activated.' ), $signup);
        
        do_action('gform_activate_user', $user_id, $user_data, $signup->meta);
        
        if(is_multisite()) {
            $ms_options = rgars( $signup->config, 'meta/multisite_options');
            if($ms_options['create_site'])
                $blog_id = GFUser::create_new_multisite($user_id, $signup->config, $signup->lead, $user_data['password']);    
        }
        
        return array('user_id' => $user_id, 'password' => $user_data['password'], 'blog_id' => $blog_id);
    }
    
    public static function delete_signup($key) {
        global $wpdb;
        return $wpdb->query($wpdb->prepare("DELETE FROM $wpdb->signups WHERE activation_key = %s", $key));
    }
    
}

/**
* Create a signup object from a signup key.
*/

class GFSignup {
    
    public $meta;
    public $lead;
    public $form;
    public $config;
    
    private $error;
    
    function __construct( $signup ) {
        
        // @alex: not sure this is a good thing to do?
        foreach( $signup as $key => $value ) {
            $this->$key = $value;
        }
            
        $this->meta = unserialize( $signup->meta );
        $this->lead = RGFormsModel::get_lead( $this->meta['lead_id'] );
        $this->form = RGFormsModel::get_form_meta( $this->lead['form_id'] );
        $this->config = GFUser::get_active_config( $this->form, $this->lead );
        
    }
    
    public static function get( $key ) {
        global $wpdb;
        
        $signup = $wpdb->get_row( $wpdb->prepare("SELECT * FROM $wpdb->signups WHERE activation_key = %s", $key) );
        
        if(empty($signup))
            return new WP_Error( 'invalid_key', __( 'Invalid activation key.' ) );

        if($signup->active)
            return new WP_Error( 'already_active', __( 'The user is already active.' ), $signup );
            
        return new GFSignup( $signup );
    }
    
    function get_activation_type() {
        return rgars( $this->config, 'meta/user_activation_type' );
    }
    
    function set_as_activated() {
        global $wpdb;
        
        $now = current_time('mysql', true);
        $result = $wpdb->update( $wpdb->signups, array( 'active' => 1, 'activated' => $now ), array( 'activation_key' => $this->activation_key ) );
        
        return $result;
    }
    
}

?>
