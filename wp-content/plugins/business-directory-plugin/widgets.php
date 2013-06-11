<?php
/**
 * Latest listings widget.
 * @since 2.1
 */
class WPBDP_LatestListingsWidget extends WP_Widget {

    public function __construct() {
        parent::__construct(false,
                            _x('Business Directory - Latest Listings', 'widgets', 'WPBDM'),
                            array('description' => _x('Displays a list of the latest listings in the Business Directory.', 'widgets', 'WPBDM')));
    }

    public function form($instance) {
        if (isset($instance['title']))
            $title = $instance['title'];
        else
            $title = _x('Latest Listings', 'widgets', 'WPBDM');

        echo sprintf('<p><label for="%s">%s</label> <input class="widefat" id="%s" name="%s" type="text" value="%s" /></p>',
                     $this->get_field_id('title'),
                     _x('Title:', 'widgets', 'WPBDM'),
                     $this->get_field_id('title'),
                     $this->get_field_name('title'),
                     esc_attr($title)
                    );
        echo sprintf('<p><label for="%s">%s</label> <input class="widefat" id="%s" name="%s" type="text" value="%s" /></p>',
                     $this->get_field_id('number_of_listings'),
                     _x('Number of listings to display:', 'widgets', 'WPBDM'),
                     $this->get_field_id('number_of_listings'),
                     $this->get_field_name('number_of_listings'),
                     isset($instance['number_of_listings']) ? intval($instance['number_of_listings']) : 10
                    );        
    }

    public function update($new_instance, $old_instance) {
        $new_instance['title'] = strip_tags($new_instance['title']);
        $new_instance['number_of_listings'] = max(intval($new_instance['number_of_listings']), 0);
        return $new_instance;
    }

    public function widget($args, $instance) {
        extract($args);
        $title = apply_filters( 'widget_title', $instance['title'] );

        echo $before_widget;
        if ( ! empty( $title ) )
            echo $before_title . $title . $after_title;
        echo wpbdp_latest_listings($instance['number_of_listings']);
        echo $after_widget;        
    }

}


/**
 * Featured listings widget.
 * @since 2.1
 */
class WPBDP_FeaturedListingsWidget extends WP_Widget {
    
    public function __construct() {
        parent::__construct(false, _x('Business Directory - Featured Listings', 'widgets', 'WPBDM'),
                            array('description' => _x('Displays a list of the featured/sticky listings in the directory.', 'widgets', 'WPBDM')));
    }

    public function form($instance) {
        echo sprintf('<p><label for="%s">%s</label> <input class="widefat" id="%s" name="%s" type="text" value="%s" /></p>',
                     $this->get_field_id('title'),
                     _x('Title:', 'widgets', 'WPBDM'),
                     $this->get_field_id('title'),
                     $this->get_field_name('title'),
                     isset($instance['title']) ? esc_attr($instance['title']) : _x('Featured Listings', 'widgets', 'WPBDM')
                    );
        echo sprintf('<p><label for="%s">%s</label> <input class="widefat" id="%s" name="%s" type="text" value="%s" /></p>',
                     $this->get_field_id('number_of_listings'),
                     _x('Number of listings to display:', 'widgets', 'WPBDM'),
                     $this->get_field_id('number_of_listings'),
                     $this->get_field_name('number_of_listings'),
                     isset($instance['number_of_listings']) ? intval($instance['number_of_listings']) : 10
                    );
    }

    public function update($new_instance, $old_instance) {
        $new_instance['title'] = strip_tags($new_instance['title']);
        $new_instance['number_of_listings'] = max(intval($new_instance['number_of_listings']), 0);
        return $new_instance;
    }

    public function widget($args, $instance) {
        extract($args);
        $title = apply_filters( 'widget_title', $instance['title'] );

        $posts = get_posts(array(
            'post_type' => wpbdp_post_type(),
            'post_status' => 'publish',
            'numberposts' => $instance['number_of_listings'],
            'orderby' => 'date',
            'meta_query' => array(
                array('key' => '_wpbdp[sticky]', 'value' => 'sticky')
            )
        ));

        if ($posts) {
            echo $before_widget;
            if ( ! empty( $title ) ) echo $before_title . $title . $after_title;

            echo '<ul>';
            foreach ($posts as $post) {
                echo '<li>';
                echo sprintf('<a href="%s">%s</a>', get_permalink($post->ID), get_the_title($post->ID));
                echo '</li>';
            }

            echo '</ul>';
            echo $after_widget;
        }
    }    


}

/**
 * Random listings widget.
 * @since 2.1
 */
class WPBDP_RandomListingsWidget extends WP_Widget {

    public function __construct() {
        parent::__construct(false,
                            _x('Business Directory - Random Listings', 'widgets', 'WPBDM'),
                            array('description' => _x('Displays a list of random listings from the Business Directory.', 'widgets', 'WPBDM')));
    }

    public function form($instance) {
        if (isset($instance['title']))
            $title = $instance['title'];
        else
            $title = _x('Random Listings', 'widgets', 'WPBDM');

        echo sprintf('<p><label for="%s">%s</label> <input class="widefat" id="%s" name="%s" type="text" value="%s" /></p>',
                     $this->get_field_id('title'),
                     _x('Title:', 'widgets', 'WPBDM'),
                     $this->get_field_id('title'),
                     $this->get_field_name('title'),
                     esc_attr($title)
                    );
        echo sprintf('<p><label for="%s">%s</label> <input class="widefat" id="%s" name="%s" type="text" value="%s" /></p>',
                     $this->get_field_id('number_of_listings'),
                     _x('Number of listings to display:', 'widgets', 'WPBDM'),
                     $this->get_field_id('number_of_listings'),
                     $this->get_field_name('number_of_listings'),
                     isset($instance['number_of_listings']) ? intval($instance['number_of_listings']) : 10
                    );        
    }

    public function update($new_instance, $old_instance) {
        $new_instance['title'] = strip_tags($new_instance['title']);
        $new_instance['number_of_listings'] = max(intval($new_instance['number_of_listings']), 0);
        return $new_instance;
    }

    private function random_posts($n) {
        global $wpdb;

        $n = max(intval($n), 0);

        $query = $wpdb->prepare("SELECT ID FROM {$wpdb->posts} WHERE post_type = %s AND post_status = %s ORDER BY RAND() LIMIT {$n}",
                                wpbdp_post_type(), 'publish');
        return $wpdb->get_col($query);
    }

    public function widget($args, $instance) {
        $post_ids = $this->random_posts($instance['number_of_listings']);

        if (!$post_ids) return;

        $posts = get_posts(array(
            'post_type' => wpbdp_post_type(),
            'post_status' => 'publish',
            'numberposts' => $instance['number_of_listings'],
            'post__in' => $post_ids
        ));

        if ($posts) {
            extract($args);
            $title = apply_filters( 'widget_title', $instance['title'] );

            echo $before_widget;
            if ( ! empty( $title ) ) echo $before_title . $title . $after_title;

            echo '<ul>';
            foreach ($posts as $post) {
                echo '<li>';
                echo sprintf('<a href="%s">%s</a>', get_permalink($post->ID), get_the_title($post->ID));
                echo '</li>';
            }

            echo '</ul>';
            echo $after_widget;
        }        

    }

}

/**
 * Search widget.
 * @since 2.1.6
 */
class WPBDP_SearchWidget extends WP_Widget {

    public function __construct() {
        parent::__construct(false,
                            _x('Business Directory - Search', 'widgets', 'WPBDM'),
                            array('description' => _x('Displays a search form to look for Business Directory listings.', 'widgets', 'WPBDM')));
    }

    public function form($instance) {
        if (isset($instance['title']))
            $title = $instance['title'];
        else
            $title = _x('Search the Business Directory', 'widgets', 'WPBDM');

        echo sprintf('<p><label for="%s">%s</label> <input class="widefat" id="%s" name="%s" type="text" value="%s" /></p>',
                     $this->get_field_id('title'),
                     _x('Title:', 'widgets', 'WPBDM'),
                     $this->get_field_id('title'),
                     $this->get_field_name('title'),
                     esc_attr($title)
                    );
        echo '<p>';

        echo _x('Form Style:', 'widgets', 'WPBDM');
        echo '<br/>';
        echo sprintf('<input id="%s" name="%s" type="radio" value="%s" %s/> <label for="%s">%s</label>',
                     $this->get_field_id('use_basic_form'),
                     $this->get_field_name('form_mode'),
                     'basic', 
                     wpbdp_getv($instance, 'form_mode', 'basic') == 'basic' ? 'checked="checked"' : '',
                     $this->get_field_id('use_basic_form'),                     
                    _x('Basic', 'widgets', 'WPBDM') );
        echo '&nbsp;&nbsp;';
        echo sprintf('<input id="%s" name="%s" type="radio" value="%s" %s/> <label for="%s">%s</label>',
                     $this->get_field_id('use_advanced_form'),
                     $this->get_field_name('form_mode'),
                     'advanced',
                     wpbdp_getv($instance, 'form_mode', 'basic') == 'advanced' ? 'checked="checked"' : '',
                     $this->get_field_id('use_advanced_form'),
                    _x('Advanced', 'widgets', 'WPBDM') );
        echo '</p>';

        echo '<p class="wpbdp-search-widget-advanced-settings">';
        echo _x('Search Fields (advanced mode):', 'widgets', 'WPBDM') . '<br/>';
        echo ' <span class="description">' . _x('Display the following fields in the form.', 'widgets', 'WPBDM') . '</span>';

        $instance_fields = wpbdp_getv( $instance, 'search_fields', array() );

        $api = wpbdp_formfields_api();

        echo sprintf('<select name="%s[]" multiple="multiple">', $this->get_field_name('search_fields'));

        foreach ( $api->get_fields() as $field ) {
            if ( $field->display_in( 'search' ) ) {
                echo sprintf( '<option value="%s" %s>%s</option>',
                              $field->get_id(),
                              ( !$instance_fields || in_array( $field->get_id(), $instance_fields) ) ? 'selected="selected"' : '',
                             esc_attr( $field->get_label() ) );
            }
        }

        echo '</select>';
        echo '</p>';
    }

    public function update($new_instance, $old_instance) {
        $new_instance['title'] = strip_tags($new_instance['title']);
        $new_instance['form_mode'] = wpbdp_getv($new_instance, 'form_mode', 'basic');
        $new_instance['search_fields'] = wpbdp_getv($new_instance, 'search_fields', array());
        return $new_instance;
    }

    public function widget($args, $instance) {
        extract($args);
        $title = apply_filters( 'widget_title', $instance['title'] );

        echo $before_widget;
        if ( ! empty( $title ) ) echo $before_title . $title . $after_title;

        echo sprintf('<form action="%s" method="GET">', wpbdp_get_page_link() );
        echo '<input type="hidden" name="action" value="search" />';
        echo sprintf('<input type="hidden" name="page_id" value="%s" />', wpbdp_get_page_id('main'));
        echo '<input type="hidden" name="dosrch" value="1" />';

        if (wpbdp_getv($instance, 'form_mode', 'basic') == 'advanced') {
            $fields_api = wpbdp_formfields_api();

            foreach  ( $fields_api->get_fields() as $field ) {
                if ( $field->display_in( 'search' ) && in_array( $field->get_id(), $instance['search_fields'] ) ) {
                    echo $field->render( null, 'search' );
                }
            }
        } else {
            echo '<input type="text" name="q" value="" />';
        }

        echo sprintf('<p><input type="submit" value="%s" class="submit wpbdp-search-widget-submit" /></p>', _x('Search', 'widgets', 'WPBDM'));
        echo '</form>';

        echo $after_widget;
    }    

}