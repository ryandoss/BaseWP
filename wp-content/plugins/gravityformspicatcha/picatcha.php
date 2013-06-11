<?php
/**
Plugin Name: Gravity Forms Picatcha Add-On
Plugin URI: http://www.picatcha.com
Description: Integrates Gravity Forms with Picatcha, enabling users to prevent spam by adding a Picatcha field to their forms.
Version: 1.2
Author: rocketgenius
Author URI: http://www.rocketgenius.com

This program is free software; you can redistribute it and/or modify
it under the terms of the GNU General Public License as published by
the Free Software Foundation; either version 3 of the License, or
(at your option) any later version.

This program is distributed in the hope that it will be useful,
but WITHOUT ANY WARRANTY; without even the implied warranty of
MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
GNU General Public License for more details.

You should have received a copy of the GNU General Public License
along with this program.  If not, see <http://www.gnu.org/licenses/>.

*/

add_action('init',  array('GFPicatcha', 'init'));
register_activation_hook( __FILE__, array("GFPicatcha", "add_permissions"));

class GFPicatcha {

    private static $path = "gravityformspicatcha/picatcha.php";
    private static $url = "http://www.gravityforms.com";
    private static $slug = "gravityformspicatcha";
    private static $version = "1.2";
    private static $min_gravityforms_version = "1.6.4.5";

    //Plugin starting point. Will load appropriate files
    public static function init(){


        if(RG_CURRENT_PAGE == "plugins.php"){
            //loading translations
            load_plugin_textdomain('gravityformspicatcha', FALSE, '/gravityformspicatcha/languages' );

            add_action('after_plugin_row_' . self::$path, array('GFPicatcha', 'plugin_row') );

           //force new remote request for version info on the plugin page
            self::flush_version_info();
        }

        if(!self::is_gravityforms_supported()){
           return;
        }

        //displays the captcha input
        add_action("gform_field_input" ,array('GFPicatcha', "picatcha_input"), 10, 5);

        if(is_admin()){

            //loading translations
            load_plugin_textdomain('gravityformspicatcha', FALSE, '/gravityformspicatcha/languages' );

            //Automatic upgrade functionality
            add_filter("transient_update_plugins", array('GFPicatcha', 'check_update'));
            add_filter("site_transient_update_plugins", array('GFPicatcha', 'check_update'));
            add_action('install_plugins_pre_plugin-information', array('GFPicatcha', 'display_changelog'));

            //Adds Picatcha field button to form editor
            add_filter("gform_add_field_buttons", array('GFPicatcha', 'add_field'));

            //Formats Picatcha field type title
            add_filter('gform_field_type_title', array('GFPicatcha', 'field_title'));

            //Adds form editor script
            add_action("gform_editor_js", array('GFPicatcha', "editor_script"));

            //Adds basic field settings
            add_action("gform_field_standard_settings", array('GFPicatcha', "standard_field_settings"), 10,2);

            //Registers Picatcha tooltips
            add_filter('gform_tooltips', array('GFPicatcha', 'tooltips'));

            //creates a new Settings page on Gravity Forms' settings screen
            if(self::has_access("gravityforms_picatcha")){
                RGForms::add_settings_page("Picatcha", array("GFPicatcha", "settings_page"), self::get_base_url() . "/images/picatcha_wordpress_icon_32.png");
            }
        }
        else{

            //including the picatchalib
            if (!function_exists('_picatcha_http_post')) {
                require_once('picatcha/picatchalib.php');
            }

            //Enqueue the picatcha script on the front end
            add_action("gform_enqueue_scripts", array('GFPicatcha',"enqueue_scripts"), 10,2);

            //Prints sripts and styles for preview page
            add_action("gform_preview_footer", array("GFPicatcha", "print_preview_scripts"));

            //Validate the Picatcha field
            add_filter("gform_field_validation", array('GFPicatcha', "validate_form"), 10, 4);

            // ManageWP premium update filters
            add_filter( 'mwp_premium_update_notification', array('GFPicatcha', 'premium_update_push') );
            add_filter( 'mwp_premium_perform_update', array('GFPicatcha', 'premium_update') );
        }

        //integrating with Members plugin
        if(function_exists('members_get_capabilities'))
            add_filter('members_get_capabilities', array("GFPicatcha", "members_get_capabilities"));

    }

    public static function add_field($field_groups){
    //script to name the widget
    ?>
    <script type="text/javascript">
      function AddPicatchaField(){
        for(var i=0; i<form.fields.length; i++){
          if(form.fields[i].type == 'picatcha'){
            alert("<?php _e("Only one Picatcha Image Captcha field can be added to the form.", "gravityformspicatcha");?>");
            return
          }
        }
        StartAddField('picatcha');
      }

      function SetDefaultValues_picatcha(field) {
          field.label = "Picatcha";
          field['displayOnly'] = true;
          field['noDuplicates'] = true;
          field['type'] = 'picatcha';
          field['field_picatcha_format'] = '2';
          field['field_picatcha_color'] = '#2a1f19';
          field['field_picatcha_imgSize'] = '75';
          field['field_picatcha_noise_type'] = '0';
          field['field_picatcha_noise_level'] = '0';
          field['field_picatcha_lang'] = 'en';
          field['field_picatcha_lang_override'] = '0';
          return field;
      }
    </script>
    <?php
    foreach($field_groups as &$group){
        if($group["name"] == "advanced_fields"){
            $group["fields"][] = array("class"=>"button", "value" => __("Picatcha", "gravityformspicatcha"), "onclick" => "AddPicatchaField()");
            break;
        }
    }
    return $field_groups;
}

    //sets the title of the widget
    public static function field_title($type) {

        if ($type == 'picatcha')
            return __('Picatcha', 'gravityformspicatcha');

        return $type;
    }

    public static function picatcha_input($input, $field, $value, $lead_id, $form_id){

      if ($field["type"] == "picatcha"){
        //Public
        if(IS_ADMIN){
            if(self::has_valid_keys())
                $input = "<img src='". self::get_base_url() . '/images/picatcha.png' ."' />";
            else
                $input = "<div class='captcha_message'>" . __("To use the Picatcha field you must first do the following:", "gravityformspicatcha") . "</div><div class='captcha_message'>1 - <a href='http://www.picatcha.com/signup' target='_blank'>" . sprintf(__("Sign up%s for a free Picatcha account", "gravityformspicatcha"), "</a>") . "</div><div class='captcha_message'>2 - " . sprintf(__("Enter your Picatcha keys in the %ssettings page%s", "gravityformspicatcha"), "<a href='?page=gf_settings&addon=Picatcha'>", "</a>") . "</div>";
        }
        else{
            $settings = get_option('gf_picatcha_settings');
            $input = picatcha_get_html($form_id, $settings["public_key"], NULL, $field['field_picatcha_format'], $field['field_picatcha_color'], 1, $field['field_picatcha_imgSize'], $field["field_picatcha_noise_level"], $field["field_picatcha_noise_type"],$field["field_picatcha_lang"],$field['field_picatcha_lang_override']);
        }
      }

      return $input;
    }

    public static function editor_script(){
      ?>
      <script type='text/javascript'>
        //Add in the settings
        jQuery(document).ready(function($){
            fieldSettings["picatcha"] = ".error_message_setting, .conditional_logic_field_setting, .css_class_setting, .label_setting, .picatcha_color, .picatcha_format, .picatcha_image_size, .picatcha_language, .picatcha_language_override, .picatcha_noise_level, .picatcha_noise_type";
        });

        //load in the settings
        jQuery(document).bind("gform_load_field_settings", function(event, field, form){

            if(field["type"] != "picatcha")
                return;

            //theme color
            jQuery("#picatcha_color").attr("value", field["field_picatcha_color"]);

            //captcha format
            jQuery("#picatcha_format").attr("value", field["field_picatcha_format"]);

            //image size
            jQuery("#picatcha_imgSize").attr("value", field["field_picatcha_imgSize"]);

            //language
            jQuery("#picatcha_lang").attr("value", field["field_picatcha_lang"]);

            //language override
            jQuery("#picatcha_langOverride").attr("checked", field["field_picatcha_lang_override"]);

            //noise type
            jQuery("#picatcha_noise_type").attr("value", field["field_picatcha_noise_type"]);

            //noise level
            jQuery("#picatcha_noise_level").attr("value", field["field_picatcha_noise_level"]);

            ToggleNoiseType(true);
        });

        function SetPicatchaColor(){
            SetFieldProperty('field_picatcha_color', jQuery("#picatcha_color").val());
        }

        function ToggleNoiseType(isInit){
            var hideType = jQuery("#picatcha_noise_level").val() == "0";
            var speed = "";
            if(!isInit)
                speed = "slow";

            if(hideType){
                jQuery(".picatcha_noise_type").hide(speed);
                jQuery("#picatcha_noise_type").val("");
            }
            else{
                jQuery(".picatcha_noise_type").show(speed);
            }
        }
      </script>
      <?php
    }

    // Adds custom settings to the standard (properties?) tab
    public static function standard_field_settings($position, $form_id){

      if($position == 50){
        ?>
          <li class="picatcha_color field_setting">
            <label for="picatcha_color"><?php _e("Theme Color","gravityformspicatcha")?> <?php gform_tooltip("picatcha_color") ?></label>
            <?php GFFormDetail::color_picker("picatcha_color", "SetPicatchaColor") ?>
          </li>

          <li class="picatcha_format field_setting">
            <label for="picatcha_format"><?php _e("Number of Images", "gravityformspicatcha")?> <?php gform_tooltip("picatcha_images") ?></label>
            <select id="picatcha_format" onchange="SetFieldProperty('field_picatcha_format', this.value)">
              <option value="1">6</option>
              <option value="2">8</option>
              <option value="3">10</option>
              <option value="4">12</option>
            </select><br />
          </li>

          <li class="picatcha_image_size field_setting">
            <label for="picatcha_imgSize"><?php _e("Image Size", "gravityformspicatcha")?> <?php gform_tooltip("picatcha_image_size") ?></label>
            <select id="picatcha_imgSize" onchange="SetFieldProperty('field_picatcha_imgSize',this.value)">
              <option value="50"><?php _e("Small", "gravityformspicatcha") ?></option>
              <option value="60"><?php _e("Medium", "gravityformspicatcha") ?></option>
              <option value="75"><?php _e("Large", "gravityformspicatcha") ?></option>
            </select><br />
          </li>

          <li class="picatcha_noise_level field_setting" style="float: left;">
            <label for="picatcha_noise_level"><?php _e("Noise", "gravityformspicatcha")?> <?php gform_tooltip("picatcha_noise") ?></label>
            <select id="picatcha_noise_level" onchange="SetFieldProperty('field_picatcha_noise_level',this.value); ToggleNoiseType(false);">
              <option value="0"><?php _e("Off", "gravityformspicatcha") ?></option>
              <option value="1">1</option>
              <option value="2">2</option>
              <option value="3">3</option>
              <option value="4">4</option>
              <option value="5">5</option>
              <option value="6">6</option>
              <option value="7">7</option>
              <option value="8">8</option>
              <option value="9">9</option>
              <option value="10">10 - <?php _e("Maximum", "gravityformspicatcha") ?></option>
            </select><br />
          </li>

          <li class="picatcha_noise_type field_setting">
            <label for="picatcha_noise_type">&nbsp;</label>
            <select id="picatcha_noise_type" onchange="SetFieldProperty('field_picatcha_noise_type', this.value)">
              <option value=""><?php _e("Select noise type", "gravityformspicatcha")?></option>
              <option value="0"><?php _e("Random", "gravityformspicatcha")?></option>
              <option value="1"><?php _e("Shadow", "gravityformspicatcha")?></option>
              <option value="2"><?php _e("Pixelation", "gravityformspicatcha")?></option>
            </select>
          </li>

          <li class="picatcha_language field_setting" style="clear:both">
            <label for="picatcha_lang"><?php _e("Language","gravityformspicatcha")?> <?php gform_tooltip("picatcha_language") ?></label>
            <select id="picatcha_lang" onchange="SetFieldProperty('field_picatcha_lang',this.value)">
                <option value="en">English</option>
                <option value="es">Español</option>
                <option value="fr">Français</option>
                <option value="de">Deutch</option>
                <option value="hi">हिंदी</option>
                <option value="is">Íslenska</option>
                <option value="ru">Pусский</option>
                <option value="zh">中国</option>
                <option value="ar">العربية</option>
                <option value="tl">Filipino</option>
                <option value="it">Italiano</option>
                <option value="vi">Việt</option>
                <option value="nl">Nederlands</option>
                <option value="pt">Português</option>
                <option value="tr">Türkçe</option>
                <option value="sk">Slovenských</option>
            </select><br />
          </li>

          <li class="picatcha_language_override field_setting">
            <input type="checkbox" id="picatcha_langOverride" onclick="SetFieldProperty('field_picatcha_lang_override', this.checked)" />
            <label for="picatcha_langOverride" class="inline"><?php _e("Allow users to select language","gravityformspicatcha")?></label>
          </li>


        <?php
      }
    }

    public static function print_preview_scripts($form_id){
        if(!$form_id)
            $form_id = rgget("id");

        $form = RGFormsModel::get_form_meta($form_id);
        foreach( $form['fields'] as $field){
            if( ($field['type']=='picatcha') ){
                wp_print_scripts(array("gform_picatcha_script"));
                wp_print_styles(array("gform_picatcha_style"));
                return;
            }
        }

    }

    public static function enqueue_scripts($form, $ajax){

      if(!is_array(rgar($form, "fields")))
        return;

      $protocol = GFCommon::is_ssl() ? "https" : "http";

      //cycle through the fields to see if picatcha is being used
      foreach( $form['fields'] as $field){
        if( ($field['type']=='picatcha') ){
          wp_enqueue_script("gform_picatcha_script", "{$protocol}://api.picatcha.com/static/client/picatcha.js", array("jquery"), self::$version);
          wp_enqueue_style("gform_picatcha_style", "{$protocol}://api.picatcha.com/static/client/picatcha.css", null, self::$version);
          break;
        }
      }
    }

    public static function validate_form($result, $value, $form, $field){

      if ($field['type'] == 'picatcha'){

        //only check if the rest of the form is valid
        if($result["is_valid"]){

            $settings = get_option('gf_picatcha_settings');
            //check if the picatcha is correct
            $response = picatcha_check_answer($settings["private_key"],
            $_SERVER['REMOTE_ADDR'],
            $_SERVER['HTTP_USER_AGENT'], $_POST['picatcha']['token'], $_POST['picatcha']['r']);

            if ($response->is_valid == true){
                $result["is_valid"] = true;
            }
            else{
                //not sure if this is necessary..
                $result["is_valid"] = false;
                $result["message"] = rgempty("errorMessage", $field) ? __("Incorrect. Please select the correct images.") : rgar($field, "errorMessage");
            }
        }
      }
      return $result;
    }

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
        RGPicatchaUpgrade::set_version_info(false);
    }

    public static function plugin_row(){
        if(!self::is_gravityforms_supported()){
            $message = sprintf(__("Gravity Forms " . self::$min_gravityforms_version . " is required. Activate it now or %spurchase it today!%s", "gravityformspicatcha"), "<a href='http://www.gravityforms.com'>", "</a>");
            RGPicatchaUpgrade::display_plugin_message($message, true);
        }
        else{
            $version_info = RGPicatchaUpgrade::get_version_info(self::$slug, self::get_key(), self::$version);

            if(!$version_info["is_valid_key"]){
                $new_version = version_compare(self::$version, $version_info["version"], '<') ? __('There is a new version of Gravity Forms Picatcha Add-On available.', 'gravityformspicatcha') .' <a class="thickbox" title="Gravity Forms Picatcha Add-On" href="plugin-install.php?tab=plugin-information&plugin=' . self::$slug . '&TB_iframe=true&width=640&height=808">'. sprintf(__('View version %s Details', 'gravityformspicatcha'), $version_info["version"]) . '</a>. ' : '';
                $message = $new_version . sprintf(__('%sRegister%s your copy of Gravity Forms to receive access to automatic upgrades and support. Need a license key? %sPurchase one now%s.', 'gravityformspicatcha'), '<a href="admin.php?page=gf_settings">', '</a>', '<a href="http://www.gravityforms.com">', '</a>') . '</div></td>';
                RGPicatchaUpgrade::display_plugin_message($message);
            }
        }
    }

    //Displays current version details on Plugin's page
    public static function display_changelog(){
        if($_REQUEST["plugin"] != self::$slug)
            return;

        //loading upgrade lib
        require_once("plugin-upgrade.php");

        RGPicatchaUpgrade::display_changelog(self::$slug, self::get_key(), self::$version);
    }

    public static function check_update($update_plugins_option){
        require_once("plugin-upgrade.php");

        return RGPicatchaUpgrade::check_update(self::$path, self::$slug, self::$url, self::$slug, self::get_key(), self::$version, $update_plugins_option);
    }

    private static function get_key(){
        if(self::is_gravityforms_supported())
            return GFCommon::get_key();
        else
            return "";
    }
    //---------------------------------------------------------------------------------------

    //Adds feed tooltips to the list of tooltips
    public static function tooltips($tooltips){
        $picatcha_tooltips = array(
            "picatcha_color" => "<h6>" . __("Theme Color", "gravityformspicatcha") . "</h6>" . __("Select the primary theme color for the Picatcha field using the color picker.", "gravityformspicatcha"),
            "picatcha_image_size" => "<h6>" . __("Image Size", "gravityformspicatcha") . "</h6>" . __("Choose what size images you would like Picatcha to display: Small, Medium or Large.", "gravityformspicatcha"),
            "picatcha_noise" => "<h6>" . __("Noise", "gravityformspicatcha") . "</h6>" . __("Control the amount of noise that will be applied to the Picatcha images as an image filter.", "gravityformspicatcha"),
            "picatcha_language" => "<h6>" . __("Language", "gravityformspicatcha") . "</h6>" . __("Select which language Picatcha should use when presenting the field to the user.", "gravityformspicatcha"),
            "picatcha_images" => "<h6>" . __("Number of Images", "gravityformspicatcha") . "</h6>" . __("Select how many Picatcha images you would like the user to choose from.", "gravityformspicatcha")
        );
        return array_merge($tooltips, $picatcha_tooltips);
    }

    public static function has_valid_keys(){
        //Get the settings
        $settings = get_option('gf_picatcha_settings');

        $is_valid_public_key = !rgempty("public_key", $settings) && self::is_valid_key("pub", rgar($settings,"public_key"));
        $is_valid_private_key = !rgempty("private_key", $settings) && self::is_valid_key("pri", rgar($settings,"private_key"));

        return $is_valid_public_key && $is_valid_private_key;
    }

    public static function is_valid_key($type, $key){
        $protocol = GFCommon::is_ssl() ? "https" : "http";
        $url = "{$protocol}://api.picatcha.com/vk?{$type}={$key}";
        $raw_response = wp_remote_request($url, array("timeout" => 4, "sslverify" => false));

        if ( is_wp_error( $raw_response ) || 200 != $raw_response['response']['code'])
            return false;
        else
        {
            $result = json_decode($raw_response['body']);
            return $result->s;
        }
    }

    public static function settings_page(){

        require_once("plugin-upgrade.php");

        if(!rgempty("uninstall")){
            check_admin_referer("uninstall_picatcha", "uninstall_picatcha");
            self::uninstall();
            ?>
            <div class="updated fade" style="padding:20px;"><?php _e(sprintf("Gravity Forms Picatcha Add-On have been successfully uninstalled. It can be re-activated from the %splugins page%s.", "<a href='plugins.php'>","</a>"), "gravityformspicatcha")?></div>
            <?php
            return;
        }
        else if(isset($_POST["gforms_picatcha"])){
            check_admin_referer("update_picatcha", "update_picatcha");
            $settings = array(
              "public_key" => trim($_POST['picatcha_public_key']),
              "private_key" => trim($_POST['picatcha_private_key']),
            );

            $message = __('Settings saved', 'gravityformspicatcha');
            update_option('gf_picatcha_settings', $settings);
          }
        else{
            //Get the settings
            $settings = get_option('gf_picatcha_settings');

            //if there is no settings...
            if (!$settings){
              //defaults
              $settings = array(
                "public_key" => "",
                "private_key" => "",
              );
            }
        }

        $is_valid_public_key = self::is_valid_key("pub", $settings["public_key"]);
        $is_valid_private_key = self::is_valid_key("pri", $settings["private_key"]);

        $public_key_message = "";
        if($is_valid_public_key)
            $public_key_message = __("Valid Picatcha Public Key.", "gravityformspicatcha");
        else if(!empty($settings["public_key"]))
            $public_key_message = __("Invalid Picatcha Public Key.", "gravityformspicatcha");

        $private_key_message = "";
        if($is_valid_private_key)
            $private_key_message = __("Valid Picatcha Private Key.", "gravityformspicatcha");
        else if(!empty($settings["private_key"]))
            $private_key_message = __("Invalid Picatcha Private Key.", "gravityformspicatcha");

        ?>
        <style>
            .valid_credentials{color:green;}
            .invalid_credentials{color:red;}
            .size-1{width:400px;}
        </style>

        <form method="POST">
            <?php wp_nonce_field("update_picatcha", "update_picatcha") ?>
            <h3><?php _e("Picatcha Settings", "gravityformspicatcha"); ?></h3>
            <p><?php _e("Picatcha&apos;s image CAPTCHA system provides better security and better user experience for your forms than existing text CAPTCHAs. If you don't have a Picatcha account, <a href='http://www.picatcha.com/signup'>sign up for free here</a>.", "gravityformspicaptcha") ?></p>
            <input type="hidden" name="gforms_picatcha" value="1">
            <div class="wrap">
                <table class="form-table">
                    <tr valign="top">
                      <th scope="row">
                        <label for="picatcha_public_key"><?php _e("Public Key", "gravityformspicatcha") ?></label>
                      </th>
                      <td>
                        <input type="text" id="picatcha_public_key" name="picatcha_public_key" value="<?php echo $settings["public_key"]; ?>" class="size-1" />
                        <img src="<?php echo self::get_base_url() ?>/images/<?php echo $is_valid_public_key ? "tick.png" : "stop.png" ?>" border="0" alt="<?php $public_key_message ?>" title="<?php echo $public_key_message ?>" style="display:<?php echo empty($public_key_message) ? 'none;' : 'inline;' ?>" />
                      </td>
                    </tr>
                    <tr>
                      <th scope="row">
                        <label for="picatcha_private_key"><?php _e("Private Key", "gravityformspicatcha") ?></label>
                      </th>
                      <td>
                        <input type="text" id="picatcha_private_key" name="picatcha_private_key" value="<?php echo $settings["private_key"]?>" class="size-1" />
                        <img src="<?php echo self::get_base_url() ?>/images/<?php echo $is_valid_private_key ? "tick.png" : "stop.png" ?>" border="0" alt="<?php $private_key_message ?>" title="<?php echo $private_key_message ?>" style="display:<?php echo empty($private_key_message) ? 'none;' : 'inline;' ?>" />
                      </td>
                    </tr>
                </table>
            </div>

            <p class="submit">
                <input type="Submit" value="<?php _e("Save Settings", "gravityformspicatcha") ?>" class="button-primary picatcha_settings_savebutton"/>
            </p>

        </form>

        <form action="" method="post">
            <?php wp_nonce_field("uninstall_picatcha", "uninstall_picatcha") ?>
            <?php
            if(GFCommon::current_user_can_any("gravityforms_base_uninstall")){ ?>
                <div class="hr-divider"></div>

                <h3><?php _e("Uninstall Picatcha Add-On", "gravityformspicatcha") ?></h3>
                <div class="delete-alert alert_red">
                    <h3><?php _e("Warning", "gravityforms") ?></h3>
                    <p><?php _e("Warning! This operation deletes ALL Picatcha settings.", "gravityformspicatcha") ?></p>
                    <input type="submit" name="uninstall" value="<?php _e("Uninstall Picatcha Add-On", "gravityformspicatcha") ?>" class="button" onclick="return confirm('<?php _e("Warning! ALL Picatcha settings will be deleted. This cannot be undone. \'OK\' to delete, \'Cancel\' to stop", "gravityformspicatcha") ?>'); "/>
                </div>

            <?php
            } ?>
        </form>
        <?php
    }

    public static function add_permissions(){
        global $wp_roles;
        $wp_roles->add_cap("administrator", "gravityforms_picatcha");
        $wp_roles->add_cap("administrator", "gravityforms_picatcha_uninstall");
    }

    //Target of Member plugin filter. Provides the plugin with Gravity Forms lists of capabilities
    public static function members_get_capabilities( $caps ) {
        return array_merge($caps, array("gravityforms_picatcha", "gravityforms_picatcha_uninstall"));
    }

    public static function uninstall(){

        if(!GFPicatcha::has_access("gravityforms_picatcha_uninstall"))
            die(__("You don't have adequate permission to uninstall Picatcha Add-On.", "gravityformspicatcha"));

        //removing options
        delete_option("gf_picatcha_settings");

        //Deactivating plugin
        $plugin = "gravityformspicatcha/picatcha.php";
        deactivate_plugins($plugin);
        update_option('recently_activated', array($plugin => time()) + (array)get_option('recently_activated'));
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
    protected function get_base_url(){
        return plugins_url(null, __FILE__);
    }

    //Returns the physical path of the plugin's root folder
    protected function get_base_path(){
        $folder = picatchaname(dirname(__FILE__));
        return WP_PLUGIN_DIR . "/" . $folder;
    }

}
?>
