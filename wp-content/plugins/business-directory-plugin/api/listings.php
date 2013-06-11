<?php
if (!class_exists('WPBDP_ListingsAPI')) {

/**
 * @since 2.1.6
 */
class WPBDP_ListingUpgrades {

    private static $instance = null;

    private function __construct() {
        // register default levels
        $this->register('normal', null, array(
            'name' => _x('Normal Listing', 'listings-api', 'WPBDM'),
            'is_sticky' => false
        ));
        $this->register('sticky', 'normal', array(
            'name' => _x('Featured Listing', 'listings-api', 'WPBDM'),
            'cost' => wpbdp_get_option('featured-price'),
            'description' => wpbdp_get_option('featured-description'),
            'is_sticky' => true            
        ));
    }

    public static function instance() {
        if (is_null(self::$instance)) {
            self::$instance = new self;
        }

        return self::$instance;
    }

    /*
     * General functions.
     */
    public function get_levels() {
        $res = array();

        foreach ($this->_order as $level_id) {
            $res[] = $this->get($level_id);
        }

        return $res;
    }

    public function register($upgrade_id, $after_id, $data) {
        if ( !isset($this->_levels) )
            $this->_levels = array();

        if ( !isset($this->_order) )
            $this->_order = array();

        if ( empty($upgrade_id) )
            return false;

       if ( $upgrade_id != 'normal' && (!$after_id || !in_array( $after_id, array_keys ($this->_levels) )) )
            $after_id = end ( $this->_order );

        $data = array_merge(array(
            'name' => $upgrade_id,
            'cost' => 0.0,
            'description' => '',
            'is_sticky' => false,
            'downgrade' => $after_id,
            'upgrade' => null,
        ), $data);

        if ( !isset($this->_levels[$upgrade_id]) ) {
            $obj = (object) $data;
            $obj->id = $upgrade_id;

            if ($obj->downgrade) {
                $prev_upgrade = $this->next($obj->downgrade);
                $this->_levels[$obj->downgrade]->upgrade = $obj->id;

                if ($prev_upgrade)
                    $this->_levels[$prev_upgrade]->downgrade = $obj->id;
            }

            $this->_levels[$upgrade_id] = $obj;
        } else {
            // TODO: support updates too
        }

        if ($obj->downgrade) {
            $down_key = array_search($obj->downgrade, $this->_order);

            array_splice($this->_order, max(0, $down_key + 1), 0, array($obj->id));
        } else {
            $this->_order[] = $upgrade_id;
        }

    }

    public function get($upgrade_id) {
        return wpbdp_getv($this->_levels, $upgrade_id, null);
    }

    public function prev($upgrade_id) {
        if ($u = $this->get($upgrade_id))
            return $u->downgrade;
        return null;
    }

    public function next($upgrade_id) {
        if ($u = $this->get($upgrade_id))
            return $u->upgrade;
        return null;
    }

    /**
     * Generates a unique level id from a given name. Useful for plugins extending functionality the
     * number of featured levels.
     * @since 2.1.7
     */
    public function unique_id($name) {
        $key = sanitize_key( $name );

        if ( !in_array( $key, $this->_order ) )
            return $key;

        $n = 0;
        while ( true ) {
            $key = $key . strval( $n );

            if ( !in_array( $key, $this->_order ) )
                return $key;

            $n += 1;
        }

    }

    /*
     * Listing-related.
     */

    public function is_sticky($listing_id) {

        //      if ($sticky_status = get_post_meta($listing_id, '_wpbdp[sticky]', true)) {
        //     return $sticky_status;
        // }

        // return 'normal';   
    }

    public function get_listing_level($listing_id) {
        $sticky_status = get_post_meta( $listing_id, '_wpbdp[sticky]', true );
        $level = get_post_meta( $listing_id, '_wpbdp[sticky_level]', true );

        switch ($sticky_status) {
            case 'sticky':
                if (!$level)
                    return $this->get('sticky');
                else
                    return $this->get($level) ? $this->get($level) : $this->get('sticky');

                break;
            case 'pending':
                if (!$level)
                    return $this->get('normal');
                else
                    return $this->get($level) ? $this->get($level) : $this->get('sticky');

                break;
            case 'normal':
            default:
                return $this->get('normal');
                break;
        }

    }

    public function get_info($listing_id) {
        if (!$listing_id)
            return null;

        $sticky_status = get_post_meta( $listing_id, '_wpbdp[sticky]', true );

        $res = new StdClass();
        $res->level = $this->get_listing_level( $listing_id );
        $res->status = $sticky_status ? $sticky_status : 'normal';
        $res->pending = $sticky_status == 'pending' ? true : false;
        $res->sticky = $res->level->is_sticky;
        $res->upgradeable = !empty($res->level->upgrade);
        $res->upgrade = $res->upgradeable ? $this->get($res->level->upgrade) : null;
        $res->downgradeable = $res->pending ? true : !empty($res->level->downgrade);
        $res->downgrade = $res->pending ? $this->get($res->level->id) : ($res->downgradeable ? $this->get($res->level->downgrade) : null);
        
        return $res;
    }

    public function set_sticky($listing_id, $level_id, $only_upgrade=false) {
        $current_info = $this->get_info( $listing_id );

        if ( $only_upgrade && (array_search($level_id, $this->_order) < array_search($current_info->level->id, $this->_order)) )
            return false;

        if ( $level_id == 'normal' ) {
            delete_post_meta( $listing_id, '_wpbdp[sticky]' );
            delete_post_meta( $listing_id, '_wpbdp[sticky_level]' );
        } else {
            update_post_meta( $listing_id, '_wpbdp[sticky]', 'sticky' );
            update_post_meta( $listing_id, '_wpbdp[sticky_level]', $level_id );
        }
    }

    public function request_upgrade($listing_id) {
        $payments_api = wpbdp_payments_api();
        
        $info = $this->get_info($listing_id);

        if ( !$info->pending && $info->upgradeable && $payments_api->payments_possible() ) {
            $transaction_id = $payments_api->save_transaction(array(
                'payment_type' => 'upgrade-to-sticky',
                'listing_id' => $listing_id,
                'amount' => $info->upgrade->cost
            ));

            update_post_meta( $listing_id, '_wpbdp[sticky]', 'pending' );
            return $transaction_id;
        }

        return 0;
    }

}


class WPBDP_ListingsAPI {

    public function __construct() {
        add_filter('post_type_link', array($this, '_post_link'), 10, 2);
        add_filter('post_type_link', array($this, '_post_link_qtranslate'), 11, 2); // basic support for qTranslate

        add_filter('term_link', array($this, '_category_link'), 10, 3);
        add_filter('term_link', array($this, '_tag_link'), 10, 3);
        add_filter('comments_open', array($this, '_allow_comments'), 10, 2);

        // notify admins of new listings (if needed)
        add_action( 'wpbdp_create_listing', array( $this, '_new_listing_notify' ) );

        $this->upgrades = WPBDP_ListingUpgrades::instance();
    }

    public function _category_link($link, $category, $taxonomy) {
        if ( ($taxonomy == wpbdp_categories_taxonomy()) && (_wpbdp_template_mode('category') == 'page') ) {
            if (wpbdp_rewrite_on()) {
                return rtrim(wpbdp_get_page_link('main'), '/') . '/' . wpbdp_get_option('permalinks-category-slug') . '/' . $category->slug . '/';
            } else {
                return add_query_arg('category', $category->slug, wpbdp_get_page_link('main'));
            }
        }

        return $link;
    }

    public function _tag_link($link, $tag, $taxonomy) {
        if ( ($taxonomy == WPBDP_TAGS_TAX) && (_wpbdp_template_mode('category') == 'page') ) {
            if (wpbdp_rewrite_on()) {
                return rtrim(wpbdp_get_page_link('main'), '/') . '/' . wpbdp_get_option('permalinks-tags-slug') . '/' . $tag->slug . '/';
            } else {
                return add_query_arg('tag', $tag->slug, wpbdp_get_page_link('main'));
            }
        }

        return $link;                
    }

    public function _post_link($url, $post) {
        if (is_admin())
            return $url;

        if ( ($post->post_type == wpbdp_post_type()) && (_wpbdp_template_mode('single') == 'page') ) {
            if (wpbdp_rewrite_on()){
                return rtrim(wpbdp_get_page_link('main'), '/') . '/' . $post->ID . '/' . ($post->post_name ? $post->post_name . '/' : '');
            } else {
                return add_query_arg( 'id', $post->ID, wpbdp_get_page_link( 'main' ) );
            }
        }

        return $url;
    }

    public function _post_link_qtranslate( $url, $post ) {
        if ( is_admin() || !function_exists( 'qtrans_convertURL' ) )
            return $url;

        global $q_config;

        $lang = isset( $_GET['lang'] ) ? $_GET['lang'] : $q_config['language'];
        $default_lang = $q_config['default_language'];

        if ( $lang != $default_lang )
            return add_query_arg( 'lang', $lang, $url );

        return $url;
    }

    public function _allow_comments($open, $post_id) {
        // comments on directory pages
        if ($post_id == wpbdp_get_page_id('main'))
            return false;

        // comments on listings
        if (get_post_type($post_id) == wpbdp_post_type())
            return wpbdp_get_option('show-comment-form');
        
        return $open;
    }

    public function _new_listing_notify( $listing_id ) {
        if ( !wpbdp_get_option( 'notify-admin' ) )
            return;

        $categories = wp_get_post_terms( $listing_id, wpbdp_categories_taxonomy(), array( 'fields' => 'names' ) );
        if ( $categories ) {
            $categories_str = implode( ',', $categories );
        } else {
            $categories_str = '-';
        }

        if ( get_post_status( $listing_id ) == 'publish' ) {
            $url = get_permalink( $listing_id );
        } else {
            $url = _x( '(not yet published)', 'notify email', 'WPBDM' );
        }

        $post = get_post( $listing_id );

        $message = wpbdp_render( 'email/listing-added', array(
            'id' => $listing_id,
            'title' => get_the_title( $listing_id ),
            'url' => $url,
            'categories' => $categories_str,
            'user_name' => get_the_author_meta( 'user_login', $post->post_author ),
            'user_email' => get_the_author_meta( 'user_email', $post->post_author )
        ), false );

        $email = new WPBDP_Email();
        $email->subject = sprintf( _x( '[%s] New listing notification', 'notify email', 'WPBDM' ), get_bloginfo( 'name' ) );
        $email->to[] = get_bloginfo( 'admin_email' );
        $email->body = $message;
        $email->send();
    }

    // sets the default settings to listings created through the admin site
    public function set_default_listing_settings($post_id) {
        $fees_api = wpbdp_fees_api();       
        $payments_api = wpbdp_payments_api();

        // if has not initial transaction, create one
        if (!$payments_api->get_transactions($post_id)) {
            $payments_api->save_transaction(array(
                'amount' => 0.0,
                'payment_type' => 'initial',
                'listing_id' => $post_id,
                'processed_by' => 'admin'
            ));
        }

        // assign a fee to all categories
        $post_categories = wp_get_post_terms($post_id, wpbdp_categories_taxonomy());

        foreach ($post_categories as $category) {
            if ($fee = $this->get_listing_fee_for_category($post_id, $category->term_id)) {
                // do nothing
            } else {
                // assign a fee
                $choices = $fees_api->get_fees_for_category($category->term_id);
                $this->assign_fee($post_id, $category->term_id, $choices[0], $false);
            }
        }

    }

    public function assign_fee($listing_id, $category_id, $fee_id, $charged=false) {
        global $wpdb;

        wp_set_post_terms( $listing_id, array( intval( $category_id ) ), wpbdp_categories_taxonomy(), true );

        $fee = is_object($fee_id) ? $fee_id : wpbdp_fees_api()->get_fee_by_id($fee_id);
        if ($fee) {
            if ($fee->categories['all'] || count(array_intersect(wpbdp_get_parent_catids($category_id), $fee->categories['categories'])) > 0) {
                $wpdb->query($wpdb->prepare("DELETE FROM {$wpdb->prefix}wpbdp_listing_fees WHERE listing_id = %d AND category_id = %d", $listing_id, $category_id));

                $feerow = array(
                    'listing_id' => $listing_id,
                    'category_id' => $category_id,
                    'fee' => serialize((array) $fee),
                    'charged' => $charged ? 1 : 0
                );

                $expiration_date = $this->calculate_expiration_date(time(), $fee);
                if ($expiration_date != null)
                    $feerow['expires_on'] = $expiration_date;

                // wpbdp_debug_e($feerow);

                $wpdb->insert($wpdb->prefix . 'wpbdp_listing_fees', $feerow);

                return true;
            }
        }

        return false;
    }

    public function get_thumbnail_id($listing_id) {
        if ($thumbnail_id = get_post_meta($listing_id, '_wpbdp[thumbnail_id]', true)) {
            return intval($thumbnail_id);
        } else {
            if ($images = $this->get_images($listing_id)) {
                update_post_meta($listing_id, '_wpbdp[thumbnail_id]', $images[0]->ID);
                return $images[0]->ID;
            }
        }
        
        return 0;
    }

    public function get_images($listing_id) {
        $attachments = get_posts(array(
            'numberposts' => -1,
            'post_type' => 'attachment',
            'post_parent' => $listing_id
        ));

        $result = array();

        foreach ($attachments as $attachment) {
            if (wp_attachment_is_image($attachment->ID))
                $result[] = $attachment;
        }

        return $result;
    }

    public function get_allowed_images($listing_id) {
        $images = 0;
        
        foreach ($this->get_listing_fees($listing_id) as $fee) {
            $fee_ = unserialize($fee->fee);
            $images += intval($fee_['images']);
        }

        return $images;
    }

    public function get_payment_status($listing_id) {
        if ($payment_status = get_post_meta($listing_id, '_wpbdp[payment_status]', true))
            return $payment_status;

        return 'not-paid';
    }

    public function set_payment_status($listing_id, $status='not-paid') {
        // global $wpdb;

        // if ($last_transaction = wpbdp_payments_api()->get_last_transaction($listing_id)) {
        //  $last_transaction->processed_on = current_time('mysql');
        //  $last_transaction->processed_by = 'admin';
        //  $last_transaction->status = ($status == 'paid') ? 'approved' : 'rejected';
        //  wpbdp_payments_api()->save_transaction($last_transaction);
        //  return true;
        // }

        // return false;

        update_post_meta($listing_id, '_wpbdp[payment_status]', $status);
        return true;
    }

    public function get_listing_fees($listing_id) {
        global $wpdb;
        return $wpdb->get_results($wpdb->prepare("SELECT * FROM {$wpdb->prefix}wpbdp_listing_fees WHERE listing_id = %d", $listing_id));
    }

    // effective_cost means do not include already paid fees
    public function cost_of_listing($listing_id, $effective_cost=false) {
        if (is_object($listing_id)) return $this->cost_of_listing($listing_id->ID);

        global $wpdb;

        $cost = 0.0;

        if ($fees = $wpdb->get_col($wpdb->prepare("SELECT fee FROM {$wpdb->prefix}wpbdp_listing_fees WHERE listing_id = %d" . ($effective_cost ? ' AND charged = 1' : ''), $listing_id))) {
            foreach ($fees as &$fee) {
                $fee = unserialize($fee);
                $cost += floatval($fee['amount']);
            }
        }

        return round($cost, 2);
    }

    // TODO revisar que tampoco hayan transacciones pendientes
    public function is_free_listing($listing_id) {
        return $this->cost_of_listing($listing_id) == 0.0;
    }

    public function get_expiration_time($listing_id, $fee) {
        if (is_array($fee)) return $this->get_expiration_time($listing_id, (object) $fee);

        if ($fee->days == 0)
            return null;

        $start_time = get_post_time('U', false, $listing_id);
        $expire_time = strtotime(sprintf('+%d days', $fee->days), $start_time);
        return $expire_time;
    }

    public function get_listing_fee_for_category($listing_id, $catid) {
        global $wpdb;

        $row = $wpdb->get_row($wpdb->prepare("SELECT * FROM {$wpdb->prefix}wpbdp_listing_fees WHERE listing_id = %d AND category_id = %d", $listing_id, $catid));

        if ($row != null) {
            $fee = unserialize($row->fee);
            $fee['expires_on'] = $row->expires_on;
            return (object) $fee;
        }

        return null;
    }

    /*
     * Featured listings.
     */

    public function get_stickies() {
        global $wpdb;

        $stickies = $wpdb->get_col($wpdb->prepare("SELECT DISTINCT post_id FROM {$wpdb->postmeta} WHERE meta_key = %s AND meta_value = %s",
                                             '_wpbdp[sticky]',
                                             'sticky'));

        return $stickies;
    }

    // TODO: deprecate (move to ListingUpgrades)
    public function get_sticky_status($listing_id) {
        if ($sticky_status = get_post_meta($listing_id, '_wpbdp[sticky]', true)) {
            return $sticky_status;
        }

        return 'normal';
    }

    public function calculate_expiration_time($time, $fee) {
        if ($fee->days == 0)
            return null;

        $expire_time = strtotime(sprintf('+%d days', $fee->days), $time);
        return $expire_time;
    }
            // $start_time = get_post_time('U', false, $listing_id);

    public function calculate_expiration_date($time, $fee) {
        if ($expire_time = $this->calculate_expiration_time($time, $fee))
            return date('Y-m-d H:i:s', $expire_time);
        
        return null;
    }

    public function renew_listing($renewal_id, $fee) {
        global $wpdb;

        if ($renewal = $wpdb->get_row($wpdb->prepare("SELECT * FROM {$wpdb->prefix}wpbdp_listing_fees WHERE id = %d AND expires_on IS NOT NULL AND expires_on < %s", $renewal_id, current_time('mysql')))) {
            if ( !has_term($renewal->category_id, wpbdp_categories_taxonomy(), $renewal->listing_id) ) {
                // set payment status to not-paid
                update_post_meta($renewal->listing_id, '_wpbdp[payment_status]', 'not-paid');

                // register the new transaction
                $transaction_id = wpbdp_payments_api()->save_transaction(array(
                    'listing_id' => $renewal->listing_id,
                    'amount' => $fee->amount,
                    'payment_type' => 'renewal',
                    'extra_data' => serialize(array('renewal_id' => $renewal_id, 'fee' => $fee))
                ));

                return $transaction_id;
            }
        }

        return 0;
    }

    public function add_listing($data_, &$transaction_id=null) {
        global $wpdb;

        $data = is_object($data_) ? (array) $data_ : $data_;
        $editing = isset($data['listing_id']) && $data['listing_id'];

        $listingfields = $data['fields'];

        $api = wpbdp_formfields_api();

        $listing_id = wp_insert_post( array(
            'post_title' => 'Untitled Listing',
            'post_status' => $editing ? wpbdp_get_option( 'edit-post-status' ) : 'pending',
            'post_type' => wpbdp_post_type(),
            'ID' => $editing ? intval( $data['listing_id'] ) : null
        ) );

        if ( !$editing ) {
            $current_user = wp_get_current_user();

            if ( $current_user->ID == 0 ) {
                if ( wpbdp_get_option( 'require-login' ) ) {
                    exit;
                }
                // create user
                if ( $email_field = $api->find_fields( array( 'validator' => 'email' ), true ) ) {
                    $email = $listingfields[ $email_field->get_id() ];
                    
                    if ( email_exists( $email ) ) {
                        $post_author = get_user_by_email( $email )->ID;
                    } else {
                        $randvalue = wpbdp_generate_password( 5, 2 );
                        $post_author = wp_insert_user( array(
                            'display_name' => 'Guest ' . $randvalue,
                            'user_login' => 'guest_' . $randvalue,
                            'user_email' => $email,
                            'user_pass' => wpbdp_generate_password( 7, 2 )
                        ) );
                    }

                    wp_update_post( array( 'ID' => $listing_id, 'post_author' => $post_author ) );
                }
            }
        }

        // store field values
        $formfields = wpbdp_get_form_fields();
        foreach ( $formfields as $field ) {
            if ( isset( $listingfields[ $field->get_id() ] ) ) {
                $field->store_value( $listing_id, $listingfields[ $field->get_id() ] );
            } else {
                $field->store_value( $listing_id, $field->convert_input( null ) );
            }
        }

        // attach images
        if (isset($data['images']) && $data['images']) {
            foreach ($data['images'] as $image_id) {
                wp_update_post(array('ID' => $image_id,
                                     'post_parent' => $listing_id));
            }

            if (isset($data['thumbnail_id']) && $data['thumbnail_id']) {
                update_post_meta($listing_id, '_wpbdp[thumbnail_id]', $data['thumbnail_id']);
            } else {
                update_post_meta($listing_id, '_wpbdp[thumbnail_id]', $data['images'][0]);
            }
        }

        // register fee information
        if (!isset($data['fees'])) $data['fees'] = array();

        $post_categories = wp_get_post_terms( $listing_id, wpbdp_categories_taxonomy(), 'fields=ids' );
        
        foreach ( $post_categories as $catid ) {
            $fee = (array) ( isset( $data['fees'][ $catid ] ) ? $data['fees'][ $catid ] : wpbdp_fees_api()->get_free_fee() );
            $fee['category_id'] = $catid;
            unset( $fee['categories'], $fee['extra_data'] );

            if ( isset( $fee['_nocharge'] ) && $fee['_nocharge'] == true ) {
                $wpdb->update( $wpdb->prefix . 'wpbdp_listing_fees', array( 'charged' => 0 ), array( 'listing_id' => $listing_id,
                                                                                                     'category_id' => $catid ) );
            } else {
                $this->assign_fee( $listing_id, $catid, $fee['id'], true );
            }
        }
        if ( $post_categories )
            $wpdb->query( $wpdb->prepare( "DELETE FROM {$wpdb->prefix}wpbdp_listing_fees WHERE listing_id = %d AND category_id NOT IN (" . join( ',', $post_categories ) . ")", $listing_id ) );

        // register payment info
        $cost = $this->cost_of_listing( $listing_id, true );

        if ( isset( $data['upgrade-listing'] ) && $data['upgrade-listing'] ) {
            $upgrades_api = wpbdp_listing_upgrades_api();
            $upgrades_api->set_sticky( $listing_id, 'sticky' );

            $level = $upgrades_api->get( 'sticky' );
            $cost += $level->cost;
        }

        $payment_api = wpbdp_payments_api();
        $transaction_id = $payment_api->save_transaction( array(
            'amount' => $cost,
            'payment_type' => !$editing ? 'initial' : 'edit',
            'listing_id' => $listing_id
        ) );
        update_post_meta( $listing_id, '_wpbdp[payment_status]', !current_user_can( 'administrator' ) && ( $cost > 0.0 ) ? 'not-paid' : 'paid' );

        if ( !$cost || current_user_can( 'administrator' ) ) {
            wp_update_post( array( 'ID' => $listing_id, 'post_status' => wpbdp_get_option( 'new-post-status' ) ) );
        }

        do_action( 'wpbdp_add_listing', $listing_id, $listingfields );


        if ( !$editing )
            do_action( 'wpbdp_create_listing', $listing_id, $listingfields );
        else
            do_action( 'wpbdp_edit_listing', $listing_id, $listingfields );

        do_action( 'wpbdp_save_listing', $listing_id, $listingfields );

        return $listing_id;
    }

    /* listings search */
    public function search($args) {
        global $wpdb;

        $term = str_replace('*', '', trim(wpbdp_getv($args, 'q', '')));

        if (!$term && (!isset($args['fields']) || !$args['fields']) && (!isset($args['extra']) || !$args['extra']) )
            return array();

        $query = "SELECT DISTINCT ID FROM {$wpdb->posts}";
        $where = $wpdb->prepare("{$wpdb->posts}.post_type = %s AND {$wpdb->posts}.post_status = %s",
                                wpbdp_post_type(), 'publish');

        if ($term) {
            // process term
            $where .= $wpdb->prepare(" AND ({$wpdb->posts}.post_title LIKE '%%%s%%' OR {$wpdb->posts}.post_content LIKE '%%%s%%' OR {$wpdb->posts}.post_excerpt LIKE '%%%s%%')", $term, $term, $term);
        }

        if (isset($args['fields'])) {
            foreach ($args['fields'] as $i => $meta_search) {

                if ( $field = wpbdp_get_formfield( $meta_search['field_id'] ) ) {
                    $q = is_array( $meta_search['q'] ) ? array_map( 'trim', $meta_search['q'] ) : trim( $meta_search['q'] );

                    if (!$q) continue;

                    switch ( $field->get_association() ) {
                        case 'title':
                            $where .= $wpdb->prepare(" AND {$wpdb->posts}.post_title LIKE '%%%s%%'", $q);
                            break;
                        case 'content':
                            $where .= $wpdb->prepare(" AND {$wpdb->posts}.post_content LIKE '%%%s%%'", $q);
                            break;
                        case 'excerpt':
                            $where .= $wpdb->prepare(" AND {$wpdb->posts}.post_excerpt LIKE '%%%s%%'", $q);
                            break;
                        case 'category':
                            $term_ids = array_diff( is_array($q) ? $q : array($q), array('-1', '0') ) ;
                            $terms = array();

                            // $term_ids = implode(',',  array_diff($term_ids, array('-1', '0')) );

                            foreach ( $term_ids as $tid ) {
                                $terms[] = $tid;
                                $terms = array_merge( $terms, get_term_children( $tid, wpbdp_categories_taxonomy() ) );
                            }

                             if ($terms) {
                                $query .= " LEFT JOIN {$wpdb->term_relationships} AS trel1 ON ({$wpdb->posts}.ID = trel1.object_id) LEFT JOIN {$wpdb->term_taxonomy} AS ttax1 ON (trel1.term_taxonomy_id = ttax1.term_taxonomy_id)";
                                $where .= " AND ttax1.term_id IN (" . implode( ',', $terms ) . ") ";
                            }

                            break;
                        case 'tags':
                            $terms = is_array($q) ? array_values($q) : explode(',', $q);
                            $term_ids = array();

                            foreach ($terms as $term_name) {
                                $term = null;

                                if ( $term_name == -1 || $term_name == 0 )
                                    continue;

                                if ( is_numeric( $term_name ) )
                                    $term = get_term_by( 'id', $term_name, WPBDP_TAGS_TAX );

                                if ( !$term )
                                    $term = get_term_by( 'name', $term_name, WPBDP_TAGS_TAX );

                                if ( $term ) {
                                    $term_ids[] = $term->term_id;
                                } else {
                                    $where .= ' AND 1=0'; // force no results when a tag does not exist
                                }
                            }                       

                            if ($term_ids) {
                                $term_ids = implode(',', $term_ids);
                                $query .= " LEFT JOIN {$wpdb->term_relationships} AS trel2 ON ({$wpdb->posts}.ID = trel2.object_id) LEFT JOIN {$wpdb->term_taxonomy} AS ttax2 ON (trel2.term_taxonomy_id = ttax2.term_taxonomy_id)";
                                $where .= " AND ttax2.term_id IN ({$term_ids}) ";
                            }

                            break;
                        case 'meta':
                            if (in_array($field->get_field_type()->get_id(), array('checkbox', 'multiselect', 'select'))) { // multivalued field
                                $options = array_diff(is_array($q) ? $q : array($q), array(''));
                                
                                $pattern = '(' . implode('|', $options) . '){1}([tab]{0,1})';

                                $query .= " INNER JOIN {$wpdb->postmeta} AS mt{$i}mv ON ({$wpdb->posts}.ID = mt{$i}mv.post_id)";
                                $where .= $wpdb->prepare(" AND (mt{$i}mv.meta_key = %s AND mt{$i}mv.meta_value REGEXP %s)",
                                                         "_wpbdp[fields][" . $field->get_id() . "]",
                                                         $pattern);
                            } else { // single-valued field
                                $query .= sprintf(" INNER JOIN {$wpdb->postmeta} AS mt%1$1d ON ({$wpdb->posts}.ID = mt%1$1d.post_id)", $i);
                                $where .= $wpdb->prepare(" AND (mt{$i}.meta_key = %s AND mt{$i}.meta_value LIKE '%%%s%%')",
                                                         '_wpbdp[fields][' . $field->get_id() . ']',
                                                         $q);
                            }

                            break;
                        default:
                            break;
                    }
                }
            
            }
        }

        $query .= ' WHERE ' . apply_filters('wpbdp_search_where', $where, $args);

        wpbdp_debug($query);

        return $wpdb->get_col($query);
    }

}

}