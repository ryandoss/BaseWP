<?php
/*
 * General directory views
 */

if (!class_exists('WPBDP_DirectoryController')) {

class WPBDP_DirectoryController {

    public function __construct() {
        add_action( 'wp', array( $this, '_handle_action'), 10, 1 );
        $this->_extra_sections = array();
    }

    public function init() {
        $this->listings = wpbdp_listings_api();
    }

    public function check_main_page(&$msg) {
        $msg = '';

        $wpbdp = wpbdp();
        if (!$wpbdp->_config['main_page']) {
            if (current_user_can('administrator') || current_user_can('activate_plugins'))
                $msg = __('You need to create a page with the [businessdirectory] shortcode for the Business Directory plugin to work correctly.', 'WPBDM');
            else
                $msg = __('The directory is temporarily disabled.', 'WPBDM');
            return false;
        }

        return true;
    }

    public function _handle_action(&$wp) {
        if ( is_page() && get_the_ID() == wpbdp_get_page_id( 'main' ) ) {
            $action = get_query_var('action') ? get_query_var('action') : ( isset( $_REQUEST['action'] ) ? $_REQUEST['action'] : '' );

            if (get_query_var('category_id') || get_query_var('category')) $action = 'browsecategory';
            if (get_query_var('tag')) $action = 'browsetag';
            if (get_query_var('id') || get_query_var('listing')) $action = 'showlisting';

            if (!$action) $action = 'main';

            $this->action = $action;
        } else {
            $this->action = null;
        }
    }

    public function get_current_action() {
        return $this->action;
    }

    public function dispatch() {
        switch ($this->action) {
            case 'showlisting':
                return $this->show_listing();
                break;
            case 'browsecategory':
                return $this->browse_category();
                break;
            case 'browsetag':
                return $this->browse_tag();
                break;
            case 'editlisting':
            case 'submitlisting':
                return $this->submit_listing();
                break;
            case 'sendcontactmessage':
                return $this->send_contact_message();
                break;
            case 'deletelisting':
                return $this->delete_listing();
                break;
            case 'upgradetostickylisting':
                return $this->upgrade_to_sticky();
                break;
            case 'viewlistings':
                return $this->view_listings(true);
                break;
            case 'renewlisting':
                return $this->renew_listing();
                break;
            case 'payment-process':
                return $this->process_payment();
                break;
            case 'search':
                return $this->search();
                break;
            default:
                return $this->main_page();
                break;
        }
    }

    /* Show listing. */
    public function show_listing() {
        if (!$this->check_main_page($msg)) return $msg;

        if (get_query_var('listing') || isset($_GET['listing'])) {
            if ($posts = get_posts(array('post_status' => 'publish', 'numberposts' => 1, 'post_type' => wpbdp_post_type(), 'name' => get_query_var('listing') ? get_query_var('listing') : wpbdp_getv($_GET, 'listing', null) ) )) {
                $listing_id = $posts[0]->ID;
            } else {
                $listing_id = null;
            }
        } else {
            $listing_id = get_query_var('id') ? get_query_var('id') : wpbdp_getv($_GET, 'id', null);
        }

        if ($listing_id)
            return wpbdp_render_listing($listing_id, 'single');
    }

    /* Display category. */
    public function browse_category($category_id=null) {
        if (!$this->check_main_page($msg)) return $msg;

        if (get_query_var('category')) {
            if ($term = get_term_by('slug', get_query_var('category'), wpbdp_categories_taxonomy())) {
                $category_id = $term->term_id;
            } else {
                $category_id = intval(get_query_var('category'));
            }
        }

        $category_id = $category_id ? $category_id : intval(get_query_var('category_id'));

        $listings_api = wpbdp_listings_api();

        query_posts(array(
            'post_type' => wpbdp_post_type(),
            'post_status' => 'publish',
            'posts_per_page' => 0,
            'paged' => get_query_var('paged') ? get_query_var('paged') : 1,
            'orderby' => wpbdp_get_option('listings-order-by', 'date'),
            'order' => wpbdp_get_option('listings-sort', 'ASC'),
            'tax_query' => array(
                array('taxonomy' => wpbdp_categories_taxonomy(),
                      'field' => 'id',
                      'terms' => $category_id)
            )
        ));

        $html = wpbdp_render( 'category',
                             array(
                                'category' => get_term( $category_id, WPBDP_CATEGORY_TAX ),
                                'is_tag' => false
                                ),
                             false );

        wp_reset_query();

        return $html;
    }

    /* Display category. */
    public function browse_tag() {
        if (!$this->check_main_page($msg)) return $msg;

        $tag = get_term_by('slug', get_query_var('tag'), WPBDP_TAGS_TAX);
        $tag_id = $tag->term_id;

        $listings_api = wpbdp_listings_api();

        query_posts(array(
            'post_type' => wpbdp_post_type(),
            'post_status' => 'publish',
            'posts_per_page' => 0,
            'paged' => get_query_var('paged') ? get_query_var('paged') : 1,
            'orderby' => wpbdp_get_option('listings-order-by', 'date'),
            'order' => wpbdp_get_option('listings-sort', 'ASC'),
            'tax_query' => array(
                array('taxonomy' => WPBDP_TAGS_TAX,
                      'field' => 'id',
                      'terms' => $tag_id)
            )
        ));

        $html = wpbdp_render( 'category',
                             array(
                                'category' => get_term( $tag_id, WPBDP_TAGS_TAX ),
                                'is_tag' => true
                                ),
                             false );        

        wp_reset_query();

        return $html;
    }    

    /* display listings */
    public function view_listings($include_buttons=false) {
        $paged = 1;

        if (get_query_var('page'))
            $paged = get_query_var('page');
        elseif (get_query_var('paged'))
            $paged = get_query_var('paged');

        query_posts(array(
            'post_type' => wpbdp_post_type(),
            'posts_per_page' => 0,
            'post_status' => 'publish',
            'paged' => intval($paged),
            'orderby' => wpbdp_get_option('listings-order-by', 'date'),
            'order' => wpbdp_get_option('listings-sort', 'ASC')
        ));

        $html = wpbdp_capture_action( 'wpbdp_before_viewlistings_page' );
        $html .= wpbdp_render('businessdirectory-listings', array(
                'excludebuttons' => !$include_buttons
            ), true);
        $html .= wpbdp_capture_action( 'wpbdp_after_viewlistings_page' );

        wp_reset_query();

        return $html;
    }

    /*
     * Send contact message to listing owner.
     */
    public function send_contact_message() {
        if ($listing_id = wpbdp_getv($_REQUEST, 'listing_id', 0)) {
            $current_user = is_user_logged_in() ? wp_get_current_user() : null;

            $author_name = htmlspecialchars(trim(wpbdp_getv($_POST, 'commentauthorname', $current_user ? $current_user->data->user_login : '')));
            $author_email = trim(wpbdp_getv($_POST, 'commentauthoremail', $current_user ? $current_user->data->user_email : ''));
            $message = trim(wp_kses(stripslashes(wpbdp_getv($_POST, 'commentauthormessage', '')), array()));

            $validation_errors = array();

            if (!$author_name)
                $validation_errors[] = _x("Please enter your name.", 'contact-message', "WPBDM");

            if ( !wpbdp_validate_value( $author_email, 'email' ) )
                $validation_errors[] = _x("Please enter a valid email.", 'contact-message', "WPBDM");

            if (!$message)
                $validation_errors[] = _x('You did not enter a message.', 'contact-message', 'WPBDM');

            if (wpbdp_get_option('recaptcha-on')) {
                if ($private_key = wpbdp_get_option('recaptcha-private-key')) {
                    require_once(WPBDP_PATH . 'recaptcha/recaptchalib.php');

                    $resp = recaptcha_check_answer($private_key, $_SERVER['REMOTE_ADDR'], $_POST['recaptcha_challenge_field'], $_POST['recaptcha_response_field']);
                    if (!$resp->is_valid)
                        $validation_errors[] = sprintf(_x("The reCAPTCHA wasn't entered correctly: %s", 'contact-message', 'WPBDM'), $resp->error);
                }
            }

            if (!$validation_errors) {
                $headers =  "MIME-Version: 1.0\n" .
                        "From: $author_name <$author_email>\n" .
                        "Reply-To: $author_email\n" .
                        "Content-Type: text/plain; charset=\"" . get_option('blog_charset') . "\"\n";

                $subject = "[" . get_option( 'blogname' ) . "] " . sprintf(_x('Contact via "%s"', 'contact email', 'WPBDM'), wp_kses( get_the_title($listing_id), array() ));
                $wpbdmsendtoemail=wpbusdirman_get_the_business_email($listing_id);
                $time = date_i18n( __('l F j, Y \a\t g:i a'), current_time( 'timestamp' ) );

                $body = wpbdp_render_page(WPBDP_PATH . 'templates/parts/contact.email', array(
                    'listing_url' => get_permalink($listing_id),
                    'name' => $author_name,
                    'email' => $author_email,
                    'message' => $message,
                    'time' => $time
                ), false);

                $html = '';

                // TODO: should use WPBDP_Email instead
                if(wp_mail( $wpbdmsendtoemail, $subject, $body, $headers )) {
                    $html .= "<p>" . _x("Your message has been sent.", 'contact-message', "WPBDM") . "</p>";
                } else {
                    $html .= "<p>" . _x("There was a problem encountered. Your message has not been sent", 'contact-message', "WPBDM") . "</p>";
                }

                $html .= sprintf('<p><a href="%s">%s</a></p>', get_permalink($listing_id), _x('Return to listing.', 'contact-message', "WPBDM"));

                return $html;
            } else {
                return wpbdp_listing_contact_form( $listing_id, $validation_errors );
            }
        }
    }

    /*
     * Directory views/actions
     */
    public function main_page() {
        $html = '';

        if ( count(get_terms(wpbdp_categories_taxonomy(), array('hide_empty' => 0))) == 0 ) {
            if (is_user_logged_in() && current_user_can('install_plugins')) {
                $html .= "<p>" . _x('There are no categories assigned to the business directory yet. You need to assign some categories to the business directory. Only admins can see this message. Regular users are seeing a message that there are currently no listings in the directory. Listings cannot be added until you assign categories to the business directory.', 'templates', 'WPBDM') . "</p>";
            } else {
                $html .= "<p>" . _x('There are currently no listings in the directory.', 'templates', 'WPBDM') . "</p>";
            }
        }

        if (current_user_can('administrator')) {
            if ($errors = wpbdp_payments_api()->check_config()) {
                foreach ($errors as $error) {
                    $html .= wpbdp_render_msg($error, 'error');
                }
            }
        }        

        $listings = '';
        if (wpbdp_get_option('show-listings-under-categories'))
            $listings = $this->view_listings(false);

        $html .= wpbdp_render(array('businessdirectory-main-page', 'wpbusdirman-index-categories'),
                               array(
                                'submit_listing_button' => wpbusdirman_post_menu_button_submitlisting(),
                                'view_listings_button' => wpbusdirman_post_menu_button_viewlistings(),
                                'action_links' => wpbusdirman_post_menu_button_submitlisting() . wpbusdirman_post_menu_button_viewlistings(),
                                'search_form' => wpbdp_get_option('show-search-listings') ? wpbdp_search_form() : '',
                                'listings' => $listings
                               ));

        return $html;
    }


    /*
     * Submit listing process.
     */

    /*
     * @since 2.1.6
     */
    public function register_listing_form_section($id, $section=array()) {
        $section = array_merge( array(
            'title' => '',
            'display' => null,
            'process' => null,
            'save' => null
        ), $section);

        if (!$section['display'] && !$section['process'])
            return false;

        $section['id'] = $id;
        $this->_extra_sections[$id] = (object) $section;

        return true;
    }

    // TODO login is required for edits
    public function submit_listing($listing_id=null) {
        if (!$this->check_main_page($msg)) return $msg;

        $no_categories_msg = false;

        if (count(get_terms(wpbdp_categories_taxonomy(), array('hide_empty' => false))) == 0) {
            if (is_user_logged_in() && current_user_can('install_plugins')) {
                return wpbdp_render_msg(_x('There are no categories assigned to the business directory yet. You need to assign some categories to the business directory. Only admins can see this message. Regular users are seeing a message that they cannot add their listing at this time. Listings cannot be added until you assign categories to the business directory.', 'templates', 'WPBDM'), 'error');
            } else {
                return wpbdp_render_msg(_x('Your listing cannot be added at this time. Please try again later.', 'templates', 'WPBDM'), 'error');
            }
        }

        if (wpbdp_get_option('require-login') && !is_user_logged_in())
            return wpbdp_render('parts/login-required', array(), false);

        $step = wpbdp_getv($_POST, '_step', 'fields');
        $this->_listing_data = array('listing_id' => 0,
                                     'fields' => array(),
                                     'fees' => array(),
                                     'images' => array(),
                                     'thumbnail_id' => 0);

        if (isset($_POST['listing_data'])) {
            $this->_listing_data = unserialize(base64_decode($_POST['listing_data']));
        } else {
            if (isset($_POST['listingfields'])) {
                foreach ( $_POST['listingfields'] as $field_id => $form_value ) {
                    $this->_listing_data['fields'][ $field_id ] = WPBDP_FormField::get( $field_id )->convert_input( $form_value );
                }
            }

            if (isset($_REQUEST['listing_id']))
                $this->_listing_data['listing_id'] = intval($_REQUEST['listing_id']);
        }

        if ($listing_id = $this->_listing_data['listing_id']) {
            $current_user = wp_get_current_user();
            if ( (get_post($listing_id)->post_author != $current_user->ID) && (!current_user_can('administrator')) )
                return wpbdp_render_msg(_x('You are not authorized to edit this listing.', 'templates', 'WPBDM'), 'error');

            if (wpbdp_payment_status($listing_id) != 'paid' && !current_user_can('administrator')) {
                $html  = '';
                $html .= wpbdp_render_msg(_x('You can not edit your listing until its payment has been cleared.', 'templates', 'WPBDM'), 'error');
                $html .= sprintf('<a href="%s">%s</a>', get_permalink($this->_listing_data['listing_id']), _x('Return to listing.', 'templates', 'WPBDM'));
                return $html;                
            }
        }

        $html = '';

        if (current_user_can('administrator')) {
            if ($errors = wpbdp_payments_api()->check_config()) {
                foreach ($errors as $error) {
                    $html .= wpbdp_render_msg($error, 'error');
                }
            }

            $html .= wpbdp_render_msg(_x('You are logged in as an administrator. Any payment steps will be skipped.', 'templates', 'WPBDM'));
        }

        $html .= call_user_func(array($this, 'submit_listing_' . $step), $listing_id);
        $html .= apply_filters('wpbdp_listing_form', '', $this->_listing_data['listing_id']);

        return $html;
    }

    public function submit_listing_fields() {
        unset( $_SESSION['wpbdp-submitted-listing-id'] );

        $formfields_api = wpbdp_formfields_api();

        $post_values = isset( $_POST['listingfields'] ) ? $_POST['listingfields'] : array();
        $post_values = stripslashes_deep( $post_values );
        $validation_errors = array();

        $fields = array();
        $ffields = wpbdp_get_form_fields();
        $ffields = apply_filters_ref_array( 'wpbdp_get_form_fields', array( &$ffields, $this->_listing_data['listing_id'] ) ); // TODO: move this to wpbpd_get_form_fields() ?

        foreach ( $ffields as &$field ) {
            $field_value = isset( $post_values[$field->get_id()] ) ? $field->convert_input( $post_values[$field->get_id()] ) : ( $this->_listing_data['listing_id'] ? $field->value( intval( $this->_listing_data['listing_id'] )  ) : $field->convert_input( null ) );

            if ( $post_values ) {
                $field_errors = null;

                $validate_res = $field->validate( $field_value, $field_errors);
                $validate_res = apply_filters_ref_array( 'wpbdp_form_field_validate', array( $validate_res, &$field_errors, &$field, $field_value, $this->_listing_data['listing_id'] ) );

                if ( !$validate_res )
                    $validation_errors = array_merge( $validation_errors, $field_errors );
            }

            $fields[] = array( 'field' => $field,
                               'value' => $field_value,
                               'html'  => $field->render( $field_value, 'submit' ) );
        }

        if ( wpbdp_get_option('recaptcha-for-submits') ) {
            if ( $private_key = wpbdp_get_option( 'recaptcha-private-key' ) ) {
                if ( isset( $_POST['recaptcha_challenge_field'] ) ) {
                    require_once( WPBDP_PATH . 'recaptcha/recaptchalib.php' );

                    $resp = recaptcha_check_answer( $private_key, $_SERVER['REMOTE_ADDR'], $_POST['recaptcha_challenge_field'], $_POST['recaptcha_response_field'] );
                    if (!$resp->is_valid)
                        $validation_errors[] = _x( "The reCAPTCHA wasn't entered correctly.", 'templates', 'WPBDM' );
                }
            }
        }

        // if there are values POSTed and everything validates, move on
        if ( $post_values && !$validation_errors ) {
            return $this->submit_listing_payment();
        }

        $recaptcha = null;
        if ( wpbdp_get_option('recaptcha-for-submits') ) {
            if ( $public_key = wpbdp_get_option( 'recaptcha-public-key' ) ) {
                require_once( WPBDP_PATH . 'recaptcha/recaptchalib.php' );
                $recaptcha = recaptcha_get_html( $public_key );
            }
        }

        return wpbdp_render( 'listing-form-fields', array(
                             'validation_errors' => $validation_errors,
                             'listing_id' => $this->_listing_data['listing_id'],
                             'fields' => $fields,
                             'recaptcha' => $recaptcha
                            ), false );
    }

    private function edit_listing_payment() {
        $listing_id = $this->_listing_data['listing_id'];

        $api = wpbdp_formfields_api();
        
        $category_field = $api->find_fields( array( 'association' => 'category' ), true );
        $post_categories = $this->_listing_data['fields'][ $category_field->get_id() ];

        // if categories are the same, move on
        $previous_categories = wp_get_post_terms( $listing_id, wpbdp_categories_taxonomy(), array( 'fields' => 'ids' ) );

        $new_categories = array();
        foreach ( $post_categories as $catid ) {
            if ( !in_array($catid, $previous_categories) ) {
                $new_categories[] = $catid;
            } else {
                $fee = wpbdp_listings_api()->get_listing_fee_for_category($listing_id, $catid);
                $fee->_nocharge = true;
                $this->_listing_data['fees'][$catid] = $fee;
            }
        }

        if (!$new_categories)
            return $this->submit_listing_images();

        // there are new categories, show the fee options
        $available_fees = wpbdp_fees_api()->get_fees($new_categories);
        $fees = array();

        foreach ($available_fees as $catid => $fee_options) {
            $fees[] = array('category' => get_term($catid, wpbdp_categories_taxonomy()),
                            'fees' => $fee_options);
        }

        $validation_errors = array();

        // check every category has a fee selected
        if ($_POST['_step'] == 'payment') {
            $post_fees = wpbdp_getv($_POST, 'fees', array());

            foreach ($new_categories as $catid) {
                $selected_fee_option = wpbdp_getv($post_fees, $catid, null);

                // TODO: check fee is a valid fee for the given category (check $available_fees[$catid] for the id)
                if ($selected_fee_option == null || !isset($available_fees[$catid])) {
                    $validation_errors[] = sprintf(_x('Please select a fee option for the "%s" category.', 'templates', 'WPBDM'), get_term($catid, wpbdp_categories_taxonomy())->name);
                }
            }

            if (!$validation_errors) {
                foreach ($new_categories as $catid) {
                    $this->_listing_data['fees'][$catid] = wpbdp_fees_api()->get_fee_by_id(wpbdp_getv($post_fees, $catid));
                }

                return $this->submit_listing_images();
            }
        }

        return wpbdp_render('listing-form-fees', array(
                            'validation_errors' => $validation_errors,
                            'listing_id' => $this->_listing_data['listing_id'],
                            'listing_data' => $this->_listing_data,
                            'fee_options' => $fees,
                            ), false);
    }

    public function submit_listing_payment() {
        if ($this->_listing_data['listing_id'])
            return $this->edit_listing_payment();

        $api = wpbdp_formfields_api();

        $category_field = $api->find_fields( array( 'association' => 'category' ), true );
        $post_categories = $this->_listing_data['fields'][ $category_field->get_id() ];

        $available_fees = wpbdp_fees_api()->get_fees($post_categories);
        $fees = array();

        foreach ($available_fees as $catid => $fee_options) {
            $fees[] = array('category' => get_term($catid, wpbdp_categories_taxonomy()),
                            'fees' => $fee_options);
        }

        // if all fees are free-fees move on
        foreach ($fees as $fee_option) {
            if (count($fee_option['fees']) == 1 && $fee_option['fees'][0]->id == 0) {
                $this->_listing_data['fees'][$fee_option['category']->term_id] = wpbdp_fees_api()->get_fee_by_id(0);
                return $this->submit_listing_images();
            }
        }

        $validation_errors = array();

        // check every category has a fee selected
        if ($_POST['_step'] == 'payment') {
            $post_fees = wpbdp_getv($_POST, 'fees', array());

            foreach ($post_categories as $catid) {
                $selected_fee_option = wpbdp_getv($post_fees, $catid, null);

                // TODO: check fee is a valid fee for the given category (check $available_fees[$catid] for the id)
                if ($selected_fee_option == null || !isset($available_fees[$catid])) {
                    $validation_errors[] = sprintf(_x('Please select a fee option for the "%s" category.', 'templates', 'WPBDM'), get_term($catid, wpbdp_categories_taxonomy())->name);
                }
            }

            if (!$validation_errors) {
                foreach ($post_categories as $catid) {
                    $this->_listing_data['fees'][$catid] = wpbdp_fees_api()->get_fee_by_id(wpbdp_getv($post_fees, $catid));
                }

                if ( isset( $_POST['upgrade-listing'] ) && $_POST['upgrade-listing'] == 'upgrade' ) {
                    $this->_listing_data['upgrade-listing'] = true;
                }

                return $this->submit_listing_images();
            }
        }


        if ( wpbdp_get_option( 'featured-on' ) ) {
            $upgrades_api = wpbdp_listing_upgrades_api();
            $upgrade_option = $upgrades_api->get( 'sticky' );
        } else {
            $upgrade_option = false;
        }

        return wpbdp_render('listing-form-fees', array(
                            'validation_errors' => $validation_errors,
                            'listing_id' => $this->_listing_data['listing_id'],
                            'listing_data' => $this->_listing_data,
                            'fee_options' => $fees,
                            'upgrade_option' => $upgrade_option
                            ), false);
    }

    // todo - avoid duplicate uploads (unset something?)
    public function submit_listing_images() {
        $action = '';
        if (isset($_POST['_step']) && $_POST['_step'] == 'images') {
            if (isset($_POST['upload_image']))
                $action = 'upload';
            if (isset($_POST['delete_image']) && intval($_POST['delete_image']) > 0)
                $action = 'delete';
            if (isset($_POST['finish']))
                $action = 'submit';
        }

        $validation_errors = array();

        $images_allowed = 0;
        foreach ($this->_listing_data['fees'] as $fee)
            $images_allowed += $fee->images;

        if (!wpbdp_get_option('allow-images') || $images_allowed == 0)
            return $this->submit_listing_before_save();

        if ($this->_listing_data['listing_id'] && !$this->_listing_data['images']) {
            foreach (wpbdp_listings_api()->get_images($this->_listing_data['listing_id']) as $image) {
                $this->_listing_data['images'][] = $image->ID;
            }
        }

        $images = $this->_listing_data['images'];
        // sanitize images (maybe some got deleted while we were here?)
        $images = array_filter($images, create_function('$x', 'return get_post($x) !== null;'));
        $this->_listing_data['images'] = $images;

        switch ($action) {
            case 'upload':
                if (($images_allowed - count($images) - 1) >= 0) {
                    if ($image_file = $_FILES['image']) {
                        $image_error = '';

                        if ( $attachment_id = wpbdp_media_upload( $image_file, true, true, array(
                            'image' => true,
                            'max-size' => intval(wpbdp_get_option( 'image-max-filesize' )) * 1024
                            ), $image_error ) ) {
                            $this->_listing_data['images'][] = $attachment_id;
                        } else {
                            $validation_errors[] = $image_error;
                        }

                    }
                }
                break;
            case 'delete':
                $attachment_id = intval($_POST['delete_image']);

                $key = array_search($attachment_id, $this->_listing_data['images']);
                if ($key !== FALSE) {
                    wp_delete_attachment($attachment_id, true);
                    unset($this->_listing_data['images'][$key]);
                }
                    
                break;
            case 'submit':
                return $this->submit_listing_before_save();
                break;
            default:
                break;
        }

        $images = $this->_listing_data['images'];

        if (isset($_POST['thumbnail_id']) && in_array($_POST['thumbnail_id'], $images)) {
            $this->_listing_data['thumbnail_id'] = $_POST['thumbnail_id'];
        } elseif ($this->_listing_data['listing_id']) {
            $this->_listing_data['thumbnail_id'] = wpbdp_listings_api()->get_thumbnail_id($this->_listing_data['listing_id']);
        }

        return wpbdp_render('listing-form-images', array(
                            'validation_errors' => $validation_errors,
                            'listing' => null,
                            'listing_data' => $this->_listing_data,
                            'can_upload_images' => (($images_allowed - count($images))> 0),
                            'images_left' => ($images_allowed - count($images)),
                            'images_allowed' => $images_allowed,
                            'images' => $images,
                            'thumbnail_id' => $this->_listing_data['thumbnail_id']
                            ), false);
    }

    public function submit_listing_before_save() {
        if ( isset($_POST['thumbnail_id']) )
            $this->_listing_data['thumbnail_id'] = intval( $_POST['thumbnail_id'] );

        if ( !$this->_extra_sections )
            return $this->submit_listing_save();

        if ( !isset($this->_listing_data['extra_sections']) )
            $this->_listing_data['extra_sections'] = array();

        $theres_output = false;
        $continue_to_save = true;
        if ( !isset($_POST['do_extra_sections']) )
            $continue_to_save = false;

        foreach ( $this->_extra_sections as &$section ) {
            if ( !isset($this->_listing_data['extra_sections'][$section->id]) )
                $this->_listing_data['extra_sections'][$section->id] = array();

            $process_result = false;

            if ( isset($_POST['do_extra_sections']) && $section->process ) {
                $process_result = call_user_func_array( $section->process, array(&$this->_listing_data['extra_sections'][$section->id], $this->_listing_data['listing_id']) );
                $continue_to_save = $continue_to_save && $process_result;
            }

            if ( !$process_result && $section->display ) {
                $section->_output = call_user_func_array( $section->display, array(&$this->_listing_data['extra_sections'][$section->id], $this->_listing_data['listing_id']) );

                if ( !empty( $section->_output ) && !$theres_output )
                    $theres_output = true;                
            }
        }

        if ( $continue_to_save || !$theres_output ) {
            return $this->submit_listing_save();
        }

        return wpbdp_render('listing-form-extra', array(
                            'listing_data' => $this->_listing_data,
                            'sections' => $this->_extra_sections
                            ), false);

        return $html;
    }

    public function submit_listing_save() {
        $data = $this->_listing_data;

        if (isset($_SESSION['wpbdp-submitted-listing-id']) && $_SESSION['wpbdp-submitted-listing-id'] > 0) {
            $listing_id = $_SESSION['wpbdp-submitted-listing-id'];

            return wpbdp_render('listing-form-done', array(
                'listing_data' => $this->_listing_data,
                'listing' => get_post($listing_id)
            ), false);
        }

        $transaction_id = null;
        if ($listing_id = $this->listings->add_listing($data, $transaction_id)) {
            $_SESSION['wpbdp-submitted-listing-id'] = $listing_id;

            // call save() on extra sections
            if ( $this->_extra_sections ) {
                foreach ( $this->_extra_sections as &$section ) {
                    if ( $section->save )
                        call_user_func_array( $section->save, array(&$this->_listing_data['extra_sections'][$section->id], $listing_id) );
                }
            }

            $cost = $this->listings->cost_of_listing($listing_id, true);

            if (!current_user_can('administrator') && ($cost > 0.0)) {
                $payments_api = wpbdp_payments_api();
                $payment_page = $payments_api->render_payment_page(array(
                    'title' => _x('Step 5 - Checkout', 'templates', 'WPBDM'),
                    'transaction_id' => $transaction_id,
                    'item_text' => _x('Pay %1$s listing fee via %2$s', 'templates', 'WPBDM')
                ));

                return wpbdp_render('listing-form-checkout', array(
                    'listing_data' => $this->_listing_data,
                    'listing' => get_post($listing_id),
                    'payment_page' => $payment_page
                ), false);
            }

            if (wpbdp_get_option('send-email-confirmation')) {
                $message = wpbdp_get_option('email-confirmation-message');
                $message = str_replace("[listing]", get_the_title($listing_id), $message);
                
                $email = new WPBDP_Email();
                $email->subject = "[" . get_option( 'blogname' ) . "] " . wp_kses( get_the_title($listing_id), array() );
                $email->to[] = wpbusdirman_get_the_business_email($listing_id);
                $email->body = $message;
                $email->send();
            }

            return wpbdp_render('listing-form-done', array(
                            'listing_data' => $this->_listing_data,
                            'listing' => get_post($listing_id)
                        ), false);
        } else {
            return wpbdp_render(_x('An error occurred while saving your listing. Please try again later.', 'templates', 'WPBDM'), 'error');
        }
    }

    /* Manage Listings */
    public function manage_listings() {
        if (!$this->check_main_page($msg)) return $msg;

        $current_user = is_user_logged_in() ? wp_get_current_user() : null;
        $listings = array();

        if ($current_user) {
            query_posts(array(
                'author' => $current_user->ID,
                'post_type' => wpbdp_post_type(),
                'post_status' => 'publish',
                'paged' => get_query_var('paged') ? get_query_var('paged') : 1
            ));
        }

        $html = wpbdp_render('manage-listings', array(
            'current_user' => $current_user
            ), false);

        if ($current_user)
            wp_reset_query();

        return $html;
    }

    public function delete_listing() {
        if ($listing_id = wpbdp_getv($_REQUEST, 'listing_id')) {
            if ( (wp_get_current_user()->ID == get_post($listing_id)->post_author) || (current_user_can('administrator')) ) {
                $post_update = array('ID' => $listing_id,
                                     'post_type' => wpbdp_post_type(),
                                     'post_status' => wpbdp_get_option('deleted-status'));
                
                wp_update_post($post_update);

                return wpbdp_render_msg(_x('The listing has been deleted.', 'templates', 'WPBDM'))
                      . $this->main_page();
            }
        }
    }

    /* Upgrade to sticky. */
    public function upgrade_to_sticky() {
        $listing_id = wpbdp_getv($_REQUEST, 'listing_id');

        if ( !$listing_id || !wpbdp_user_can('upgrade-to-sticky', $listing_id) )
            return '';

        $upgrades_api = wpbdp_listing_upgrades_api();
        $listings_api = wpbdp_listings_api();

        if ($listings_api->get_payment_status($listing_id) != 'paid' && !current_user_can('administrator')) {
            $html  = '';
            $html .= wpbdp_render_msg(_x('You can not upgrade your listing until its payment has been cleared.', 'templates', 'WPBDM'));
            $html .= sprintf('<a href="%s">%s</a>', get_permalink($listing_id), _x('Return to listing.', 'templates', 'WPBDM'));
            return $html;
        }

        $sticky_info = $upgrades_api->get_info($listing_id);

        if ($sticky_info->pending) {
            $html  = '';
            $html .= wpbdp_render_msg(_x('Your listing is already pending approval for "featured" status.', 'templates', 'WPBDM'));
            $html .= sprintf('<a href="%s">%s</a>', get_permalink($listing_id), _x('Return to listing.', 'templates', 'WPBDM'));
            return $html;
        }

        $action = isset($_POST['do_upgrade']) ? 'do_upgrade' : 'pre_upgrade';

        switch ($action) {
            case 'do_upgrade':
                $payments_api = wpbdp_payments_api();
                
                $transaction_id = $upgrades_api->request_upgrade($listing_id);

                if ($transaction_id && current_user_can('administrator')) {
                    // auto-approve transaction if we are admins
                    $transaction = $payments_api->get_transaction($transaction_id);
                    $transaction->status = 'approved';
                    $transaction->processed_by = 'admin';
                    $transaction->processed_on = date('Y-m-d H:i:s', time());
                    $payments_api->save_transaction($transaction);

                    $html  = '';
                    $html .= wpbdp_render_msg(_x('Listing has been upgraded.', 'templates', 'WPBDM'));
                    $html .= sprintf('<a href="%s">%s</a>', get_permalink($listing_id), _x('Return to listing.', 'templates', 'WPBDM'));
                    return $html;
                }

                return $payments_api->render_payment_page(array(
                    'title' => _x('Upgrade listing', 'templates', 'WPBDM'),
                    'transaction_id' => $transaction_id,
                    'item_text' => _x('Pay %s upgrade fee via %s', 'templates', 'WPBDM'),
                    'return_link' => array(
                        get_permalink($listing_id),
                        _x('Return to listing.', 'templates', 'WPBDM')
                    )
                ));

                break;
            default:
                $html  = '';

                return wpbdp_render('listing-upgradetosticky', array(
                    'listing' => get_post($listing_id),
                    'featured_level' => $sticky_info->upgrade
                ), false);

                return $html;
                break;
        }
    }

    /* listing renewal */
    public function renew_listing() {
        global $wpdb;

        if (!wpbdp_get_option('listing-renewal'))
            return '';

        $current_date = current_time('mysql');

        $fee_info = $wpdb->get_row( $wpdb->prepare( "SELECT * FROM {$wpdb->prefix}wpbdp_listing_fees WHERE id = %d AND expires_on IS NOT NULL AND expires_on < %s", intval($_GET['renewal_id']), $current_date ) );
        if ( !$fee_info )
            return;

        $post = get_post( $fee_info->listing_id );

        if ( !$post || $post->post_type != wpbdp_post_type() )
            return;

        $listings_api = wpbdp_listings_api();
        $fees_api = wpbdp_fees_api();
        $payments_api = wpbdp_payments_api();

        $available_fees = $fees_api->get_fees_for_category( $fee_info->category_id );

        if ( isset( $_POST['fee_id'] ) ) {
            $fee = $fees_api->get_fee_by_id( $_POST['fee_id'] );

            if ( !$fee )
                return;

            if ( $transaction_id = $listings_api->renew_listing( $_GET['renewal_id'], $fee ) ) {
                return $payments_api->render_payment_page( array(
                    'title' => _x('Renew Listing', 'templates', 'WPBDM'),
                    'item_text' => _x('Pay %1$s renewal fee via %2$s.', 'templates', 'WPBDM'),
                    'transaction_id' => $transaction_id,
                ) );
            }
        }

        return wpbdp_render( 'renewlisting-fees', array(
            'fee_options' => $available_fees,
            'category' => get_term( $fee_info->category_id, wpbdp_categories_taxonomy() ),
            'listing' => $post
        ), false );
    }

    /* payment processing */
    public function process_payment() {
        $html = '';
        $api = wpbdp_payments_api();

        if ($transaction_id = $api->process_payment($_REQUEST['gateway'])) {
            $transaction = $api->get_transaction($transaction_id);

            if ($transaction->payment_type == 'upgrade-to-sticky') {
                $html .= sprintf('<h2>%s</h2>', _x('Listing Upgrade Payment Status', 'templates', 'WPBDM'));
            } elseif ($transaction->payment_type == 'initial') {
                $html .= sprintf('<h2>%s</h2>', _x('Listing Submitted', 'templates', 'WPBDM'));
            } else {
                $html .= sprintf('<h2>%s</h2>', _x('Listing Payment Confirmation', 'templates', 'WPBDM'));
            }

            if (wpbdp_get_option('send-email-confirmation')) {
                $listing_id = $transaction->listing_id;
                $message = wpbdp_get_option('payment-message');
                $message = str_replace("[listing]", get_the_title($listing_id), $message);

                $email = new WPBDP_Email();
                $email->subject = "[" . get_option( 'blogname' ) . "] " . wp_kses( get_the_title($listing_id), array() );
                $email->to[] = wpbusdirman_get_the_business_email($listing_id);
                $email->body = $message;
                $email->send();
            }

            $html .= sprintf('<p>%s</p>', wpbdp_get_option('payment-message'));
        }

        return $html;
    }

    /*
     * Search functionality
     */
    public function search() {
        $results = array();

        if ( isset( $_GET['dosrch'] ) ) {
            $search_args = array();
            $search_args['q'] = wpbdp_getv($_GET, 'q', null);
            $search_args['fields'] = array(); // standard search fields
            $search_args['extra'] = array(); // search fields added by plugins

            foreach ( wpbdp_getv( $_GET, 'listingfields', array() ) as $field_id => $field_search )
                $search_args['fields'][] = array( 'field_id' => $field_id, 'q' => $field_search );

            foreach ( wpbdp_getv( $_GET, '_x', array() ) as $label => $field )
                $search_args['extra'][ $label ] = $field;

            $listings_api = wpbdp_listings_api();
            $results = $listings_api->search( $search_args );
        }

        $form_fields = wpbdp_get_form_fields( array( 'display_flags' => 'search', 'validators' => '-email' ) );
        $fields = '';
        foreach ( $form_fields as &$field ) {
            $field_value = isset( $_REQUEST['listingfields'] ) && isset( $_REQUEST['listingfields'][ $field->get_id() ] ) ? $field->convert_input( $_REQUEST['listingfields'][ $field->get_id() ] ) : $field->convert_input( null );
            $fields .= $field->render( $field_value, 'search' );
        }

        query_posts( array(
            'post_type' => wpbdp_post_type(),
            'posts_per_page' => 10,
            'paged' => get_query_var('paged') ? get_query_var('paged') : 1,
            'post__in' => $results ? $results : array(0),
            'orderby' => wpbdp_get_option( 'listings-order-by', 'date' ),
            'order' => wpbdp_get_option( 'listings-sort', 'ASC' )
        ) );

        $html = wpbdp_render( 'search', array( 'fields' => $fields, 'searching' => isset( $_GET['dosrch'] ) ? true : false ), false );
        wp_reset_query();

        return $html;
    }

}

}