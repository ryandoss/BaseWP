<?php
/**
 * Form fields API.
 */

if (!class_exists('WPBDP_FormFields')) {


class WPBDP_FormFieldType {

    private $name = null;

    public function __construct( $name ) {
        $this->name = trim( $name );
    }

    public function get_id() {
        return get_class( $this );
    }

    public function get_name() {
        return $this->name;
    }

    /**
     * Called after a field of this type is constructed.
     * @param object $field
     */
    public function setup_field( &$field ) {
        return;
    }

    public function get_field_value( &$field, $post_id ) {
        $post = get_post( $post_id );

        if ( !$post )
            return null;

        switch ( $field->get_association() ) {
            case 'title':
                $value = $post->post_title;
                break;
            case 'excerpt':
                $value = $post->post_excerpt;
                break;
            case 'content':
                $value = $post->post_content;
                break;
            case 'category':
                $value = wp_get_object_terms( $post_id, WPBDP_CATEGORY_TAX, array( 'fields' => 'ids' ) );
                break;
            case 'tags':
                $value = wp_get_object_terms( $post_id, WPBDP_TAGS_TAX, array( 'fields' => 'ids' ) );
                break;
            case 'meta':
                $value = get_post_meta( $post_id, '_wpbdp[fields][' . $field->get_id() . ']', true );
                break;
            default:
                $value = null;
                break;
        }

        return $value;
    }

    public function get_field_html_value( &$field, $post_id ) {
        $post = get_post( $post_id );

        switch ( $field->get_association() ) {
            case 'title':
                $value = sprintf( '<a href="%s">%s</a>', get_permalink( $post_id ), get_the_title( $post_id ) );
                break;
            case 'excerpt':
                $value = apply_filters( 'get_the_excerpt', wpautop( $post->post_excerpt, true ) );
                break;
            case 'content':
                $value = apply_filters( 'the_content', $post->post_content );
                break;
            case 'category':
                $value = get_the_term_list( $post_id, WPBDP_CATEGORY_TAX, '', ', ', '' );
                break;
            case 'tags':
                $value = get_the_term_list( $post_id, WPBDP_TAGS_TAX, '', ', ', '' );
                break;
            case 'meta':
            default:
                $value = $field->value( $post_id );
                break;
        }

        return $value;
    }

    public function get_field_plain_value( &$field, $post_id ) {
        return $this->get_field_value( $field, $post_id );
    }

    public function is_empty_value( $value ) {
        return empty( $value );
    }

    public function convert_input( &$field, $input ) {
        return $input;
    }

    public function store_field_value( &$field, $post_id, $value ) {
        switch ( $field->get_association() ) {
            case 'title':
                wp_update_post( array( 'ID' => $post_id, 'post_title' => trim( strip_tags( $value ) ) ) );
                break;
            case 'excerpt':
                wp_update_post( array( 'ID' => $post_id, 'post_excerpt' => $value ) );
                break;
            case 'content':
                wp_update_post( array( 'ID' => $post_id, 'post_content' => $value ) );
                break;
            case 'category':
                wp_set_post_terms( $post_id, $value, WPBDP_CATEGORY_TAX, false );
                break;
            case 'tags':
                wp_set_post_terms( $post_id, $value, WPBDP_TAGS_TAX, false );
                break;
            case 'meta':
                update_post_meta( $post_id, '_wpbdp[fields][' . $field->get_id() . ']', $value );
                break;
        }        
    }

    // this function should not try to hide values depending on field, context or value itself.
    public function display_field( &$field, $post_id, $display_context ) {
        return self::standard_display_wrapper( $field, $field->html_value( $post_id ) );
    }

    public function render_field_inner( &$field, $value, $render_context ) {
        return '';
    }

    public function render_field( &$field, $value, $render_context ) {
        $html = '';

        switch ( $render_context ) {
            case 'search':
                $html .= sprintf( '<div class="search-filter %s %s" %s>',
                                  $field->get_field_type()->get_id(),
                                  implode(' ', $field->css_classes ),
                                  $this->html_attributes( $field->html_attributes ) );
                $html .= sprintf( '<div class="label"><label>%s</label></div>', esc_attr( $field->get_label() ) );
                $html .= '<div class="field inner">';

                $field_inner = $this->render_field_inner( $field, $value, $render_context );
                $field_inner = apply_filters_ref_array( 'wpbdp_render_field_inner', array( $field_inner, &$field, $value, $render_context ) );
                
                $html .= $field_inner;
                $html .= '</div>';
                $html .= '</div>';

                break;

            case 'submit':
            case 'edit':
            default:
                $html .= sprintf( '<div class="wpbdp-form-field %s %s %s %s" %s>',
                                  $field->get_field_type()->get_id(),
                                  $field->get_description() ? 'with-description' : '',
                                  implode( ' ', $field->get_validators() ),
                                  implode( ' ', $field->css_classes),
                                  $this->html_attributes( $field->html_attributes )
                                   );
                $html .= '<div class="wpbdp-form-field-label">';
                $html .= sprintf( '<label for="%s">%s</label>', 'wpbdp-field-' . $field->get_id(), esc_attr( $field->get_label() ) );

                if ( $field->get_description() )
                    $html .= sprintf( '<span class="field-description">(%s)</span>', $field->get_description() );

                $html .= '</div>';
                $html .= '<div class="wpbdp-form-field-html wpbdp-form-field-inner">';

                $field_inner = $this->render_field_inner( $field, $value, $render_context );
                $field_inner = apply_filters_ref_array( 'wpbdp_render_field_inner', array( $field_inner, &$field, $value, $render_context ) );                

                $html .= $field_inner;
                $html .= '</div>';
                $html .= '</div>';

                break;
        }

        return $html;      
    }

    /**
     * Called after a field of this type is deleted.
     * @param object $field the deleted WPBDP_FormField object.
     */
    public function cleanup( &$field ) {
        if ( $field->get_association() == 'meta' ) {
            global $wpdb;
            $wpdb->query( $wpdb->prepare( "DELETE * FROM {$wpdb->postmeta} WHERE meta_key = %s", '_wpbdp[fields][' . $field->get_id() . ']' ) );
        }


    }

    /**
     * Returns an array of valid associations for this field type.
     * @return array
     */
    public function get_supported_associations() {
        return array( 'title', 'excerpt', 'content', 'category', 'tags', 'meta' );
    }

    /**
     * Renders the field-specific settings area for fields of this type.
     * It is recommended to use `render_admin_settings` here to keep an uniform look.
     * `$_POST` values can be used here to populate things when needed.
     * @param object $field might be NULL if field is new or the field that is being edited.
     * @param string $association field association.
     * @return string the HTML output.
     */
    public function render_field_settings( &$field=null, $association=null ) {
        return '';
    }

    /**
     * Called when saving fields of this type.
     * It should be used by field types to store any field type specific configuration.
     * @param object $field the field being saved.
     * @return mixed WP_Error in case of error, anything else for success.
     */
    public function process_field_settings( &$field ) {
        return;
    }


    /* Utils. */
    public static function standard_display_wrapper( $labelorfield, $content=null, $extra_classes='', $args=array() ) {
        $css_classes = 'field-value ';

        if ( is_object( $labelorfield ) ) {
            if ( $labelorfield->has_display_flag( 'social' ) )
                return $content;

            $css_classes .= 'wpbdp-field-' . strtolower( str_replace( array( ' ', '/' ), '', $labelorfield->get_label() ) ) . ' ' . $labelorfield->get_association() . ' ';
            $label = $labelorfield->has_display_flag( 'nolabel' ) ? null : $labelorfield->get_label();
        } else {
            $label = $labelorfield;
        }

        $html  = '';
        $html .= '<div class="' . $css_classes . '">';
        
        if ( $label )
            $html .= '<label>' . esc_html( $label ) . ':</label> ';
        
        if ($content)
            $html .= '<span class="value">' . $content . '</span>';
        
        $html .= '</div>';

        return $html;
    }

    public static function render_admin_settings( $admin_settings=array() ) {
        if ( !$admin_settings )
            return '';

        $html  = '';
        $html .= '<table class="form-table">';

        foreach ( $admin_settings as $s ) {
            $label = is_array( $s ) ? $s[0] : '';
            $content = is_array( $s ) ? $s[1] : $s;

            $html .= '<tr>';
            if ( $label ) {
                $html .= '<th scope="row">';
                $html .= '<label>' . $label . '</label>';
                $html .= '</th>';
            }

            $html .= $label ? '<td>' : '<td colspan="2">';
            $html .= $content;
            $html .= '</td>';
            $html .= '</tr>';
        }

        $html .= '</table>';

        return $html;
    }

    public static function html_attributes( $attrs ) {
        $html = '';

        foreach ( $attrs as $k => $v ) {
            if ( $k == 'class' ) continue; // use ->css_classes for this

            $html .= sprintf( '%s=%s ', $k, $v );
        }

        return $html;
    }

}

/**
 * Represents a single field from the database. This class can not be instantiated directly.
 *
 * @since 2.3
 */
class WPBDP_FormField {

    private $id;
    private $type;
    private $association;

    private $label;
    private $description;    

    private $weight = 0;

    private $validators = array();
    
    private $display_flags = array();
    private $field_data = array();

    public $css_classes = array();
    public $html_attributes = array();


    public function __construct( $attrs=array() ) {
        $defaults = array(
            'id' => 0,
            'label' => '',
            'description' => '',
            'field_type' => 'textfield',
            'association' => 'meta',
            'weight' => 0,
            'validators' => array(),
            'display_flags' => array(),
            /*'display_flags' => array( 'excerpt', 'listing', 'search' ),*/
            'field_data' => array()
        );

        $attrs = wp_parse_args( $attrs, $defaults );

        $formfields = WPBDP_FormFields::instance();

        $this->id = intval( $attrs['id'] );
        $this->label = $attrs['label'];
        $this->description = $attrs['description'];
        $this->type = is_object( $attrs['field_type'] ) ? $attrs['field_type'] : WPBDP_FormFields::instance()->get_field_type( $attrs['field_type'] );

        // if ( !$this->type )
        //     throw new Exception( _x( 'Invalid form field type', 'form-fields-api', 'WPBDM' ) );

        if ( !$this->type ) // temporary workaround related to 3.0 upgrade issues (issue #365)
            $this->type = WPBDP_FormFields::instance()->get_field_type( 'textfield' );

        $this->association = $attrs['association'];
        $this->weight = intval( $attrs['weight'] );

        /* Validators */
        // TODO: make sure validators are valid here
        if ( is_array( $attrs['validators'] ) ) {
            foreach ( $attrs['validators'] as $validator ) {
                if ( $validator && !in_array( $validator, $this->validators, true ) )
                    $this->validators[] = $validator;
            }
        }

        /* display_options */
        $this->display_flags = $attrs['display_flags'];
        $this->field_data = $attrs['field_data'];

        if ( in_array( $this->association, array( 'category', 'tags' ), true ) ) {
            // TODO: make this hierarchical (see https://codex.wordpress.org/Function_Reference/Walker_Class)
            $terms = get_terms( $this->association == 'tags' ? WPBDP_TAGS_TAX : wpbdp_categories_taxonomy(), 'hide_empty=0&hierarchical=1' );
            $options = array();

            foreach ( $terms as &$term ) {
                $k = $this->association == 'tags' ? $term->slug : $term->term_id;
                $options [ $k ] = $term->name;
            }

            $this->field_data['options'] = $options;
        } else {
            // handle some special extra data from previous BD versions
            // TODO: this is not needed anymore since the 3.2 upgrade routine
            if ( isset( $attrs['field_data'] ) && isset( $attrs['field_data']['options'] )  ) {
                $options = array();

                foreach ( $attrs['field_data']['options'] as $option_value ) {
                    if ( is_array( $option_value ) )
                        $options[ $option_value[0] ] = $option_value[1];
                    else
                        $options[ $option_value ] = $option_value;
                }

                $this->field_data['options'] = $options;
            }
        }

        $this->type->setup_field( $this );
        do_action_ref_array( 'wpbdp_form_field_setup', array( &$this ) );
    }

    public function get_id() {
        return $this->id;
    }

    public function &get_field_type() {
        return $this->type;
    }

    public function get_field_type_id() {
        return $this->type->get_id();
    }

    public function get_association() {
        return $this->association;
    }

    public function get_label() {
        return $this->label;
    }

    public function get_description() {
        return $this->description;
    }

    public function get_short_name() {
        global $wpbdp;
        return $wpbdp->formfields->get_short_names( $this->id );
    }

    public function &get_validators() {
        return $this->validators;
    }

    public function get_weight() {
        return $this->weight;
    }

    public function has_validator( $validator ) {
        return in_array( $validator, $this->validators, true );
    }

    public function add_validator( $validator ) {
        if ( !$this->has_validator( $validator ) )
            $this->validators[] = $validator;
    }

    public function is_required() {
        return in_array( 'required', $this->validators, true );
    }

    public function display_in( $context ) {
        return in_array( $context, $this->display_flags, true);
    }

    public function add_display_flag( $flagorflags ) {
        $flagorflags = is_array( $flagorflags ) ? $flagorflags : array( $flagorflags );

        foreach ( $flagorflags as $flag ) {
            if ( !$this->has_display_flag( $flag ) ) {
                $this->display_flags[] = $flag;    
            }
        }
    }

    public function remove_display_flag( $flagorflags ) {
        $flagorflags = is_array( $flagorflags ) ? $flagorflags : array( $flagorflags );
        
        foreach ( $flagorflags as $flag )
            wpbdp_array_remove_value( $this->display_flags, $flag );
    }

    public function has_display_flag( $flag ) {
        return in_array( $flag, $this->display_flags, true );
    }

    public function set_display_flags( $flags ) {
        $this->display_flags = is_array( $flags ) ? $flags : array();
    }

    public function get_display_flags() {
        return $this->display_flags;
    }

    /**
     * Returns field-type specific configuration options for this field.
     * @param string $key configuration key name
     * @return mixed|array if $key is ommitted an array of all key/values will be returned
     */
    public function data( $key=null ) {
        if ( !$key )
            return $this->field_data;

        $res = isset( $this->field_data[$key] ) ? $this->field_data[$key] : null;
        return apply_filters( 'wpbdp_form_field_data', $res, $key, $this );
    }

    /**
     * Saves field-type specific configuration options for this field.
     * @param string $key configuration key name.
     * @param mixed $value data value.
     * @return mixed data value.
     */
    public function set_data( $key, $value=null ) {
        $this->field_data[ $key ] = $value;
    }

    /**
     * Removes any field-type specific configuration option from this field. Use with caution.
     */
    public function clear_data() {
        $this->field_data = array();
    }

    /**
     * Returns this field's raw value for the given post.
     * @param int|object $post_id post ID or object.
     * @return mixed
     */
    public function value( $post_id ) {
        if ( !get_post_type( $post_id ) == wpbdp_post_type() )
            return null;        

        $value = $this->type->get_field_value( $this, $post_id );
        $value = apply_filters( 'wpbdp_form_field_value', $value, $post_id, $this );

        return $value;
    }

    /**
     * Returns this field's HTML value for the given post. Useful for display.
     * @param int|object $post_id post ID or object.
     * @return string valid HTML.
     */
    public function html_value( $post_id ) {
        $value = $this->type->get_field_html_value( $this, $post_id );
        return apply_filters( 'wpbdp_form_field_html_value', $value , $post_id, $this );
    }

    /**
     * Returns this field's value as plain text. Useful for emails or cooperation between modules.
     * @param int|object $post_id post ID or object.
     * @return string
     */
    public function plain_value( $post_id ) {
        $value = $this->type->get_field_plain_value( $this, $post_id );
        return apply_filters( 'wpbdp_form_field_plain_value', $value, $post_id, $this );
    }

    /**
     * Converts input from forms to a value useful for this field.
     * @param mixed $input form input.
     * @return mixed
     */
    public function convert_input( $input=null ) {
        return $this->type->convert_input( $this, $input );
    }

    public function store_value( $post_id, $value ) {
        return $this->type->store_field_value( $this, $post_id, $value );
    }

    public function is_empty_value( $value ) {
        return $this->type->is_empty_value( $value );
    }

    public function validate( $value, &$errors=null ) {
        $errors = !is_array( $errors ) ? array() : $errors;

        $validation_api = WPBDP_FieldValidation::instance();

        if ( !$this->is_required() && $this->type->is_empty_value( $value ) )
            return true;

        foreach ( $this->validators as $validator ) {
            $res = $validation_api->validate_field( $this, $value, $validator );

            if ( is_wp_error( $res ) ) {
                $errors[] = $res->get_error_message();
            }
        }

        if ( !$errors )
            return true;

        return false;
    }

    /**
     * Returns HTML apt for display of this field's value.
     * @param int|object $post_id post ID or object
     * @param string $display_context the display context. defaults to 'listing'.
     * @return string
     */
    public function display( $post_id, $display_context='listing' ) {
        if ( in_array( 'email', $this->validators, true ) && !wpbdp_get_option('override-email-blocking') )
            return '';

        if ( $this->type->is_empty_value( $this->value( $post_id ) ) )
            return '';

        return $this->type->display_field( $this, $post_id, $display_context );
    }

    /**
     * Returns HTML apt for displaying this field in forms.
     * @param mixed $value the value to be displayed. defaults to null.
     * @param string $display_context the rendering context. defaults to 'submit'.
     * @return string
     */
    public function render( $value=null, $display_context='submit' ) {
        do_action_ref_array( 'wpbdp_form_field_pre_render', array( &$this, $value, $display_context ) );
        return $this->type->render_field( $this, $value, $display_context );
    }

    /**
     * Tries to save this field to the database. If successfully, sets the new id too.
     * @return mixed True if successfully created, WP_Error in the other case
     */
    public function save() {
        global $wpdb;

        $api = wpbdp_formfields_api();

        if ( !$this->label || trim( $this->label ) == '' )
            return new WP_Error( 'wpbdp-save-error', _x('Field label is required.', 'form-fields-api', 'WPBDM') );

        if ( isset( $_POST['field'] ) ) {
            $res = $this->type->process_field_settings( $this );

            if ( is_wp_error( $res ) )
                return $res;
        }

        // enforce association constraints
        global $wpbdp;
        $flags = $wpbdp->formfields->get_association_flags( $this->association );
        
        if ( in_array( 'unique', $flags ) ) {
            if ( $otherfields = wpbdp_get_form_fields( 'association=' . $this->association ) ) {
                if ( ( count( $otherfields ) > 1 ) || ( $otherfields[0]->get_id() != $this->id ) ) {
                    return new WP_Error( 'wpbdp-field-error', sprintf( _x( 'There can only be one field with association "%s". Please select another association.', 'form-fields-api', 'WPBDM' ), $this->association ) );
                }
            }
        }

      if ( !in_array( $this->type->get_id(), $wpbdp->formfields->get_association_field_types( $this->association ) ) ) {
            return new WP_Error( 'wpbdp-field-error', sprintf( _x( '"%s" is an invalid field type for this association.', 'form-fields-api', 'WPBDM' ), $this->type->get_name() ) );
        }

        $data = array();
        $data['label'] = $this->label;
        $data['description'] = trim( $this->description );
        $data['field_type'] = $this->type->get_id();
        $data['association'] = $this->association;
        $data['validators'] = implode( ',', $this->validators );
        $data['weight'] = $this->weight;
        $data['display_flags'] = implode( ',', $this->display_flags );
        $data['field_data'] = serialize( $this->field_data );

        if ( $this->id ) {
            $wpdb->update( "{$wpdb->prefix}wpbdp_form_fields", $data, array( 'id' => $this->id ) );
        } else {
            $wpdb->insert( "{$wpdb->prefix}wpbdp_form_fields", $data );
            $this->id = intval( $wpdb->insert_id );
        }

        $api->_calculate_short_names();
    }

    /**
     * Tries to delete this field from the database.
     * @return mixed True if successfully deleted, WP_Error in the other case
     */
    public function delete() {
        if ( !$this->id )
            return new WP_Error( 'wpbdp-delete-error', _x( 'Invalid field ID', 'form-fields-api', 'WPBDM' ) );

        global $wpbdp;
        $flags = $wpbdp->formfields->get_association_flags( $this->association );

        if ( in_array( 'required', $flags ) ) {
            $otherfields = wpbdp_get_form_fields( array( 'association' => $this->association ) );

            if ( !$otherfields || ( $otherfields[0]->get_id() == $this->id ) )
               return new WP_Error( 'wpbdp-delete-error', _x( "This form field can't be deleted because it is required for the plugin to work.", 'form-fields api', 'WPBDM' ) ); 
        }

        global $wpdb;

        if ( $wpdb->query( $wpdb->prepare( "DELETE FROM  {$wpdb->prefix}wpbdp_form_fields WHERE id = %d", $this->id ) ) !== false ) {
            $this->type->cleanup( $this );
            $this->id = 0;
        } else {
            return new WP_Error( 'wpbdp-delete-error', _x( 'An error occurred while trying to delete this field.', 'form-fields-api', 'WPBDM' ) );
        }

        $api = wpbdp_formfields_api();
        $api->_calculate_short_names();

        return true;
    }

    /**
     * Reorders this field within the list of fields.
     * @param int $delta if positive, field is moved up. else is moved down.
     */
    public function reorder( $delta=0 ) {
        global $wpdb;

        $delta = intval( $delta );

        if ( !$delta )
            return;

        if ( $delta > 0 ) {
            $fields = $wpdb->get_results( $wpdb->prepare( "SELECT id, weight FROM {$wpdb->prefix}wpbdp_form_fields WHERE weight >= %d ORDER BY weight ASC", $this->weight ) );

            if ( $fields[count($fields) - 1]->id == $this->id )
                return;

            for ( $i = 0; $i < count( $fields ); $i++ ) {
                $fields[ $i ]->weight = intval( $this->weight ) + $i;

                if ($fields[ $i ]->id == $this->id ) {
                    $fields[ $i ]->weight += 1;
                    $fields[ $i+1 ]->weight -= 1;
                    $i += 1;
                } 
            }

            foreach ( $fields as &$f ) {
                $wpdb->update( "{$wpdb->prefix}wpbdp_form_fields", array( 'weight' => $f->weight ), array( 'id' => $f->id ) );
            }
        } else {
            $fields = $wpdb->get_results( $wpdb->prepare( "SELECT id, weight FROM {$wpdb->prefix}wpbdp_form_fields WHERE weight <= %d ORDER BY weight ASC", $this->weight ) );

            if ( $fields[0]->id == $this->id )
                return;

            foreach ( $fields as $i => $f ) {
                if ( $f->id == $this->id ) {
                    self::get( $fields[ $i-1 ]->id )->reorder( 1 );
                    return;
                }
            }

        }

    } 

    /**
     * Creates a WPBDP_FormField from a database record.
     * @param int $id the database record ID.
     * @return WPBDP_FormField a valid WPBDP_FormField if the record exists or null if not.
     */
    public static function get( $id ) {
        global $wpdb;

        $field = $wpdb->get_row( $wpdb->prepare( "SELECT * FROM {$wpdb->prefix}wpbdp_form_fields WHERE id = %d", $id ) );

        if ( !$field )
            return null;

        $field = (array) $field;

        $field['display_flags'] = explode( ',', $field['display_flags'] ); 
        $field['validators'] = explode( ',', $field['validators'] );
        $field['field_data'] = unserialize( $field['field_data'] );

        try {
            return new WPBDP_FormField( $field );
        } catch (Exception $e) {
            return null;
        }
    }

}

require_once( WPBDP_PATH . 'api/form-fields-types.php' );

class WPBDP_FormFields {

    private $associations = array();
    private $association_flags = array();
    private $association_field_types = array();

    private $field_types = array();

    private static $instance = null;

    public static function instance() {
        if (is_null(self::$instance)) {
            self::$instance = new self;
        }

        return self::$instance;
    }   

    private function __construct() {
        // register core associations
        $this->register_association( 'title', _x( 'Post Title', 'form-fields api', 'WPBDM' ), array( 'required', 'unique' ) );
        $this->register_association( 'content', _x( 'Post Content', 'form-fields api', 'WPBDM' ), array( 'required', 'unique' ) );
        $this->register_association( 'excerpt', _x( 'Post Excerpt', 'form-fields api', 'WPBDM' ), array( 'unique' ) );
        $this->register_association( 'category', _x( 'Post Category', 'form-fields api', 'WPBDM' ), array( 'required', 'unique' ) );
        $this->register_association( 'tags', _x( 'Post Tags', 'form-fields api', 'WPBDM' ), array( 'unique' ) );
        $this->register_association( 'meta', _x( 'Post Metadata', 'form-fields api', 'WPBDM' ) );
        
        // $this->register_association( 'custom', _x('Custom', 'form-fields api', 'WPBDM'), array( 'private' ) );

        // register core field types
        $this->register_field_type( 'WPBDP_FieldTypes_TextField', 'textfield' );
        $this->register_field_type( 'WPBDP_FieldTypes_Select', 'select' );
        $this->register_field_type( 'WPBDP_FieldTypes_URL', 'url' );        
        $this->register_field_type( 'WPBDP_FieldTypes_TextArea', 'textarea' );
        $this->register_field_type( 'WPBDP_FieldTypes_RadioButton', 'radio' );
        $this->register_field_type( 'WPBDP_FieldTypes_MultiSelect', 'multiselect' );
        $this->register_field_type( 'WPBDP_FieldTypes_Checkbox', 'checkbox' );
        $this->register_field_type( 'WPBDP_FieldTypes_Twitter', 'social-twitter' );
        $this->register_field_type( 'WPBDP_FieldTypes_Facebook', 'social-facebook' );
        $this->register_field_type( 'WPBDP_FieldTypes_LinkedIn', 'social-linkedin' );
        $this->register_field_type( 'WPBDP_FieldTypes_Image', 'image' );
    }

    /**
     * Registers a new association within the form fields API.
     * @param string $association association id
     * @param string $name human-readable name
     * @param array $flags association flags
     */
    public function register_association( $association, $name='', $flags=array() ) {
        if ( isset( $this->associations[$association] ) )
            return false;

        $this->associations[ $association ] = $name ? $name : $association;
        $this->association_flags[ $association ] = is_array( $flags ) ? $flags : array( strval( $flags ) );
        
        if ( !isset( $this->association_field_types[ $association ] ) )
            $this->association_field_types[ $association ] = array();
    }

    /**
     * Returns the known form field associations.
     * @return array associative array with key/name pairs
     */
    public function &get_associations() {
        return $this->associations;
    }

    public function &get_association_field_types( $association=null ) {
        if ( $association ) {
            if ( in_array( $association, array_keys( $this->associations ), true ) ) {
                return $this->association_field_types[ $association ];    
            } else {
                return null;
            }
        }
            

        return $this->association_field_types;
    }

    public function get_association_flags( $association ) {
        if ( array_key_exists( $association, $this->associations )  )
            return $this->association_flags[ $association ];

        return array();
    }

    /**
     * Returns associations marked with the given flags.
     * @param string|array $flags flags to be checked
     * @param boolean $any if True associations marked with any (and not all) of the flags will also be returned
     * @return array
     */
    public function &get_associations_with_flag( $flags, $any=false ) {
        if ( is_string( $flags ) )
            $flags = array( $flags );

        $res = array();

        foreach ( $this->association_flags as $association => $association_flags ) {
            $intersection = array_intersect( $flags, $association_flags );

            if ( ( $any && ( count( $intersection ) > 0 ) ) || ( !$any && ( count( $intersection ) == count( $flags ) )  ) )
                $res[] = $association;
        }

        return $res;
    }    

    public function &get_required_field_associations() {
        return $this->get_associations_with_flag( 'required' );
    }

    public function &get_field_type( $field_type ) {
        $field_type_obj = wpbdp_getv( $this->field_types, $field_type, null );
        return $field_type_obj;
    }

    public function &get_field_types() {
        return $this->field_types;
    }

    public function get_validators() {
        $validators = WPBDP_FieldValidation::instance()->get_validators();
        return $validators;
    }

    public function register_field_type( $field_type_class, $alias=null ) {
        $field_type = new $field_type_class();
        
        $this->field_types[ $alias ? $alias : $field_type_class ] = $field_type;
        
        foreach ( $field_type->get_supported_associations() as $association ) {
            $this->association_field_types[ $association ] = array_merge( isset( $this->association_field_types[ $association ] ) ? $this->association_field_types[ $association ] : array(), array( $alias ? $alias : $field_type_class ) );
        }
    }

    public function &get_field( $id=0 ) {
        $field = WPBDP_FormField::get( $id );
        return $field;
    }

    public function &get_fields() {
        global $wpdb;

        $res = array();

        $field_ids = $wpdb->get_col( "SELECT ID FROM {$wpdb->prefix}wpbdp_form_fields ORDER BY weight DESC" );

        foreach ( $field_ids as $field_id ) {
            if ( $field = WPBDP_FormField::get( $field_id ) )
                $res[] = $field;
        }

        return $res;
    }

    public function &find_fields( $args=array(), $one=false ) {
        global $wpdb;
        $res = array();

        $args = wp_parse_args( $args, array(
            'association' => null,
            'validators' => null,
            'display_flags' => null,
            'unique' => false
        ) );

        if ( $one == true )
            $args['unique'] = true;

        extract( $args );

        $validators = !is_array( $validators ) ? array( $validators ) : $validators;
        $display_flags = !is_array( $display_flags ) ? array( $display_flags ) : $display_flags;

        $where = '';
        if ( $args['association'] )
            $where .= $wpdb->prepare( " AND ( association = %s ) ", $args['association'] );

        foreach ( $display_flags as $f ) {
            if ( substr($f, 0, 1) == '-' )
                $where .= $wpdb->prepare( " AND ( display_flags IS NULL OR display_flags NOT LIKE '%%%s%%' )", substr( $f, 1 ) );
            else
                $where .= $wpdb->prepare( " AND ( display_flags LIKE '%%%s%%' )", $f );
        }

        foreach ( $validators as $v ) {
            if ( substr($v, 0, 1) == '-' )
                $where .= $wpdb->prepare( " AND ( validators IS NULL OR validators NOT LIKE '%%%s%%' )", substr( $v, 1 ) );
            else
                $where .= $wpdb->prepare( " AND ( validators LIKE '%%%s%%' )", $v );
        }

        if ( $where )
            $sql = "SELECT id FROM {$wpdb->prefix}wpbdp_form_fields WHERE 1=1 {$where} ORDER BY weight DESC";
        else
            $sql = "SELECT id FROM {$wpdb->prefix}wpbdp_form_fields ORDER BY weight DESC";

        $ids = $wpdb->get_col( $sql );

        foreach ( $ids as $id ) {
            if ( $field = WPBDP_FormField::get( $id ) )
                $res[] = $field;
        }

        $res = $unique ? ( $res ? $res[0] : null ) : $res;
        
        return $res;
    }

    public function get_missing_required_fields() {
        global $wpdb;

        $missing = $this->get_required_field_associations();

        $sql_in = '(\'' . implode( '\',\'', $missing ) . '\')';
        $res = $wpdb->get_col( "SELECT association FROM {$wpdb->prefix}wpbdp_form_fields WHERE association IN {$sql_in} GROUP BY association" );

        return array_diff( $missing, $res );
    }

    public function create_default_fields( $identifiers=array() ) {
        $default_fields = array(
            'title' => array( 'label' => __('Business Name', 'WPBDM'), 'field_type' => 'textfield', 'association' => 'title', 'weight' => 9,
                              'validators' => array( 'required' ), 'display_flags' => array( 'excerpt', 'listing', 'search' ) ),
            'category' => array( 'label' => __('Business Genre', 'WPBDM'), 'field_type' => 'select', 'association' => 'category', 'weight' => 8,
                                 'validators' => array( 'required' ), 'display_flags' => array( 'excerpt', 'listing', 'search' ) ),
            'excerpt' => array( 'label' => __('Short Business Description', 'WPBDM'), 'field_type' => 'textarea', 'association' => 'excerpt', 'weight' => 7,
                                'display_flags' => array( 'excerpt', 'listing', 'search' ) ),
            'content' => array( 'label' => __("Long Business Description","WPBDM"), 'field_type' => 'textarea', 'association' => 'content', 'weight' => 6,
                                'validators' => array( 'required' ), 'display_flags' => array( 'excerpt', 'listing', 'search' ) ),
            'meta0' => array( 'label' => __("Business Website Address","WPBDM"), 'field_type' => 'url', 'association' => 'meta', 'weight' => 5,
                              'validators' => array( 'url' ), 'display_flags' => array( 'excerpt', 'listing', 'search' ) ),
            'meta1' => array( 'label' => __("Business Phone Number","WPBDM"), 'field_type' => 'textfield', 'association' => 'meta', 'weight' => 4,
                              'display_flags' => array( 'excerpt', 'listing', 'search' ) ),
            'meta2' => array( 'label' => __("Business Fax","WPBDM"), 'field_type' => 'textfield', 'association' => 'meta', 'weight' => 3,
                              'display_flags' => array( 'excerpt', 'listing', 'search' ) ),
            'meta3' => array( 'label' => __("Business Contact Email","WPBDM"), 'field_type' => 'textfield', 'association' => 'meta', 'weight' => 2,
                             'validators' => array( 'email', 'required' ), 'display_flags' => array( 'excerpt', 'listing' ) ),
            'meta4' => array( 'label' => __("Business Tags","WPBDM"), 'field_type' => 'textfield', 'association' => 'tags', 'weight' => 1,
                              'display_flags' => array( 'excerpt', 'listing', 'search' ) )
        );      

        $fields_to_create = $identifiers ? array_intersect_key( $default_fields, array_flip ( $identifiers ) ) : $default_fields;

        foreach ( $fields_to_create as &$f) {
            $field = new WPBDP_FormField( $f );
            $field->save();
        }
    }

    public function get_short_names( $fieldid=null ) {
        $names = get_option( 'wpbdp-field-short-names', false );

        if ( !$names )
            $names = $this->_calculate_short_names();

        if ( $fieldid ) {
            return isset( $names[ $fieldid ] ) ? $names[ $fieldid ] : null;
        }

        return $names;
    }

    public function _calculate_short_names() {
        $fields = $this->get_fields();
        $names = array();

        foreach ( $fields as $field ) {
            $name = strtolower( $field->get_label() );
            $name = str_replace( array( ',', ';' ), '', $name );
            $name = str_replace( array( ' ', '/' ), '-', $name );

            if ( $name == 'images' || $name == 'image' || $name == 'username' || in_array( $name, $names, true ) ) {
                $name = $field->get_id() . '/' . $name;
            }
            
            $names[ $field->get_id() ] = $name;
        }

        update_option( 'wpbdp-field-short-names', $names );

        return $names;
    }

}

/*
 * Validation.
 */

function WPBDP_ValidationError( $msg, $stop_validation=false ) {
    if ( $stop_validation )
        return new WP_Error( 'wpbdp-validation-error-stop', $msg );

    return new WP_Error( 'wpbdp-validation-error', $msg );
}


class WPBDP_FieldValidation {

    private static $instance = null;

    public static function instance() {
        if (is_null(self::$instance)) {
            self::$instance = new self;
        }

        return self::$instance;
    }

    /**
     * Get the set of publicly available validators.
     * @return array associative array with validator name as key and display name as value
     */
    public function get_validators() {
        $validators = array(
            'email' => _x('Email Validator', 'form-fields-api', 'WPBDM'),
            'url' => _x('URL Validator', 'form-fields-api', 'WPBDM'),
            'integer_number' => _x('Whole Number Validator', 'form-fields-api', 'WPBDM'),
            'decimal_number' => _x('Decimal Number Validator', 'form-fields-api', 'WPBDM'),
            'date_' => _x('Date Validator', 'form-fields-api', 'WPBDM')
        );

        return $validators;
    }

    public function validate_field( $field, $value, $validator, $args=array() ) {
        $args['field-label'] = is_object( $field ) && $field ? $field->get_label() : _x( 'Field', 'form-fields-api validation', 'WPBDM' );
        $args['field'] = $field;

        return call_user_func( array( $this, $validator ) , $value, $args );
    }

    public function validate_value( $value, $validator, $args=array() ) {
        return !is_wp_error( $this->validate_field( null, $value, $validator, $args ) );
    }

    /* Required validator */
    private function required( $value, $args=array() ) {
        $args = wp_parse_args( $args, array( 'allow_whitespace' => false, 'field' => null ) );

        if ( $args['field'] && $args['field']->get_association() == 'category' ) {
            if ( is_array( $value ) && count( $value ) == 1 && !$value[0] )
                return WPBDP_ValidationError( sprintf( _x( '%s is required.', 'form-fields-api validation', 'WPBDM' ), esc_attr( $args['field-label'] ) ) );
        }

        if ( ( $args['field'] && $args['field']->is_empty_value( $value ) ) || !$value || ( is_string( $value ) && !$args['allow_whitespace'] && !trim( $value ) ) )
            return WPBDP_ValidationError( sprintf( _x( '%s is required.', 'form-fields-api validation', 'WPBDM' ), esc_attr( $args['field-label'] ) ) );
    }

    /* URL Validator */
    private function url( $value, $args=array() ) {
        if ( is_array( $value ) ) $value = $value[0];

        if ( function_exists( 'filter_var' ) ) {
            if ( !filter_var( $value, FILTER_VALIDATE_URL ) ) {
                return WPBDP_ValidationError( sprintf( _x( '%s is badly formatted. Valid URL format required. Include http://', 'form-fields-api validation', 'WPBDM' ), esc_attr( $args['field-label'] ) )  );
            } else {
                return;
            }
        }

        if ( !preg_match( '|^http(s)?://[a-z0-9-]+(.[a-z0-9-]+)*(:[0-9]+)?(/.*)?$|i', $value ) )
            return WPBDP_ValidationError( sprintf( _x( '%s is badly formatted. Valid URL format required. Include http://', 'form-fields-api validation', 'WPBDM' ), esc_attr( $args['field-label'] ) )  );
    }

    /* EmailValidator */
    private function email( $value, $args=array() ) {
        $valid = false;

        if ( function_exists( 'filter_var' ) ) {
            $valid = filter_var( $value, FILTER_VALIDATE_EMAIL );
        } else {
            $valid = (bool) preg_match( '/^(?!(?>\x22?(?>\x22\x40|\x5C?[\x00-\x7F])\x22?){255,})(?!(?>\x22?\x5C?[\x00-\x7F]\x22?){65,}@)(?>[\x21\x23-\x27\x2A\x2B\x2D\x2F-\x39\x3D\x3F\x5E-\x7E]+|(?>\x22(?>[\x01-\x08\x0B\x0C\x0E-\x1F\x21\x23-\x5B\x5D-\x7F]|\x5C[\x00-\x7F])*\x22))(?>\.(?>[\x21\x23-\x27\x2A\x2B\x2D\x2F-\x39\x3D\x3F\x5E-\x7E]+|(?>\x22(?>[\x01-\x08\x0B\x0C\x0E-\x1F\x21\x23-\x5B\x5D-\x7F]|\x5C[\x00-\x7F])*\x22)))*@(?>(?>(?!.*[^.]{64,})(?>(?>xn--)?[a-z0-9]+(?>-[a-z0-9]+)*\.){0,126}(?>xn--)?[a-z0-9]+(?>-[a-z0-9]+)*)|(?:\[(?>(?>IPv6:(?>(?>[a-f0-9]{1,4}(?>:[a-f0-9]{1,4}){7})|(?>(?!(?:.*[a-f0-9][:\]]){8,})(?>[a-f0-9]{1,4}(?>:[a-f0-9]{1,4}){0,6})?::(?>[a-f0-9]{1,4}(?>:[a-f0-9]{1,4}){0,6})?)))|(?>(?>IPv6:(?>(?>[a-f0-9]{1,4}(?>:[a-f0-9]{1,4}){5}:)|(?>(?!(?:.*[a-f0-9]:){6,})(?>[a-f0-9]{1,4}(?>:[a-f0-9]{1,4}){0,4})?::(?>[a-f0-9]{1,4}(?>:[a-f0-9]{1,4}){0,4}:)?)))?(?>25[0-5]|2[0-4][0-9]|1[0-9]{2}|[1-9]?[0-9])(?>\.(?>25[0-5]|2[0-4][0-9]|1[0-9]{2}|[1-9]?[0-9])){3}))\]))$/isD', $value );
        }

        if ( !$valid )
            return WPBDP_ValidationError( sprintf( _x( '%s is badly formatted. Valid Email format required.', 'form-fields-api validation', 'WPBDM' ), esc_attr( $args['field-label'] ) ) );
    }

    /* IntegerNumberValidator */
    private function integer_number( $value, $args=array() ) {
        if ( !ctype_digit( $value ) )
            return WPBDP_ValidationError( sprintf( _x( '%s must be a number. Decimal values are not allowed.', 'form-fields-api validation', 'WPBDM' ), esc_attr ( $args['field-label'] ) ) );
    }

    /* DecimalNumberValidator */
    private function decimal_number( $value, $args=array() ) {
        if ( !is_numeric( $value ) )
            return WPBDP_ValidationError( sprintf( _x( '%s must be a number.', 'form-fields-api validation', 'WPBDM' ), esc_attr( $args['field-label'] ) ) );
    }

    /* DateValidator */
    private function date_( $value, $args=array() ) {
        $args = wp_parse_args( $args, array( 'format' => 'm/d/Y' ) );

        // TODO: validate with format
        list( $m, $d, $y ) = explode( '/', $value );

        if ( !is_numeric( $m ) || !is_numeric( $d ) || !is_numeric( $y ) || !checkdate( $m, $d, $y ) )
            return WPBDP_ValidationError( sprintf( _x( '%s must be in the format MM/DD/YYYY.', 'form-fields-api validation', 'WPBDM' ), esc_attr( $args['field-label'] ) ) );
    }

    private function any_of( $value, $args=array() ) {
        $args = wp_parse_args( $args, array( 'values' => array(), 'formatter' => create_function( '$x', 'return join(",", $x);' ) ) );
        extract( $args, EXTR_SKIP );

        if ( is_string( $values ) )
            $values = explode( ',', $values );

        if ( !in_array( $value, $values ) )
            return WPBDP_ValidationError( sprintf( _x( '%s is invalid. Value most be one of %s.', 'form-fields-api validation', 'WPBDM' ), esc_attr( $args['field-label'] ), call_user_func( $formatter, $values ) ) );        
    }

}

}



/**
 * @since 2.3
 * @see WPBDP_FormFields::find_fields()
 */
function &wpbdp_get_form_fields( $args=array() ) {
    global $wpbdp;
    return $wpbdp->formfields->find_fields( $args );
}

/**
 * @since 2.3
 * @see WPBDP_FormFields::get_field()
 */
function wpbdp_get_form_field( $id ) {
    global $wpbdp;
    return $wpbdp->formfields->get_field( $id );
}

/**
 * Validates a value against a given validator.
 * @param mixed $value
 * @param string $validator one of the registered validators.
 * @param array $args optional arguments to be passed to the validator.
 * @return boolean True if value validates, False otherwise.
 * @since 2.3
 * @see WPBDP_FieldValidation::validate_value()
 */
function wpbdp_validate_value( $value, $validator, $args=array() ) {
    $validation = WPBDP_FieldValidation::instance();
    return $validation->validate_value( $value, $validator, $args );
}

