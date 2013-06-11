<?php
    echo wpbdp_admin_header(null, null, array(
        array(_x('Help', 'admin csv-import', 'WPBDM'), '#help'),
        array(_x('See an example CSV import file', 'admin csv-import', 'WPBDM'), esc_url(add_query_arg('action', 'example-csv')))
        ) );
?>

<?php wpbdp_admin_notices(); ?>

<form id="wpbdp-csv-import-form" action="" method="POST" enctype="multipart/form-data">
    <input type="hidden" name="action" value="do-import" />

    <h4><?php _ex('Import Files', 'admin csv-import'); ?></h4>
    <table class="form-table">
        <tbody>
            <tr class="form-field form-required">
                <th scope="row">
                    <label> <?php _ex('CSV File', 'admin csv-import', 'WPBDM'); ?> <span class="description">(<?php _ex('required', 'admin forms'); ?>)</span></label>
                </th>
                <td>
                    <input name="csv-file"
                           type="file"
                           aria-required="true" />
                </td>
            </tr>
            <tr class="form-field">
                <th scope="row">
                    <label> <?php _ex('ZIP file containing images', 'admin csv-import', 'WPBDM'); ?></label>
                </th>
                <td>
                    <input name="images-file"
                           type="file"
                           aria-required="true" />
                </td>
            </tr>            
    </table>

    <h4><?php _ex('CSV File Settings', 'admin csv-import', 'WPBDM'); ?></h4>
    <table class="form-table">
            <tr class="form-required">
                <th scope="row">
                    <label> <?php _ex('Column Separator', 'admin csv-import', 'WPBDM'); ?> <span class="description">(<?php _ex('required', 'admin forms'); ?>)</span></label>
                </th>
                <td>
                    <input name="settings[csv-file-separator]"
                           type="text"
                           aria-required="true"
                           value="," />
                </td>
            </tr>
            <tr class="form-required">
                <th scope="row">
                    <label> <?php _ex('Image Separator', 'admin csv-import', 'WPBDM'); ?> <span class="description">(<?php _ex('required', 'admin forms'); ?>)</span></label>
                </th>
                <td>
                    <input name="settings[images-separator]"
                           type="text"
                           aria-required="true"
                           value=";" />
                </td>
            </tr>
            <tr class="form-required">
                <th scope="row">
                    <label> <?php _ex('Category Separator', 'admin csv-import', 'WPBDM'); ?> <span class="description">(<?php _ex('required', 'admin forms'); ?>)</span></label>
                </th>
                <td>
                    <input name="settings[category-separator]"
                           type="text"
                           aria-required="true"
                           value=";" />
                </td>
            </tr>
    </table>

    <h4><?php _ex('Import settings', 'admin csv-import', 'WPBDM'); ?></h4>
    <table class="form-table">
<!--            <tr class="form-required">
                <th scope="row">
                    <label> <?php _ex('Allow partial imports?', 'admin csv-import', 'WPBDM'); ?></label>
                </th>
                <td>
                    <label><input name="settings[allow-partial-imports]"
                           type="checkbox"
                           value="1" checked="checked" /> <?php _ex('Allow partial imports.', 'admin csv-import', 'WPBDM'); ?></label>

                    <span class="description"><?php _ex('If checked, invalid lines from the CSV file will be ignored.', 'admin csv-import', 'WPBDM'); ?></span>
                </td>
            </tr>    -->    
            <tr class="form-required">
                <th scope="row">
                    <label> <?php _ex('Missing categories handling', 'admin csv-import', 'WPBDM'); ?> <span class="description">(<?php _ex('required', 'admin forms'); ?>)</span></label>
                </th>
                <td>
                    <label><input name="settings[create-missing-categories]"
                           type="radio"
                           value="1" checked="checked" /> <?php _ex('Auto-create categories', 'admin csv-import', 'WPBDM'); ?></label>
                    <label><input name="settings[create-missing-categories]"
                           type="radio"
                           value="0" /> <?php _ex('Generate errors when a category is not found', 'admin csv-import', 'WPBDM'); ?></label>                           
                </td>
            </tr>
            <tr class="form-required">
                <th scope="row">
                    <label> <?php _ex('Assign listings to a user?', 'admin csv-import', 'WPBDM'); ?>
                </th>
                <td>
                    <label><input name="settings[assign-listings-to-user]"
                           type="checkbox"
                           class="assign-listings-to-user"
                           value="1" checked="checked" /> <?php _ex('Assign listings to a user.', 'admin csv-import', 'WPBDM'); ?></label>
                </td>
            </tr>
            <tr class="form-required default-user-selection">
                <th scope="row">
                    <label> <?php _ex('Default listing user', 'admin csv-import', 'WPBDM'); ?>
                </th>
                <td>
                    <label>
                        <select name="settings[default-user]" class="default-user">
                            <option value="0"><?php _ex('Use spreadsheet information only.', 'admin csv-import', 'WPBDM'); ?></option>
                            <?php foreach (get_users('orderby=display_name') as $user): ?>
                            <option value="<?php echo $user->ID; ?>"><?php echo $user->display_name; ?> (<?php echo $user->user_login; ?>)</option>
                            <?php endforeach; ?>
                        </select>
                    </label>
                    <span class="description"><?php _ex('This user will be used if the username column is not present in the CSV file.', 'admin csv-import', 'WPBDM'); ?></span>
                </td>
            </tr>            
    </table>

    <p class="submit">
        <?php echo submit_button(_x('Test Import', 'admin csv-import', 'WPBDM'), 'secondary', 'test-import', false); ?>
        <?php echo submit_button(_x('Import Listings', 'admin csv-import', 'WPBDM'), 'primary', 'do-import', false); ?>
    </p>
</form>

<a name="help"></a>
<h3><?php _ex('Help', 'admin csv-import', 'WPBDM'); ?></h3>
<p>
    <?php echo sprintf(_x('The following are the valid header names to be used in the CSV file. Multivalued fields (such as category or tags) can appear multiple times in the file. Click <a href="%s">"See an example CSV import file"</a> to see how an import file should look like.', 'admin csv-import', 'WPBDM'),
                  esc_url(add_query_arg('action', 'example-csv'))); ?>
</p>

<table class="wpbdp-csv-import-headers">
    <thead>
        <tr>
            <th class="header-name"><?php _ex('Header name/label', 'admin csv-import', 'WPBDM'); ?></th>
            <th class="field-label"><?php _ex('Field', 'admin csv-import', 'WPBDM'); ?></th>
            <th class="field-type"><?php _ex('Type', 'admin csv-import', 'WPBDM'); ?></th>
            <th class="field-is-required"><?php _ex('Required?', 'admin csv-import', 'WPBDM'); ?></th>
            <th class="field-is-multivalued"><?php _ex('Multivalued?', 'admin csv-import', 'WPBDM'); ?></th>
        </tr>
    </thead>
    <tbody>
    <?php $i = 0; foreach ( wpbdp_formfields_api()->get_fields() as $field ) : ?>
        <tr class="<?php echo $i % 2 == 0 ? 'alt' : ''; ?>">
            <td class="header-name"><?php echo $field->get_short_name(); ?></td>
            <td class="field-label"><?php echo $field->get_label(); ?></td>
            <td class="field-type"><?php echo $field->get_field_type()->get_name(); ?></td>
            <td class="field-is-required"><?php echo $field->is_required() ? 'X' : ''; ?></td>
            <td class="field-is-multivalued">
                <?php echo ($field->get_association() == 'category' || $field->get_association() == 'tags') || ($field->get_field_type_id() == 'checkbox' || $field->get_field_type_id() == 'multiselect') ? 'X' : ''; ?>
            </td>
        </tr>
    <?php $i++; endforeach; ?>
        <tr class="<?php echo $i % 2 == 0 ? 'alt' : ''; ?>">
            <td class="header-name">images</td>
            <td class="field-label"><?php _ex('Semicolon separated list of listing images (from the ZIP file)', 'admin csv-import', 'WPBDM'); ?></td>
            <td class="field-type">-</td>
            <td class="field-is-required"></td>
            <td class="field-is-multivalued">X</td>
        </tr>
        <tr class="<?php echo ($i + 1) % 2 == 0 ? 'alt' : ''; ?>">
            <td class="header-name">username</td>
            <td class="field-label"><?php _ex('Listing author\'s username', 'admin csv-import', 'WPBDM'); ?></td>
            <td class="field-type">-</td>
            <td class="field-is-required"></td>
            <td class="field-is-multivalued"></td>
        </tr>            
    </tbody>
</table>

<?php echo wpbdp_admin_footer(); ?>