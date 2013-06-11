<?php
/**
 * CSV Import admin pages.
 * @since 2.1
 */
class WPBDP_CSVImportAdmin {

    public static function admin_menu_cb() {
        $instance = new WPBDP_CSVImportAdmin();
        $instance->dispatch();
    }

    public function __construct() {
        $this->admin = wpbdp()->admin;
    }

    public function dispatch() {
        $action = wpbdp_getv($_REQUEST, 'action');
        $api = wpbdp_formfields_api();

        switch ($action) {
            case 'example-csv':
                $this->example_csv();
                break;
            case 'do-import':
                $this->import();
                break;
            default:
                $this->import_settings();
                break;
        }
    }

    private function example_data_for_field( $field=null, $shortname=null ) {
        $letters = 'ABCDEFGHIJKLMNOPQRSTUVWXYZ';

        if ( $field ) {
            if ( $field->get_association() == 'title' ) {
                return sprintf(_x('Business %s', 'admin csv-import', 'WPBDM'), $letters[rand(0,strlen($letters)-1)]);
            } elseif ( $field->get_association() == 'category') {
                if ( $terms = get_terms(wpbdp_categories_taxonomy(), 'number=5&hide_empty=0') ) {
                    return $terms[array_rand($terms)]->name;
                } else {
                    return '';
                }
            } elseif ($field->get_association() == 'tags') {
                if ( $terms = get_terms(WPBDP_TAGS_TAX, 'number=5&hide_empty=0') ) {
                    return $terms[array_rand($terms)]->name;
                } else {
                    return '';
                }                
            } elseif ( $field->has_validator( 'url' ) ) {
                return get_site_url();
            } elseif ( $field->has_validator( 'email' ) ) {
                return get_option( 'admin_email' );
            } elseif ( $field->has_validator('integer_number') ) {
                return rand(0, 100);
            } elseif ( $field->has_validator( 'decimal_number' ) ) {
                return rand(0, 100) / 100.0;
            } elseif ( $field->has_validator( 'date_' ) ) {
                return date( 'd/m/Y' );
            } elseif ( $field->get_field_type()->get_id() == 'multiselect' || $field->get_field_type()->get_id() == 'checkbox' ) {
                if ( $field->data( 'options' ) ) {
                    $options = $field->data( 'options' );
                    return $options[array_rand($options)];
                }
                
                return '';
            }
        }

        if ($shortname == 'user') {
            $users = get_users();
            return $users[array_rand($users)]->user_login;
        }

        return _x('Whatever', 'admin csv-import', 'WPBDM');
    }

    private function example_csv() {
        echo wpbdp_admin_header(_x('Example CSV Import File', 'admin csv-import', 'WPBDM'), null, array(
            array(_x('← Return to "CSV Import"', 'admin csv-import', 'WPBDM'), esc_url(remove_query_arg('action')))
        ));

        $posts = get_posts(array(
            'post_type' => wpbdp_post_type(),
            'post_status' => 'publish',
            'numberposts' => 10
        ));

        //echo sprintf('<input type="button" value="%s" />', _x('Copy CSV', 'admin csv-import', 'WPBDM'));
        echo '<textarea class="wpbdp-csv-import-example" rows="30">';

        $fields_api = wpbdp_formfields_api();

        $short_names = $fields_api->get_short_names();

        foreach ($short_names as $name) {
            echo $name . ',';
        }
        echo 'username';
        echo "\n";

        if (count($posts) >= 5) {
            foreach ($posts as $post) {
                foreach (array_keys($short_names) as $field_id) {
                    $field = $fields_api->get_field( $field_id );
                    $value = $field->plain_value( $post->ID );

                    echo str_replace( ',', ';', $value );
                    echo ',';
                }
                echo get_the_author_meta('user_login', $post->post_author);

                echo "\n";
            }
        } else {
            for ($i = 0; $i < 5; $i++) {
                foreach ($short_names as $field_id => $shortname) {
                    $field = $fields_api->get_field( $field_id );
                    echo sprintf( '"%s"', $this->example_data_for_field( $field, $shortname ) );
                    echo ',';
                }

                echo sprintf( '"%s"', $this->example_data_for_field( null, 'user' ) );
                echo "\n";
            }
            
        }

        echo '</textarea>';

        echo wpbdp_admin_footer();
    }

    private function import_settings() {
        echo wpbdp_render_page(WPBDP_PATH . 'admin/templates/csv-import.tpl.php');
    }

    private function import() {
        $csvfile = $_FILES['csv-file'];
        $zipfile = $_FILES['images-file'];

        if ($csvfile['error'] || !is_uploaded_file($csvfile['tmp_name'])) {
            $this->admin->messages[] = array(_x('There was an error uploading the CSV file.', 'admin csv-import', 'WPBDM'), 'error');
            return $this->import_settings();
        }

        if (strtolower(pathinfo($csvfile['name'], PATHINFO_EXTENSION)) != 'csv' &&
            $csvfile['type'] != 'text/csv') {
            $this->admin->messages[] = array(_x('The uploaded file does not look like a CSV file.', 'admin csv-import', 'WPBDM'), 'error');
            return $this->import_settings();
        }

        $formfields_api = wpbdp_formfields_api();
        $form_fields = $formfields_api->get_fields();
        $shortnames = $formfields_api->get_short_names();

        $fields = array();
        foreach ($form_fields as $field)
            $fields[$shortnames[$field->get_id()]] = $field;

        $importer = new WPBDP_CSVImporter();
        $importer->set_settings(array_merge($_POST['settings'], array('test-import' => isset($_POST['test-import']) ? true : false)));
        $importer->set_fields($fields);
        $importer->import($csvfile['tmp_name'], $zipfile['tmp_name']);

        if ($importer->in_test_mode())
            $this->admin->messages[] = array(_x('* Import is in test mode. Nothing was actually inserted into the database. *', 'admin csv-import', 'WPBDM'), 'error');

        if ( $importer->fatal_errors ) {
            foreach ( $importer->fatal_errors as $err ) {
                $this->admin->messages[] = array( $err, 'error' );
            }

            $this->admin->messages[] = array( _x( 'Fatal errors encountered. Import will not proceed.', 'admin csv-import', 'WPBDM' ), 'error' );
        }

        if ($importer->rejected_rows)
            $this->admin->messages[] = _x('Import was completed but some rows were rejected.', 'admin csv-import', 'WPBDM');
        else
            $this->admin->messages[] = _x('Import was completed successfully.', 'admin csv-import', 'WPBDM');

        echo wpbdp_admin_header();
        echo wpbdp_admin_notices();

        echo '<h3>' . _x('Import Summary', 'admin csv-import', 'WPBDM') . '</h3>';
        echo '<dl>';
        echo '<dt>' . _x('Correctly imported rows:', 'admin csv-import', 'WPBDM') . '</dt>';
        echo '<dd>' . count($importer->imported_rows) . '</dd>';
        echo '<dt>' . _x('Rejected rows:', 'admin csv-import', 'WPBDM') . '</dt>';
        echo '<dd>' . count($importer->rejected_rows) . '</dd>';
        echo '</dl>';

        if ($importer->rejected_rows) {
            echo '<h3>' . _x('Rejected Rows', 'admin csv-import', 'WPBDM') . '</h3>';
            echo '<table class="wpbdp-csv-import-results wp-list-table widefat">';
            echo '<thead><tr>';
            echo '<th class="line-no">' . _x('Line #', 'admin csv-import', 'WPBDM') . '</th>';
            echo '<th class="line">' . _x('Line', 'admin csv-import', 'WPBDM') . '</th>';
            echo '<th class="error">' . _x('Error', 'admin csv-import', 'WPBDM') . '</th>';
            echo '</tr></thead>';

            echo '<tbody>';

            foreach ($importer->rejected_rows as $row) {
                foreach ($row['errors'] as $i => $error) {
                    echo sprintf('<tr class="%s">', $i % 2 == 0 ? 'alternate' : '');
                    echo '<td class="line-no">' . $row['line'] . '</td>';
                    echo '<td class="line">' . substr($importer->csv[$row['line'] - 1], 0, 60) . '...</td>';
                    echo '<td class="error">' . $error . '</td>';
                    echo '</tr>';
                }
            }

            echo '</tbody>';
            echo '</table>';
        }

        if ($importer->warnings > 0) {
            echo '<h3>' . _x('Import warnings (not critical)', 'admin csv-import', 'WPBDM') . '</h3>';
            echo '<table class="wpbdp-csv-import-warnings wp-list-table widefat">';
            echo '<thead><tr>';
            echo '<th class="line-no">' . _x('Line #', 'admin csv-import', 'WPBDM') . '</th>';
            echo '<th class="line">' . _x('Line', 'admin csv-import', 'WPBDM') . '</th>';
            echo '<th class="error">' . _x('Warning', 'admin csv-import', 'WPBDM') . '</th>';
            echo '</tr></thead>';

            echo '<tbody>';        
            foreach ($importer->imported_rows as $row) {
                if (!isset($row['warnings']))
                    continue;

                foreach ($row['warnings'] as $i => $warning) {
                    echo sprintf('<tr class="%s">', $i % 2 == 0 ? 'alternate' : '');
                    echo '<td class="line-no">' . $row['line'] . '</td>';
                    echo '<td class="line">' . substr($importer->csv[$row['line'] - 1], 0, 60) . '...</td>';
                    echo '<td class="error">' . $warning . '</td>';
                    echo '</tr>';
                }

            }
            echo '</tbody>';
            echo '</table>';
        }

        echo wpbdp_admin_footer();
    }

}


require_once(ABSPATH . 'wp-admin/includes/file.php');
require_once(ABSPATH . 'wp-admin/includes/image.php');

/**
 * CSV import class.
 * @since 2.1
 */
class WPBDP_CSVImporter {

    private $settings = array(
        'allow-partial-imports' => true,

        'csv-file-separator' => ',',
        'images-separator' => ';',
        'category-separator' => ';',
        'create-missing-categories' => true,

        'assign-listings-to-user' => true,
        'default-user' => '0',

        'test-import' => false
    );

    private $fields = array();
    private $required_fields = array();
    
    public $csv = array();
    private $header = array();
    private $data = array();

    private $imagesdir = null;

    public $rows = array(); /* valid rows */
    public $imported_rows = array();
    public $rejected_rows = array();
    public $warnings = 0;
    public $fatal_errors = array();


    public function __construct() { }

    public function set_fields($fields) {
        $this->fields = $fields;
        
        foreach ( $this->fields as &$field ) {
            if ( $field->is_required() )
                $this->required_fields[ $field->get_short_name() ] = $field;
        }
    }

    public function set_settings($settings=array()) {
        $this->settings = array_merge($this->settings, $settings);
        $this->settings['allow-partial-imports'] = (boolean) $this->settings['allow-partial-imports'];        
        $this->settings['create-missing-categories'] = (boolean) $this->settings['create-missing-categories'];
        $this->settings['assign-listings-to-user'] = (boolean) $this->settings['assign-listings-to-user'];
        $this->settings['default-user'] = intval($this->settings['default-user']);
    }

    public function in_test_mode() {
        return $this->settings['test-import'] == true;
    }

    public function reset() {
        $this->csv = array();
        $this->header = array();
        $this->data = array();

        $this->rows = array();
        $this->imported_rows = array();
        $this->rejected_rows = array();
        $this->warnings = 0;

        $this->imagesdir = null;
    }

    public function import($csv_file, $zipfile) {
        $this->reset();
        $this->extract_data($csv_file);
        $this->extract_images($zipfile);

        foreach ($this->rows as $row) {
            if ($this->import_row($row['data'], $errors, $warnings)) {
                if ($warnings) {
                    $this->warnings += count($warnings);
                    $row['warnings'] = $warnings;
                }

                $this->imported_rows[] = $row;
            } else {
                $row['errors'] = $errors;
                $this->rejected_rows[] = $row;
            }
        }

        // delete $imagesdir
        if ($this->imagesdir)
            $this->remove_directory($this->imagesdir);
    }

    private function process_line($row) {
        if (count($row) > count($this->header)) {
            return false; // row has more columns than the header
        }

        if (count($row) < count($this->header)) {
            $row = array_merge($row, array_fill(0, count($this->header) - count($row), null));            
        }

        return $row;
    }

    private function extract_data($csv_file) {
        ini_set('auto_detect_line_endings', true);

        $fp = fopen($csv_file, 'r');

        $n = 0;
        while (($line_data = fgetcsv($fp, 0, $this->settings['csv-file-separator'])) !== FALSE) {
            if ($line_data) {
                if (!$this->header) {
                    $this->header = $line_data;
                    
                    foreach ($this->header as &$h) $h = trim($h);

                    foreach ( $this->required_fields as $shortname => $field ) {
                        if ( !in_array( $shortname, $this->header ) ) {
                            $this->fatal_errors[] = sprintf( _x( 'Missing required header column: %s', 'admin csv-import', 'WPBDM' ), $shortname );
                        }
                    }

                    if ( $this->fatal_errors ) {
                        @fclose( $fp );
                        return false;
                    }

                } else {
                    if ($row = $this->process_line($line_data)) {
                        $this->rows[] = array('line' => $n + 1, 'data' => $row, 'error' => false);
                    } else {
                        $this->rejected_rows[] = array('line' => $n + 1, 'data' => $row, 'errors' => array(_x('Malformed row (too many columns)', 'admin csv-import', 'WPBDM')) );
                    }
                }
            }

            $n++;
        }

        @fclose($fp);

    }

    private function extract_images($zipfile) {
        $dir = trailingslashit(trailingslashit(sys_get_temp_dir()) . 'wpbdp_' . time());

        require_once(ABSPATH . 'wp-admin/includes/class-pclzip.php');

        $zip = new PclZip($zipfile);
        if ($files = $zip->extract(PCLZIP_OPT_PATH, $dir, PCLZIP_OPT_REMOVE_ALL_PATH)) {
            $this->imagesdir = $dir;
            return true;
        }

        return false;
    }

    private function import_row($data, &$errors=null, &$warnings=null) {
        $errors = array();
        $warnings = array();

        $listing_username = null;

        $listing = array('fields' => array(), 'images' => array());

        $listing_images = array();
        $listing_fields = array();

        foreach ($this->header as $i => $header_name) {
            if ( ($header_name == 'image' || $header_name == 'images') ) {
                if ( !empty($data[$i]) ) {
                    if (strpos($data[$i], $this->settings['images-separator']) !== false) {
                        foreach (explode($this->settings['images-separator'], $data[$i]) as $image) {
                            $listing_images[] = trim($image);
                        }
                    } else {
                        $listing_images[] = trim($data[$i]);
                    }
                }

                continue;
            }

            if ($header_name == 'username') {
                $listing_username = $data[$i];

                if ( $listing_username ) {
                    if ( !username_exists( $listing_username ) ) {
                        $errors[] = sprintf( _x( 'Username "%s" does not exist', 'admin csv-import', 'WPBDM' ), $listing_username );
                        return false;
                    }
                }
                continue;
            }

            if (!array_key_exists($header_name, $this->fields)) {
                $warnings[] = sprintf(_x('Ignoring unknown field "%s"', 'admin csv-import', 'WPBDM'), $header_name);
                continue;
            }

            $field = $this->fields[$header_name];

            if ( $field->is_required() && $field->is_empty_value( $data[$i] ) ) {
                $errors[] = sprintf( _x( 'Missing required field: %s', 'admin csv-import', 'WPBDM' ), $header_name );
                return false;
            }           

            if ($field->get_association() == 'category') {
                $categories = array_map('trim', explode($this->settings['category-separator'], $data[$i]));

                foreach ($categories as $category_name) {
                    $category_name = strip_tags(str_replace("\n", "-", $category_name));

                    if (!$category_name)
                        continue;

                    if ($term = term_exists($category_name, wpbdp_categories_taxonomy())) {
                        $listing_fields[$field->get_id()][] = $term['term_id'];
                    } else {
                        if ($this->settings['create-missing-categories']) {
                            if ($this->in_test_mode())
                                continue;

                            if ($newterm = wp_insert_term($category_name, wpbdp_categories_taxonomy())) {
                                $listing_fields[$field->get_id()][] = $newterm['term_id'];
                            } else {
                                $errors[] = sprintf(_x('Could not create listing category "%s"', 'admin csv-import', 'WPBDM'), $category_name);
                                return false;
                            }
                            
                        } else {
                            $errors[] = sprintf(_x('Listing category "%s" does not exist', 'admin csv-import', 'WPBDM'), $category_name);
                            return false;
                        }
                    }
                }
            } elseif ($field->get_association() == 'tags') {
                $listing_fields[$field->get_id()][] = $data[$i];
            } else {
                $listing_fields[$field->get_id()] = $data[$i];
            }
        }

        if ($listing_images) {
            if (!$this->imagesdir) {
                $errors[] = _x('Images were specified but no image file was uploaded.', 'admin csv-import', 'WPBDM');
                return false;
            }

            foreach ($listing_images as $filename) {
                if (file_exists($this->imagesdir . $filename)) {
                    $filepath = $this->imagesdir . $filename;

                    $file = array('name' => basename($filepath),
                                  'tmp_name' => $filepath,
                                  'error' => 0,
                                  'size' => filesize($filepath)
                    );

                    copy($filepath, $filepath . '.backup'); // make a file backup becase wp_handle_sideload() moves the original file and it may be needed for other listings
                    $wp_image = wp_handle_sideload($file, array('test_form' => FALSE));
                    rename($filepath . '.backup', $filepath);

                    if (!isset($wp_image['error'])) {
                        if ($attachment_id = wp_insert_attachment(array(
                                'post_mime_type' => $wp_image['type'],
                                'post_title' => preg_replace('/\.[^.]+$/', '', basename($wp_image['file'])),
                                'post_content' => '',
                                'post_status' => 'inherit'
                            ), $wp_image['file'])) {

                            $attach_data = wp_generate_attachment_metadata($attachment_id, $wp_image['file']);
                            wp_update_attachment_metadata($attachment_id, $attach_data);

                            $listing['images'][] = $attachment_id;

                        } else {
                            $errors[] = sprintf(_x('Image file "%s" could not be inserted.', 'admin csv-import', 'WPBDM'), $filename);
                            return false;
                        }
                    } else {
                        $errors[] = sprintf(_x('Image file "%s" could not be uploaded.', 'admin csv-import', 'WPBDM'), $filename);
                        return false;
                    }
                } else {
                    $errors[] = sprintf(_x('Referenced image file "%s" was not found inside ZIP file.', 'admin csv-import'. 'WPBDM'), $filename);
                    return false;
                }
            }
        }

        $listing['fields'] = $listing_fields;

        if ($this->settings['test-import'])
            return true;
        $listing_id = wpbdp_listings_api()->add_listing($listing);

        // create permalink
        $post = get_post($listing_id);
        wp_update_post(array('ID' => $post->ID,
                             'post_name' => wp_unique_post_slug(sanitize_title($post->post_title), $post->ID, $post->post_status, $post->post_type, $post->post_parent)
                      ));


        if ($this->settings['assign-listings-to-user']) {
            if ($listing_username) {
                if ($user = get_user_by('login', $listing_username))
                    wp_update_post(array('ID' => $listing_id, 'post_author' => $user->ID));
            } else {
                if ($this->settings['default-user'])
                    wp_update_post(array('ID' => $listing_id, 'post_author' => $this->settings['default-user']));
            }
        }

        set_time_limit(5);

        return $listing_id > 0;
    }

    private function remove_directory($dir) {
        foreach (scandir($dir) as $file) {
            if ($file == '.' || $file == '..')  continue;

            if (is_dir($dir . $file)) {
                $this->remove_directory($dir . $file);
                rmdir($dir.  $file);
            } else {
                unlink($dir . $file);
            }
        }

        rmdir($dir);

        $this->imagesdir = null;
    }

 }