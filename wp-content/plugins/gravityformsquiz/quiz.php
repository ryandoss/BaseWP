<?php
/*
Plugin Name: Gravity Forms Quiz Add-On
Plugin URI: http://www.gravityforms.com
Description: Quiz Add-on for Gravity Forms
Version: 1.0.beta2
Author: Rocketgenius
Author URI: http://www.rocketgenius.com

------------------------------------------------------------------------
Copyright 2012-2013 Rocketgenius Inc.

This program is free software; you can redistribute it and/or modify
it under the terms of the GNU General Public License as published by
the Free Software Foundation; either version 2 of the License, or
(at your option) any later version.

This program is distributed in the hope that it will be useful,
but WITHOUT ANY WARRANTY; without even the implied warranty of
MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE. See the
GNU General Public License for more details.

You should have received a copy of the GNU General Public License
along with this program; if not, write to the Free Software
Foundation, Inc., 59 Temple Place, Suite 330, Boston, MA 02111-1307 USA
*/


/* example usage of the indicator filters

//easier to use if you just want to change the images
add_filter( 'gquiz_correct_indicator', 'gquiz_correct_indicator');
function gquiz_correct_indicator ($correct_answer_indicator_url){
    $correct_answer_indicator_url = "http://myserver.com/correct.png";
    return $correct_answer_indicator_url;
}
add_filter( 'gquiz_incorrect_indicator', 'gquiz_incorrect_indicator');
function gquiz_incorrect_indicator ($incorrect_answer_indicator_url){
    $incorrect_answer_indicator_url = "http://myserver.com/incorrect.png";
    return $incorrect_answer_indicator_url;
}


//advanced - more control
add_filter( 'gquiz_answer_indicator', 'gquiz_answer_indicator', 10, 7);
function gquiz_answer_indicator ($indicator_markup, $form, $field, $choice, $lead, $is_response_correct, $is_response_wrong){
    if ( $is_response_correct )
        $indicator_markup = " (you got this one right!)";
    elseif ( $is_response_wrong ) {
	    if  ( $field["inputType"] == "checkbox" && rgar( $choice, "gquizIsCorrect" ) )
	        $indicator_markup = " (you missed this one!)";
	    else
	        $indicator_markup = " (you got this one wrong!)";
    } elseif ( rgar( $choice, "gquizIsCorrect" ) ){
        $indicator_markup = " (this was the correct answer!)";
    }
    return $indicator_markup;
}


*/

add_action('init', array('GFQuiz', 'init'));

//show quiz custom columns in entry list and export
add_filter('gform_entry_meta', array('GFQuiz', 'entry_meta'), 10, 2);

add_filter('gform_export_field_value', array('GFQuiz', 'display_entries_field_value'), 10, 4);

//------------------------------------------
class GFQuiz {

    private static $path = "gravityformsquiz/quiz.php";
    private static $url = "http://www.gravityforms.com";
    private static $slug = "gravityformsquiz";
    private static $version = "1.0.beta2";
    private static $min_gravityforms_version = "1.6.10";

    private static $_form_meta_by_id = array();
    private static $_random_ids = array();

    private static $_correct_indicator_url;
    private static $_incorrect_indicator_url;

    //Plugin starting point. Will load appropriate files
    public static function init() {

        //loading translations
        load_plugin_textdomain('gravityformsquiz', FALSE, '/gravityformsquiz/languages');

        if (RG_CURRENT_PAGE == "plugins.php")
            add_action('after_plugin_row_' . self::$path, array('GFQuiz', 'plugin_row'));

        if (!self::is_gravityforms_supported()) {
            return;
        }

        self::register_scripts();

        self::$_correct_indicator_url   = apply_filters("gquiz_correct_indicator", self::get_base_url() . "/images/tick.png");
        self::$_incorrect_indicator_url = apply_filters("gquiz_incorrect_indicator", self::get_base_url() . "/images/cross.png");


        if (is_admin()) {


            //settings page
            add_action('gform_form_settings_menu', array('GFQuiz', 'add_form_settings_menu'), 10, 2);
            add_action("gform_form_settings_page_quiz", array('GFQuiz', 'add_form_settings_page'));

            //form editor
            add_filter('gform_add_field_buttons', array('GFQuiz', 'add_quiz_field'));
            add_filter('gform_field_type_title', array('GFQuiz', 'assign_title'), 10, 2);
            add_action('gform_field_standard_settings', array('GFQuiz', 'quiz_field_settings'), 10, 2);
            add_action('gform_editor_js', array('GFQuiz', 'quiz_editor_script'));
            add_filter('gform_tooltips', array('GFQuiz', 'add_quiz_tooltips'));
            add_action('gform_properties_settings', array('GFQuiz', 'form_settings'), 10, 2);

            //scripts
            add_action('admin_print_styles', array('GFQuiz', 'enqueue_admin_styles'));
            add_action('admin_print_scripts', array('GFQuiz', 'enqueue_admin_scripts'));
            add_action('gform_enqueue_scripts', array('GFQuiz', 'enqueue_gquiz_form_editor_js'), 10, 2);
            add_filter('gform_noconflict_scripts', array('GFQuiz', 'register_noconflict_scripts'));
            add_filter('gform_noconflict_styles', array('GFQuiz', 'register_noconflict_styles'));

            //display quiz results on entry detail & entry list
            add_filter('gform_entries_field_value', array('GFQuiz', 'display_entries_field_value'), 10, 4);
            add_action('gform_entry_detail_sidebar_middle', array('GFQuiz', 'entry_detail_sidebar_middle'), 10, 2);

            //merge tags
            add_filter('gform_custom_merge_tags', array('GFQuiz', 'custom_merge_tags'), 10, 4);

            //declare arrays on form import
            add_filter('gform_import_form_xml_options', array('GFQuiz', 'import_file_options'));

            //Automatic upgrade functionality
            add_filter('transient_update_plugins', array('GFQuiz', 'check_update'));
            add_filter('site_transient_update_plugins', array('GFQuiz', 'check_update'));
            add_action('install_plugins_pre_plugin-information', array('GFQuiz', 'display_changelog'));

            //add top toolbar menu item
            add_filter("gform_toolbar_menu", array('GFQuiz', 'add_toolbar_menu_item'), 10, 2);
            //add custom form action
            add_filter("gform_form_actions", array('GFQuiz', 'add_form_action'), 10, 2);

            //add the gf_quiz_results view
            add_action("gform_view", array('GFQuiz', 'add_view'), 10, 2);


            if (self::has_members_plugin())
                add_filter('members_get_capabilities', array("GFQuiz", "members_get_capabilities"));

            if (RG_CURRENT_PAGE == "admin-ajax.php") {
                if (rgpost("view") == "gf_quiz_results") {
                    require_once(self::get_base_path() . "/results.php");
                    add_action('wp_ajax_gresults_get_results_gf_quiz_results', array('GFResults', 'ajax_get_results'));
                    add_filter('gresults_entries_data_gf_quiz_results', array('GFQuizResults', 'results_entries_data'), 10, 4);
                    add_filter('gresults_markup_gf_quiz_results', array('GFQuizResults', 'results_markup'), 10, 4);
                }

            }

        } else {

            //scripts
            add_action('gform_enqueue_scripts', array('GFQuiz', 'enqueue_front_end_scripts'), 10, 2);

            //maybe shuffle fields
            add_filter('gform_form_tag', array('GFQuiz', 'maybe_store_selected_field_ids'), 10, 2);
            add_filter('gform_pre_render', array('GFQuiz', 'pre_render'));
            add_action('gform_pre_validation', array('GFQuiz', 'pre_render'));

            //shuffle choices if configured
            add_filter('gform_field_content', array('GFQuiz', 'render_quiz_field_content'), 10, 5);

            //merge tags
            add_filter('gform_merge_tag_filter', array('GFQuiz', 'merge_tag_filter'), 10, 5);
            add_filter('gform_replace_merge_tags', array('GFQuiz', 'render_merge_tag'), 10, 7);

            //confirmation
            add_filter("gform_confirmation", array('GFQuiz', 'display_confirmation'), 10, 4);


            //------------------- admin but outside admin context ------------------------

            //enqueue styles for the preview & print pages - admin but outside admin context
            add_filter('gform_preview_styles', array('GFQuiz', 'enqueue_preview_style'), 10, 2);
            add_filter('gform_print_styles', array('GFQuiz', 'enqueue_preview_style'), 10, 2);

            // display quiz results on entry footer
            add_action('gform_print_entry_footer', array('GFQuiz', 'print_entry_footer'), 10, 2);

            // ManageWP premium update filters
            add_filter('mwp_premium_update_notification', array('GFQuiz', 'premium_update_push'));
            add_filter('mwp_premium_perform_update', array('GFQuiz', 'premium_update'));

        }


        //------------------- both outside and inside admin context ------------------------

        //add a special class to quiz fields so we can identify them later
        add_action('gform_field_css_class', array('GFQuiz', 'add_custom_class'), 10, 3);

        //display quiz results on entry detail & entry list
        add_filter('gform_entry_field_value', array('GFQuiz', 'display_quiz_on_entry_detail'), 10, 4);


    } //end function init


    //--------------  Front-end UI functions  ---------------------------------------------------


    public static function pre_render($form) {

        //maybe shuffle fields
        if (rgar($form, "gquizShuffleFields")) {
            $random_ids    = self::get_random_ids($form);
            $c             = 0;
            $page_number   = 1;
            $random_fields = array();
            foreach ($random_ids as $random_id) {
                $random_fields[] = self::get_field_by_id($form, $random_id);
            }
            foreach ($form["fields"] as $key => $field) {
                if ($field["type"] == "quiz") {
                    $form["fields"][$key]               = $random_fields[$c++];
                    $form["fields"][$key]["pageNumber"] = $page_number;
                } elseif ($field["type"] == "page") {
                    $page_number++;
                }
            }

        }

        return $form;
    }

    public static function get_field_by_id($form, $field_id) {
        foreach ($form["fields"] as $field) {
            if ($field["id"] == $field_id) {
                return $field;
            }
        }
    }

    public static function get_random_ids($form) {

        $random_ids = array();
        if (false === empty(self::$_random_ids)) {
            $random_ids = self::$_random_ids;
        } elseif (rgpost('gquiz_random_ids')) {
            $random_ids = explode(',', rgpost('gquiz_random_ids'));
        } else {
            $quiz_fields = GFCommon::get_fields_by_type($form, array('quiz'));
            foreach ($quiz_fields as $quiz_field) {
                $random_ids[] = $quiz_field["id"];
            }
            shuffle($random_ids);
            self::$_random_ids = $random_ids;
        }

        return $random_ids;
    }

    public function maybe_store_selected_field_ids($form_tag, $form) {
        if (rgar($form, "gquizShuffleFields")) {
            $value = implode(',', self::get_random_ids($form));
            $input = "<input type='hidden' value='$value' name='gquiz_random_ids'>";
            $form_tag .= $input;
        }

        return $form_tag;
    }

    public static function display_confirmation($confirmation, $form, $lead, $ajax) {

        if (isset($form["gquizGrading"]) && $form["gquizGrading"] != "none") {
            $confirmation      = substr($confirmation, 0, strlen($confirmation) - 6);
            $results           = self::get_quiz_results($form, $lead);
            $quiz_confirmation = '<div id="gquiz_confirmation_message">';
            $nl2br             = true;
            if (rgar($form, "gquizGrading") == "letter") {
                $quiz_confirmation .= rgar($form, "gquizConfirmationLetter");
                if (rgar($form, "gquizConfirmationLetterAutoformatDisabled") === true)
                    $nl2br = false;
            } else {
                if ($results["is_pass"]) {
                    $quiz_confirmation .= rgar($form, "gquizConfirmationPass");
                    if (rgar($form, "gquizConfirmationPassAutoformatDisabled") === true)
                        $nl2br = false;
                } else {
                    $quiz_confirmation .= rgar($form, "gquizConfirmationFail");
                    if (rgar($form, "gquizConfirmationFailAutoformatDisabled") === true)
                        $nl2br = false;
                }
            }
            $quiz_confirmation .= '</div>';
            $confirmation .= GFCommon::replace_variables($quiz_confirmation, $form, $lead, $url_encode = false, $esc_html = true, $nl2br, $format = "html") . "</div>";
        }

        return $confirmation;
    }

    public function merge_tag_filter($value, $merge_tag, $options, $field, $raw_value) {

        if ($merge_tag == "all_fields" && $field["type"] == "quiz" && is_array($field["choices"])) {
            if ($field["inputType"] == "checkbox") {
                //parse checkbox string (from $value variable) and replace values with text
                foreach ($raw_value as $key => $val) {
                    $text  = RGFormsModel::get_choice_text($field, $val);
                    $value = str_replace($val, $text, $value);
                }
            } else {
                //replacing value with text
                $value = RGFormsModel::get_choice_text($field, $value);
            }
        }

        return $value;
    }

    public function render_merge_tag($text, $form, $entry, $url_encode, $esc_html, $nl2br, $format) {

        $quiz_fields = GFCommon::get_fields_by_type($form, array('quiz'));
        if (empty ($quiz_fields))
            return $text;

        $results = GFQuiz::get_quiz_results($form, $entry);

        $text          = str_replace("{all_quiz_results}", $results["summary"], $text);
        $text          = str_replace("{quiz_score}", $results["score"], $text);
        $text          = str_replace("{quiz_percent}", $results["percent"], $text);
        $text          = str_replace("{quiz_grade}", $results["grade"], $text);
        $is_pass       = $results["is_pass"];
        $pass_fail_str = $is_pass ? __("Pass", "gravityformsquiz") : __("Fail", "gravityformsquiz");
        $text          = str_replace("{quiz_passfail}", $pass_fail_str, $text);

        preg_match_all("/\{quiz:(.*?)\}/", $text, $matches, PREG_SET_ORDER);

        foreach ($matches as $match) {
            $full_tag = $match[0];

            $options_string = isset($match[1]) ? $match[1] : "";
            $options        = shortcode_parse_atts($options_string);

            extract(shortcode_atts(array(
                'id' => 0
            ), $options));

            $fields              = $results["fields"];
            $result_field_markup = "";
            foreach ($fields as $results_field) {
                if ($results_field["id"] == $id) {
                    $result_field_markup = $results_field["markup"];
                    break;
                }
            }
            $new_value = $result_field_markup;

            $text = str_replace($full_tag, $new_value, $text);

        }

        return $text;

    }

    public function get_quiz_results($form, $lead = array(), $show_question = true) {
        $total_score       = 0;
        $output['fields']  = array();
        $output['summary'] = '<div class="gquiz-container">';
        $fields            = GFCommon::get_fields_by_type($form, array('quiz'));
        $total_quiz_fields = count($fields);
        $pass_mark         = $form["gquizPassMark"];
        $grades            = $form["gquizGrades"];

        foreach ($fields as $field) {

            $value = RGFormsModel::get_lead_field_value($lead, $field);

            $field_markup = '<div class="gquiz-field">';
            if ($show_question) {
                $field_markup .= '    <div class="gquiz-field-label">';
                $field_markup .= GFCommon::get_label($field);
                $field_markup .= '    </div>';
            }

            $field_markup .= '    <div class="gquiz-field-choice">';
            $field_markup .= '    <ul>';

            // for checkbox inputs with multiple correct choices
            $completely_correct = true;

            $choices = $field["choices"];
            foreach ($choices as $choice) {
                $is_choice_correct       = isset($choice['gquizIsCorrect']) && $choice['gquizIsCorrect'] == "1" ? true : false;
                $choice_class            = $is_choice_correct ? "gquiz-correct-choice " : "";
                $response_matches_choice = false;
                $user_responded          = true;
                if (is_array($value)) {
                    foreach ($value as $item) {
                        if (RGFormsModel::choice_value_match($field, $choice, $item)) {
                            $response_matches_choice = true;
                            break;
                        }
                    }
                } elseif (empty($value)) {
                    $response_matches_choice = false;
                    $user_responded          = false;
                } else {
                    $response_matches_choice = RGFormsModel::choice_value_match($field, $choice, $value) ? true : false;
                }
                $is_response_correct = $is_choice_correct && $response_matches_choice;

                if ($field["inputType"] == "checkbox")
                    $is_response_wrong = ((!$is_choice_correct) && $response_matches_choice) || ($is_choice_correct && (!$response_matches_choice)) || $is_choice_correct && !$user_responded;
                else
                    $is_response_wrong = ((!$is_choice_correct) && $response_matches_choice) || $is_choice_correct && !$user_responded;

                $indicator_markup = '';
                if ($is_response_correct) {

                    $indicator_markup = '<img src="' . self::$_correct_indicator_url . '" />';
                    $choice_class .= "gquiz-correct-response ";
                } elseif ($is_response_wrong) {
                    $indicator_markup   = '<img src="' . self::$_incorrect_indicator_url . '" />';
                    $completely_correct = false;
                    $choice_class .= "gquiz-incorrect-response ";
                }

                $indicator_markup = apply_filters('gquiz_answer_indicator', $indicator_markup, $form, $field, $choice, $lead, $is_response_correct, $is_response_wrong);

                $choice_class_markup = empty($choice_class) ? "" : 'class="' . $choice_class . '"';
                $field_markup .= "<li {$choice_class_markup}>";

                $field_markup .= $choice['text'] . $indicator_markup;
                $field_markup .= '</li>';

            }
            //end foreach choice

            $field_markup .= '    </ul>';
            $field_markup .= '    </div>';

            if (rgar($field, "gquizShowAnswerExplanation")) {
                $field_markup .= '<div class="gquiz-answer-explanation">';
                $field_markup .= $field["gquizAnswerExplanation"];
                $field_markup .= '</div>';
            }

            $field_markup .= '</div>';
            if ($completely_correct)
                $total_score += 1;
            $output['summary'] .= $field_markup;
            array_push($output['fields'], array("id" => $field["id"], "markup" => $field_markup));

        } //end foreach field

        $output['summary'] .= '</div>';
        $output['score']   = $total_score;
        $total_percent     = $total_quiz_fields > 0 ? $total_score / $total_quiz_fields * 100 : 0;
        $output['percent'] = round($total_percent);
        $total_grade       = self::get_grade($grades, $total_percent);

        $output['grade']   = $total_grade;
        $is_pass           = $total_percent >= $pass_mark ? true : false;
        $output['is_pass'] = $is_pass;

        return $output;
    }

    public static function get_grade($grades, $percent) {
        $the_grade = "";
        usort($grades, array('GFQuiz', 'sort_grades'));
        foreach ($grades as $grade) {
            if ($grade["value"] <= (double)$percent) {
                $the_grade = $grade["text"];
                break;
            }
        }

        return $the_grade;
    }

    function sort_grades($a, $b) {
        return $a['value'] < $b['value'];
    }

    public function custom_merge_tags($merge_tags, $form_id, $fields, $element_id) {
        $contains_quiz_field = false;
        foreach ($fields as $field) {
            if ($field["type"] == "quiz") {
                $contains_quiz_field = true;
                $field_id            = $field["id"];
                $field_label         = $field['label'];
                $merge_tags[]        = array('label' => $field_label . ': Quiz Results', 'tag' => "{quiz:id={$field_id}}");
            }
        }
        if ($contains_quiz_field) {
            $merge_tags[] = array('label' => 'All Quiz Results', 'tag' => '{all_quiz_results}');
            $merge_tags[] = array('label' => 'Quiz Score Total', 'tag' => '{quiz_score}');
            $merge_tags[] = array('label' => 'Quiz Score Percentage', 'tag' => '{quiz_percent}');
            $merge_tags[] = array('label' => 'Quiz Grade', 'tag' => '{quiz_grade}');
            $merge_tags[] = array('label' => 'Quiz Pass/Fail', 'tag' => '{quiz_passfail}');
        }

        return $merge_tags;
    }

    public function render_quiz_field_content($content, $field, $value, $lead_id, $form_id) {

        if ($lead_id === 0 && $field["type"] == "quiz") {

            //maybe shuffle choices
            if (rgar($field, 'gquizEnableRandomizeQuizChoices')) {

                //pass the HTML for the choices through DOMdocument to make sure we get the complete li node
                $dom     = new DOMDocument();
                $content = '<?xml version="1.0" encoding="UTF-8"?>' . $content;
                //allow malformed HTML inside the choice label
                $previous_value = libxml_use_internal_errors(TRUE);
                $dom->loadHTML($content);
                libxml_clear_errors();
                libxml_use_internal_errors($previous_value);

                $content = $dom->saveXML($dom->documentElement);

                //pick out the elements: LI for radio & checkbox, OPTION for select
                $element_name = $field['inputType'] == 'select' ? 'select' : 'ul';
                $nodes        = $dom->getElementsByTagName($element_name)->item(0)->childNodes;

                //cycle through the LI elements and swap them around randomly
                $temp_str1 = "gquiz_shuffle_placeholder1";
                $temp_str2 = "gquiz_shuffle_placeholder2";
                for ($i = $nodes->length - 1; $i >= 0; $i--) {
                    $n = rand(0, $i);
                    if ($i <> $n) {
                        $i_str   = $dom->saveXML($nodes->item($i));
                        $n_str   = $dom->saveXML($nodes->item($n));
                        $content = str_replace($i_str, $temp_str1, $content);
                        $content = str_replace($n_str, $temp_str2, $content);
                        $content = str_replace($temp_str2, $i_str, $content);
                        $content = str_replace($temp_str1, $n_str, $content);
                    }
                }

                //snip off the tags that DOMdocument adds
                $content = str_replace("<html><body>", "", $content);
                $content = str_replace("</body></html>", "", $content);

            }
            if ($field['inputType'] == 'select') {
                $new_option = '<option selected="selected" value="">' . __("Select an option", "gravityformsquiz") . '</option></select>';
                $content    = str_replace("</select>", $new_option, $content);
            }

        }

        return $content;
    }


    //--------------  Scripts & Styles  ---------------------------------------------------

    function enqueue_front_end_scripts($form, $is_ajax) {
        $quiz_fields = GFCommon::get_fields_by_type($form, array('quiz'));
        if (empty ($quiz_fields))
            return;

        wp_enqueue_style('gquiz_css');


        if (rgar($form, "gquizInstantFeedback")) {
            wp_enqueue_script('jquery');
            wp_enqueue_script('gquiz_js');

            $params = array(
                'correctIndicator'   => self::$_correct_indicator_url,
                'incorrectIndicator' => self::$_incorrect_indicator_url
            );
            wp_localize_script('gquiz_js', 'gquizVars', $params);

            $answers = array();
            foreach ($quiz_fields as $quiz_field) {
                $choices       = $quiz_field["choices"];
                $correct_value = self::get_correct_choice_value($choices);

                $answer_explanation         = rgar($quiz_field, "gquizShowAnswerExplanation") ? rgar($quiz_field, "gquizAnswerExplanation") : "";
                $answers[$quiz_field["id"]] = array(
                    'correctValue' => base64_encode($correct_value),
                    'explanation'  => base64_encode($answer_explanation)
                );
            }

            wp_localize_script('gquiz_js', 'gquizAnswers', $answers);
        }


    }


    public static function get_correct_choice_value($choices) {
        $correct_choice_value = "";
        foreach ($choices as $choice) {
            if (rgar($choice, "gquizIsCorrect")) {
                $correct_choice_value = rgar($choice, "value");
            }
        }

        return $correct_choice_value;
    }

    public static function register_noconflict_scripts($scripts) {

        //registering script with Gravity Forms so that it gets enqueued when running in no-conflict mode
        $scripts[] = "gquiz_form_editor_js";
        $scripts[] = "gquiz_results_js";
        $scripts[] = "jquery-ui-resizable";

        return $scripts;
    }

    public static function register_noconflict_styles($styles) {

        //registering styles with Gravity Forms so that it gets enqueued when running in no-conflict mode
        $styles[] = "gquiz_css";
        $styles[] = "gquiz_form_editor_css";
        $styles[] = "gquiz_results_css";

        return $styles;
    }

    public static function enqueue_preview_style($styles, $form) {
        $quiz_fields = GFCommon::get_fields_by_type($form, array('quiz'));
        if (false === empty ($quiz_fields))
            $styles[] = "gquiz_css";

        return $styles;
    }

    public static function enqueue_admin_styles() {
        $id      = rgget("id");
        $view    = rgget("view");
        $subview = rgget("subview");
        if (rgget("page") == "gf_edit_forms" && $view == "gf_quiz_results") {
            if (version_compare(GFCommon::$version, "1.6.999", '>')) {
                wp_enqueue_style('jquery-ui-styles', GFCommon::get_base_url() . '/css/jquery-ui-1.7.2.custom.css');
            }
            wp_enqueue_style('gquiz_results_css');
        } elseif (rgget("page") == "gf_edit_forms" && $view == "settings" && $subview == "quiz") {
            wp_enqueue_style('gquiz_form_settings_css');
        } elseif ((rgget("page") == "gf_edit_forms" && !empty($id) && empty($view)) || rgget("page") == "gf_new_form") {
            wp_enqueue_style('gquiz_form_editor_css');
            wp_enqueue_style('gquiz_css');
        } elseif (rgget("page") == "gf_entries" && rgget("view") == "entry") {
            wp_enqueue_style('gquiz_css');
        }
    }

    public static function enqueue_admin_scripts() {
        $id      = rgget("id");
        $view    = rgget("view");
        $subview = rgget("subview");
        if (rgget("page") == "gf_edit_forms" && $view == "gf_quiz_results") {
            wp_enqueue_script('jquery-ui-resizable', false, array('jquery'), false, false);
            if (version_compare(GFCommon::$version, "1.6.999", '>')) {
                wp_enqueue_script('jquery-ui-datepicker', false, array('jquery'), false, false);
            }
            wp_enqueue_script('google_charts', 'https://www.google.com/jsapi');
            wp_enqueue_script('gquiz_results_js');
            self::localize_results_scripts();
        } elseif (rgget("page") == "gf_edit_forms" && $view == "settings" && $subview == "quiz") {
            wp_enqueue_script("gforms_json", GFCommon::get_base_url() . "/js/jquery.json-1.3.js", array("jquery"), GFCommon::$version, true);
            wp_enqueue_script('jquery-ui-sortable');
            wp_enqueue_script('gquiz_form_settings_js');
            self::localize_form_settings_scripts();
        } elseif ((rgget("page") == "gf_edit_forms" && !empty($id) && empty($view)) || rgget("page") == "gf_new_form") {
            wp_enqueue_script('gquiz_form_editor_js');
            wp_enqueue_script('jquery-ui-sortable');
            self::localize_admin_scripts();
        }

    }

    public static function enqueue_gquiz_form_editor_js($form, $is_ajax) {
        wp_enqueue_script('gquiz_form_editor_js');
    }

    public static function register_scripts() {
        wp_register_script('gquiz_form_editor_js', plugins_url('js/gquiz_form_editor.js', __FILE__));
        wp_register_script('gquiz_form_settings_js', plugins_url('js/gquiz_form_settings.js', __FILE__));
        wp_register_script('gquiz_results_js', plugins_url('js/gquiz_results.js', __FILE__));
        wp_register_script('gquiz_js', plugins_url('js/gquiz.js', __FILE__));
        wp_register_style('gquiz_form_editor_css', plugins_url('css/gquiz_form_editor.css', __FILE__));
        wp_register_style('gquiz_form_settings_css', plugins_url('css/gquiz_form_settings.css', __FILE__));
        wp_register_style('gquiz_css', plugins_url('css/gquiz.css', __FILE__));
        wp_register_style('gquiz_results_css', plugins_url('css/gquiz_results.css', __FILE__));

    } // end function register_scripts

    public static function localize_admin_scripts() {

        // Get current page protocol
        $protocol = isset($_SERVER["HTTPS"]) ? 'https://' : 'http://';
        // Output admin-ajax.php URL with same protocol as current page
        $params = array(
            'ajaxurl'   => admin_url('admin-ajax.php', $protocol),
            'imagesUrl' => self::get_base_url() . "/images"
        );
        wp_localize_script('gquiz_form_editor_js', 'gquizVars', $params);


        //localize strings
        $strings = array(
            'dragToReOrder'           => __("Drag to re-order", "gravityformsquiz"),
            'addAnotherGrade'         => __("add another grade", "gravityformsquiz"),
            'removeThisGrade'         => __("remove this grade", "gravityformsquiz"),
            'firstChoice'             => __("First Choice", "gravityformsquiz"),
            'secondChoice'            => __("Second Choice", "gravityformsquiz"),
            'thirdChoice'             => __("Third Choice", "gravityformsquiz"),
            'toggleCorrectIncorrect'  => __("Click to toggle as correct/incorrect", "gravityformsquiz"),
            'defineAsCorrect'         => __("Click to define as correct", "gravityformsquiz"),
            'markAnAnswerAsCorrect'   => __("Mark an answer as correct by using the checkmark icon to the right of the answer.", "gravityformsquiz"),
            'defineAsIncorrect'       => __("Click to define as incorrect", "gravityformsquiz"),
            'gradeA'                  => __("A", "gravityformsquiz"),
            'gradeB'                  => __("B", "gravityformsquiz"),
            'gradeC'                  => __("C", "gravityformsquiz"),
            'gradeD'                  => __("D", "gravityformsquiz"),
            'gradeE'                  => __("E", "gravityformsquiz"),
            'gradeF'                  => __("F", "gravityformsquiz"),
            'gquizConfirmationFail'   => __("<strong>Quiz Results:</strong> You Failed!\n<strong>Correct Answers:</strong> {quiz_score}\n<strong>Percentage:</strong> {quiz_percent}%", "gravityformsquiz"),
            'gquizConfirmationPass'   => __("<strong>Quiz Results:</strong> You Passed!\n<strong>Correct Answers:</strong> {quiz_score}\n<strong>Percentage:</strong> {quiz_percent}%", "gravityformsquiz"),
            'gquizConfirmationLetter' => __("<strong>Quiz Grade:</strong> {quiz_grade}\n<strong>Correct Answers:</strong> {quiz_score}\n<strong>Percentage:</strong> {quiz_percent}%", "gravityformsquiz"),

        );
        wp_localize_script('gquiz_form_editor_js', 'gquiz_strings', $strings);

    }

    public static function localize_form_settings_scripts() {

        // Get current page protocol
        $protocol = isset($_SERVER["HTTPS"]) ? 'https://' : 'http://';
        // Output admin-ajax.php URL with same protocol as current page
        $params = array(
            'ajaxurl'   => admin_url('admin-ajax.php', $protocol),
            'imagesUrl' => self::get_base_url() . "/images"
        );
        wp_localize_script('gquiz_form_settings_js', 'gquizVars', $params);


        //localize strings
        $strings = array(
            'dragToReOrder'           => __("Drag to re-order", "gravityformsquiz"),
            'addAnotherGrade'         => __("add another grade", "gravityformsquiz"),
            'removeThisGrade'         => __("remove this grade", "gravityformsquiz"),
            'gradeA'                  => __("A", "gravityformsquiz"),
            'gradeB'                  => __("B", "gravityformsquiz"),
            'gradeC'                  => __("C", "gravityformsquiz"),
            'gradeD'                  => __("D", "gravityformsquiz"),
            'gradeE'                  => __("E", "gravityformsquiz"),
            'gradeF'                  => __("F", "gravityformsquiz"),
            'gquizConfirmationFail'   => __("<strong>Quiz Results:</strong> You Failed!\n<strong>Correct Answers:</strong> {quiz_score}\n<strong>Percentage:</strong> {quiz_percent}%", "gravityformsquiz"),
            'gquizConfirmationPass'   => __("<strong>Quiz Results:</strong> You Passed!\n<strong>Correct Answers:</strong> {quiz_score}\n<strong>Percentage:</strong> {quiz_percent}%", "gravityformsquiz"),
            'gquizConfirmationLetter' => __("<strong>Quiz Grade:</strong> {quiz_grade}\n<strong>Correct Answers:</strong> {quiz_score}\n<strong>Percentage:</strong> {quiz_percent}%", "gravityformsquiz"),

        );
        wp_localize_script('gquiz_form_settings_js', 'gquiz_strings', $strings);

    }


    public static function localize_results_scripts() {

        $filter_fields    = rgget("f");
        $filter_types     = rgget("t");
        $filter_operators = rgget("o");
        $filter_values    = rgget("v");

        // Get current page protocol
        $protocol = isset($_SERVER["HTTPS"]) ? 'https://' : 'http://';
        // Output admin-ajax.php URL with same protocol as current page

        $vars = array(
            'ajaxurl'         => admin_url('admin-ajax.php', $protocol),
            'imagesUrl'       => self::get_base_url() . "/images",
            'filterFields'    => $filter_fields,
            'filterTypes'     => $filter_types,
            'filterOperators' => $filter_operators,
            'filterValues'    => $filter_values
        );


        wp_localize_script('gquiz_results_js', 'gresultsVars', $vars);

        $strings = array(
            'noFilters'         => __("No filters", "gravityformsquiz"),
            'addFieldFilter'    => __("Add a field filter", "gravityformsquiz"),
            'removeFieldFilter' => __("Remove a field filter", "gravityformsquiz"),
            'ajaxError'         => __("Error retrieving results. Please contact support.", "gravityformsquiz")
        );


        wp_localize_script('gquiz_results_js', 'gresultsStrings', $strings);

    }

    //--------------  Admin functions  ---------------------------------------------------
    public static function add_form_settings_menu($tabs, $form_id) {
        $form        = self::get_form_meta($form_id);
        $quiz_fields = GFCommon::get_fields_by_type($form, array('quiz'));
        if (false === empty($quiz_fields))
            $tabs[] = array("name" => 'quiz', "label" => __("Quiz Settings", "gravityformsquiz"));

        return $tabs;
    }

    public static function add_form_settings_page() {
        require_once(self::get_base_path() . "/form_settings.php");
        GFQuizFormSettings::form_settings_page();
    }

    public static function add_view($view, $form_id) {
        if ($view == "gf_quiz_results") {
            $form      = GFFormsModel::get_form_meta($form_id);
            $filters   = array();
            $filters[] = array(
                "key"           => "gquiz_score",
                "text"          => "Quiz Score",
                "type"          => "meta",
                "preventMultiple" => false,
                "operators"     => array(
                    0 => array("value" => "=", "text" => "is"),
                    1 => array("value" => ">", "text" => "greater than"),
                    2 => array("value" => "<", "text" => "less than"),
                    3 => array("value" => "<>", "text" => "is not"),
                )
            );

            $filters[] = array(
                "key"       => "gquiz_percent",
                "type"      => "meta",
                "text"      => "Quiz Percentage",
                "preventMultiple" => false,
                "operators" => array(
                    0 => array("value" => "=", "text" => "is"),
                    1 => array("value" => ">", "text" => "greater than"),
                    2 => array("value" => "<", "text" => "less than")
                )
            );

            if ("letter" === rgar($form, "gquizGrading")) {
                $filters[] = array(
                    "key"       => "gquiz_grade",
                    "type"      => "meta",
                    "text"      => "Quiz Grade",
                    "preventMultiple" => false,
                    "operators" => array(
                        0 => array("value" => "=", "text" => "is"),
                        1 => array("value" => "<>", "text" => "is not")
                    )
                );
            }

            if ("passfail" === rgar($form, "gquizGrading")) {
                $filters[] = array(
                    "key"       => "gquiz_is_pass",
                    "type"      => "meta",
                    "text"      => "Pass/Fail",
                    "preventMultiple" => true,
                    "operators" => array(
                        0 => array("value" => "=", "text" => "is"),
                        1 => array("value" => "<>", "text" => "is not")
                    ),
                    "values"    => array(
                        0 => array("value" => "1", "text" => "Pass"),
                        1 => array("value" => "0", "text" => "Fail")
                    )
                );
            }


            require_once(self::get_base_path() . "/results.php");
            GFResults::results_page($form_id, array('quiz'), __("Quiz Results", "gravityformsquiz"), "gf_edit_forms", $view, $filters);
        }
    }

    public static function has_members_plugin() {
        return function_exists('members_get_capabilities');
    }

    public static function members_get_capabilities($caps) {
        return array_merge($caps, array("gravityforms_quiz_results"));
    }

    public static function get_form_meta($form_id) {
        $form_metas = self::$_form_meta_by_id;

        if (empty($form_metas)) {
            $form_ids = array();
            $forms    = RGFormsModel::get_forms();
            foreach ($forms as $form) {
                $form_ids[] = $form->id;
            }
            //backwards compatiblity with <1.7
            if (method_exists('GFFormsModel', 'get_form_meta_by_id'))
                $form_metas = GFFormsModel::get_form_meta_by_id($form_ids);
            else
                $form_metas = GFFormsModel::get_forms_by_id($form_ids);

            self::$_form_meta_by_id = $form_metas;
        }
        foreach ($form_metas as $form_meta) {
            if ($form_meta["id"] == $form_id)
                return $form_meta;
        }

    }

    public static function add_form_action($actions, $form_id) {

        if (isset($actions["settings"]))
            return self::filter_menu_items($actions, $form_id, true);

        $new_actions = $actions;
        $form_meta   = self::get_form_meta($form_id);
        $quiz_fields = GFCommon::get_fields_by_type($form_meta, array('quiz'));
        if (false === empty($quiz_fields)) {
            $results_link = '<a href="' . admin_url("admin.php?page=gf_edit_forms&view=gf_quiz_results&id={$form_id}") . '">' . __("Results", "gravityformsquiz") . "</a>";

            $offset      = 3;
            $new_actions = array_slice($actions, 0, $offset, true) +
                array('results' => $results_link) +
                array_slice($actions, $offset, NULL, true);
        }

        return $new_actions;
    }

    public static function add_toolbar_menu_item($menu_items, $form_id) {
        return self::filter_menu_items($menu_items, $form_id, false);
    }

    public static function filter_menu_items($menu_items, $form_id, $compact) {
        $form_meta   = self::get_form_meta($form_id);
        $quiz_fields = GFCommon::get_fields_by_type($form_meta, array('quiz'));
        if (false === empty($quiz_fields)) {
            $form_id    = $form_meta["id"];
            $link_class = "";
            if (rgget("page") == "gf_new_form")
                $link_class = "gf_toolbar_disabled";
            else if (rgget("page") == "gf_edit_forms" && rgget("view") == "gf_quiz_results")
                $link_class = "gf_toolbar_active";

            $sub_menu_items   = array();
            $sub_menu_items[] = array(
                'label'        => __("Quiz Results", "gravityformsquiz"),
                'title'        => __("View quiz results generated by this form", "gravityformsquiz"),
                'link_class'   => $link_class,
                'url'          => admin_url("admin.php?page=gf_edit_forms&view=gf_quiz_results&id={$form_id}"),
                'capabilities' => array("gravityforms_quiz_results")
            );

            // test submenu item
            /*
            $sub_menu_items[] = array(
                'label' 		=> __("Test menu item", "gravityformsquiz"),
                'title' 		=> __("This is the title", "gravityformsquiz"),
                'url' 			=> "http://google.com",
                'capabilities' => array("gravityforms_quiz_results")
            );
            */

            if (isset($menu_items["results"])) {
                $existing_link_class = $menu_items["results"]["link_class"];
                $link_class == empty($existing_link_class) ? $link_class : $existing_link_class;
                $existing_capabilities                   = $menu_items["results"]["capabilities"];
                $merged_capabilities                     = array_merge($existing_capabilities, array("gravityforms_quiz_results"));
                $existing_sub_menu_items                 = $menu_items["results"]["sub_menu_items"];
                $merged_sub_menu_items                   = array_merge($existing_sub_menu_items, $sub_menu_items);
                $menu_items["results"]["link_class"]     = $link_class;
                $menu_items["results"]["capabilities"]   = $merged_capabilities;
                $menu_items["results"]["sub_menu_items"] = $merged_sub_menu_items;

            } else {
                $menu_items["results"] = array(
                    'label'          => __("Results", "gravityformsquiz"),
                    'title'          => __("View results generated by this form", "gravityformsquiz"),
                    'url'            => "",
                    'onclick'        => $compact ? "toggleSubMenu(this);return false;" : "return false;",
                    'menu_class'     => 'gf_form_toolbar_results',
                    'link_class'     => $link_class,
                    'capabilities'   => array("gravityforms_quiz_results"),
                    'sub_menu_items' => $sub_menu_items,
                    'priority'       => 750
                );
            }

        }

        return $menu_items;
    }


    public static function entry_meta($custom_entry_properties, $form_id) {
        $form        = RGFormsModel::get_form_meta($form_id);
        $quiz_fields = GFCommon::get_fields_by_type($form, array('quiz'));
        if (false === empty ($quiz_fields)) {
            $grading = rgar($form, "gquizGrading");


            $custom_entry_properties['gquiz_score']   = array(
                'label'                      => 'Quiz Score Total',
                'is_numeric'                 => true,
                'is_default_column'          => true,
                'update_entry_meta_callback' => array('GFQuiz', 'update_entry_meta')
            );
            $custom_entry_properties['gquiz_percent'] = array(
                'label'                      => 'Quiz Percentage',
                'is_numeric'                 => true,
                'is_default_column'          => $grading == "letter" || $grading == "passfail" ? true : false,
                'update_entry_meta_callback' => array('GFQuiz', 'update_entry_meta')
            );
            $custom_entry_properties['gquiz_grade']   = array(
                'label'                      => 'Quiz Grade',
                'is_numeric'                 => false,
                'is_default_column'          => $grading == "letter" ? true : false,
                'update_entry_meta_callback' => array('GFQuiz', 'update_entry_meta')
            );
            $custom_entry_properties['gquiz_is_pass'] = array(
                'label'                      => 'Quiz Pass/Fail',
                'is_numeric'                 => false,
                'is_default_column'          => $grading == "passfail" ? true : false,
                'update_entry_meta_callback' => array('GFQuiz', 'update_entry_meta')
            );

        }

        return $custom_entry_properties;
    }

    public static function update_entry_meta($key, $lead, $form) {
        $value   = "";
        $results = self::get_quiz_results($form, $lead, false);

        if ($key == "gquiz_score")
            $value = $results["score"];
        elseif ($key == "gquiz_percent")
            $value = $results["percent"]; elseif ($key == "gquiz_grade")
            $value = $results["grade"]; elseif ($key == "gquiz_is_pass")
            $value = $results["is_pass"] ? "1" : "0";

        return $value;
    }


    public function display_entries_field_value($value, $form_id, $field_id, $lead) {
        $new_value = $value;
        if ($field_id == "gquiz_is_pass") {
            $is_pass   = $value;
            $new_value = $is_pass ? __("Pass", "gravityformsquiz") : __("Fail", "gravityformsquiz");

        } elseif ($field_id == "gquiz_percent") {
            $new_value = $new_value . "%";
        } else {

            $form_meta       = RGFormsModel::get_form_meta($form_id);
            $form_meta_field = RGFormsModel::get_field($form_meta, $field_id);
            if ($form_meta_field["type"] == "quiz") {
                if ($form_meta_field["inputType"] == "radio" || $form_meta_field["inputType"] == "select") {
                    $new_value = GFCommon::selection_display($value, $form_meta_field, $currency = "", $use_text = true);
                } elseif ($form_meta_field["inputType"] == "checkbox") {
                    $ary        = explode(", ", $value);
                    $new_values = array();
                    foreach ($ary as $response) {
                        $new_values[] = GFCommon::selection_display($response, $form_meta_field, $currency = "", $use_text = true);
                    }
                    $new_value = implode(', ', $new_values);
                }
            }

        }

        return $new_value;
    }

    public function display_quiz_on_entry_detail($value, $field, $lead, $form) {
        $new_value = "";

        if ($field["type"] == 'quiz') {
            $new_value .= '<div class="gquiz_entry">';
            $results      = self::get_quiz_results($form, $lead, false);
            $field_markup = "";
            foreach ($results["fields"] as $field_results) {
                if ($field_results["id"] == $field["id"]) {
                    $field_markup = $field_results["markup"];
                    break;
                }
            }

            $new_value .= $field_markup;
            $new_value .= '</div>';

            // if original response is not in results display below
            // TODO - handle orphaned repsonses (orginal choice is deleted)

        } else {
            $new_value = $value;
        }

        return $new_value;
    }

    public static function import_file_options($options) {
        $options["gquizGrade"] = array("unserialize_as_array" => true);

        return $options;
    }

    public static function form_settings($position, $form_id) {
        if (class_exists('GFFormSettings')) //1.7
            return;

        if ($position == 500) {
            $form        = RGFormsModel::get_form_meta($form_id);
            $quiz_fields = GFCommon::get_fields_by_type($form, array('quiz'));

            $display_style = empty ($quiz_fields) ? "display:none;" : "";

            ?>
        <div id="gquiz-form-settings" style="<?php echo $display_style ?>">
            <strong><?php _e("Quiz Settings", "gravityformsquiz"); ?></strong>
            <br/><br/>
            <ul>
                <li>
                    <input type="checkbox" id="gquiz-shuffle-fields"/> <label
                        for="gquiz-shuffle-fields"><?php _e("Shuffle quiz fields", "gravityformsquiz") ?> <?php gform_tooltip("gquiz_shuffle_fields") ?></label>
                </li>
                <li>
                    <input type="checkbox" id="gquiz-instant-feedback"/> <label
                        for="gquiz-instant-feedback"><?php _e("Instant feedback", "gravityformsquiz") ?> <?php gform_tooltip("gquiz_instant_feedback") ?></label>
                </li>
                <li>
                    <label><?php _e("Grading", "gravityformsquiz"); ?></label>

                    <div id="gquiz-grading-options">
                        <input type="radio" id="gquiz-grading-none" name="gquiz-grading" value="none"
                               onclick="gquiz_toggle_grading_options('none')"/>
                        <label for="gquiz-grading-none" class="inline">
                            <?php _e("None", "gravityformsquiz"); ?>
                            <?php gform_tooltip("gquiz_grading_none") ?>
                        </label>
                        &nbsp;&nbsp;
                        <input type="radio" id="gquiz-grading-passfail" name="gquiz-grading" value="passfail"
                               onclick="gquiz_toggle_grading_options('passfail')"/>
                        <label for="gquiz-grading-passfail" class="inline">
                            <?php _e("Pass/Fail", "gravityformsquiz"); ?>
                            <?php gform_tooltip("gquiz_grading_pass_fail") ?>
                        </label>
                        &nbsp;&nbsp;
                        <input type="radio" id="gquiz-grading-letter" name="gquiz-grading" value="letter"
                               onclick="gquiz_toggle_grading_options('letter')"/>
                        <label for="gquiz-grading-letter" class="inline">
                            <?php _e("Letter", "gravityformsquiz"); ?>
                            <?php gform_tooltip("gquiz_grading_letter") ?>
                        </label>

                        <div id="gquiz-grading-pass-fail-container" style="margin-top:10px;display:none;">
                            <div id="gquiz-form-setting-pass-grade">
                                <label for="gquiz-pass-mark" style="display:block;">
                                    <?php _e("Pass Percentage", "gravityformsquiz"); ?>
                                    <?php gform_tooltip("gquiz_pass_percentage") ?>
                                </label>

                                <div id="gquiz-form-setting-pass-grade-value">
                                    <input type="text" id="gquiz-pass-mark" class="gquiz-grade-value"
                                           value=""><span>%</span>
                                </div>
                            </div>

                            <div id="gquiz-form-setting-pass-confirmation-message">
                                <label for="gquiz-pass-confirmation-message"
                                       style="display:block;"><?php _e("Quiz Pass Confirmation", "gravityformsquiz"); ?>
                                    <?php gform_tooltip("gquiz_pass_confirmation") ?>
                                </label>

                                <div>
                                    <?php GFCommon::insert_variables($form["fields"], "gquiz-pass-confirmation-message"); ?>
                                </div>
                                <textarea id="gquiz-pass-confirmation-message"
                                          class="fieldwidth-3 fieldheight-1"></textarea>

                                <div style="margin-top:5px;margin-left:1px;">
                                    <input type="checkbox" id="gquiz-pass-confirmation-message-disable-autoformatting"/>
                                    <label for="gquiz-pass-confirmation-message-disable-autoformatting"><?php _e("Disable Auto-formatting", "gravityformsquiz") ?> <?php gform_tooltip("form_confirmation_autoformat") ?></label>
                                </div>
                            </div>
                            <br/>

                            <div id="gquiz-form-setting-fail-confirmation-message">
                                <label for="gquiz-fail-confirmation-message" style="display:block;">
                                    <?php _e("Quiz Fail Confirmation", "gravityformsquiz"); ?>
                                    <?php gform_tooltip("gquiz_fail_confirmation") ?>
                                </label>

                                <div>
                                    <?php GFCommon::insert_variables($form["fields"], "gquiz-fail-confirmation-message"); ?>
                                </div>
                                <textarea id="gquiz-fail-confirmation-message"
                                          class="fieldwidth-3 fieldheight-1"></textarea>

                                <div style="margin-top:5px;margin-left:1px;">
                                    <input type="checkbox" id="gquiz-fail-confirmation-message-disable-autoformatting"/>
                                    <label for="gquiz-fail-confirmation-message-disable-autoformatting"><?php _e("Disable Auto-formatting", "gravityformsquiz") ?> <?php gform_tooltip("form_confirmation_autoformat") ?></label>
                                </div>
                            </div>
                        </div>

                        <div id="gquiz-grading-letter-container" style="margin-top:10px;display:none;">
                            <label for="gquiz-settings-grades-container" style="display:block;">
                                <?php _e("Letter Grades", "gravityformsquiz"); ?>
                                <?php gform_tooltip("gquiz_letter_grades") ?>
                            </label>

                            <div id="gquiz-settings-grades-container">
                                <label class="gquiz-grades-header-label"><?php _e("Label", "gravityformsquiz") ?></label><label
                                    class="gquiz-grades-header-value"><?php _e("Percentage", "gravityformsquiz") ?></label>
                                <ul id="gquiz-grades"></ul>
                            </div>
                            <br/>

                            <div id="gquiz-form-setting-letter-confirmation-message">
                                <label for="gquiz-letter-confirmation-message" style="display:block;">
                                    <?php _e("Quiz Confirmation", "gravityformsquiz"); ?>
                                    <?php gform_tooltip("gquiz_letter_confirmation") ?>
                                </label>

                                <div>
                                    <?php GFCommon::insert_variables($form["fields"], "gquiz-letter-confirmation-message"); ?>
                                </div>
                                <textarea id="gquiz-letter-confirmation-message"
                                          class="fieldwidth-3 fieldheight-1"></textarea>

                                <div style="margin-top:5px;margin-left:1px;">
                                    <input type="checkbox"
                                           id="gquiz-letter-confirmation-message-disable-autoformatting"/> <label
                                        for="gquiz-letter-confirmation-message-disable-autoformatting"><?php _e("Disable Auto-formatting", "gravityformsquiz") ?> <?php gform_tooltip("form_confirmation_autoformat") ?></label>
                                </div>
                            </div>
                        </div>
                    </div>

                </li>


            </ul>
        </div>
        <?php
        }
    }

    public static function print_entry_footer($form, $lead) {
        self::entry_results($form, $lead);
    }

    public static function entry_detail_sidebar_middle($form, $lead) {
        self::entry_results($form, $lead);
    }

    public static function entry_results($form, $lead) {

        $fields            = GFCommon::get_fields_by_type($form, array('quiz'));
        $count_quiz_fields = count($fields);
        if ($count_quiz_fields == 0)
            return;

        $grading = $form["gquizGrading"];
        $score   = rgar($lead, "gquiz_score");
        $percent = rgar($lead, "gquiz_percent");
        $is_pass = rgar($lead, "gquiz_is_pass");
        $grade   = rgar($lead, "gquiz_grade");

        ?>
    <div id="gquiz-entry-detail-score-info-container" class="postbox">
        <h3 style="cursor: default;"><?php _e("Quiz Results", "gravityformsquiz"); ?></h3>

        <div id="gquiz-entry-detail-score-info">
            Score: <?php echo $score . "/" . $count_quiz_fields ?><br/><br/>
            Percentage: <?php  echo $percent ?>%<br/><br/>
            <?php if ($grading == "passfail"): ?>
            <?php $pass_fail_str = $is_pass ? __("Pass", "gravityformsquiz") : __("Fail", "gravityformsquiz"); ?>
            Pass/Fail: <?php echo $pass_fail_str ?><br/>
            <?php elseif ($grading == "letter"): ?>
            Grade: <?php echo $grade ?><br/>
            <?php endif; ?>
        </div>

    </div>

    <?php
    }

    // adds gquiz-field class to quiz fields
    public static function add_custom_class($classes, $field, $form) {
        if ($field["type"] == "quiz")
            $classes .= " gquiz-field ";
        if (rgar($form, "gquizInstantFeedback"))
            $classes .= " gquiz-instant-feedback ";

        return $classes;
    }

    public static function assign_title($title, $field_type) {
        if ($field_type == "quiz")
            return __("Quiz", "gravityformsquiz");

        return $title;
    }

    public static function add_quiz_field($field_groups) {

        foreach ($field_groups as &$group) {
            if ($group["name"] == "advanced_fields") {
                $group["fields"][] = array("class" => "button", "value" => __("Quiz", "gravityformsquiz"), "onclick" => "StartAddField('quiz');");
                break;
            }
        }

        return $field_groups;
    }

    public static function add_quiz_tooltips($tooltips) {
        //form settings
        $tooltips["gquiz_shuffle_fields"]   = "<h6>" . __("Shuffle Quiz Fields", "gravityformsquiz") . "</h6>" . __("Display the quiz fields in a random order. This doesn't affect the position of the other fields on the form", "gravityformsquiz");
        $tooltips["gquiz_instant_feedback"] = "<h6>" . __("Instant Feedback", "gravityformsquiz") . "</h6>" . __("Display the correct answers plus explanations immediately after selecting an answer. Once an answer has been selected it can't be changed unless the form is reloaded. This setting only applies to radio button quiz fields and it is intended for training applications and trivial quizzes. It should not be considered a secure option for testing.", "gravityformsquiz");


        $tooltips["gquiz_pass_confirmation"]   = "<h6>" . __("Quiz Pass Confirmation", "gravityformsquiz") . "</h6>" . __("Enter the message that should appear when the user reaches the pass percentage. This will appear immediately below the form confirmation message.", "gravityformsquiz");
        $tooltips["gquiz_fail_confirmation"]   = "<h6>" . __("Quiz Fail Confirmation", "gravityformsquiz") . "</h6>" . __("Enter the message that should appear when the user doesn't reach the pass percentage. This will appear immediately below the form confirmation message.", "gravityformsquiz");
        $tooltips["gquiz_letter_confirmation"] = "<h6>" . __("Quiz Confirmation Message", "gravityformsquiz") . "</h6>" . __("Enter the message that should appear immediately below the form confirmation message.", "gravityformsquiz");
        $tooltips["gquiz_grading_none"]        = "<h6>" . __("No Grading", "gravityformsquiz") . "</h6>" . __("Grading will not be used for this form.", "gravityformsquiz");
        $tooltips["gquiz_grading_pass_fail"]   = "<h6>" . __("Enable Pass/Fail Grading", "gravityformsquiz") . "</h6>" . __("Select this option to enable the pass/fail grading system for this form.", "gravityformsquiz");
        $tooltips["gquiz_grading_letter"]      = "<h6>" . __("Enable Letter Grading", "gravityformsquiz") . "</h6>" . __("Select this option to enable the letter grading system for this form.", "gravityformsquiz");
        $tooltips["gquiz_letter_grades"]       = "<h6>" . __("Letter Grades", "gravityformsquiz") . "</h6>" . __("Define the minimum percentage required for each grade.", "gravityformsquiz");
        $tooltips["gquiz_pass_percentage"]     = "<h6>" . __("Pass Percentage", "gravityformsquiz") . "</h6>" . __("Define the minimum percentage required to pass the quiz.", "gravityformsquiz");


        //field settings
        $tooltips["gquiz_question"]                  = "<h6>" . __("Quiz Question", "gravityformsquiz") . "</h6>" . __("Enter the question you would like to ask the user. The user can then answer the question by selecting from the available choices.", "gravityformsquiz");
        $tooltips["gquiz_field_type"]                = "<h6>" . __("Quiz Type", "gravityformsquiz") . "</h6>" . __("Select the field type you'd like to use for the quiz. Choose radio buttons or drop down if question only has one correct answer. Choose checkboxes if your question requires more than one correct choice.", "gravityformsquiz");
        $tooltips["gquiz_randomize_quiz_choices"]    = "<h6>" . __("Randomize Quiz Answers", "gravityformsquiz") . "</h6>" . __("Check the box to randomize the order in which the answers are displayed to the user. This setting affects only the quiz front-end. It will not affect the order of the results.", "gravityformsquiz");
        $tooltips["gquiz_enable_answer_explanation"] = "<h6>" . __("Enable Answer Explanation", "gravityformsquiz") . "</h6>" . __("Activate this option to display an explanation of the answer along with the quiz results.", "gravityformsquiz");
        $tooltips["gquiz_answer_explanation"]        = "<h6>" . __("Quiz Answer Explanation", "gravityformsquiz") . "</h6>" . __("Enter the explanation for the correct answer and/or incorrect answers. This text will appear below the results for this field.", "gravityformsquiz");
        $tooltips["gquiz_field_choices"]             = "<h6>" . __("Quiz Answers", "gravityformsquiz") . "</h6>" . __("Enter the answers for the quiz question. You can mark each choice as correct by using the radio/checkbox fields on the right.", "gravityformsquiz");

        return $tooltips;
    }

    public static function quiz_editor_script() {

        if (class_exists('GFFormSettings')) //1.7
            return;

        ?>
    <script type='text/javascript'>
        //add five new settings to the quiz field type
        fieldSettings["quiz"] = ".gquiz-setting-field-type, .gquiz-setting-question, .gquiz-setting-choices, .gquiz-setting-show-answer-explanation,  .gquiz-setting-randomize-quiz-choices";

        jQuery(document).ready(function () {

            jQuery(document).on("blur", 'input.gquiz-grade-value', (function () {
                var percent = jQuery(this).val();
                if (percent < 0 || isNaN(percent)) {
                    jQuery(this).val(0);
                } else if (percent > 100) {
                    jQuery(this).val(100);
                }
            })
            );
            jQuery(document).on("keypress", 'input.gquiz-grade-value', (function (event) {
                if (event.which == 27) {
                    this.blur();
                    return false;
                }
                if (event.which === 0 || event.which === 8)
                    return true;
                if (event.which < 48 || event.which > 57) {
                    event.preventDefault();
                }

            })

            );

            //enble sorting on the grades table
            jQuery('#gquiz-grades').sortable({
                axis  :'y',
                handle:'.gquiz-grade-handle',
                update:function (event, ui) {
                    var fromIndex = ui.item.data("index");
                    var toIndex = ui.item.index();
                    guiz_move_grade(fromIndex, toIndex);
                }
            });

            //enble sorting on the choices/answers
            jQuery('#gquiz-field-choices').sortable({
                axis  :'y',
                handle:'.field-choice-handle',
                update:function (event, ui) {
                    var fromIndex = ui.item.data("index");
                    var toIndex = ui.item.index();
                    MoveFieldChoice(fromIndex, toIndex);
                }
            })

        });

    </script>

    <?php
    }

    public static function quiz_field_settings($position, $form_id) {

        //create settings on position 25 (right after Field Label)
        if ($position == 25) {
            ?>

        <li class="gquiz-setting-question field_setting">
            <label for="gquiz-question">
                <?php _e("Quiz Question", "gravityformsquiz"); ?>
                <?php gform_tooltip("gquiz_question"); ?>
            </label>
            <textarea id="gquiz-question" class="fieldwidth-3 fieldheight-2" onkeyup="SetFieldLabel(this.value)"
                      size="35"></textarea>

        </li>

        <li class="gquiz-setting-field-type field_setting">
            <label for="gquiz-field-type">
                <?php _e("Quiz Field Type", "gravityformsquiz"); ?>
                <?php gform_tooltip("gquiz_field_type"); ?>
            </label>
            <select id="gquiz-field-type"
                    onchange="if(jQuery(this).val() == '') return; jQuery('#field_settings').slideUp(function(){StartChangeQuizType(jQuery('#gquiz-field-type').val());});">
                <option value="select"><?php _e("Drop Down", "gravityformsquiz"); ?></option>
                <option value="radio"><?php _e("Radio Buttons", "gravityformsquiz"); ?></option>
                <option value="checkbox"><?php _e("Checkboxes", "gravityformsquiz"); ?></option>

            </select>

        </li>
        <li class="gquiz-setting-choices field_setting">

            <?php _e("Quiz Answers", "gravityformsquiz"); ?> <?php gform_tooltip("gquiz_field_choices") ?><br/>

            <div id="gquiz_gfield_settings_choices_container">
                <ul id="gquiz-field-choices"></ul>
            </div>

            <?php $window_title = __("Bulk Add / Predefined Choices", "gravityformsquiz"); ?>
            <input type='button' value='<?php echo esc_attr($window_title) ?>'
                   onclick="tb_show('<?php echo esc_js($window_title) ?>', '#TB_inline?height=500&amp;width=600&amp;inlineId=gfield_bulk_add', '');"
                   class="button"/>

        </li>

        <?php
        } elseif ($position == 1368) {
            //right after the other_choice_setting
            ?>
        <li class="gquiz-setting-randomize-quiz-choices field_setting">

            <input type="checkbox" id="gquiz-randomize-quiz-choices"
                   onclick="var value = jQuery(this).is(':checked'); SetFieldProperty('gquizEnableRandomizeQuizChoices', value);"/>
            <label for="gquiz-randomize-quiz-choices" class="inline">
                <?php _e('Randomize order of choices', "gravityformsquiz"); ?>
                <?php gform_tooltip("gquiz_randomize_quiz_choices") ?>
            </label>

        </li>
        <li class="gquiz-setting-show-answer-explanation field_setting">

            <input type="checkbox" id="gquiz-show-answer-explanation"
                   onclick="var value = jQuery(this).is(':checked'); SetFieldProperty('gquizShowAnswerExplanation', value); gquiz_toggle_answer_explanation(value);"/>
            <label for="gquiz-show-answer-explanation" class="inline">
                <?php _e('Enable answer explanation', "gravityformsquiz"); ?>
                <?php gform_tooltip("gquiz_enable_answer_explanation") ?>
            </label>

        </li>
        <li class="gquiz-setting-answer-explanation field_setting">
            <label for="gquiz-answer-explanation">
                <?php _e("Quiz answer explanation", "gravityformsquiz"); ?>
                <?php gform_tooltip("gquiz_answer_explanation"); ?>
            </label>
            <textarea id="gquiz-answer-explanation" class="fieldwidth-3 fieldheight-2" size="35"
                      onkeyup="SetFieldProperty('gquizAnswerExplanation',this.value)"></textarea>

        </li>

        <?php
        }
    }


    //--------------  Helper functions  ---------------------------------------------------

    //Returns the url of the plugin's root folder
    public static function get_base_url() {
        return plugins_url(null, __FILE__);
    }

    //Returns the physical path of the plugin's root folder
    public static function get_base_path() {
        $folder = basename(dirname(__FILE__));

        return WP_PLUGIN_DIR . "/" . $folder;
    }

    private static function is_gravityforms_installed() {
        return class_exists("RGForms");
    }

    private static function is_gravityforms_supported() {
        if (class_exists("GFCommon")) {
            $is_correct_version = version_compare(GFCommon::$version, self::$min_gravityforms_version, ">=");

            return $is_correct_version;
        } else {
            return false;
        }
    }


    //--------------   Automatic upgrade ---------------------------------------------------

    //Integration with ManageWP
    public static function premium_update_push($premium_update) {

        if (!function_exists('get_plugin_data'))
            include_once(ABSPATH . 'wp-admin/includes/plugin.php');

        $update = GFCommon::get_version_info();
        if ($update["is_valid_key"] == true && version_compare(self::$version, $update["version"], '<')) {
            $plugin_data                = get_plugin_data(__FILE__);
            $plugin_data['type']        = 'plugin';
            $plugin_data['slug']        = self::$path;
            $plugin_data['new_version'] = isset($update['version']) ? $update['version'] : false;
            $premium_update[]           = $plugin_data;
        }

        return $premium_update;
    }

    //Integration with ManageWP
    public static function premium_update($premium_update) {

        if (!function_exists('get_plugin_data'))
            include_once(ABSPATH . 'wp-admin/includes/plugin.php');

        $update = GFCommon::get_version_info();
        if ($update["is_valid_key"] == true && version_compare(self::$version, $update["version"], '<')) {
            $plugin_data         = get_plugin_data(__FILE__);
            $plugin_data['slug'] = self::$path;
            $plugin_data['type'] = 'plugin';
            $plugin_data['url']  = isset($update["url"]) ? $update["url"] : false; // OR provide your own callback function for managing the update

            array_push($premium_update, $plugin_data);
        }

        return $premium_update;
    }

    public static function flush_version_info() {
        require_once("plugin-upgrade.php");
        RGQuizUpgrade::set_version_info(false);
    }

    public static function plugin_row() {
        require_once("plugin-upgrade.php");

        if (!self::is_gravityforms_supported()) {
            $message = sprintf(__("Gravity Forms " . self::$min_gravityforms_version . " is required. Activate it now or %spurchase it today!%s", "gravityformsquiz"), "<a href='http://www.gravityforms.com'>", "</a>");
            RGQuizUpgrade::display_plugin_message($message, true);
        } else {
            $version_info = RGQuizUpgrade::get_version_info(self::$slug, self::get_key(), self::$version);

            if (!$version_info["is_valid_key"]) {
                $new_version = version_compare(self::$version, $version_info["version"], '<') ? __('There is a new version of Gravity Forms Quiz Add-On available.', 'gravityformsquiz') . ' <a class="thickbox" title="Gravity Forms Quiz Add-On" href="plugin-install.php?tab=plugin-information&plugin=' . self::$slug . '&TB_iframe=true&width=640&height=808">' . sprintf(__('View version %s Details', 'gravityformsquiz'), $version_info["version"]) . '</a>. ' : '';
                $message     = $new_version . sprintf(__('%sRegister%s your copy of Gravity Forms to receive access to automatic upgrades and support. Need a license key? %sPurchase one now%s.', 'gravityformsquiz'), '<a href="admin.php?page=gf_settings">', '</a>', '<a href="http://www.gravityforms.com">', '</a>') . '</div></td>';
                RGQuizUpgrade::display_plugin_message($message);
            }
        }
    }

    //Displays current version details on plugins page
    public static function display_changelog() {
        if ($_REQUEST["plugin"] != self::$slug)
            return;

        //loading upgrade lib
        require_once("plugin-upgrade.php");

        RGQuizUpgrade::display_changelog(self::$slug, self::get_key(), self::$version);
    }

    public static function check_update($update_plugins_option) {
        require_once("plugin-upgrade.php");

        return RGQuizUpgrade::check_update(self::$path, self::$slug, self::$url, self::$slug, self::get_key(), self::$version, $update_plugins_option);
    }

    private static function get_key() {
        if (self::is_gravityforms_supported())
            return GFCommon::get_key();
        else
            return "";
    }

    //---------------------------------------------------------------------------------------


} // end class


