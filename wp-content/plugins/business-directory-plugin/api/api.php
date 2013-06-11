<?php
/*
 * Plugin API
 */

function wpbdp() {
    global $wpbdp;
    return $wpbdp;
}

function wpbdp_get_version() {
    return WPBDP_VERSION;
}

/**
 * @deprecated since 2.3
 */
function wpbdp_post_type() {
    return WPBDP_POST_TYPE;
}

/**
 * @deprecated since 2.3
 */
function wpbdp_categories_taxonomy() {
    return WPBDP_CATEGORY_TAX;
}

function wpbdp_get_page_id($name='main') {
    global $wpdb;

    static $shortcodes = array(
        'main' => array('businessdirectory', 'business-directory', 'WPBUSDIRMANUI'),
        'add-listing' => array('businessdirectory-submitlisting', 'WPBUSDIRMANADDLISTING'),
        'manage-listings' => array('businessdirectory-managelistings', 'WPBUSDIRMANMANAGELISTING'),
        'view-listings' => array('businessdirectory-viewlistings', 'businessdirectory-listings', 'WPBUSDIRMANMVIEWLISTINGS'),
        'paypal' => 'WPBUSDIRMANPAYPAL',
        '2checkout' => 'WPBUSDIRMANTWOCHECKOUT',
        'googlecheckout' => 'WPBUSDIRMANGOOGLECHECKOUT'
    );

    if (!array_key_exists($name, $shortcodes))
        return null;

    $where = '1=0';
    $options = is_string($shortcodes[$name]) ? array($shortcodes[$name]) : $shortcodes[$name];
    foreach ($options as $shortcode) {
        $where .= sprintf(" OR post_content LIKE '%%[%s]%%'", $shortcode);
    }

    $id = $wpdb->get_var("SELECT ID FROM {$wpdb->posts} WHERE ({$where}) AND post_status = 'publish' AND post_type = 'page' LIMIT 1");
    return $id;
}

function wpbdp_get_page_link($name='main', $arg0=null) {
    if ( $page_id = wpbdp_get_page_id( $name ) ) {
        return _get_page_link( $page_id );
    }

    switch ( $name ) {
        case 'view':
        case 'viewlisting':
        case 'show-listing':
        case 'showlisting':
            $link = get_permalink( intval( $arg0 ) );
            break;
        case 'edit':
        case 'editlisting':
        case 'edit-listing':
        case 'delete':
        case 'deletelisting':
        case 'delete-listing':
        case 'upgrade':
        case 'upgradetostickylisting':
        case 'upgradelisting':
        case 'upgrade-listing':
            $link = add_query_arg( array( 'action' => $name, 'listing_id' => intval( $arg0 ) ), wpbdp_get_page_link( 'main' ) );
            break;
        case 'viewlistings':
        case 'view-listings':
            $link = add_query_arg( array( 'action' => 'viewlistings' ), wpbdp_get_page_link( 'main' ) );
            break;
        case 'add':
        case 'addlisting':
        case 'add-listing':
        case 'submit':
        case 'submitlisting':
        case 'submit-listing':
            $link = add_query_arg( array( 'action' => 'submitlisting' ), wpbdp_get_page_link( 'main' ) );
            break;
        case 'search':
            $link = add_query_arg( array( 'action' => 'search' ), wpbdp_get_page_link( 'main' ) );
            break;
        default:
            $link = wpbdp_get_page_link( 'main' );
            break;
    }

    return $link;
}

/* Admin API */
function wpbdp_admin() {
    return wpbdp()->admin;
}

function wpbdp_admin_notices() {
    wpbdp_admin()->admin_notices();
}

/* Settings API */
function wpbdp_settings_api() {
    global $wpbdp;
    return $wpbdp->settings;
}

function wpbdp_get_option($key, $def=null) {
    global $wpbdp;
    return $wpbdp->settings->get($key, $def);
}

function wpbdp_set_option($key, $value) {
    global $wpbdp;
    return $wpbdp->settings->set($key, $value);
}

/* Form Fields API */
function wpbdp_formfields_api() {
    global $wpbdp;
    return $wpbdp->formfields;
}

function wpbdp_get_formfield($id) {
    if (is_numeric($id) && is_string($id))
        return wpbdp_get_formfield(intval($id));

    if (is_string($id))
        return wpbdp_formfields_api()->getFieldsByAssociation($id, true);

    return wpbdp_formfields_api()->get_field($id);
}

/* Fees/Payment API */
function wpbdp_payments_possible() {
    return wpbdp_payments_api()->payments_possible();
}

function wpbdp_payment_status($listing_id) {
    return wpbdp_listings_api()->get_payment_status($listing_id);
}

function wpbdp_fees_api() {
    return wpbdp()->fees;
}

function wpbdp_payments_api() {
    return wpbdp()->payments;
}

/* Listings API */
function wpbdp_listings_api() {
    return wpbdp()->listings;
}

function wpbdp_listing_upgrades_api() {
    return wpbdp()->listings->upgrades;
}

/* Misc. */
function _wpbdp_save_object($obj_, $table, $id='id') {
    global $wpdb;

    $obj = is_object($obj_) ? (array) $obj_ : $obj_;

    if (!$obj) return 0;

    foreach ($obj as $k => $v) {
        if (is_array($v) || is_object($v))
            $obj[$k] = serialize($v);
    }

    $obj_id = 0;

    if (isset($obj[$id])) {
        if ($wpdb->update("{$wpdb->prefix}wpbdp_" . $table, $obj, array($id => $obj[$id])) !== false)
            $obj_id = $obj[$id];
    } else {
        if ($wpdb->insert("{$wpdb->prefix}wpbdp_" . $table, $obj)) {
            $obj_id = $wpdb->insert_id;
        }
    }

    // if ($obj_id)
    //  do_action('wpbdp_save_' . $table, $obj_id);

    return $obj_id;
}

function wpbdp_categories_list($parent=0, $hierarchical=true) {
    $terms = get_categories(array(
        'taxonomy' => wpbdp_categories_taxonomy(),
        'parent' => $parent,
        'orderby' => 'name',
        'hide_empty' => 0,
        'hierarchical' => 0
    ));

    if ($hierarchical) {
        foreach ($terms as &$term) {
            $term->subcategories = wpbdp_categories_list($term->term_id, true);
        }
    }

    return $terms;
}

function wpbdp_get_parent_categories($catid) {
    $category = get_term(intval($catid), wpbdp_categories_taxonomy());

    if ($category->parent) {
        return array_merge(array($category), wpbdp_get_parent_categories($category->parent));
    }

    return array($category);
}

function wpbdp_get_parent_catids($catid) {
    $parent_categories = wpbdp_get_parent_categories($catid);
    array_walk($parent_categories, create_function('&$x', '$x = intval($x->term_id);'));    

    return $parent_categories;
}

function wpbdp_locate_template($template, $allow_override=true, $try_defaults=true) {
    $template_file = '';

    if (!is_array($template))
        $template = array($template);

    if ($allow_override) {
        $search_for = array();

        foreach ($template as $t) {
            $search_for[] = $t . '.tpl.php';
            $search_for[] = $t . '.php';
            $search_for[] = 'single/' . $t . '.tpl.php';
            $search_for[] = 'single/' . $t . '.php';
        }

        $template_file = locate_template($search_for);
    }

    if (!$template_file && $try_defaults) {
        foreach ($template as $t) {
            $template_path = WPBDP_TEMPLATES_PATH . '/' . $t . '.tpl.php'; 
            
            if (file_exists($template_path)) {
                $template_file = $template_path;
                break;
            }
        }
    }

    return $template_file;
}

function wpbdp_render($template, $vars=array(), $allow_override=true) {
    $vars = wp_parse_args($vars, array(
        '__page__' => array(
            'class' => array(),
            'content_class' => array(),
            'before_content' => '')));
    $vars = apply_filters('wpbdp_template_vars', $vars, $template);

    return apply_filters( "wpbdp_render_{$template}", wpbdp_render_page(wpbdp_locate_template($template, $allow_override), $vars, false) );
}

function wpbdp_render_msg($msg, $type='status') {
    $html = '';
    $html .= sprintf('<div class="wpbdp-msg %s">%s</div>', $type, $msg);
    return $html;
}


/*
 * Template functions
 */

/**
 * Displays a single listing view taking into account all of the theme overrides.
 * @param mixed $listing_id listing object or listing id to display.
 * @param string $view 'single' for single view or 'excerpt' for summary view.
 * @return string HTML output.
 */
function wpbdp_render_listing($listing_id=null, $view='single', $echo=false) {
    if (is_object($listing_id)) $listing_id = $listing_id->ID;

    global $post;
    $listings_api = wpbdp_listings_api();

    if ($listing_id)  {
        query_posts(array(
            'post_type' => wpbdp_post_type(),
            'post_status' => 'publish',
            'p' => $listing_id
        ));

        if (have_posts()) the_post();
    }

    if (!$post || $post->post_type != wpbdp_post_type()) {
        return '';
    }

    if ($view == 'excerpt')
        $html = _wpbdp_render_excerpt();
    else
        $html = _wpbdp_render_single();

    if ($listing_id)
        wp_reset_query();

    if ($echo)
        echo $html;

    return $html;
}

function _wpbdp_render_single() {
    global $post;

    $html = '';

    $sticky_status = wpbdp_listings_api()->get_sticky_status($post->ID);

    $html .= sprintf('<div id="wpbdp-listing-%d" class="wpbdp-listing wpbdp-listing-single %s %s" itemscope itemtype="http://schema.org/Thing">', $post->ID, 'single', $sticky_status);
    $html .= apply_filters('wpbdp_listing_view_before', '', $post->ID, 'single');
    $html .= wpbdp_capture_action('wpbdp_before_single_view', $post->ID);

    $sticky_tag = '';
    if ($sticky_status == 'sticky')
        $sticky_tag = sprintf('<div class="stickytag"><img src="%s" alt="%s" border="0" title="%s"></div>',
                        WPBDP_URL . 'resources/images/featuredlisting.png',
                        _x('Featured Listing', 'templates', 'WPBDM'),
                        the_title(null, null, false));

    $formfields_api = WPBDP_FormFields::instance();

    $listing_fields = '';
    $social_fields = '';

    $ffields = wpbdp_get_form_fields();
    $ffields = apply_filters_ref_array( 'wpbdp_get_form_fields', array( &$ffields, $post->ID ) ); // TODO: move this to wpbpd_get_form_fields() ?    

    foreach ( $ffields as &$field ) {
        if ( !$field->display_in( 'listing' ) )
            continue;

        if ( $field->display_in( 'social' ) ) {
            $social_fields .= $field->display( $post->ID, 'social' );
        } else {
            $listing_fields .= $field->display( $post->ID, 'listing' );
        }
    }

    // images
    $thumbnail_id = wpbdp_listings_api()->get_thumbnail_id($post->ID);
    $images = wpbdp_listings_api()->get_images($post->ID);
    $extra_images = array();

    foreach ($images as $img) {
        // create thumbnail of correct size if needed (only in single view to avoid consuming server resources)
        _wpbdp_resize_image_if_needed( $img->ID );

        if ($img->ID == $thumbnail_id) continue;

        $full_image_data = wp_get_attachment_image_src( $img->ID, 'wpbdp-large', false );
        $full_image_url = $full_image_data[0];

        $extra_images[] = sprintf('<a href="%s" class="thickbox lightbox" rel="lightbox" target="_blank">%s</a>',
                                    $full_image_url,
                                    wp_get_attachment_image( $img->ID, 'wpbdp-thumb', false, array(
                                        'class' => 'wpbdp-thumbnail size-thumbnail',
                                        'alt' => the_title(null, null, false),
                                        'title' => the_title(null, null, false)
                                    ) ));
    }

    $vars = array(
        'actions' => wpbdp_render('parts/listing-buttons', array('listing_id' => $post->ID, 'view' => 'single'), false),
        'is_sticky' => $sticky_status == 'sticky',
        'sticky_tag' => $sticky_tag,
        'title' => get_the_title(),
        'main_image' => wpbdp_listing_thumbnail( null, 'link=picture&class=wpbdp-single-thumbnail' ),
        'listing_fields' => apply_filters('wpbdp_single_listing_fields', $listing_fields, $post->ID),
        'extra_images' => $extra_images
    );

    $html .= wpbdp_render('businessdirectory-listing', $vars, true);

    $social_fields = apply_filters('wpbdp_single_social_fields', $social_fields, $post->ID);
    if ($social_fields)
        $html .= '<div class="social-fields cf">' . $social_fields . '</div>';

    $html .= apply_filters('wpbdp_listing_view_after', '', $post->ID, 'single');
    $html .= wpbdp_capture_action('wpbdp_after_single_view', $post->ID);

    $show_contact_form = apply_filters('wpbdp_show_contact_form', wpbdp_get_option('show-contact-form'), $post->ID);
    if ($show_contact_form) {
        $html .= '<div class="contact-form">';
        $html .= wpbdp_listing_contact_form();
        $html .= '</div>';
    }

    if (wpbdp_get_option('show-comment-form')) {
        $html .= '<div class="comments">';

        ob_start();
        comments_template(null, true);
        $html .= ob_get_contents();
        ob_end_clean();

        $html .= '</div>';
    }

    $html .= '</div>';

    return $html;
}

function _wpbdp_render_excerpt() {
    global $post;
    static $counter = 0;

    $sticky_status = wpbdp_listings_api()->get_sticky_status($post->ID);

    $html = '';
    $html .= sprintf('<div id="wpbdp-listing-%d" class="wpbdp-listing excerpt wpbdp-listing-excerpt %s %s cf">',
                     $post->ID,
                     $sticky_status,
                     ($counter & 1) ? 'odd':  'even');
    $html .= wpbdp_capture_action('wpbdp_before_excerpt_view', $post->ID);

    $formfields_api = WPBDP_FormFields::instance();

    $listing_fields = '';
    $social_fields = '';

    $ffields = wpbdp_get_form_fields();
    $ffields = apply_filters_ref_array( 'wpbdp_get_form_fields', array( &$ffields, $post->ID ) ); // TODO: move this to wpbpd_get_form_fields() ?

    foreach ( $ffields as &$field) {
        if ( !$field->display_in( 'excerpt' ) )
            continue;

        if ( $field->display_in( 'social' ) ) {
            $social_fields .= $field->display( $post->ID, 'social' );
        } else {
            $listing_fields .= $field->display( $post->ID, 'excerpt' );
        }

    }

    $vars = array(
        'is_sticky' => $sticky_status == 'sticky',
        'thumbnail' => wpbdp_listing_thumbnail( null, 'link=listing&class=wpbdmthumbs wpbdp-excerpt-thumbnail' ),
        'title' => get_the_title(),
        'listing_fields' => apply_filters('wpbdp_excerpt_listing_fields', $listing_fields, $post->ID)
    );

    $html .= wpbdp_render('businessdirectory-excerpt', $vars, true);

    $social_fields = apply_filters('wpbdp_excerpt_social_fields', $social_fields, $post->ID);
    if ($social_fields)
        $html .= '<div class="social-fields cf">' . $social_fields . '</div>';

    $html .= wpbdp_capture_action('wpbdp_after_excerpt_view', $post->ID);
    $html .= wpbdp_render('parts/listing-buttons', array('listing_id' => $post->ID, 'view' => 'excerpt'), false);
    $html .= '</div>';

    $counter++;

    return $html;
}

function wpbdp_latest_listings($n=10, $before='<ul>', $after='</ul>', $before_item='<li>', $after_item = '</li>') {
    $n = max(intval($n), 0);

    $posts = get_posts(array(
        'post_type' => wpbdp_post_type(),
        'post_status' => 'publish',
        'numberposts' => $n,
        'orderby' => 'date'
    ));

    $html = '';

    $html .= $before;

    foreach ($posts as $post) {
        $html .= $before_item;
        $html .= sprintf('<a href="%s">%s</a>', get_permalink($post->ID), get_the_title($post->ID));
        $html .= $after_item;
    }

    $html .= $after;

    return $html;
}

function _wpbdp_template_mode($template) {
    if ( wpbdp_locate_template(array('businessdirectory-' . $template, 'wpbusdirman-' . $template), true, false) )
        return 'template';
    return 'page';
}

/**
 * Checks if permalinks are enabled.
 * @return boolean
 * @since 2.1
 */
function wpbdp_rewrite_on() {
    global $wp_rewrite;
    return $wp_rewrite->permalink_structure ? true : false;
}

/**
 * Checks if a given user can perform some action to a listing.
 * @param string $action the action to be checked. available actions are 'view', 'edit', 'delete' and 'upgrade-to-sticky'
 * @param (object|int) $listing_id the listing ID. if null, the current post ID will be used
 * @param int $user_id the user ID. if null, the current user will be used
 * @return boolean
 * @since 2.1
 */
function wpbdp_user_can($action, $listing_id=null, $user_id=null) {
    $listing_id = $listing_id ? ( is_object($listing_id) ? $listing_id->ID : intval($listing_id) ) : get_the_ID();
    $user_id = $user_id ? $user_id : wp_get_current_user()->ID;
    $post = get_post($listing_id);

    if ($post->post_type != wpbdp_post_type())
        return false;

    switch ($action) {
        case 'view':
            return true;
            break;
        case 'edit':
        case 'delete':
            return user_can($user_id, 'administrator') || ($post->post_author == $user_id);
            break;
        case 'upgrade-to-sticky':
            if ( !wpbdp_get_option('featured-on') || !wpbdp_get_option('payments-on') )
                return false;

            if ( !wpbdp_payments_possible() )
                return false;

            $sticky_info = wpbdp_listing_upgrades_api()->get_info( $listing_id );
            return $sticky_info->upgradeable && (user_can($user_id, 'administrator') || ($post->post_author == $user_id));
            break;
    }

    return false;
}

function wpbdp_get_post_by_slug($slug, $post_type=null) {
    $post_type = $post_type ? $post_type : wpbdp_post_type();

    $posts = get_posts(array(
        'name' => $slug,
        'post_type' => $post_type,
        'post_status' => 'publish',
        'numberposts' => 1
    ));

    if ($posts)
        return $posts[0];
    else
        return 0;
}

function wpbdp_get_current_sort_option() {
    if ($sort = trim(wpbdp_getv($_GET, 'wpbdp_sort', null))) {
        $order = substr($sort, 0, 1) == '-' ? 'DESC' : 'ASC';
        $sort = ltrim($sort, '-');

        $obj = new StdClass();
        $obj->option = $sort;
        $obj->order = $order;

        return $obj;
    }

    return null;
}

/*
 * @since 2.1.6
 */
function wpbdp_listing_form_register_section($id, $section=array()) {
    return wpbdp()->controller->register_listing_form_section($id, $section);
}

/*
 * @since 2.1.6
 */
function _wpbdp_resize_image_if_needed($id) {
    require_once( ABSPATH . 'wp-admin/includes/image.php' );

    if ( $metadata = wp_get_attachment_metadata( $id ) ) {
        if ( !isset( $metadata['sizes']['wpbdp-thumb'] ) || !isset( $metadata['sizes']['wpbdp-thumb'] ) || 
            (isset($metadata['sizes']['wpbdp-thumb']) && (abs( intval($metadata['sizes']['wpbdp-thumb']['width']) - intval( wpbdp_get_option( 'thumbnail-width' ) ) ) >= 15) ) ) {
            wpbdp_log( sprintf( 'Re-creating thumbnails for attachment %d', $id ) );
            $filename = get_attached_file($id, true);
            $attach_data = wp_generate_attachment_metadata( $id, $filename );
            wp_update_attachment_metadata( $id,  $attach_data );
        }
    }
}

/*
 * @since 2.1.7
 */
function wpbdp_format_currency($amount, $decimals=2) {
    if ( $amount == 0.0 )
        return '—';
    
    return wpbdp_get_option( 'currency-symbol' ) . ' ' . number_format( $amount, $decimals );
}


/**
 * @since 2.3
 */
function wpbdp_has_module( $module ) {
    global $wpbdp;
    return $wpbdp->has_module( $module );
}