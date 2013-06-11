<?php echo wpbdp_admin_header( _x( 'Add Form Field', 'form-fields admin', 'WPBDM' ) ); ?>
<?php wpbdp_admin_notices(); ?>

<form id="wpbdp-formfield-form" action="" method="POST">
    <input type="hidden" name="field[id]" value="<?php echo $field->get_id(); ?>" />
    <input type="hidden" name="field[weight]" value="<?php echo $field->get_weight(); ?>" />

    <table class="form-table">
        <tbody>
            <!-- association -->
            <tr>
                <th scope="row">
                    <label> <?php _ex('Field Association', 'form-fields admin', 'WPBDM'); ?> <span class="description">(required)</span></label>
                </th>
                <td>
                    <select name="field[association]" id="field-association">
                    <?php foreach ( $field_associations as $association => $name ): ?>
                        <option value="<?php echo $association; ?>" <?php echo $field->get_association() == $association ? ' selected="selected"' : ''; ?> ><?php echo $name; ?></option>
                    <?php endforeach; ?>
                    </select>
                </td>
            </tr>

            <!-- field type -->
            <tr class="form-field form-required">
                <th scope="row">
                    <label> <?php _ex('Field Type', 'form-fields admin', 'WPBDM'); ?> <span class="description">(required)</span></label>
                </th>
                <td>
                    <select name="field[field_type]" id="field-type">
                        <?php foreach ( $field_types as $key => &$field_type ) : ?>
                            <?php if ( !in_array( $field->get_association(), $field_type->get_supported_associations() ) ): ?>
                            <option value="<?php echo $key; ?>" disabled="disabled"><?php echo $field_type->get_name(); ?></option>                            
                            <?php else: ?>
                            <option value="<?php echo $key; ?>" <?php echo $field->get_field_type() == $field_type ? 'selected="true"' : ''; ?>><?php echo $field_type->get_name(); ?></option>
                            <?php endif; ?>
                        <?php endforeach; ?>
                    </select>
                </td>
            </tr>

            <!-- label -->
            <tr class="form-field form-required">
                <th scope="row">
                    <label> <?php _ex('Field Label', 'form-fields admin', 'WPBDM'); ?> <span class="description">(required)</span></label>
                </th>
                <td>
                    <input name="field[label]" type="text" aria-required="true" value="<?php echo esc_attr( $field->get_label() ); ?>" />
                </td>
            </tr>

            <!-- description -->
            <tr class="form-field">
                <th scope="row">
                    <label> <?php _ex('Field description', 'form-fields admin', 'WPBDM'); ?> <span class="description">(optional)</span></label>
                </th>
                <td>
                    <input name="field[description]" type="text" value="<?php echo esc_attr( $field->get_description() ); ?> " />
                </td>
            </tr>           
    </table>

    <!-- field-specific settings -->
    <?php
    $field_settings = $field->get_field_type()->render_field_settings( $field, $field->get_association() );
    ?>
    <div id="wpbdp-fieldsettings" style="<?php echo $field_settings ? '' : 'display: none;'; ?>">
    <h3><?php _ex('Field-specific settings', 'form-fields admin', 'WPBDM'); ?></h3>
    <div id="wpbdp-fieldsettings-html"><?php echo $field_settings; ?></div>
    </div>
    <!-- /field-specific settings -->

    <!-- validation -->
    <h3><?php _ex('Field validation options', 'form-fields admin', 'WPBDM'); ?></h3>
    <table class="form-table">
            <tr>
                <th scope="row">
                    <label> <?php _ex('Field Validator', 'form-fields admin', 'WPBDM'); ?></label>
                </th>
                <td>
                    <select name="field[validators][]" id="field-validator">
                        <option value=""><?php _ex('No validation', 'form-fields admin', 'WPBDM'); ?></label>
                        <?php foreach ( $validators as $key => $name): ?>
                        <option value="<?php echo $key; ?>" <?php echo in_array( $key, $field->get_validators(), true ) ? 'selected="selected"' : ''; ?>><?php echo $name; ?></option>
                        <?php endforeach; ?>
                    </select>
                </td>
            </tr>
            <tr>
                <th scope="row">
                    <label> <?php _ex('Is field required?', 'form-fields admin', 'WPBDM'); ?></label>
                </th>
                <td>
                    <label>
                        <input name="field[validators][]"
                               value="required"
                               type="checkbox" <?php echo $field->is_required() ? 'checked="checked"' : ''; ?>/> <?php _ex('This field is required.', 'form-fields admin', 'WPBDM'); ?>
                    </label>
                </td>
            </tr>
    </table>

    <!-- display options -->
    <h3><?php _ex('Field display options', 'form-fields admin', 'WPBDM'); ?></h3>
    <table class="form-table">
            <tr>
                <th scope="row">
                    <label> <?php _ex('Show this value in excerpt view?', 'form-fields admin', 'WPBDM'); ?></label>
                </th>
                <td>
                    <label>
                        <input name="field[display_flags][]"
                               value="excerpt"
                               type="checkbox" <?php echo $field->display_in( 'excerpt') ? 'checked="checked"' : ''; ?>/> <?php _ex('Display this value in post excerpt view.', 'form-fields admin', 'WPBDM'); ?>
                    </label>
                </td>
            </tr>
            <tr>
                <th scope="row">
                    <label> <?php _ex('Show this value in listing view?', 'form-fields admin', 'WPBDM'); ?></label>
                </th>
                <td>
                    <label>
                        <input name="field[display_flags][]"
                               value="listing"
                               type="checkbox" <?php echo $field->display_in( 'listing' ) ? 'checked="checked"' : ''; ?>/> <?php _ex('Display this value in the listing view.', 'form-fields admin', 'WPBDM'); ?>
                    </label>
                </td>
            </tr>
            <tr>
                <th scope="row">
                    <label> <?php _ex('Include this field in the search form?', 'form-fields admin', 'WPBDM'); ?></label>
                </th>
                <td>
                    <label>
                        <input name="field[display_flags][]"
                               value="search"
                               type="checkbox" <?php echo $field->display_in( 'search' ) ? 'checked="checked"' : ''; ?>/> <?php _ex('Include this field in the search form.', 'form-fields admin', 'WPBDM'); ?>
                    </label>
                </td>
            </tr>
            <tr>
                <th scope="row">
                    <label> <?php _ex('Hide this field\'s label?', 'form-fields admin', 'WPBDM'); ?></label>
                </th>
                <td>
                    <label>
                        <input name="field[display_flags][]"
                               value="nolabel"
                               type="checkbox" <?php echo $field->has_display_flag( 'nolabel' ) ? 'checked="checked"' : ''; ?>/> <?php _ex('Hide this field\'s label when displaying it.', 'form-fields admin', 'WPBDM'); ?>
                    </label>
                </td>
            </tr>            
    </table>

    <?php if ( $field->get_id() ): ?>
        <?php echo submit_button(_x('Update Field', 'form-fields admin', 'WPBDM')); ?>
    <?php else: ?>
        <?php echo submit_button(_x('Add Field', 'form-fields admin', 'WPBDM')); ?>
    <?php endif; ?>
</form>

<script type="text/javascript">
jQuery(document).ready(function(){
WPBDP_associations_fieldtypes = <?php echo json_encode( $association_field_types ); ?>
});
</script>

<?php echo wpbdp_admin_footer(); ?>