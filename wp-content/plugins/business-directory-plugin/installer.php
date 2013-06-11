<?php
 
 class WPBDP_Installer {

    const DB_VERSION = '3.3';

    private $installed_version = null;


    public function __construct() {
        $this->installed_version = get_option( 'wpbdp-db-version', get_option( 'wpbusdirman_db_version', null ) );
    }   

    public function install() {
        if ( $this->installed_version != self::DB_VERSION ) {
            $this->_database_schema();
        }

        if ( $this->installed_version ) {
            wpbdp_log('WPBDP is already installed.');
            $this->_update();
        } else {
            wpbdp_log('New installation. Creating default form fields.');
            global $wpbdp;
            $wpbdp->formfields->create_default_fields();                    
        }

        delete_option('wpbusdirman_db_version');
        update_option('wpbdp-db-version', self::DB_VERSION);

        // schedule expiration hook if needed
        if (!wp_next_scheduled('wpbdp_listings_expiration_check')) {
            wpbdp_log('Expiration check was not in schedule. Scheduling.');
            wp_schedule_event(current_time('timestamp'), 'hourly', 'wpbdp_listings_expiration_check'); // TODO change to daily
        } else {
            wpbdp_log('Expiration check was in schedule. Nothing to do.');
        }        
    }

    public function _database_schema() {
        global $wpdb;
        
        wpbdp_log( 'Running dbDelta.' );

        require_once(ABSPATH . 'wp-admin/includes/upgrade.php');

        $sql = "CREATE TABLE {$wpdb->prefix}wpbdp_form_fields (
            id mediumint(9) PRIMARY KEY  AUTO_INCREMENT,
            label varchar(255) CHARACTER SET utf8 COLLATE utf8_general_ci NOT NULL,
            description varchar(255) CHARACTER SET utf8 COLLATE utf8_general_ci NULL,
            field_type varchar(100) NOT NULL,
            association varchar(100) NOT NULL,
            validators text CHARACTER SET utf8 COLLATE utf8_general_ci NULL,
            weight int(5) NOT NULL DEFAULT 0,
            display_flags text CHARACTER SET utf8 COLLATE utf8_general_ci NULL,
            field_data blob NULL
        ) DEFAULT CHARSET=utf8 COLLATE=utf8_general_ci;";

        dbDelta($sql);

        $sql = "CREATE TABLE {$wpdb->prefix}wpbdp_fees (
            id mediumint(9) PRIMARY KEY  AUTO_INCREMENT,
            label varchar(255) CHARACTER SET utf8 COLLATE utf8_general_ci NOT NULL,
            amount DECIMAL(10,2) NOT NULL DEFAULT 0.00,
            days smallint UNSIGNED NOT NULL DEFAULT 0,
            images smallint UNSIGNED NOT NULL DEFAULT 0,
            categories blob NOT NULL,
            extra_data blob NULL
        ) DEFAULT CHARSET=utf8 COLLATE=utf8_general_ci;";

        dbDelta($sql);

        $sql = "CREATE TABLE {$wpdb->prefix}wpbdp_payments (
            id mediumint(9) PRIMARY KEY  AUTO_INCREMENT,
            listing_id mediumint(9) NOT NULL,
            gateway varchar(255) CHARACTER SET utf8 COLLATE utf8_general_ci NULL,
            amount DECIMAL(10,2) NOT NULL DEFAULT 0.00,
            payment_type varchar(255) NOT NULL,
            status varchar(255) NOT NULL,
            created_on TIMESTAMP NOT NULL,
            processed_on TIMESTAMP NULL,
            processed_by varchar(255) NOT NULL DEFAULT 'gateway',               
            payerinfo blob NULL,
            extra_data blob NULL
        ) DEFAULT CHARSET=utf8 COLLATE=utf8_general_ci;";

        dbDelta($sql);

        $sql = "CREATE TABLE {$wpdb->prefix}wpbdp_listing_fees (
            id mediumint(9) PRIMARY KEY  AUTO_INCREMENT,
            listing_id mediumint(9) NOT NULL,
            category_id mediumint(9) NOT NULL,
            fee blob NOT NULL,
            expires_on TIMESTAMP NULL DEFAULT NULL,
            updated_on TIMESTAMP NOT NULL,
            charged tinyint(1) NOT NULL DEFAULT 0,
            email_sent tinyint(1) NOT NULL DEFAULT 0
        ) DEFAULT CHARSET=utf8 COLLATE=utf8_general_ci;";

        dbDelta($sql);
    }

    public function _update() {
        global $wpbdp;

        $upgrade_routines = array( '2.0', '2.1', '2.2', '2.3', '2.4', '2.5', '3.1', '3.2' );

        foreach ( $upgrade_routines as $v ) {
            if ( version_compare( $this->installed_version, $v ) < 0 ) {
                wpbdp_log( sprintf( 'Running upgrade routine for version %s', $v ) );
                $_v = str_replace( '.', '_', $v );
                call_user_func( array( $this, 'upgrade_to_' . $_v ) );
                update_option('wpbdp-db-version', $v);
            }
        }
    }


    /*
     * Upgrade routines.
     */

    public function upgrade_to_2_0() {
        global $wpdb;
        global $wpbdp;

        $wpbdp->settings->upgrade_options();
        wpbdp_log('WPBDP settings updated to 2.0-style');

        // make directory-related metadata hidden
        $old_meta_keys = array(
            'termlength', 'image', 'listingfeeid', 'sticky', 'thumbnail', 'paymentstatus', 'buyerfirstname', 'buyerlastname',
            'paymentflag', 'payeremail', 'paymentgateway', 'totalallowedimages', 'costoflisting'
        );

        foreach ($old_meta_keys as $meta_key) {
            $query = $wpdb->prepare("UPDATE {$wpdb->postmeta} SET meta_key = %s WHERE meta_key = %s AND {$wpdb->postmeta}.post_id IN (SELECT ID FROM {$wpdb->posts} WHERE post_type = %s)",
                                    '_wpbdp_' . $meta_key, $meta_key, 'wpbdm-directory');
            $wpdb->query($query);
        }

        wpbdp_log('Made WPBDP directory metadata hidden attributes');
    }

    public function upgrade_to_2_1() {
        global $wpdb;

        /* This is only to make this routine work for BD 3.0. It's not necessary in other versions. */
        $wpdb->query( "ALTER TABLE {$wpdb->prefix}wpbdp_form_fields ADD COLUMN validator VARCHAR(255) NULL;" );
        $wpdb->query( "ALTER TABLE {$wpdb->prefix}wpbdp_form_fields ADD COLUMN display_options BLOB NULL;" );
        $wpdb->query( "ALTER TABLE {$wpdb->prefix}wpbdp_form_fields ADD COLUMN is_required TINYINT(1) NOT NULL DEFAULT 0;" );
        $wpdb->query( "ALTER TABLE {$wpdb->prefix}wpbdp_form_fields ADD COLUMN type VARCHAR(255) NOT NULL;" );

        static $pre_2_1_types = array(null, 'textfield', 'select', 'textarea', 'radio', 'multiselect', 'checkbox');
        static $pre_2_1_validators = array(
            'email' => 'EmailValidator',
            'url' => 'URLValidator',
            'missing' => null, /* not really used */
            'numericwhole' => 'IntegerNumberValidator',
            'numericdeci' => 'DecimalNumberValidator',
            'date' => 'DateValidator'
        );
        static $pre_2_1_associations = array(
            'title' => 'title',
            'description' => 'content',
            'category' => 'category',
            'excerpt' => 'excerpt',
            'meta' => 'meta',
            'tags' => 'tags'
        );

        $field_count = $wpdb->get_var(
            sprintf("SELECT COUNT(*) FROM {$wpdb->prefix}options WHERE option_name LIKE '%%%s%%'", 'wpbusdirman_postform_field_label'));

        for ($i = 1; $i <= $field_count; $i++) {
            $label = get_option('wpbusdirman_postform_field_label_' . $i);
            $type = get_option('wpbusdirman_postform_field_type_'. $i);
            $validation = get_option('wpbusdirman_postform_field_validation_'. $i);
            $association = get_option('wpbusdirman_postform_field_association_'. $i);
            $required = strtolower(get_option('wpbusdirman_postform_field_required_'. $i));
            $show_in_excerpt = strtolower(get_option('wpbusdirman_postform_field_showinexcerpt_'. $i));
            $hide_field = strtolower(get_option('wpbusdirman_postform_field_hide_'. $i));
            $options = get_option('wpbusdirman_postform_field_options_'. $i);

            $newfield = array();
            $newfield['label'] = $label;
            $newfield['type'] = wpbdp_getv($pre_2_1_types, intval($type), 'textfield');
            $newfield['validator'] = wpbdp_getv($pre_2_1_validators, $validation, null);
            $newfield['association'] = wpbdp_getv($pre_2_1_associations, $association, 'meta');
            $newfield['is_required'] = $required == 'yes' ? true : false;
            $newfield['display_options'] = serialize(
                array('show_in_excerpt' => $show_in_excerpt == 'yes' ? true : false,
                      'hide_field' => $hide_field == 'yes' ? true : false)
            );
            $newfield['field_data'] = $options ? serialize(array('options' => explode(',', $options))) : null;

            if ($wpdb->insert($wpdb->prefix . 'wpbdp_form_fields', $newfield)) {
                delete_option('wpbusdirman_postform_field_label_' . $i);
                delete_option('wpbusdirman_postform_field_type_' . $i);
                delete_option('wpbusdirman_postform_field_validation_' . $i);
                delete_option('wpbusdirman_postform_field_association_' . $i);
                delete_option('wpbusdirman_postform_field_required_' . $i);
                delete_option('wpbusdirman_postform_field_showinexcerpt_' . $i);
                delete_option('wpbusdirman_postform_field_hide_' . $i);
                delete_option('wpbusdirman_postform_field_options_' . $i);
                delete_option('wpbusdirman_postform_field_order_' . $i);
            } else {
                wpbdp_debug_e( 'not added', $newfield );                
            }

        }
    }

    public function upgrade_to_2_2() {
        global $wpdb;
        $wpdb->query("ALTER TABLE {$wpdb->prefix}wpbdp_form_fields CHARACTER SET utf8 COLLATE utf8_general_ci");
        $wpdb->query("ALTER TABLE {$wpdb->prefix}wpbdp_form_fields CHANGE `label` `label` VARCHAR(255) CHARACTER SET utf8 COLLATE utf8_general_ci NOT NULL");
        $wpdb->query("ALTER TABLE {$wpdb->prefix}wpbdp_form_fields CHANGE `description` `description` VARCHAR(255) CHARACTER SET utf8 COLLATE utf8_general_ci NULL");
    }

    public function upgrade_to_2_3() {
        global $wpdb;

        $count = $wpdb->get_var(
            sprintf("SELECT COUNT(*) FROM {$wpdb->prefix}options WHERE option_name LIKE '%%%s%%'", 'wpbusdirman_settings_fees_label_'));

        for ($i = 1; $i <= $count; $i++) {
            $label = get_option('_settings_fees_label_' . $i, get_option('wpbusdirman_settings_fees_label_' . $i));
            $amount = get_option('_settings_fees_amount' . $i, get_option('wpbusdirman_settings_fees_amount_' . $i, '0.00'));
            $days = intval( get_option('_settings_fees_increment_' . $i, get_option('wpbusdirman_settings_fees_increment_' . $i, 0)) );
            $images = intval( get_option('_settings_fees_images_' . $i, get_option('wpbusdirman_settings_fees_images_' . $i, 0)) );
            $categories = get_option('_settings_fees_categories_' . $i, get_option('wpbusdirman_settings_fees_categories_' . $i, ''));

            $newfee = array();
            $newfee['label'] = $label;
            $newfee['amount'] = $amount;
            $newfee['days'] = $days;
            $newfee['images'] = $images;

            $category_data = array('all' => false, 'categories' => array());
            if ($categories == '0') {
                $category_data['all'] = true;
            } else {
                foreach (explode(',', $categories) as $category_id) {
                    $category_data['categories'][] = intval($category_id);
                }
            }
            
            $newfee['categories'] = serialize($category_data);

            if ($wpdb->insert($wpdb->prefix . 'wpbdp_fees', $newfee)) {
                $new_id = $wpdb->insert_id;

                $query = $wpdb->prepare("UPDATE {$wpdb->postmeta} SET meta_value = %s WHERE meta_key = %s AND meta_value = %s AND {$wpdb->postmeta}.post_id IN (SELECT ID FROM {$wpdb->posts} WHERE post_type = %s)",
                                         $new_id, '_wpbdp_listingfeeid', $i, 'wpbdm-directory');
                $wpdb->query($query);

                foreach (array('label', 'amount', 'increment', 'images', 'categories') as $k) {
                    delete_option('wpbusdirman_settings_fees_' . $k . '_' . $i);
                    delete_option('_settings_fees_' . $k . '_' . $i);
                }
            }

        }
    }

    public function upgrade_to_2_4() {
        global $wpdb;
        global $wpbdp;

        $fields = $wpbdp->formfields->get_fields();

        foreach ($fields as &$field) {
            $query = $wpdb->prepare("UPDATE {$wpdb->postmeta} SET meta_key = %s WHERE meta_key = %s AND {$wpdb->postmeta}.post_id IN (SELECT ID FROM {$wpdb->posts} WHERE post_type = %s)",
                                    '_wpbdp[fields][' . $field->get_id() . ']', $field->get_label(), 'wpbdm-directory');
            $wpdb->query($query);
        }
    }

    public function upgrade_to_2_5() {
        global $wpdb;

        wpbdp_log('Updating payment/sticky status values.');
        $wpdb->query($wpdb->prepare("UPDATE {$wpdb->postmeta} SET meta_key = %s WHERE meta_key = %s", '_wpbdp[sticky]', '_wpbdp_sticky'));
        $wpdb->query($wpdb->prepare("UPDATE {$wpdb->postmeta} SET meta_value = %s WHERE meta_key = %s AND meta_value = %s", 'sticky', '_wpbdp[sticky]', 'approved'));
        $wpdb->query($wpdb->prepare("UPDATE {$wpdb->postmeta} SET meta_value = %s WHERE meta_key = %s AND meta_value != %s", 'pending', '_wpbdp[sticky]', 'approved'));
        $wpdb->query($wpdb->prepare("UPDATE {$wpdb->postmeta} SET meta_key = %s WHERE meta_key = %s", '_wpbdp[payment_status]', '_wpbdp_paymentstatus'));
        $wpdb->query($wpdb->prepare("UPDATE {$wpdb->postmeta} SET meta_value = %s WHERE meta_key = %s AND meta_value != %s", 'not-paid', '_wpbdp[payment_status]', 'paid'));

        // Misc updates
        $wpdb->query($wpdb->prepare("DELETE FROM {$wpdb->postmeta} WHERE meta_key = %s", '_wpbdp_totalallowedimages'));
        $wpdb->query($wpdb->prepare("DELETE FROM {$wpdb->postmeta} WHERE meta_key = %s", '_wpbdp_termlength'));
        $wpdb->query($wpdb->prepare("DELETE FROM {$wpdb->postmeta} WHERE meta_key = %s", '_wpbdp_costoflisting'));
        $wpdb->query($wpdb->prepare("DELETE FROM {$wpdb->postmeta} WHERE meta_key = %s", '_wpbdp_listingfeeid'));

        wpbdp_log('Updating listing images to new framework.');

        $old_images = $wpdb->get_results($wpdb->prepare("SELECT * FROM {$wpdb->postmeta} WHERE meta_key = %s", '_wpbdp_image'));
        foreach ($old_images as $old_image) {
            require_once(ABSPATH . 'wp-admin/includes/file.php');
            require_once(ABSPATH . 'wp-admin/includes/image.php');

            $filename = ABSPATH . 'wp-content/uploads/wpbdm/' . $old_image->meta_value;

            $wp_filetype = wp_check_filetype(basename($filename), null);
            
            $attachment_id = wp_insert_attachment(array(
                'post_mime_type' => $wp_filetype['type'],
                'post_title' => preg_replace('/\.[^.]+$/', '', basename($filename)),
                'post_content' => '',
                'post_status' => 'inherit'
            ), $filename, $old_image->post_id);
            $attach_data = wp_generate_attachment_metadata( $attachment_id, $filename );
            wp_update_attachment_metadata( $attachment_id, $attach_data );
        }
        $wpdb->query($wpdb->prepare("DELETE FROM {$wpdb->postmeta} WHERE meta_key = %s", '_wpbdp_image'));
        $wpdb->query($wpdb->prepare("DELETE FROM {$wpdb->postmeta} WHERE meta_key = %s", '_wpbdp_thumbnail'));        
    }

    public function upgrade_to_3_1() {
        global $wpdb;

        $wpdb->query($wpdb->prepare("UPDATE {$wpdb->posts} SET post_type = %s WHERE post_type = %s", WPBDP_POST_TYPE, 'wpbdm-directory'));

        if (function_exists('flush_rewrite_rules'))
            flush_rewrite_rules(false);
    }

    /*
     * This update converts all form fields to a new, more flexible format that uses a new API introduced in BD 2.3.
     */
    public function upgrade_to_3_2() {
        global $wpdb;

        $validators_trans = array(
            'EmailValidator' => 'email',
            'URLValidator' => 'url',
            'IntegerNumberValidator' => 'integer_number',
            'DecimalNumberValidator' => 'decimal_number',
            'DateValidator' => 'date_'
        );

        $old_fields = $wpdb->get_results( "SELECT * FROM {$wpdb->prefix}wpbdp_form_fields" );

        foreach ( $old_fields as &$f ) {
            $newfield = array();
            $newfield['field_type'] = strtolower( $f->type );

            if ( empty( $newfield['field_type'] ) )
                $newfield['field_type'] = 'textfield';

            $newfield['display_flags'] = array();
            $newfield['field_data'] = array();
            $newfield['validators'] = array();

            // display options
            $f_display_options = array_merge(array('show_in_excerpt' => true, 'show_in_listing' => true, 'show_in_search' => true), $f->display_options ? (array) unserialize($f->display_options) : array());
            if ( isset( $f_display_options['hide_field'] ) && $f_display_options['hide_field'] ) {
                // do nothing
            } else {
                if ( $f_display_options['show_in_excerpt'] ) $newfield['display_flags'][] = 'excerpt';
                if ( $f_display_options['show_in_listing'] ) $newfield['display_flags'][] = 'listing';
                if ( $f_display_options['show_in_search'] ) $newfield['display_flags'][] = 'search';
            }

            // validators
            if ( $f->validator && isset( $validators_trans[ $f->validator ] ) ) $newfield['validators'] = array( $validators_trans[ $f->validator ] );
            if ( $f->is_required ) $newfield['validators'][] = 'required';

            // options for multivalued fields
            $f_data = $f->field_data ? unserialize( $f->field_data ) : null;
            $f_data = is_array( $f_data ) ? $f_data : array();

            if ( isset( $f_data['options'] ) && is_array( $f_data['options'] ) ) $newfield['field_data']['options'] = $f_data['options'];
            if ( isset( $f_data['open_in_new_window'] ) && $f_data['open_in_new_window'] ) $newfield['field_data']['open_in_new_window'] = true;

            if ( $newfield['field_type'] == 'textfield' && in_array( 'url', $newfield['validators']) )
                $newfield['field_type'] = 'url';

            $newfield['display_flags'] = implode( ',', $newfield['display_flags'] );
            $newfield['validators'] = implode( ',', $newfield['validators'] );
            $newfield['field_data'] = serialize( $newfield['field_data'] );

            $wpdb->update( "{$wpdb->prefix}wpbdp_form_fields", $newfield, array( 'id' => $f->id ) );
        }

        $wpdb->query( "ALTER TABLE {$wpdb->prefix}wpbdp_form_fields DROP COLUMN validator;" );
        $wpdb->query( "ALTER TABLE {$wpdb->prefix}wpbdp_form_fields DROP COLUMN display_options;" );
        $wpdb->query( "ALTER TABLE {$wpdb->prefix}wpbdp_form_fields DROP COLUMN is_required;" );
        $wpdb->query( "ALTER TABLE {$wpdb->prefix}wpbdp_form_fields DROP COLUMN type;" );

        add_action( 'admin_notices', array( $this, 'disable_regions_in_3_2_upgrade' )  );
    }

    public function disable_regions_in_3_2_upgrade() {
        require_once( ABSPATH . 'wp-admin/includes/plugin.php' );

        if ( class_exists( 'WPBDP_RegionsPlugin' ) && version_compare( WPBDP_RegionsPlugin::VERSION, '1.1', '<' ) ) {
            deactivate_plugins( 'business-directory-regions/business-directory-regions.php', true );
            echo sprintf( '<div class="error"><p>%s</p></div>',
                          _x( '<b>Business Directory Plugin - Regions Module</b> was disabled because it is incompatible with the current version of Business Directory. Please update the Regions module.', 'installer', 'WPBDM' )
                        );
        }        
    }    

 }
