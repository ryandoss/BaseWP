<?php
/*
 * Deprecated functionality.
 */

$wpbusdirmanconfigoptionsprefix = "wpbusdirman";

$wpbdmposttype = "wpbdp_listing";
$wpbdmposttypecategory = "wpbdm-category";
$wpbdmposttypetags = "wpbdm-tags";

define('WPBUSDIRMAN_TEMPLATES_PATH', WPBDP_PATH . '/deprecated/templates');


/* template-related */
function wpbusdirman_single_listing_details() {
    echo wpbusdirman_post_single_listing_details();
}

function wpbusdirman_post_single_listing_details() {
    return wpbdp_render_listing( null, 'single' );
}

function wpbusdirman_the_listing_title() {
    if ( $field = wpbdp_get_form_fields( array( 'association' => 'title', 'unique' => true ) ) )
        return $field->display( get_the_ID() );
}

function wpbusdirman_the_listing_excerpt() {
    if ( $field = wpbdp_get_form_fields( array( 'association' => 'excerpt', 'unique' => true ) ) )
        return $field->display( get_the_ID() );
}

function wpbusdirman_the_listing_content() {
    if ( $field = wpbdp_get_form_fields( array( 'association' => 'content', 'unique' => true ) ) )
        return $field->display( get_the_ID() );
}

function wpbusdirman_the_listing_category() {
    if ( $field = wpbdp_get_form_fields( array( 'association' => 'category', 'unique' => true ) ) )
        return $field->display( get_the_ID() );
}

function wpbusdirman_the_listing_tags() {
    if ( $field = wpbdp_get_form_fields( array( 'association' => 'tags', 'unique' => true ) ) )
        return $field->display( get_the_ID() );
}

function wpbusdirman_the_listing_meta($excerptorsingle) {
    $html = '';
    $fields = wpbdp_get_form_fields( array( 'association' => 'meta' ) );

    foreach ( $fields as &$f ) {
        if ( $excerptorsingle == 'excerpt' && !$field->display_in( 'excerpt' ) )
            continue;

        $html .= $f->display( get_the_ID() );
    }

    return $html;
}

function wpbusdirman_display_excerpt($deprecated=null) {
    echo wpbusdirman_post_excerpt($deprecated);
}

function wpbusdirman_post_excerpt($deprecated=null) {
    return wpbdp_render_listing( null, 'excerpt' );
}


/**
 * @deprecated since 2.3
 */
function wpbusdirman_display_main_image() {
    echo wpbusdirman_post_main_image();
}

/**
 * @deprecated since 2.3
 */
function wpbusdirman_post_main_image() {
    return wpbdp_listing_thumbnail();
}

function wpbusdirman_display_extra_thumbnails() {
    echo wpbusdirman_post_extra_thumbnails();
}

function wpbusdirman_post_extra_thumbnails() {
    $html = '';

    $thumbnail_id = wpbdp_listings_api()->get_thumbnail_id(get_the_ID());
    $images = wpbdp_listings_api()->get_images(get_the_ID());

    if ($images) {
        $html .= '<div class="extrathumbnails">';

        foreach ($images as $img) {
            if ($img->ID == $thumbnail_id)
                continue;

            $html .= sprintf('<a class="thickbox" href="%s"><img class="wpbdmthumbs" src="%s" alt="%s" title="%s" border="0" /></a>',
                             wp_get_attachment_url($img->ID),
                             wp_get_attachment_thumb_url($img->ID),
                             the_title(null, null, false),
                             the_title(null, null, false)
                             );
        }

        $html .= '</div>';      
    }

    return $html;
}

// Display the listing fields in excerpt view
function wpbusdirman_display_the_listing_fields() {
    global $post;

    $html = '';

    foreach ( wpbdp_formfields_api()->get_fields() as $field ) {
        if ( !$field->display_in( 'excerpt' ) )
            continue;

        $html .= $field->display( $post->ID, 'excerpt' );
    }

    return $html;
}

//Display the listing thumbnail
function wpbusdirman_display_the_thumbnail() {
    return wpbdp_listing_thumbnail();
}

function wpbusdirman_sticky_loop() { return; }

function wpbusdirman_view_edit_delete_listing_button() {
    $wpbusdirman_permalink=get_permalink(wpbdp_get_page_id('main'));
    $html = '';

    $html .= '<div style="clear:both;"></div><div class="vieweditbuttons"><div class="vieweditbutton"><form method="post" action="' . get_permalink() . '"><input type="hidden" name="action" value="viewlisting" /><input type="hidden" name="wpbusdirmanlistingid" value="' . get_the_id() . '" /><input type="submit" value="' . __("View","WPBDM") . '" class="button" /></form></div>';

    if ( (wp_get_current_user()->ID == get_the_author_meta('ID')) || current_user_can('administrator')) {
        $html .= '<div class="vieweditbutton"><form method="post" action="' . $wpbusdirman_permalink . '"><input type="hidden" name="action" value="editlisting" /><input type="hidden" name="listing_id" value="' . get_the_id() . '" /><input type="submit" value="' . __("Edit","WPBDM") . '" /></form></div><div class="vieweditbutton"><form method="post" action="' . $wpbusdirman_permalink . '"><input type="hidden" name="action" value="deletelisting" /><input type="hidden" name="listing_id" value="' . get_the_id() . '" /><input type="submit" value="' . __("Delete","WPBDM") . '" class="button" /></form></div>';
    }
    $html .= '</div>';

    return $html;
}

function wpbusdirman_menu_button_upgradelisting() {
    $post_id = get_the_ID();

    if ( wpbdp_get_option('featured-on') &&
         (get_post($post_id)->post_author == wp_get_current_user()->ID) &&
         wpbdp_listings_api()->get_sticky_status(get_the_ID()) == 'normal' ) {
            return '<form method="post" action="' . wpbdp_get_page_link('main') . '"><input type="hidden" name="action" value="upgradetostickylisting" /><input type="hidden" name="listing_id" value="' . $post_id . '" /><input type="submit" class="updradetostickylistingbutton" value="' . __("Upgrade Listing","WPBDM") . '" /></form>';
    }

    return '';
}

function wpbusdirman_latest_listings($numlistings) {
    return wpbdp_latest_listings($numlistings);
}

function wpbusdirman_post_catpage_title() {
    $categories = wpbdp_categories_taxonomy();

    if ( get_query_var($categories) ) {
        $term = get_term_by('slug', get_query_var($categories), $categories);
    } else if ( get_query_var('taxonomy') == $categories ) {
        $term = get_term_by('slug', get_query_var('term'), $categories);
    } elseif ( get_query_var('taxonomy') == WPBDP_TAGS_TAX ) {
        $term = get_term_by('slug', get_query_var('term'), WPBDP_TAGS_TAX);
    }

    return esc_attr($term->name);
}

function wpbusdirman_list_categories() {
    echo wpbusdirman_post_list_categories();
}

function wpbusdirman_post_list_categories() {
    return wpbdp_directory_categories();
}

function wpbusdirman_menu_buttons()
{
    echo wpbusdirman_post_menu_buttons();
}

function wpbusdirman_post_menu_buttons()
{
    $html = '';
    $html .= '<div>' . wpbusdirman_post_menu_button_submitlisting() . wpbusdirman_menu_button_directory() . '</div><div style="clear: both;"></div>';
    return $html;
}

function wpbusdirman_menu_button_submitlisting()
{
    echo wpbusdirman_post_menu_button_submitlisting();
}

function wpbusdirman_post_menu_button_submitlisting()
{
    if (!wpbdp_get_option('show-submit-listing'))
        return '';

    return '<form method="post" action="' . wpbdp_get_page_link('add-listing') . '"><input type="hidden" name="action" value="submitlisting" /><input type="submit" class="submitlistingbutton" value="' . __("Submit A Listing","WPBDM") . '" /></form>';
}

function wpbusdirman_menu_button_viewlistings()
{
    echo wpbusdirman_post_menu_button_viewlistings();
}

function wpbusdirman_post_menu_button_viewlistings()
{
    if (!wpbdp_get_option('show-view-listings'))
        return '';
    
    return '<form method="post" action="' . wpbdp_get_page_link('view-listings') . '"><input type="hidden" name="action" value="viewlistings" /><input type="submit" class="viewlistingsbutton" value="' . __("View Listings","WPBDM") . '" /></form>';
}

function wpbusdirman_menu_button_directory()
{

    echo wpbusdirman_post_menu_button_directory();
}

function wpbusdirman_post_menu_button_directory()
{
    return '<form method="post" action="' . wpbdp_get_page_link('main') . '"><input type="submit" class="viewlistingsbutton" value="' . __("Directory","WPBDM") . '" /></form>';
}

function wpbusdirman_menu_button_editlisting()
{
    global $post;
    $wpbusdirman_permalink=get_permalink(wpbdp_get_page_id('main'));
    $html = '';

    if(is_user_logged_in())
    {
        global $current_user;
        get_currentuserinfo();
        $wpbusdirmanloggedinuseremail=$current_user->user_email;
        $wpbusdirmanauthoremail=get_the_author_meta('user_email');
        if($wpbusdirmanloggedinuseremail == $wpbusdirmanauthoremail || current_user_can('administrator') || (wp_get_current_user()->ID == get_the_author_meta('ID')))
        {
            $html .= '<form method="post" action="' . $wpbusdirman_permalink . '"><input type="hidden" name="action" value="editlisting" /><input type="hidden" name="listing_id" value="' . $post->ID . '" /><input type="submit" class="editlistingbutton" value="' . __("Edit Listing","WPBDM") . '" /></form>';
        }
    }

    return $html;
}

/* deprecated since 2.1.4 */
function wpbdp_sticky_loop($category_id=null, $taxonomy=null) { return ''; }

/* deprecated since 2.1.6 */
function wpbusdirman_dropdown_categories() {
    $html  = '';

    $html .= sprintf('<form action="%s">', site_url('/'));
    $html .= wp_dropdown_categories(array(
                   'taxonomy' => wpbdp_categories_taxonomy(),
                   'show_option_none' => '—',
                   'order' => wpbdp_get_option('categories-sort'),                   
                   'orderby' => wpbdp_get_option('categories-order-by'),
                   'hide_empty' => wpbdp_get_option('hide-empty-categories'),
                   'hierarchical' => !wpbdp_get_option('show-only-parent-categories'),
                   'echo' => false,
                   'name' => wpbdp_categories_taxonomy()
             ));

    $html = preg_replace("/\\<select(.*)name=('|\")(.*)('|\")(.*)\\>/uiUs",
                         "<select name=\"$3\" onchange=\"return this.form.submit();\" $1 $5>",
                         $html);

    // no-script support
    $html .= '<noscript>';
    $html .= '<input type="submit" value="→" />';
    $html .= '</noscript>';
    $html .= '</form>';

    return $html;
}

/**
 * Small compatibility layer with old forms API. To be removed in later releases.
 * @deprecated
 * @since 2.3
 */
function wpbdp_get_formfields() {
    global $wpbdp;
    $res = array();

    foreach ( $wpbdp->formfields->get_fields() as $new_field ) {
        $field = new StdClass();
        $field->id = $new_field->get_id();
        $field->label = $new_field->get_label();
        $field->association = $new_field->get_association();
        $field->type = $new_field->get_field_type()->get_id();

        $res[] = $field;
    }

    return $res;
}


/**
 * @deprecated
 * @since 2.3
 */
function wpbusdirman_get_the_business_email($post_id) {
    $api = wpbdp_formfields_api();

    // try first with the listing fields
    foreach ( $api->get_fields() as $field ) {
        if ( !$field->has_validator( 'email' ) )
            continue;
        
        $value = $field->plain_value( $post_id );

        if ( wpbdp_validate_value( $value, 'email' ) ) {
            return $value;
        }
    }

    
    // then with the author email
    $post = get_post( $post_id );
    if ( $email = get_the_author_meta( 'user_email', $post->post_author ) )
        return $email;

    return '';
}
