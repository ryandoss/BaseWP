<?php
/*
Plugin Name: Gravity Forms Polls Add-on
Description: Polls Add-on for Gravity Forms
Version: 1.2
Author: Rocketgenius
Author URI: http://www.rocketgenius.com

------------------------------------------------------------------------
Copyright 2012 Rocketgenius Inc.

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


require_once(GFPolls::get_base_path() . "/pollwidget.php");
register_activation_hook( __FILE__, array("GFPolls", "add_permissions"));

add_action('init',  array('GFPolls', 'init'));

//need to be added before init
add_action("gform_after_submission", array('GFPolls', 'after_submission'), 10, 2);
add_filter('gform_export_field_value', array('GFPolls', 'display_entries_field_value'), 10, 3);

//------------------------------------------
class GFPolls {

    private static $path = "gravityformspolls/polls.php";
    private static $url = "http://www.gravityforms.com";
    private static $slug = "gravityformspolls";
    private static $version = "1.2";
    private static $min_gravityforms_version = "1.6.4.5.12";

	static $gpoll_add_scripts;

    //Plugin starting point. Will load appropriate files
    public static function init(){

        if(RG_CURRENT_PAGE == "plugins.php"){
            //loading translations
            load_plugin_textdomain('gravityformspolls', FALSE, '/gravityformspolls/languages' );

			add_action('after_plugin_row_' . self::$path, array('GFPolls', 'plugin_row') );
        }

        if(!self::is_gravityforms_supported()){
           return;
        }

        //registering scripts
        self::register_scripts();

        if(is_admin()){
            //loading translations
            load_plugin_textdomain('gravityformspolls', FALSE, '/gravityformspolls/languages' );

            //form editor
            add_filter('gform_add_field_buttons', array('GFPolls', 'add_poll_field'));
            add_action('gform_field_standard_settings', array('GFPolls', 'poll_field_settings'), 10, 2);
            add_action('gform_editor_js', array('GFPolls', 'poll_editor_script'));
            add_filter('gform_tooltips', array('GFPolls', 'add_poll_field_tooltips'));
            add_action("gform_after_save_form", array('GFPolls', 'after_save_form'), 10, 2);
            add_filter("gform_field_type_title", array('GFPolls', 'assign_title'), 10, 2);
            
            //scripts
            add_action( 'admin_enqueue_scripts', array('GFPolls', 'enqueue_widget_style') );
            add_action( 'admin_print_styles', array('GFPolls', 'enqueue_admin_styles') );
            add_action( 'admin_print_scripts', array('GFPolls', 'enqueue_admin_scripts') );

            add_filter('gform_noconflict_scripts', array('GFPolls', 'register_noconflict_scripts'));
            add_filter('gform_noconflict_styles', array('GFPolls', 'register_noconflict_styles'));

            //display poll results on entry detail & entry list
            add_filter('gform_entry_field_value', array('GFPolls', 'display_poll_on_entry_detail'), 10, 4);
            add_filter('gform_entries_field_value', array('GFPolls', 'display_entries_field_value'), 10, 3);
             
            //merge tags
            add_filter('gform_custom_merge_tags', array('GFPolls', 'custom_merge_tags'), 10, 4);

            //Automatic upgrade functionality
            add_filter("transient_update_plugins", array('GFPolls', 'check_update'));
            add_filter("site_transient_update_plugins", array('GFPolls', 'check_update'));
            add_action('install_plugins_pre_plugin-information', array('GFPolls', 'display_changelog'));

            //update the cache
            add_action("gform_after_update_entry", array('GFPolls', 'entry_updated'), 10, 2);
            add_action("gform_update_status", array('GFPolls', 'update_entry_status'), 10, 2);
        }
        else{
            // ManageWP premium update filters
            add_filter( 'mwp_premium_update_notification', array('GFPolls', 'premium_update_push') );
            add_filter( 'mwp_premium_perform_update', array('GFPolls', 'premium_update') );

            //enqueue styles for the preview page
            add_filter("gform_preview_styles", array('GFPolls', 'enqueue_preview_style'), 10, 2);
            add_action('gform_enqueue_scripts', array('GFPolls', 'enqueue_scripts'));
            add_action('wp_footer', array('GFPolls', 'print_scripts'));

            //maybe display results on confirmation
            add_filter("gform_confirmation", array('GFPolls', 'display_confirmation'), 10, 4);

            //shortcodes
            add_filter('gform_shortcode_polls', array('GFPolls', 'poll_shortcode'), 10, 3);
            add_shortcode( 'gfpolls_total', array('GFPolls', 'poll_total_shortcode') );

            //merge tags
            add_filter('gform_merge_tag_filter',  array('GFPolls', 'merge_tag_filter'), 10, 5);
            add_filter('gform_replace_merge_tags', array('GFPolls', 'render_merge_tag'), 10, 7);

            add_filter('gform_entry_field_value', array('GFPolls', 'display_poll_on_entry_print'), 10, 4);

            //shuffle choices if configured
            add_filter("gform_field_content", array('GFPolls', 'maybe_shuffle_choices'), 10, 5);

			//update the cache
			add_action('gform_entry_created', array('GFPolls', 'entry_created'),10, 2);
            
            add_action("gform_validation", array('GFPolls', 'form_validation'));


        }
		
		
        //add a special class to poll fields so we can identify them later
        add_action('gform_field_css_class', array('GFPolls', 'add_custom_class'), 10, 3);
        
        if(in_array(RG_CURRENT_PAGE, array("admin.php", "admin-ajax.php"))){
            //ajax
            add_action('wp_ajax_gpoll_ajax', array('GFPolls', 'gpoll_ajax'));
            add_action('wp_ajax_nopriv_gpoll_ajax', array('GFPolls', 'gpoll_ajax'));
            
        }

    }
    
    public static function gpoll_ajax() {
        $output = array();
        $form_id = rgpost('formId');
        $cookie_duration =  rgpost('cookieDuration');
        $display_results = rgpost('displayResults');
        $confirmation = rgpost('confirmation');
        $numbers = rgpost('numbers');
        $numbers =  $numbers == "1" ? true : false;
        $percentages = rgpost('percentages');
        $percentages =  $percentages == "1" ? true : false;
        $counts = rgpost('counts');
        $counts =  $counts == "1" ? true : false;
        $view_results = rgpost('viewResults');
        $view_results =  $view_results == "1" ? true : false;
        $style = rgpost('style');
        $has_voted = isset ( $_COOKIE['gpoll_form_' . $form_id ] ) ;
        if ( $view_results || ( false === empty($cookie_duration) && $has_voted ) ){
            
            if ( $display_results ) {
                 $output["canVote"] = false;
                $results = self::gpoll_get_results( $form_id, "0", $style, $numbers, $percentages, $counts );
                $results_summary = $results["summary"];
                $output["resultsUI"] = $results_summary;
            } else {
                if ( $confirmation )
                   $output["resultsUI"] = GFFormDisplay::handle_confirmation($form, null);
            else
                $output["resultsUI"] = "";
            }

        } else {
            $output["canVote"] = true;
            $output["resultsUI"] = "";
        }

        echo json_encode($output);
        die();

    }
    

	function form_validation($validation_result){
			$form = $validation_result["form"];

			if (isset($_POST["gform_field_values"])) {

				$field_values = wp_parse_args($_POST["gform_field_values"]);
				if (isset($field_values["gpoll_enabled"]) && ( $field_values["gpoll_enabled"] == "1"  ) ){
					$formid = $form["id"];
					
					$gpoll_cookie = $field_values["gpoll_cookie"];
					
					if ( false === empty($gpoll_cookie) && isset ( $_COOKIE['gpoll_form_' . $formid ] ) ){
	
						// set the form validation to false
						$validation_result["is_valid"] = false;

						//finding Field with ID of 1 and marking it as failed validation
						foreach($form["fields"] as &$field){
							$field["failed_validation"] = true;
							$field["validation_message"] = __("Repeat voting is not allowed", "gravityformspolls");
						}
						$validation_result["form"] = $form;
					}

				}
			}

			return $validation_result;
	}
	
	public function assign_title($title, $field_type){
		if($field_type == "poll")
			return __("Poll", "gravityformspolls");

        return $title;
	}

	public function display_entries_field_value($value, $form_id, $field_id) {
		$new_value = $value;
		$form_meta = RGFormsModel::get_form_meta($form_id);
		$form_meta_field = RGFormsModel::get_field($form_meta,$field_id);
		if ( $form_meta_field["type"] == "poll" ) {
			if ( $form_meta_field["inputType"] == "radio" || $form_meta_field["inputType"] == "select") {
                $new_value = GFCommon::selection_display($value, $form_meta_field, $currency="", $use_text=true);
            } elseif ( $form_meta_field["inputType"] == "checkbox" ) {
                $ary = explode(", ", $value);
                $new_values = array();
                foreach ($ary as $response){
                    $new_values[] = GFCommon::selection_display($response, $form_meta_field, $currency="", $use_text=true);
                }
                $new_value = implode(', ', $new_values);
            }
		}
		return $new_value ;
	}

	public function entry_created($entry, $form) {

		//update cache
		$formid = $form["id"];
		self::update_cache($formid);

	}

	public function after_save_form($form, $is_new){
		//update cache
		$formid = $form["id"];
		self::update_cache($formid);

	}

	//if the user has selected enableRandomizeChoices then shuffle the choices before displaying them
	public function maybe_shuffle_choices($content, $field, $value, $lead_id, $form_id){

	if ( $field["type"]=="poll" && rgar($field, 'enableRandomizeChoices') ) {

		//pass the HTML for the choices through DOMdocument to make sure we get the complete li node
		$dom = new DOMDocument();
		//allow malformed HTML inside the choice label
		$previous_value = libxml_use_internal_errors(TRUE);
		$dom->loadHTML($content);
		libxml_clear_errors();
		libxml_use_internal_errors($previous_value);

		$content = $dom->saveXML($dom->documentElement);

		//pick out the LI elements
		$li_nodes = $dom->getElementsByTagName('ul')->item(0)->childNodes;

		//cycle through the LI elements and swap them around randomly
		$temp_str1 = "gpoll_shuffle_placeholder1";
		$temp_str2 = "gpoll_shuffle_placeholder2";
		for ($i=$li_nodes->length-1; $i>=0; $i--) {
			$n=rand(0, $i);
			if ( $i <> $n ) {
				$i_str = $dom->saveXML($li_nodes->item($i));
				$n_str = $dom->saveXML($li_nodes->item($n));
				$content = str_replace($i_str, $temp_str1, $content);
				$content = str_replace($n_str, $temp_str2, $content);
				$content = str_replace($temp_str2, $i_str, $content);
				$content = str_replace($temp_str1, $n_str, $content);
			}
		}
		//snip off the tags that DOMdocument adds
		$content = str_replace("<html><body>","", $content);
		$content = str_replace("</body></html>","", $content);

	}
	return $content;
	}//end function maybe_shuffle_choices

	public function after_submission($entry, $form){

		if (isset($_POST["gform_field_values"])) {

			$field_values = wp_parse_args($_POST["gform_field_values"]);
			if (isset($field_values["gpoll_enabled"]) && ( $field_values["gpoll_enabled"] == "1"  )) {

				if (isset($field_values["gpoll_cookie"])) {
					$formid = $_POST["gform_submit"];
					$form = RGFormsModel::get_form_meta($formid);
					$lead_id = $entry["id"];
					$cookie = $field_values["gpoll_cookie"];
					$cookie_expiration_date = date(strtotime($cookie));
					$currentDate = strtotime("now");
					if ( $cookie_expiration_date > $currentDate ) {
						setcookie("gpoll_form_" .  $formid ,$lead_id, $cookie_expiration_date, COOKIEPATH, COOKIE_DOMAIN);
					}



				}

			}
		}
	}

	public function register_noconflict_scripts($scripts){

		//registering script with Gravity Forms so that it gets enqueued when running on no-conflict mode
		$scripts[] = "gpoll_js";

		return $scripts;
	}

	public function register_noconflict_styles($styles){

		//registering styles with Gravity Forms so that it gets enqueued when running on no-conflict mode
		$styles[] = "gpoll_css";
		$styles[] = "gpoll_form_editor_css";

		return $styles;
	}

	public function update_entry_status($lead_id ) {
		//update cache
		$lead = RGFormsModel::get_lead($lead_id);
		$formid =$lead["form_id"];
		self::update_cache($formid);

	}

	public function entry_updated($form, $leadid){

		//update cache
		$formid = $form["id"];
		self::update_cache($formid);
	}

	public static function update_cache($formid) {
		$gpoll_data = self::gpoll_get_data($formid);
		$cache_duration = 0;
		set_transient( 'gpoll_data_' . $formid, $gpoll_data, $cache_duration );
		return $gpoll_data;
	}

	public function merge_tag_filter($value, $merge_tag, $options, $field, $raw_value){

        if($merge_tag == "all_fields" && $field["type"] == "poll" && is_array($field["choices"]))
        {
            if($field["inputType"] == "checkbox"){
                //parse checkbox string (from $value variable) and replace values with text
                foreach($raw_value as $key => $val){
                    $text = RGFormsModel::get_choice_text($field, $val);
                    $value = str_replace($val, $text, $value);
                }
            }
            else{
                //replacing value with text
                $value = RGFormsModel::get_choice_text($field, $value);
            }
        }
        return $value;
    }

	public function render_merge_tag($text, $form, $entry, $url_encode, $esc_html, $nl2br, $format) {
		
        $quiz_fields = GFCommon::get_fields_by_type( $form, array( 'poll' ) );
        if ( empty ( $quiz_fields ) )
            return $text;
        
		$enqueue_scripts = false;
		$formid = $form["id"];


		preg_match_all("/{all_poll_results(:(.*?))?}/", $text, $matches, PREG_SET_ORDER);
		foreach($matches as $match){

			$full_tag = $match[0];
            $options_string = isset( $match[2] ) ? $match[2] : "";
			$options = shortcode_parse_atts($options_string);

			extract(shortcode_atts(array(
				'field' => 0,
				'style' => "green",
				'numbers' => "false",
				'percentages' => "true",
				'counts' => "true"
			), $options));
			$numbers = strtolower($numbers) == "false" ? false : true;
			$percentages = strtolower($percentages) == "false" ? false : true;
			$counts = strtolower($counts) == "false" ? false : true;
			$results = GFPolls::gpoll_get_results($formid,$field,$style,$numbers,$percentages,$counts,$entry);
			$results_summary = $results["summary"];
			$new_value = $results_summary ;

			$text = str_replace($full_tag, $new_value, $text);

			$enqueue_scripts = true;
		}

		preg_match_all("/\{gpoll:(.*?)\}/", $text, $matches, PREG_SET_ORDER);

        foreach($matches as $match){
            $full_tag = $match[0];

            $options_string = isset( $match[1] ) ? $match[1] : "";
			$options = shortcode_parse_atts($options_string);

			extract(shortcode_atts(array(
				'field' => 0,
				'style' => "green",
				'numbers' => "false",
				'percentages' => "true",
				'counts' => "true"
			), $options));

			$numbers = strtolower($numbers) == "false" ? false : true;
			$percentages = strtolower($percentages) == "false" ? false : true;
			$counts = strtolower($counts) == "false" ? false : true;
			$results = GFPolls::gpoll_get_results($formid,$field,$style,$numbers,$percentages,$counts,$entry);
			$results_summary = $results["summary"];
			$new_value = $results_summary;

			$text = str_replace($full_tag, $new_value, $text);

			$enqueue_scripts = true;

        }

		if ( $enqueue_scripts ) {
			wp_enqueue_script('jquery');
			wp_enqueue_style('gpoll_css');
			wp_enqueue_style('gpoll_form_editor_css');
			wp_enqueue_script('gpoll_js');
			self::localize_scripts();
		}

		return  $text;

	}

	public function custom_merge_tags($merge_tags, $form_id, $fields, $element_id) {
		$contains_poll_field = false;
		foreach ( $fields as $field ) {
			if ( $field["type"] == "poll" ) {
				$contains_poll_field = true;
				$field_id= $field["id"];
				$field_label = $field['label'];
				$merge_tags[] = array('label' => $field_label . ': Poll Results', 'tag' => "{gpoll:field={$field_id}}");
			}
		}
		if ( $contains_poll_field )
			$merge_tags[] = array('label' => 'All Poll Results', 'tag' => '{all_poll_results}');

		return $merge_tags;
	}

	public function add_poll_field_tooltips($tooltips){
       $tooltips["form_poll_question"] = "<h6>Poll Question</h6>Enter the question you would like to ask the user. The user can then answer the question by selecting from the available choices.";
	   $tooltips["form_poll_field_type"] = "<h6>Poll Type</h6>Select the field type you'd like to use for the poll.";
	   $tooltips["form_field_randomize_choices"] = "<h6>Randomize Choices</h6>Check the box to randomize the order in which the choices are displayed to the user. This setting affects only voting - it will not affect the order of the results.";
	   return $tooltips;
	}

	public function poll_editor_script(){

		?>
		<script type='text/javascript'>

			fieldSettings["poll"] = ".poll_field_type_setting, .poll_question_setting, .randomize_choices_setting";

			function StartChangePollType(type){
				field = GetSelectedField();

				field["poll_field_type"] = type;

				return StartChangeInputType(type, field);
			}

			function SetDefaultValues_poll(field) {

				field.poll_field_type = "radio";
				field.label = "Untitled Poll Field";
				field.inputType = "radio";
				field.inputs = null;
				field.enableChoiceValue = true;
				field.enablePrice = false;
				field.enableRandomizeChoices = false;
				if(!field.choices){
					field.choices = new Array(new Choice("<?php _e("First Choice", "gravityformspolls"); ?>",GeneratePollChoiceValue(field)), new Choice("<?php _e("Second Choice", "gravityformspolls"); ?>", GeneratePollChoiceValue(field)), new Choice("<?php _e("Third Choice", "gravityformspolls"); ?>", GeneratePollChoiceValue(field)));
				}
				return field;
			}

			function GeneratePollChoiceValue(field) {
				return 'gpoll' + field.id + 'xxxxxxxx'.replace(/[xy]/g, function(c) {
                    var r = Math.random()*16|0,v=c=='x'?r:r&0x3|0x8;
                    return v.toString(16);
                    });

			}

			function gform_new_choice_poll ( field, choice ) {
                if(field.type == "poll")
                    choice["value"] = GeneratePollChoiceValue(field);

                return choice;
            }

			//binding to the load field settings event to initialize
			jQuery(document).bind("gform_load_field_settings", function(event, field, form){
				jQuery('#field_randomize_choices').attr('checked', field.enableRandomizeChoices ? true : false);
				jQuery("#poll_field_type").val(field["poll_field_type"]);
                jQuery("#poll_question").val(field["label"]);

				if( field.type == 'poll') {

                    jQuery('li.label_setting').hide();

					if(has_entry(field.id)) {
						jQuery("#poll_field_type").attr("disabled", true);
					} else {
						jQuery("#poll_field_type").removeAttr("disabled");
					}

				}
			});

		</script>


		<?php
	}
    
    
    

	public function poll_field_settings($position, $form_id){

		//create settings on position 25 (right after Field Label)
		if($position == 25){
			?>

            <li class="poll_question_setting field_setting">
                <label for="poll_question">
                    <?php _e("Poll Question", "gravityformspolls"); ?>
                    <?php gform_tooltip("form_poll_question"); ?>
                </label>
                <input type="text" id="poll_question" class="fieldwidth-3" onkeyup="SetFieldLabel(this.value)" size="35" />
            </li>

			<li class="poll_field_type_setting field_setting">
				<label for="poll_field_type">
					<?php _e("Poll Type", "gravityformspolls"); ?>
					<?php gform_tooltip("form_poll_field_type"); ?>
				</label>
				<select id="poll_field_type" onchange="if(jQuery(this).val() == '') return; jQuery('#field_settings').slideUp(function(){StartChangePollType(jQuery('#poll_field_type').val());});">
					<option value="select"><?php _e("Drop Down", "gravityformspolls"); ?></option>
					<option value="radio"><?php _e("Radio Buttons", "gravityformspolls"); ?></option>
					<option value="checkbox"><?php _e("Checkboxes", "gravityformspolls"); ?></option>

				</select>

			</li>

			<?php
		} elseif ( $position == 1368 ) {
				//right after the other_choice_setting	 ?>
			<li class="randomize_choices_setting field_setting">

				<input type="checkbox" id="field_randomize_choices" onclick="var value = jQuery(this).is(':checked'); SetFieldProperty('enableRandomizeChoices', value); UpdateFieldChoices(GetInputType(field));" />
				<label for="field_randomize_choices" class="inline">
					<?php _e('Randomize order of choices', "gravityformspolls"); ?>
					<?php gform_tooltip("form_field_randomize_choices") ?>
				</label>

			</li>
			<?php
		}
	}

	public function add_poll_field($field_groups){

		foreach($field_groups as &$group){
			if($group["name"] == "advanced_fields"){
				$group["fields"][] = array("class"=>"button", "value" => __("Poll", "gravityformspolls"), "onclick" => "StartAddField('poll');");
				break;
			}
		}
		return $field_groups;
	}

	public function display_poll_on_entry_detail($value, $field, $lead, $form){
		$new_value = "";

		if ($field["type"] == 'poll') {
			$new_value .= '<div class="gpoll_entry">';
			$results = GFPolls::gpoll_get_results($form["id"],$field["id"],"green",false,true,true, $lead);
			$results_summary = $results["summary"];
			$new_value .= $results_summary;
			$new_value .= '</div>';
			GFPolls::$gpoll_add_scripts = true;

			//if orginal response is not in results display below
			// TODO
			$selected_values = array();
			$selected_values = self::get_selected_values($form["id"], $field["id"], $lead);
			$possible_choices = array();
			$possible_choices = self::get_possible_choices($form["id"], $field["id"]);
			foreach ( $selected_values as $selected_value ) {
				if (! in_array($selected_value, $possible_choices) ) {
					$new_value = $new_value . __("<h2>Original Response</h2>", "gravityformspolls") . $value;
					break;
				}
			}


		} else {
			$new_value = $value;
		}

		return $new_value;
	}

    public function display_poll_on_entry_print($value, $field, $lead, $form){

        $new_value = $value;

        if($field["type"] == "poll" && is_array($field["choices"]))
        {
            if($field["inputType"] == "checkbox"){

                foreach ( $field["choices"] as $choice ) {

                    $val = $choice["value"];
                    $text = RGFormsModel::get_choice_text($field, $val);
                    $new_value = str_replace($val, $text, $new_value);
                }

            }
            else{
                //replacing value with text
                $new_value = RGFormsModel::get_choice_text($field, $value);
            }
        }

        return $new_value;
    }

	//adds scripts to entries detail
	public function enqueue_admin_scripts() {
	    if(rgget("page") == "gf_entries")
		    wp_enqueue_script('gpoll_js');

        self::localize_scripts();
	}

	//adds styles to entries detail
	public function enqueue_admin_styles() {
		if(rgget("page") == "gf_entries")
			wp_enqueue_style('gpoll_css');
		else if (rgget("page") == "gf_edit_forms" || rgget("page") == "gf_new_form" )
			wp_enqueue_style('gpoll_form_editor_css');
	}

    public function print_scripts() {

		if ( self::$gpoll_add_scripts !== true) return;
		self::localize_scripts();
		wp_print_scripts('jquery');
		wp_print_scripts('gpoll_js');

		wp_print_styles('gpoll_css');

	} // end function print_scripts

	public function register_scripts(){
		wp_register_script('gpoll_js', plugins_url( 'js/gpoll.js', __FILE__ ));
		wp_register_style('gpoll_css', plugins_url( 'css/gpoll.css', __FILE__ ));
		wp_register_style('gpoll_form_editor_css', plugins_url( 'css/gpoll_form_editor.css', __FILE__ ));

	} // end function register_scripts

	// adds gpoll_field class to poll fields
	public function add_custom_class($classes, $field, $form){
		if($field["type"] == "poll"){
			$classes .= " gpoll_field";
		}
		return $classes;
	}

	public function enqueue_widget_style($hook) {

		if('widgets.php' == $hook ){

			wp_enqueue_style( 'gpoll_widget_css', plugins_url('css/gpoll_widget.css', __FILE__) );

		}


	} // end function gpoll_admin_enqueue

	public static function enqueue_preview_style($styles, $form){
		return array("gpoll_css");
	}

	public static function add_permissions(){
        global $wp_roles;
        $wp_roles->add_cap("administrator", "gpoll");
    }

    private static function is_gravityforms_installed(){
        return class_exists("RGForms");
    }

    private static function is_gravityforms_supported(){
        if(class_exists("GFCommon")){
            $is_correct_version = version_compare(GFCommon::$version, self::$min_gravityforms_version, ">=");
            return $is_correct_version;
        }
        else{
            return false;
        }
    }

    protected static function has_access($required_permission){
        $has_members_plugin = function_exists('members_get_capabilities');
        $has_access = $has_members_plugin ? current_user_can($required_permission) : current_user_can("level_7");
        if($has_access)
            return $has_members_plugin ? $required_permission : "level_7";
        else
            return false;
    }

    //Returns the url of the plugin's root folder
    public function get_base_url(){
        return plugins_url(null, __FILE__);
    }

    //Returns the physical path of the plugin's root folder
    public function get_base_path(){
        $folder = basename(dirname(__FILE__));
        return WP_PLUGIN_DIR . "/" . $folder;
    }

	//*******************************************************

	

	/*
	Cycles through all entries, counts responses and returns an associative array with the data for each field. It's then optionally cached later according to the user settings.
	*/
	public static function gpoll_get_data($form_id){

		$form_meta = RGFormsModel::get_form_meta($form_id);
		$totals = RGFormsModel::get_form_counts($form_id);
		$total = $totals["total"];

		$sort_field_number=0;
		$sort_direction='DESC';
		$search='';
        $offset=0;
		$page_size=100;
		$star=null;
		$read=null;
		$is_numeric_sort = false;
		$start_date=null;
		$end_date=null;
		$status='active';

		$gpoll_data = array();

		$field_counter = 0;

		//first build list of fields to count and later count the entries
		//it's split up this way to avoid a timeout on large resultsets

		foreach($form_meta["fields"] as $field){

			$fieldid = $field["id"];

			if ( $field["type"] !== "poll" ) {
				continue;
			}

			$gpoll_field_data = array (
				"field_label" => $field["label"],
				"field_id" => $fieldid,
				"type" =>  $field["type"],
				"inputType" =>  $field["inputType"]
				);


			$gpoll_data["fields"][$field_counter]= $gpoll_field_data;
			$gpoll_input_data = array();

			//for checkboxes
			if ($field["inputType"] == "checkbox") {
				$input_counter = 0;
				foreach($field["inputs"] as $input){
					$inputid = str_replace("." , "_" , $input["id"]);
					$gpoll_input_data = array(
						"input_id" => "#choice_{$inputid}",
						"label" => $input["label"]
						);
					$gpoll_data["fields"][$field_counter]["inputs"][$input_counter] = $gpoll_input_data;
					$input_counter += 1;
				}
			}
			else {
				//for radio & dropdowns

				$choice_counter = 0;
				if ( isset( $field["enableOtherChoice"] ) && $field["enableOtherChoice"] === true ) {
					$choice_index = count($field["choices"]);
					$field["choices"][$choice_index]["text"] = __("Other","gravitformspolls");
				}

				foreach ($field["choices"] as $choice) {
					$gpoll_input_data = array(
						"input_id" => "#choice_{$fieldid}_{$choice_counter}",
						"label" => $choice["text"]
						);
					$gpoll_data["fields"][$field_counter]["inputs"][$choice_counter] = $gpoll_input_data;
					$choice_counter += 1;
				}
			}
			$field_counter +=1;

		}

		//done collecting info about the fields we want to count
		//now count the entries

		$i = $offset;
		$entry_count = $total;

		//get leads in groups of $page_size to avoid timeouts
		while($entry_count >= 0){

			$field_counter = 0;
			$entries = RGFormsModel::get_leads($form_id,$sort_field_number,$sort_direction,$search,$i,$page_size,null,null,false,null,null);

			//loop through each field currently on the form and count the entries for each choice
			foreach($form_meta["fields"] as $field){

				$fieldid = $field["id"];

				if (  $field["type"] !== "poll"  ) {
					continue;
				}


				$gpoll_input_data = array();

				//checkboxes store entries differently to radio & dropdowns
				if ($field["inputType"] == "checkbox") {
					//for checkboxes

					//loop through all the choices and count the entries for each choice
					$input_counter = 0;
					foreach($field["inputs"] as $input){

						// running total of entries for each set of entries
						if ( isset ( $gpoll_data["fields"][$field_counter]["inputs"][$input_counter]["total_entries"] ) ) {
							$total_entries = $gpoll_data["fields"][$field_counter]["inputs"][$input_counter]["total_entries"] ;
						}
						else {
							$total_entries = 0;
						}
						$entry_index = 1;

						//loop through all the entries and count the entries for the choice
						foreach($entries as $entry){

							//loop through each item in the lead object and pick out the entries for this field id
							foreach ($entry as $key => $entry_value){

								//checkboxes store the key as [field number].[input index] (e.g. 2.1 or 2.2)
								//so convert to integer to identify all the reponses inside the lead object for this field id
								if (intval($key) == $field["id"]) {

									//compare the user's response with the current choice
									if ($entry_value == $field["choices"][$input_counter]["value"]) {

										//found a reponse for this choice so continue to the next lead
										$total_entries += 1;
										break;
									}

								}

							}

							$entry_index += 1;
						}

						//calculate the ratio of total number of reponses counted to the total number of entries for this form
						$ratio=0;

						if ( $total != 0 ) {
							$ratio = round(($total_entries / $total * 100),0);
						}

						//store the data
						$gpoll_data["fields"][$field_counter]["inputs"][$input_counter]["value"] = $field["choices"][$input_counter]["value"];
						$gpoll_data["fields"][$field_counter]["inputs"][$input_counter]["total_entries"] = $total_entries;
						$gpoll_data["fields"][$field_counter]["inputs"][$input_counter]["ratio"] = $ratio;
						$input_counter += 1;
					}
				} else {
					//for radio & dropdowns

					$choice_counter = 0;

					//if the Enable "other" choice option is selected for this field then add it as a psuedo-value
					if ( isset( $field["enableOtherChoice"] ) && $field["enableOtherChoice"] === true ) {
						$choice_index = count($field["choices"]);
						$field["choices"][$choice_index]["value"] = "gpoll_other";
					}

					//loop through each choice and count the reponses
					foreach ($field["choices"] as $choice) {

						// running total of entries for each set of entries
						if ( isset ( $gpoll_data["fields"][$field_counter]["inputs"][$choice_counter]["total_entries"] ) ) {
							$total_entries = $gpoll_data["fields"][$field_counter]["inputs"][$choice_counter]["total_entries"] ;
						}
						else {
							$total_entries = 0;
						}

						//count responses for "Other"
						if ( $choice["value"] == "gpoll_other" ) {
							$possible_choices = array();
							foreach ($field["choices"] as $possible_choice) {
								array_push($possible_choices, $possible_choice["value"]);
							}

							foreach($entries as $entry){
								$entry_value = RGFormsModel::get_lead_field_value($entry, $field);

								if ( ! empty($entry_value) && ! in_array ($entry_value, $possible_choices ) ) {
									$total_entries += 1;
								}
							}

						} else {

							//count entries
							foreach($entries as $entry){
								$entry_value = RGFormsModel::get_lead_field_value($entry, $field);
								if ( $entry_value === $choice["value"] ) {
									$total_entries += 1;
								}
							}
						}

						//calculate the ratio of total number of reponses counted to the total number of entries for this form
						$ratio=0;
						if ( $total != 0 ) {
							$ratio = round(($total_entries / $total * 100),0);
						}

						//store the data
						$gpoll_data["fields"][$field_counter]["inputs"][$choice_counter]["value"] = $choice["value"];
						$gpoll_data["fields"][$field_counter]["inputs"][$choice_counter]["total_entries"] = $total_entries;
						$gpoll_data["fields"][$field_counter]["inputs"][$choice_counter]["ratio"] = $ratio;
						$choice_counter += 1;
					}
				}
				$field_counter +=1;
			}
			$i += $page_size;
			$entry_count -= $page_size;
		} //end while
		return $gpoll_data;
	} // end function gpoll_get_data

	// returns the results in an array of HTML formated data
	public static function gpoll_get_results( $formid, $display_field = "0" /* zero = all fields */, $style = "green", $numbers = false, $show_percentages = true, $show_counts = true, $lead = array() ) {

		$gpoll_output = array();
		$gpoll_data = array();

		//each bar will receive this HTML formatting
		$bar_html  = "<div class='gpoll_wrapper {$style}'><div class='gpoll_ratio_box'><div class='gpoll_ratio_label'>%s</div></div><div class='gpoll_bar'>";
		$bar_html .= "<span class='gpoll_bar_juice' data-origwidth='%s' style='width: %s%%'><div class='gpoll_bar_count'>%s</div></span></div></div>";



		//if data is cached then pull the data out of the cache

		if ( false === ( $gpoll_data = get_transient( 'gpoll_data_' . $formid ) ) ){

			//cache has timed out so get the data again and cache it again
			$gpoll_data = self::update_cache($formid);
		}


		// build HTML output

		$gpoll_output["summary"] = "<div class='gpoll_container'>";
		$field_counter=0;

		// loop through polls data field by field
		foreach ($gpoll_data["fields"] as $field) {

			$fieldid = $field["field_id"];

			// only build html for the field(s) specified in the parameter. 0 = all fields
			if ( ($display_field != "0") && ($fieldid != $display_field)) {
				continue;
			}


			// build 2 sections: summary and individual fields
			$field_number = $field_counter + 1;
			$gpoll_output["summary"] .= "<div class='gpoll_field'>";
			$gpoll_output["summary"] .= "<div class='gpoll_field_label_container'>";

			// add field numbers if required
			if ( $numbers === true ) {
				$gpoll_output["summary"] .= "<span class='gpoll_field_number'>" .  $field_number . "</span>";
			}
			$gpoll_output["summary"] .=  "<div class='gpoll_field_label'>";
			$gpoll_output["summary"] .= $field["field_label"] . "</div></div>";

			// the individual fields HTML was used in the past but not used now.
			// it was used to display results "inline" with the form (i.e. form input then the bar below)
			// I've left it because it may be useful either to designers or for a future use
			$gpoll_output["fields"][$field_counter]["field_id"]= $field["field_id"];
			$gpoll_output["fields"][$field_counter]["type"]  = $field["type"];

			$selected_values = array();

			// if the lead object is passed then prepare to highlight the selected choices
			if (! empty ($lead) ) {
				$form_meta = RGFormsModel::get_form_meta($formid);
				// collect all the reponses in the lead for this field
				$selected_values = self::get_selected_values( $form_meta, $fieldid, $lead );

				//collect all the choices that are currently possible in the field

				$possible_choices = self::get_possible_choices($form_meta, $fieldid);

				$form_meta_field = RGFormsModel::get_field($form_meta,$fieldid);

				// if the "other" option is selected for this field
				// add the psuedo-value "gpoll_other" if responses are found that are not in the list of possible choices
				if ( isset( $form_meta_field["enableOtherChoice"] ) && $form_meta_field["enableOtherChoice"] === true ) {

					foreach ( $selected_values as $selected_value ) {
						if (! in_array($selected_value, $possible_choices) )
							array_push($selected_values, "gpoll_other");

					}
				}
			}

			// loop through all the inputs in this field (poll data field not form object field) and build the HTML for the bar
			$input_counter = 0;
			foreach($field["inputs"] as $input){

					//highlight the selected value by adding a class to the label
					$selected_class = "";
					if ( in_array($input["value"], $selected_values) ) {
						$selected_class .= " gpoll_value_selected";
					}

					//build the bar and add it to the summary
					$gpoll_output["summary"] .= sprintf("<div class='gpoll_choice_label%s'>%s</div>", $selected_class, $input["label"]);
					$ratio = $input["ratio"];
					$value = $input["value"];
					$count = $show_counts === true ? $input["total_entries"] : '';
					$percentage_label = $show_percentages === true ? $ratio . '%' : '';
					$input_html= sprintf($bar_html, $percentage_label, $ratio, $ratio, $count);
					$gpoll_output["summary"] .= $input_html;

					//add the bar HTML to the fields array ready to output alongside the summary
					$input_data = array(
						"input_id" => $input["input_id"],
						"label" => $input["label"],
						"total_entries" => $input["total_entries"],
						"ratio" => $input["ratio"],
						"bar_html" => $input_html
						);
					$gpoll_output["fields"][$field_counter]["inputs"][$input_counter]=$input_data;


					$input_counter += 1;
			}
			$gpoll_output["summary"] .= "</div>";
			$field_counter +=1;

		}
		$gpoll_output["summary"] .= "</div>";
		return $gpoll_output;

	} //end function gpoll_get_results

	// collect all the reponses in the lead for this field and returns an array
	public static function get_selected_values( $form_meta, $fieldid, $lead ) {



		$selected_values = array();

		//pick out the field we need from the fields collection in the form object
		//and add the selected values to the selected_values array
		if (is_array($form_meta["fields"]) ) {
			foreach ($form_meta["fields"] as $field) {
				if ( $field["id"] == $fieldid ) {
					if ( $field["inputType"] == "checkbox" ) {
						for ($i = 1; $i <= count($field["inputs"]); $i++) {
							$lead_index = 0;
							$lead_index = $fieldid . "." . $i;
							if ( isset($lead[$lead_index]) && ! empty($lead[$lead_index] ) )
								array_push($selected_values, $lead[$lead_index]);
						}
					} else {
						for ($i = 1; $i <= count($field["choices"]); $i++) {
							$lead_index = $fieldid;
							if ( isset($lead[$lead_index]) && ! empty($lead[$lead_index] ) )
								array_push($selected_values, $lead[$lead_index]);
						}
					}
					break;
				}

			}
		}
		return $selected_values;
	}

	public static function get_possible_choices($form_meta, $fieldid){

		$possible_choices = array();

		//pick out the field we need from the fields collection in the form object
		//and add the possible choices to the possible_choices array
		if (is_array($form_meta["fields"]) ) {
			foreach ($form_meta["fields"] as $field) {
				if ( $field["id"] == $fieldid ) {
					foreach ($field["choices"] as $possible_choice) {
						array_push($possible_choices, $possible_choice["value"]);
					}
					return $possible_choices;
				}
			}
		}
	}

	// add gf_poll scripts to the page if the form is a poll
	public static function enqueue_scripts() {

		if (isset($_POST["gform_field_values"])) {

			$field_values = wp_parse_args($_POST["gform_field_values"]);
			if (isset($field_values["gpoll_enabled"]) && ( $field_values["gpoll_enabled"] == "1"  ) && !is_admin() ){
				wp_enqueue_script('jquery');
				wp_enqueue_script('gpoll_js');
				wp_enqueue_style('gpoll_css');
			}
		}
		self::localize_scripts();

	} // end function enqueue_scripts

	public static function localize_scripts() {

		// Get current page protocol
		$protocol = isset( $_SERVER["HTTPS"]) ? 'https://' : 'http://';
		// Output admin-ajax.php URL with same protocol as current page
		$params = array(
		  'ajaxurl' => admin_url( 'admin-ajax.php', $protocol )
		);
		wp_localize_script( 'gpoll_js', 'gpoll_vars', $params );

		//localisable strings for the js file
		$strings = array(
		  'viewResults' => __("View results", "gravityformspolls"),
		  'backToThePoll' => __("Back to the poll", "gravityformspolls")

		);
		wp_localize_script( 'gpoll_js', 'gpoll_strings', $strings );

	}

	// if the confirmation is for a poll then display the results if the "display_results" attribute is set
	public static function display_confirmation($confirmation, $form, $lead, $ajax){
	
		if (isset($_POST["gform_field_values"])) {

			$field_values = wp_parse_args($_POST["gform_field_values"]);
			if (isset($field_values["gpoll_enabled"]) && ( $field_values["gpoll_enabled"] == "1"  ) ){
				$formid = $form["id"] ;
				$gpoll_field = $field_values["gpoll_field"];
				$gpoll_style = $field_values["gpoll_style"];
				$gpoll_numbers = $field_values["gpoll_numbers"];
				$gpoll_numbers = $gpoll_numbers == "1" ? true : false;
				$gpoll_percentages = $field_values["gpoll_percentages"];
				$gpoll_percentages = $gpoll_percentages == "1" ? true : false;
				$gpoll_counts = $field_values["gpoll_counts"];
				$gpoll_counts = $gpoll_counts == "1" ? true : false;
				$gpoll_cookie = $field_values["gpoll_cookie"];

				$gpoll_display_results = $field_values["gpoll_display_results"];
				$gpoll_display_results = $gpoll_display_results == "1" ? true : false;

				$gpoll_display_confirmation = $field_values["gpoll_confirmation"];
				$gpoll_display_confirmation = $gpoll_display_confirmation == "1" ? true : false;

				

				if ( $gpoll_display_confirmation && $gpoll_display_results ) {
					//confirmation message plus results
					$confirmation = substr($confirmation, 0, strlen($confirmation)-6);

					self::update_cache($form["id"]);
					$results = self::gpoll_get_results($form["id"], $gpoll_field, $gpoll_style, $gpoll_numbers, $gpoll_percentages, $gpoll_counts, $lead);
					$confirmation .= $results["summary"];

				} elseif ( ! $gpoll_display_confirmation && $gpoll_display_results ) {
					
					//only the results without the confirmation message


					$results = self::gpoll_get_results($form["id"], $gpoll_field, $gpoll_style, $gpoll_numbers, $gpoll_percentages, $gpoll_counts, $lead);

					$results_summary = $results["summary"];
					$confirmation = sprintf("<div id='gforms_confirmation_message' class='gform_confirmation_message_{$formid}'>%s</div>", $results_summary);

				} elseif ( ! $gpoll_display_confirmation && ! $gpoll_display_results ) {
					$confirmation = "<div id='gforms_confirmation_message' class='gform_confirmation_message_{$formid}'></div>";
				}
			}

		}

		return $confirmation ;
	} // end function gpoll_confirmation

	//displays the form and specifies hidden form values to enable and configure the poll.
	//if the cookie is already set then display the results.
	//TO DO - allow configuration of cookie via shortcode.

	function poll_shortcode( $string, $attributes, $content ) {

		 extract(shortcode_atts(array(
				'title' => true,
				'description' => true,
				'confirmation' => false,
				'id' => 0,
				'name' => '',
				'field_values' => "",
				'ajax' => false,
				'disable_scripts' => false,
				'tabindex' => 1,
				'mode' => 'poll',
				'field' => 0,
				'style' => 'green',
				'numbers' => false,
				'display_results' => true,
				'show_results_link' => true,
				'percentages' => true,
				'counts' => true,
				'cookie' => ''

          ), $attributes));


		
		$currentDate = strtotime("now");
		$cookie = strtolower($cookie);
		$cookie_expiration_date = date(strtotime($cookie));
        
        $confirmation = strtolower($confirmation) == "false" ? false : true;
		if ( !empty($cookie) && $cookie_expiration_date <= $currentDate ) {
			return __("Gravity Forms Polls Add-on Shortcode error: Please enter a valid date or time period for the cookie expiration cookie_expiration_date: $cookie_expiration_date","gravityformspolls");
		}

		$numbers = strtolower($numbers) == "true" ? true : false;
		$percentages = strtolower($percentages) == "false" ? false : true;
		$counts = strtolower($counts) == "false" ? false : true;
		$display_results = strtolower($display_results) == "false" ? false : true;
		$title = strtolower($title) == "false" ? false : true;
		$description = strtolower($description) == "false" ? false : true;
		$ajax = strtolower($ajax) == "true" ? true : false;
		$disable_scripts = strtolower($disable_scripts) == "true" ? true : false;
		$show_results_link = strtolower($show_results_link) == "false" ? false : true;
		$return = true;
		$poll_ui = self::build_poll_ui($id, $field, $style, $numbers, $mode, $percentages, $counts, $title, $description, $confirmation, $show_results_link, $ajax, $cookie, $display_results, $field_values, $disable_scripts, $tabindex, $return );

		return $poll_ui;

	} // end function poll_shortcode

	public static function build_poll_ui($form_id, $field_id = 0 /* zero = all fields */, $style = "green", $numbers = false, $mode = "poll", $percentages = true, $counts = true, $title = true, $description = true, $confirmation, $show_results_link, $ajax = false, $cookie = "", $display_results = true, $field_values = "", $disable_scripts = false, $tabindex, $return = true ){
		$form = RGFormsModel::get_form_meta($form_id);
		if (empty($form))
				return;

		GFPolls::$gpoll_add_scripts = true;        
        
       
        
        
        if (  $mode == "results" ) {

            $results = self::gpoll_get_results($form_id,$field_id,$style,$numbers,$percentages,$counts);
            $results_summary = $results["summary"];
            $output = $results_summary;
        
        } else {
        
            $show_results_link = false === $show_results_link ? 0 : 1;

            $field_values = htmlspecialchars_decode($field_values);
            $field_values = str_replace("&#038;", "&", $field_values);
            $numbers = $numbers === false ? 0 : 1;
            $percentages = $percentages === false ? 0 : 1;
            $counts = $counts === false ? 0 : 1;
            $display_results = $display_results ? 1 : 0;

            if ( $disable_scripts === false )
                RGForms::print_form_scripts($form, $ajax);
            
            if ( $field_values != "" ) $field_values .= "&";
            $field_values .= "gpoll_enabled=1&gpoll_field={$field_id}&gpoll_style={$style}&gpoll_numbers={$numbers}&gpoll_display_results={$display_results}&gpoll_show_results_link={$show_results_link}&gpoll_cookie={$cookie}&gpoll_confirmation={$confirmation}&gpoll_percentages={$percentages}&gpoll_counts={$counts}";

            

            parse_str($field_values, $field_value_array); //parsing query string like string for field values and placing them into an associative array
            $field_value_array = stripslashes_deep($field_value_array);

            
            $form_ui = RGForms::get_form($form_id, $title, $description, false, $field_value_array, $ajax, $tabindex);
            

            $output =  '<div class="gpoll_ajax_container">' . $form_ui . '</div>';
            
        }

        if (false === $return)
			echo $output;
		else
			return $output;
	}
	
	function poll_total_shortcode( $atts, $content = null ) {

		extract( shortcode_atts( array(
						'id' => '1',
		), $atts ) );

		$totals = RGFormsModel::get_form_counts($id);
		$total = $totals["total"];

		return $total;
	} // end function poll_total_shortcode


    //--------------   Automatic upgrade ---------------------------------------------------

    //Integration with ManageWP
    public static function premium_update_push( $premium_update ){

        if( !function_exists( 'get_plugin_data' ) )
            include_once( ABSPATH.'wp-admin/includes/plugin.php');

        $update = GFCommon::get_version_info();
        if( $update["is_valid_key"] == true && version_compare(self::$version, $update["version"], '<') ){
            $plugin_data = get_plugin_data( __FILE__ );
            $plugin_data['type'] = 'plugin';
            $plugin_data['slug'] = self::$path;
            $plugin_data['new_version'] = isset($update['version']) ? $update['version'] : false ;
            $premium_update[] = $plugin_data;
        }

        return $premium_update;
    }

    //Integration with ManageWP
    public static function premium_update( $premium_update ){

        if( !function_exists( 'get_plugin_data' ) )
            include_once( ABSPATH.'wp-admin/includes/plugin.php');

        $update = GFCommon::get_version_info();
        if( $update["is_valid_key"] == true && version_compare(self::$version, $update["version"], '<') ){
            $plugin_data = get_plugin_data( __FILE__ );
            $plugin_data['slug'] = self::$path;
            $plugin_data['type'] = 'plugin';
            $plugin_data['url'] = isset($update["url"]) ? $update["url"] : false; // OR provide your own callback function for managing the update

            array_push($premium_update, $plugin_data);
        }
        return $premium_update;
    }

    public static function flush_version_info(){
        require_once("plugin-upgrade.php");
        RGPollsUpgrade::set_version_info(false);
    }

    public static function plugin_row(){
        require_once("plugin-upgrade.php");

        if(!self::is_gravityforms_supported()){
            $message = sprintf(__("Gravity Forms " . self::$min_gravityforms_version . " is required. Activate it now or %spurchase it today!%s", "gravityformspolls"), "<a href='http://www.gravityforms.com'>", "</a>");
            RGPollsUpgrade::display_plugin_message($message, true);
        }
        else{
            $version_info = RGPollsUpgrade::get_version_info(self::$slug, self::get_key(), self::$version);

            if(!$version_info["is_valid_key"]){
                $new_version = version_compare(self::$version, $version_info["version"], '<') ? __('There is a new version of Gravity Forms Polls Add-On available.', 'gravityformspolls') .' <a class="thickbox" title="Gravity Forms Polls Add-On" href="plugin-install.php?tab=plugin-information&plugin=' . self::$slug . '&TB_iframe=true&width=640&height=808">'. sprintf(__('View version %s Details', 'gravityformspolls'), $version_info["version"]) . '</a>. ' : '';
                $message = $new_version . sprintf(__('%sRegister%s your copy of Gravity Forms to receive access to automatic upgrades and support. Need a license key? %sPurchase one now%s.', 'gravityformspolls'), '<a href="admin.php?page=gf_settings">', '</a>', '<a href="http://www.gravityforms.com">', '</a>') . '</div></td>';
                RGPollsUpgrade::display_plugin_message($message);
            }
        }
    }

    //Displays current version details on Plugin's page
    public static function display_changelog(){
        if($_REQUEST["plugin"] != self::$slug)
            return;

        //loading upgrade lib
        require_once("plugin-upgrade.php");

        RGPollsUpgrade::display_changelog(self::$slug, self::get_key(), self::$version);
    }

    public static function check_update($update_plugins_option){
        require_once("plugin-upgrade.php");

        return RGPollsUpgrade::check_update(self::$path, self::$slug, self::$url, self::$slug, self::get_key(), self::$version, $update_plugins_option);
    }

    private static function get_key(){
        if(self::is_gravityforms_supported())
            return GFCommon::get_key();
        else
            return "";
    }

    //---------------------------------------------------------------------------------------

} //end class GFPolls


?>
