<?php
/*
 * UI Functions to be called from templates.
 */

/**
 * Returns a list of directory categories using the configured directory settings.
 * The list is actually produced by {@link wpbdp_list_categories()}.
 * @return string HTML output.
 * @uses wpbdp_list_categories().
 */
function wpbdp_directory_categories() {
    $html = wpbdp_list_categories( array(
                                        'hide_empty' => wpbdp_get_option( 'hide-empty-categories' ),
                                        'parent_only' => wpbdp_get_option( 'show-only-parent-categories' )
                                 ) );

    return apply_filters( 'wpbdp_main_categories', $html );
}

/**
 * Identical to {@link wpbdp_directory_categories()}, except the output is printed instead of returned.
 * @uses wpbdp_directory_categories().
 */
function wpbdp_the_directory_categories() {
    echo wpbdp_directory_categories();
}

/**
 * @since 2.3
 * @access private
 */
function _wpbdp_padded_count( &$term ) {
    $count = intval( $term->count );

    if ( $children = get_term_children( $term->term_id, WPBDP_CATEGORY_TAX ) ) {
        foreach ( $children as $c_id ) {
            $c = get_term( $c_id, WPBDP_CATEGORY_TAX );
            $count += intval( $c->count );
        }
    }

    $term->count = $count;
}

/**
 * @since 2.3
 * @access private
 */
function _wpbdp_list_categories_walk( $parent=0, $depth=0, $args ) {
    $terms = get_terms( WPBDP_CATEGORY_TAX,
                        array( 'orderby' => $args['orderby'],
                               'order' => $args['order'],
                               'hide_empty' => false,
                               'pad_counts' => true,
                               'parent' => is_object( $args['parent'] ) ? $args['parent']->term_id : intval( $args['parent'] ) )
                        );
    
    // 'pad_counts' doesn't work because of WP bug #15626 (see http://core.trac.wordpress.org/ticket/15626).
    // we need a workaround until the bug is fixed.        
    foreach ( $terms as &$t )
        _wpbdp_padded_count( $t );

    // filter empty terms
    if ( $args['hide_empty'] ) {
        $terms = array_filter( $terms, create_function( '$x', 'return $x->count > 0;' ) );
    }

    $html = '';

    if ( !$terms && $depth == 0 ) {
        $html .= '<p>' . _x( 'No listing categories found.', 'templates', 'WPBDM' ) . '</p>';
        return $html;
    }

    if ( $depth > 0 ) {
        $html .= str_repeat( "\t", $depth );

        if ( apply_filters( 'wpbdp_categories_list_anidate_children', true ) ) {
            $html .= '<ul class="children">';
        }
    }

    foreach ( $terms as &$term ) {
        $html .= '<li class="cat-item cat-item-' . $term->term_id . ' ' . apply_filters( 'wpbdp_categories_list_item_css', '', $term ) . '">';

        $item_html = '';
        $item_html .= '<a href="' . esc_url( get_term_link( $term ) ) . '" ';
        $item_html .= 'title="' . esc_attr( strip_tags( apply_filters( 'category_description', $term->description, $term ) ) ) . '" class="category-label" >';
        $item_html .= esc_attr( $term->name );
        $item_html .= '</a>';

        if ( $args['show_count'] ) {
            $item_html .= ' (' . intval( $term->count ) . ')';
        }

        $item_html = apply_filters( 'wpbdp_categories_list_item', $item_html, $term );
        $html .= $item_html;

        if ( !$args['parent_only'] ) {
            $args['parent'] = $term->term_id;
            $html .= _wpbdp_list_categories_walk( $term->term_id, $depth + 1, $args );
        }

        $html .= '</li>';
    }

    if ( $depth > 0 ) {
        if ( apply_filters( 'wpbdp_categories_list_anidate_children', true ) ) {
            $html .= '</ul>';
        }
    }

    return $html;
}

 /**
 * Produces a list of directory categories following some configuration settings that are overridable.
 *
 * The list of arguments is below:
 *      'parent' (int|object) - Parent directory category or category ID.
 *      'orderby' (string) default is taken from BD settings - What column to use for ordering the categories.
 *      'order' (string) default is taken from BD settings - What direction to order categories.
 *      'show_count' (boolean) default is taken from BD settings - Whether to show how many listings are in the category.
 *      'hide_empty' (boolean) default is False - Whether to hide empty categories or not.
 *      'parent_only' (boolean) default is False - Whether to show only direct childs of 'parent' or make a recursive list.
 *      'echo' (boolean) default is False - If True, the list will be printed in addition to returned by this function.
 *
 * @param string|array $args array of arguments to be used while creating the list.
 * @return string HTML output.
 * @since 2.3
 * @see wpbdp_directory_categories()
 */
function wpbdp_list_categories( $args=array() ) {
    $args = wp_parse_args( $args, array(
        'parent' => null,
        'echo' => false,
        'orderby' => wpbdp_get_option( 'categories-order-by' ),
        'order' => wpbdp_get_option( 'categories-sort' ),
        'show_count' => wpbdp_get_option('show-category-post-count'),
        'hide_empty' => false,
        'parent_only' => false,
        'parent' => 0
    ) );

    $html  =  '';
    $html .= '<ul class="wpbdp-categories ' . apply_filters( 'wpbdp_categories_list_css', '' )  . '">';
    $html .= _wpbdp_list_categories_walk( 0, 0, $args );
    $html .= '</ul>';

    $html = apply_filters( 'wpbdp_categories_list', $html );

    if ( $args['echo'] )
        echo $html;

    return $html;
}

function wpbdp_main_links() {
    $html  = '';
    $html .= '<div class="wpbdp-main-links">';

    if (wpbdp_get_option('show-submit-listing')) {
        $html .= sprintf('<input id="wpbdp-bar-submit-listing-button" type="button" value="%s" onclick="window.location.href = \'%s\'" class="button" />',
                          __('Submit A Listing', 'WPBDM'),
                          wpbdp_get_page_link('add-listing'));
/*        $html .= sprintf('<a href="%s">%s</a>',
                         wpbdp_get_page_link('add-listing'),
                         __('Submit A Listing', 'WPBDM'));*/
    }

    if (wpbdp_get_option('show-view-listings')) {
        $html .= sprintf('<input id="wpbdp-bar-view-listings-button" type="button" value="%s" onclick="window.location.href = \'%s\'" class="button" />',
                          __('View Listings', 'WPBDM'),
                          wpbdp_get_page_link('view-listings'));        
/*        $html .= sprintf('<a href="%s">%s</a>',
                         wpbdp_get_page_link('view-listings'),
                         __('View Listings', 'WPBDM')
                        );*/
    }

    if (wpbdp_get_option('show-directory-button')) {
        $html .= sprintf('<input id="wpbdp-bar-show-directory-button" type="button" value="%s" onclick="window.location.href = \'%s\'" class="button" />',
                          __('Directory', 'WPBDM'),
                          wpbdp_get_page_link('main'));
/*        $html .= sprintf('<a href="%s">%s</a>',
                         wpbdp_get_page_link('main'),
                         __('Directory', 'WPBDM')
                        );*/
    }

    $html .= '</div>';

    return $html;
}

function wpbdp_the_main_links() {
    echo wpbdp_main_links();
}

function wpbdp_search_form() {
    $html = '';
    $html .= sprintf('<form id="wpbdmsearchform" action="" method="GET" class="wpbdp-search-form">
                      <input type="hidden" name="action" value="search" />
                      <input type="hidden" name="page_id" value="%d" />
                      <input type="hidden" name="dosrch" value="1" />',
                      wpbdp_get_page_id('main'));
    $html .= '<input id="intextbox" maxlength="150" name="q" size="20" type="text" value="" />';
    $html .= sprintf('<input id="wpbdmsearchsubmit" class="submit" type="submit" value="%s" />',
                     _x('Search Listings', 'templates', 'WPBDM'));
    $html .= sprintf('<a href="%s" class="advanced-search-link">%s</a>',
                     add_query_arg('action', 'search', wpbdp_get_page_link('main')),
                     _x('Advanced Search', 'templates', 'WPBDM'));
    $html .= '</form>';

    return $html;
}

function wpbdp_the_search_form() {
    if (wpbdp_get_option('show-search-listings'))
        echo wpbdp_search_form();
}

function wpbdp_the_listing_excerpt() {
    echo wpbdp_render_listing(null, 'excerpt');
}

function wpbdp_listing_sort_options() {
    $sort_options = array();
    $sort_options = apply_filters('wpbdp_listing_sort_options', $sort_options);

    if (!$sort_options)
        return '';

    $current_sort = wpbdp_get_current_sort_option();

    $html  = '';
    $html .= '<div class="wpbdp-listings-sort-options">';
    $html .= _x('Sort By:', 'templates sort', 'WPBDM') . ' ';

    foreach ($sort_options as $id => $option) {
        $html .= sprintf('<span class="%s %s"><a href="%s">%s</a> %s</span>',
                        $id,
                        ($current_sort && $current_sort->option == $id) ? 'current': '',
                        ($current_sort && $current_sort->option == $id) ? add_query_arg('wpbdp_sort', ($current_sort->order == 'ASC' ? '-' : '') . $id) : add_query_arg('wpbdp_sort', $id),
                        $option[0],
                        ($current_sort && $current_sort->option == $id) ? ($current_sort->order == 'ASC' ? '↑' : '↓') : '↑'
                        );
        $html .= ' | ';
    }
    $html = substr($html, 0, -3);
    $html .= '<br />';

    if ($current_sort)
        $html .= sprintf('(<a href="%s" class="reset">Reset</a>)', remove_query_arg('wpbdp_sort'));
    $html .= '</div>';

    return $html;
}

function wpbdp_the_listing_sort_options() {
    echo wpbdp_listing_sort_options();
}

/**
 * @deprecated since 2.2.1
 */
function wpbdp_bar($parts=array()) {
    $parts = wp_parse_args($parts, array(
        'links' => true,
        'search' => false
    ));

    $html  = '<div class="wpbdp-bar cf">';
    $html .= apply_filters('wpbdp_bar_before', '', $parts);

    if ($parts['links'])
        $html .= wpbdp_main_links();
    if ($parts['search'])
        $html .= wpbdp_search_form();

    $html .= apply_filters('wpbdp_bar_after', '', $parts);
    $html .= '</div>';

    return $html;
}

/**
 * @deprecated since 2.2.1
 */
function wpbdp_the_bar($parts=array()) {
    echo wpbdp_bar($parts);
}

/**
 * Displays the listing main image.
 * @since 2.3
 */
function wpbdp_listing_thumbnail( $listing_id=null, $args=array() ) {
    if ( !$listing_id ) $listing_id = get_the_ID();

    $args = wp_parse_args( $args, array(
        'link' => 'picture',
        'class' => '',
        'echo' => false,
    ) );

    $main_image = false;
    $image_img = '';
    $image_link = '';
    $image_classes = 'wpbdp-thumbnail attachment-wpbdp-thumb ' . $args['class'];

    if ( $thumbnail_id = wpbdp_listings_api()->get_thumbnail_id( $listing_id ) ) {
        $main_image = get_post( $thumbnail_id );
    } else {
        $images = wpbdp_listings_api()->get_images( $listing_id );
        
        if ( $images )
            $main_image = $images[0];
    }

    if ( !$main_image && function_exists( 'has_post_thumbnail' ) && has_post_thumbnail( $listing_id ) ) {
        $image_img = get_the_post_thumbnail( $listing_id, 'wpbdp-thumb' );
    } elseif( !$main_image && wpbdp_get_option( 'use-default-picture' ) ) {
        $image_img = sprintf( '<img src="%s" alt="%s" title="%s" border="0" width="%d" class="%s" />',
                              WPBDP_URL . 'resources/images/default-image-big.gif',
                              get_the_title( $listing_id ),
                              get_the_title( $listing_id ),
                              wpbdp_get_option( 'thumbnail-width' ),
                              $image_classes
                            );
        $image_link = $args['link'] == 'picture' ? WPBDP_URL . 'resources/images/default-image-big.gif' : '';
    } elseif ( $main_image ) {
        $image_img = wp_get_attachment_image( $main_image->ID,
                                              'wpbdp-thumb',
                                              false,
                                              array(
                                                'alt' => get_the_title( $listing_id ),
                                                'title' => get_the_title( $listing_id ),
                                                'class' => $image_classes
                                                )
                                             );

        if ( $args['link'] == 'picture' ) {
            $full_image_data = wp_get_attachment_image_src( $main_image->ID, 'wpbdp-large' );
            $image_link = $full_image_data[0];
        }

    }

    if ( !$image_link && $args['link'] == 'listing' )
        $image_link = get_permalink( $listing_id );

    if ( $image_img ) {
        if ( !$image_link ) {
            return $image_img;
        } else {
            return sprintf( '<div class="listing-thumbnail"><a href="%s" class="%s">%s</a></div>',
                            $image_link,
                            $args['link'] == 'picture' ? 'thickbox lightbox fancybox' : '',
                            $image_img );
        }
    }

    return '' ;
}


/**
 * Renders the listing contact form.
 * @param int $listing_id the listing ID.
 * @param array $validation_errors optional validation errors to be displayed along with the form.
 * @since 2.3
 */
function wpbdp_listing_contact_form ( $listing_id=0, $validation_errors=array() ) {
    if ( !$listing_id ) $listing_id = get_the_ID();

    if ( !wpbdp_get_option( 'show-contact-form' ) )
        return '';

    $action = '';
    $recaptcha = null;

    if ( wpbdp_get_option( 'recaptcha-on' ) ) {
        if ( $public_key = wpbdp_get_option( 'recaptcha-public-key' ) ) {
            require_once( WPBDP_PATH . 'recaptcha/recaptchalib.php' );
            $recaptcha = recaptcha_get_html( $public_key );
        }
    }

    return wpbdp_render( 'listing-contactform', array(
                         'action' => $action,
                         'validation_errors' => $validation_errors,
                         'listing_id' => $listing_id,
                         'current_user' => is_user_logged_in() ? wp_get_current_user() : null,
                         'recaptcha' => $recaptcha
                        ), false );
}
