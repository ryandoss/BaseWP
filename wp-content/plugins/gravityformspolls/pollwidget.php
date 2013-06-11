<?php
add_action( 'widgets_init', 'gpoll_register_poll_widget' );

if(!function_exists("gpoll_register_poll_widget")){
function gpoll_register_poll_widget() {
    register_widget( 'GFPollsPollWidget' );
}
}

if(!class_exists("GFPollsPollWidget")){
class GFPollsPollWidget extends WP_Widget {

    function GFPollsPollWidget() {
        $this->WP_Widget( 'gpoll_poll_widget', 'Poll',
                            array( 'classname' => 'gpoll_poll_widget', 'description' => __('Gravity Forms Poll Widget', "gravityformspolls") ),
                            array( 'width' => 200, 'height' => 250, 'id_base' => 'gpoll_poll_widget' )
                            );
    }

    function widget( $args, $instance ) {

        extract( $args );
        echo $before_widget;
        $title = apply_filters('widget_title', $instance['title'] );
		$formid= $instance['form_id'];
		
        if ( $title )
            echo $before_title . $title . $after_title;
			
		$showtitle = $instance["showtitle"];
		$showtitle = strtolower($showtitle) == "1" ? "true" : "false";
		
		$showdescription = $instance["showdescription"];
		$showdescription = strtolower($showdescription) == "1" ? "true" : "false";
		
		$displayconfirmation = $instance["displayconfirmation"];
		$displayconfirmation = strtolower($displayconfirmation) == "1" ? "true" : "false";
		
		$ajax = $instance["ajax"];
		$ajax = strtolower($ajax) == "1" ? "true" : "false";
		
		$disable_scripts = $instance["disable_scripts"];
		$disable_scripts = strtolower($disable_scripts) == "1" ? "true" : "false";
		
		$mode = $instance["mode"];
		$style = $instance["style"];
		
		$add_numbers = $instance["add_numbers"];
		$add_numbers_string =  $add_numbers == "1" ? "true" : "false";
		
		$display_results = $instance["display_results"];
		$display_results_string =  $display_results == "1" ? "true" : "false";
		
		$show_results_link = $instance["show_results_link"];
		$show_results_link_string =  $show_results_link == "1" ? "true" : "false";
		
		$show_percentages = $instance["show_percentages"];
		$show_percentages_string =  $show_percentages == "1" ? "true" : "false";
		
		$show_counts= $instance["show_counts"];
		$show_counts_string =  $show_counts == "1" ? "true" : "false";
		
		$block_repeat_voters = $instance["block_repeat_voters"];

		$tabindex = $instance["tabindex"];
		
		$cookie = $block_repeat_voters == "1" ? $instance["cookie"] :  "";
		
		$shortcode = "[gravityforms action=\"polls\" field=\"0\" id=\"{$formid}\" style=\"{$style}\" mode=\"{$mode}\" numbers=\"{$add_numbers_string}\" display_results=\"{$display_results_string}\" show_results_link=\"{$show_results_link_string}\" cookie=\"{$cookie}\" ajax=\"{$ajax}\" disable_scripts=\"{$disable_scripts}\" tabindex=\"{$tabindex}\" title=\"{$showtitle}\" description=\"{$showdescription}\" confirmation=\"{$displayconfirmation}\" percentages=\"{$show_percentages_string}\" counts=\"{$show_counts_string}\"]";
		
		
		echo do_shortcode($shortcode);
		
		
        echo $after_widget;
		return;
    }

    function update( $new_instance, $old_instance ) {
	
        $instance = $old_instance;
        $instance["title"] = strip_tags( $new_instance["title"] );
        $instance["form_id"] = $new_instance["form_id"];
        $instance["showtitle"] = empty( $new_instance["showtitle"] ) ? "Poll" : $new_instance["showtitle"];
		$instance["displayconfirmation"] = empty( $new_instance["displayconfirmation"] ) ? "0" : $new_instance["displayconfirmation"];
        $instance["ajax"] = empty( $new_instance["ajax"] ) ? "0" : $new_instance["ajax"];
        $instance["disable_scripts"] = empty( $new_instance["disable_scripts"] ) ? "0" : $new_instance["disable_scripts"];
        $instance["showdescription"] = empty( $new_instance["showdescription"] ) ? "0" : $new_instance["showdescription"];
        $instance["tabindex"] = empty( $new_instance["tabindex"] ) ? "0" : $new_instance["tabindex"];
		$instance["mode"] = $new_instance["mode"];
		$instance["style"] = $new_instance["style"];
		$instance["add_numbers"] = empty( $new_instance["add_numbers"] ) ? "0" : $new_instance["add_numbers"];
		$instance["display_results"] = empty( $new_instance["display_results"] ) ? "0" : $new_instance["display_results"];
		$instance["show_results_link"] = empty( $new_instance["show_results_link"] ) ? "0" : $new_instance["show_results_link"];
		$instance["show_percentages"] = empty( $new_instance["show_percentages"] ) ? "0" : $new_instance["show_percentages"];
		$instance["show_counts"] = empty( $new_instance["show_counts"] ) ? "0" : $new_instance["show_counts"];
		$instance["block_repeat_voters"] = empty( $new_instance["block_repeat_voters"] ) ? "0" : $new_instance["block_repeat_voters"];
		$instance["cookie"] = $new_instance["cookie"];

        
        return $instance;
    }

    function form( $instance ) {
		$first_form_id = 1;
		$forms = RGFormsModel::get_forms();
		if(!empty($forms)) {
			$first_form_id = $forms[0]->id;
		}
        $instance = wp_parse_args( (array) $instance, array(
			'title' => __("Poll", "gravityforms"), 
			'tabindex' => '1', 
			'showtitle' => '0',
			'showdescription' => '0',
			'displayconfirmation' => '0',
			'ajax' => '0',
			'disable_scripts' => '0',
			'form_id' => $first_form_id, 
			'mode' => 'poll',
			'style' => 'green',
			'add_numbers' => '0',
			'display_results' => '1',
			'show_results_link' => '1',
			'show_percentages' => '1',
			'show_counts' => '1',
			'block_repeat_voters' => '0',
			'cookie' => ''

			) );
        ?>

        <p>
            <label for="<?php echo $this->get_field_id( 'title' ); ?>"><?php _e("Title", "gravityforms"); ?>:</label>
            <input id="<?php echo $this->get_field_id( 'title' ); ?>" name="<?php echo $this->get_field_name( 'title' ); ?>" value="<?php echo $instance['title']; ?>" style="width:90%;" />
        </p>
		<fieldset class="gpoll_fieldset">
			<legend>Form Settings</legend>
			<p>
				<label for="<?php echo $this->get_field_id( 'form_id' ); ?>"><?php _e("Select a Form", "gravityforms"); ?>:</label>
				<select id="<?php echo $this->get_field_id( 'form_id' ); ?>" name="<?php echo $this->get_field_name( 'form_id' ); ?>" style="width:90%;">
					<?php
						$forms = RGFormsModel::get_forms(1, "title");
						foreach ($forms as $form) {
							$selected = '';
							if ($form->id == $instance['form_id'])
								$selected = ' selected="selected"';
							echo '<option value="'.$form->id.'" '.$selected.'>'.$form->title.'</option>';
						}
					?>
				</select>
			</p>
			<p>
		
				<input type="checkbox" name="<?php echo $this->get_field_name( 'showtitle' ); ?>" id="<?php echo $this->get_field_id( 'showtitle' ); ?>" <?php checked($instance['showtitle']); ?> value="1" /> <label for="<?php echo $this->get_field_id( 'showtitle' ); ?>"><?php _e("Display form title", "gravityforms"); ?></label><br/>
				<input type="checkbox" name="<?php echo $this->get_field_name( 'showdescription' ); ?>" id="<?php echo $this->get_field_id( 'showdescription' ); ?>" <?php checked($instance['showdescription']); ?> value="1"/> <label for="<?php echo $this->get_field_id( 'showdescription' ); ?>"><?php _e("Display form description", "gravityforms"); ?></label><br/>
				<input type="checkbox" name="<?php echo $this->get_field_name( 'displayconfirmation' ); ?>" id="<?php echo $this->get_field_id( 'displayconfirmation' ); ?>" <?php checked($instance['displayconfirmation']); ?> value="1"/> <label for="<?php echo $this->get_field_id( 'displayconfirmation' ); ?>"><?php _e("Display form confirmation", "gravityforms"); ?></label><br/>
			</p>
		</fieldset>
		<fieldset class="gpoll_fieldset">
			<legend>Poll Settings</legend>
			<p>
				<label for="<?php echo $this->get_field_id( 'mode' ); ?>"><?php _e("Display Mode", "gravityformspolls"); ?>:</label>
				<select id="<?php echo $this->get_field_id( 'mode' ); ?>" name="<?php echo $this->get_field_name( 'mode' ); ?>" style="width:90%;">
					<option value="poll" <?php echo $instance['mode'] == "poll" ? 'selected="selected"' : '' ; ?>>Poll</option>
					<option value="results" <?php echo $instance['mode'] == "results" ? 'selected="selected"' : '' ?>>Results</option>
				</select>
			</p>
			
			<p>         
				<input type="checkbox" name="<?php echo $this->get_field_name( 'display_results' ); ?>" id="<?php echo $this->get_field_id( 'display_results' ); ?>" <?php checked($instance['display_results']); ?> value="1" /> <label for="<?php echo $this->get_field_id( 'display_results' ); ?>"><?php _e("Display results after voting", "gravityformspolls"); ?></label><br/>
			  
		   
				<input type="checkbox" name="<?php echo $this->get_field_name( 'show_results_link' ); ?>" id="<?php echo $this->get_field_id( 'show_results_link' ); ?>" <?php checked($instance['show_results_link']); ?> value="1" /> <label for="<?php echo $this->get_field_id( 'show_results_link' ); ?>"><?php _e("Show link to view results", "gravityformspolls"); ?></label><br/>
				
				<input type="checkbox" name="<?php echo $this->get_field_name( 'show_percentages' ); ?>" id="<?php echo $this->get_field_id( 'show_percentages' ); ?>" <?php checked($instance['show_percentages']); ?> value="1" /> <label for="<?php echo $this->get_field_id( 'show_percentages' ); ?>"><?php _e("Show percentages", "gravityformspolls"); ?></label><br/>
				
				<input type="checkbox" name="<?php echo $this->get_field_name( 'show_counts' ); ?>" id="<?php echo $this->get_field_id( 'show_counts' ); ?>" <?php checked($instance['show_counts']); ?> value="1" /> <label for="<?php echo $this->get_field_id( 'show_counts' ); ?>"><?php _e("Show counts", "gravityformspolls"); ?></label><br/> 
				
			</p>
			<p>
				<label for="<?php echo $this->get_field_id( 'style' ); ?>"><?php _e("Style", "gravityformspolls"); ?>:</label>
				<select id="<?php echo $this->get_field_id( 'style' ); ?>" name="<?php echo $this->get_field_name( 'style' ); ?>" style="width:90%;">
					<option value="green" <?php echo $instance['style'] == "green" ? 'selected="selected"' : '' ?>><?php _e("Green","gravityformspolls") ?></option>
					<option value="blue" <?php echo $instance['style'] == "blue" ? 'selected="selected"' : '' ?>><?php _e("Blue","gravityformspolls") ?></option>
					<option value="red" <?php echo $instance['style'] == "red" ? 'selected="selected"' : '' ?>><?php _e("Red","gravityformspolls") ?></option>
					<option value="orange" <?php echo $instance['style'] == "orange" ? 'selected="selected"' : '' ?>><?php _e("Orange","gravityformspolls") ?></option>
				</select>
			</p>
		</fieldset>	  
			  <?php $cookie_expriation_div_id = $this->id . "_cookie_expriation" ?>
			<fieldset class="gpoll_fieldset">
				<legend>Repeat Voters</legend>
					<input type="radio" name="<?php echo $this->get_field_name( 'block_repeat_voters' ); ?>" value="0" <?php checked($instance['block_repeat_voters'], "0"); ?> id="<?php echo $this->get_field_id( 'block_repeat_voters' ) . "_0"; ?>" onclick="jQuery('#<?php echo $cookie_expriation_div_id ?>').hide('slow');"> <label for="<?php echo $this->get_field_id( 'block_repeat_voters' ) . "_0"; ?>"><?php _e("Don't block repeat voting", "gravityformspolls"); ?></label><br>
					<input type="radio" name="<?php echo $this->get_field_name( 'block_repeat_voters' ); ?>" value="1" <?php checked($instance['block_repeat_voters'], "1"); ?> id="<?php echo $this->get_field_id( 'block_repeat_voters' ) . "_1"; ?>" onclick="jQuery('#<?php echo $cookie_expriation_div_id ?>').show('slow');"> <label for="<?php echo $this->get_field_id( 'block_repeat_voters' ) . "_1"; ?>"><?php _e("Block repeat voting using cookie", "gravityformspolls"); ?></label><br>
					<div id="<?php echo $cookie_expriation_div_id?>" <?php echo $instance['block_repeat_voters'] == '0' ? 'style="display:none;"' : '' ?>>
						<br>
						<label for="<?php echo $this->get_field_id( 'cookie' ); ?>"><?php _e("Expires:", "gravityformspolls"); ?></label>
						<select id="<?php echo $this->get_field_id( 'cookie' ); ?>" name="<?php echo $this->get_field_name( 'cookie' ); ?>" style="width:90%;">
							<?php
								$options = array(
									"20 years"	=> __("Never","gravityformspolls"),
									"1 hour" 	=> __("1 hour","gravityformspolls"),
									"6 hours"	=> __("6 hours","gravityformspolls"),
									"12 hours"	=> __("12 hours","gravityformspolls"),
									"1 day"		=> __("1 day","gravityformspolls"),
									"1 week"	=> __("1 week","gravityformspolls"),
									"1 month"	=> __("1 month","gravityformspolls")
										);
								foreach ($options as $key => $value) {
									$selected = '';
									if ($key == rgar($instance, 'cookie'))
										$selected = ' selected="selected"';
									echo '<option value="'.$key.'" '.$selected.'>'.$value.'</option>';
								}
							?>
						</select>
					</div>
              
			</fieldset>
      
        <p>
            <a href="javascript: var obj = jQuery('.gf_widget_advanced'); if(!obj.is(':visible')) {var a = obj.show('slow');} else {var a = obj.hide('slow');}"><?php _e("advanced options", "gravityforms"); ?></a>
        </p>
        <p class="gf_widget_advanced" style="display:none;">
            <input type="checkbox" name="<?php echo $this->get_field_name( 'ajax' ); ?>" id="<?php echo $this->get_field_id( 'ajax' ); ?>" <?php checked($instance['ajax']); ?> value="1"/> <label for="<?php echo $this->get_field_id( 'ajax' ); ?>"><?php _e("Enable AJAX", "gravityforms"); ?></label><br/>
            <input type="checkbox" name="<?php echo $this->get_field_name( 'disable_scripts' ); ?>" id="<?php echo $this->get_field_id( 'disable_scripts' ); ?>" <?php checked($instance['disable_scripts']); ?> value="1"/> <label for="<?php echo $this->get_field_id( 'disable_scripts' ); ?>"><?php _e("Disable script output", "gravityforms"); ?></label><br/>
            <label for="<?php echo $this->get_field_id( 'tabindex' ); ?>"><?php _e("Tab Index Start", "gravityforms"); ?>: </label>
            <input id="<?php echo $this->get_field_id( 'tabindex' ); ?>" name="<?php echo $this->get_field_name( 'tabindex' ); ?>" value="<?php echo $instance['tabindex']; ?>" style="width:15%;" /><br/>
            <small><?php _e("If you have other forms on the page (i.e. Comments Form), specify a higher tabindex start value so that your Gravity Form does not end up with the same tabindices as your other forms. To disable the tabindex, enter 0 (zero).", "gravityforms"); ?></small>
        </p>

    <?php
    }
}
}

?>