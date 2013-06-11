<?php
/*
Plugin Name: Gravity Forms AWeber Add-On
Plugin URI: http://www.gravityforms.com
Description: Integrates Gravity Forms with AWeber allowing form submissions to be automatically sent to your AWeber account
Version: 1.4
Author: rocketgenius
Author URI: http://www.rocketgenius.com

------------------------------------------------------------------------
Copyright 2009 rocketgenius

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

add_action('init',  array('GFAWeber', 'init'));

register_activation_hook( __FILE__, array("GFAWeber", "add_permissions"));

class GFAWeber {

    private static $path = "gravityformsaweber/aweber.php";
    private static $url = "http://www.gravityforms.com";
    private static $slug = "gravityformsaweber";
    private static $version = "1.4";
    private static $min_gravityforms_version = "1.5";
    private static $supported_fields = array("checkbox", "radio", "select", "text", "website", "textarea", "email", "hidden", "number", "phone", "multiselect", "post_title",
		                            "post_tags", "post_custom_field", "post_content", "post_excerpt");


    //Plugin starting point. Will load appropriate files
    public static function init(){
    	//supports logging
		add_filter("gform_logging_supported", array("GFAWeber", "set_logging_supported"));

        if(RG_CURRENT_PAGE == "plugins.php"){
            //loading translations
            load_plugin_textdomain('gravityformsaweber', FALSE, '/gravityformsaweber/languages' );

            add_action('after_plugin_row_' . self::$path, array('GFAWeber', 'plugin_row') );

            //force new remote request for version info on the plugin page
            self::flush_version_info();
        }

        if(!self::is_gravityforms_supported()){
           return;
        }

        if(is_admin()){
            //loading translations
            load_plugin_textdomain('gravityformsaweber', FALSE, '/gravityformsaweber/languages' );

            add_filter("transient_update_plugins", array('GFAWeber', 'check_update'));
            add_filter("site_transient_update_plugins", array('GFAWeber', 'check_update'));

            add_action('install_plugins_pre_plugin-information', array('GFAWeber', 'display_changelog'));

            // paypal plugin integration hooks
            add_action("gform_paypal_action_fields", array("GFAWeber", "add_paypal_settings"), 10, 2);
            add_filter("gform_paypal_save_config", array("GFAWeber", "save_paypal_settings"));

            //creates a new Settings page on Gravity Forms' settings screen
            if(self::has_access("gravityforms_aweber")){
                RGForms::add_settings_page("AWeber", array("GFAWeber", "settings_page"), self::get_base_url() . "/images/aweber_wordpress_icon_32.png");
            }
        }
        else{
            // ManageWP premium update filters
            add_filter( 'mwp_premium_update_notification', array('GFAWeber', 'premium_update_push') );
            add_filter( 'mwp_premium_perform_update', array('GFAWeber', 'premium_update') );
        }

        //integrating with Members plugin
        if(function_exists('members_get_capabilities'))
            add_filter('members_get_capabilities', array("GFAWeber", "members_get_capabilities"));

        //creates the subnav left menu
        add_filter("gform_addon_navigation", array('GFAWeber', 'create_menu'));

        if(self::is_aweber_page()){

            //enqueueing sack for AJAX requests
            wp_enqueue_script(array("sack"));

            //loading data lib
            require_once(self::get_base_path() . "/data.php");

            //loading upgrade lib
            if(!class_exists("RGAWeberUpgrade"))
                require_once("plugin-upgrade.php");

            //loading Gravity Forms tooltips
            require_once(GFCommon::get_base_path() . "/tooltips.php");
            add_filter('gform_tooltips', array('GFAWeber', 'tooltips'));

            //runs the setup when version changes
            self::setup();

         }
         else if(in_array(RG_CURRENT_PAGE, array("admin-ajax.php"))){

            //loading data class
            require_once(self::get_base_path() . "/data.php");

            add_action('wp_ajax_rg_update_feed_active', array('GFAWeber', 'update_feed_active'));
            add_action('wp_ajax_gf_select_aweber_form', array('GFAWeber', 'select_form'));
            add_action('wp_ajax_gf_select_aweber_client', array('GFAWeber', 'select_client'));

        }
        else{
             //handling post submission.
            add_action("gform_post_submission", array('GFAWeber', 'export'), 10, 2);

            //handling paypal fulfillment
            add_action("gform_paypal_fulfillment", array("GFAWeber", "paypal_fulfillment"), 10, 4);
        }
    }


    public static function update_feed_active(){
        check_ajax_referer('rg_update_feed_active','rg_update_feed_active');
        $id = $_POST["feed_id"];
        $feed = GFAWeberData::get_feed($id);
        GFAWeberData::update_feed($id, $feed["form_id"], $_POST["is_active"], $feed["meta"]);
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
        if(!class_exists("RGAWeberUpgrade"))
            require_once("plugin-upgrade.php");

        RGAWeberUpgrade::set_version_info(false);
    }

    public static function plugin_row(){
        if(!self::is_gravityforms_supported()){
            $message = sprintf(__("Gravity Forms " . self::$min_gravityforms_version . " is required. Activate it now or %spurchase it today!%s"), "<a href='http://www.gravityforms.com'>", "</a>");
            RGAWeberUpgrade::display_plugin_message($message, true);
        }
        else{
            $version_info = RGAWeberUpgrade::get_version_info(self::$slug, self::get_key(), self::$version);

            if(!$version_info["is_valid_key"]){
                $new_version = version_compare(self::$version, $version_info["version"], '<') ? __('There is a new version of Gravity Forms AWeber Add-On available.', 'gravityformsaweber') .' <a class="thickbox" title="Gravity Forms AWeber Add-On" href="plugin-install.php?tab=plugin-information&plugin=' . self::$slug . '&TB_iframe=true&width=640&height=808">'. sprintf(__('View version %s Details', 'gravityformsaweber'), $version_info["version"]) . '</a>. ' : '';
                $message = $new_version . sprintf(__('%sRegister%s your copy of Gravity Forms to receive access to automatic upgrades and support. Need a license key? %sPurchase one now%s.', 'gravityformsaweber'), '<a href="admin.php?page=gf_settings">', '</a>', '<a href="http://www.gravityforms.com">', '</a>') . '</div></td>';
                RGAWeberUpgrade::display_plugin_message($message);
            }
        }
    }

    //Displays current version details on Plugin's page
    public static function display_changelog(){
        if($_REQUEST["plugin"] != self::$slug)
            return;

        //loading upgrade lib
        if(!class_exists("RGAWeberUpgrade"))
            require_once("plugin-upgrade.php");

        RGAWeberUpgrade::display_changelog(self::$slug, self::get_key(), self::$version);
    }

    public static function check_update($update_plugins_option){
        if(!class_exists("RGAWeberUpgrade"))
            require_once("plugin-upgrade.php");

        return RGAWeberUpgrade::check_update(self::$path, self::$slug, self::$url, self::$slug, self::get_key(), self::$version, $update_plugins_option);
    }

    private static function get_key(){
        if(self::is_gravityforms_supported())
            return GFCommon::get_key();
        else
            return "";
    }
    //---------------------------------------------------------------------------------------

    //Returns true if the current page is an Feed pages. Returns false if not
    private static function is_aweber_page(){
        $current_page = trim(strtolower(RGForms::get("page")));
        $aweber_pages = array("gf_aweber");

        return in_array($current_page, $aweber_pages);

    }


    //Creates or updates database tables. Will only run when version changes
    private static function setup(){

        if(get_option("gf_aweber_version") != self::$version)
            GFAWeberData::update_table();

        update_option("gf_aweber_version", self::$version);
    }

    //Adds feed tooltips to the list of tooltips
    public static function tooltips($tooltips){
        $aweber_tooltips = array(
            "aweber_client" => "<h6>" . __("Account", "gravityformsaweber") . "</h6>" . __("Select the AWeber account you would like to add your contacts to.", "gravityformsaweber"),
            "aweber_contact_list" => "<h6>" . __("Contact List", "gravityformsaweber") . "</h6>" . __("Select the AWeber list you would like to add your contacts to.", "gravityformsaweber"),
            "aweber_gravity_form" => "<h6>" . __("Gravity Form", "gravityformsaweber") . "</h6>" . __("Select the Gravity Form you would like to integrate with AWeber. Contacts generated by this form will be automatically added to your AWeber account.", "gravityformsaweber"),
            "aweber_map_fields" => "<h6>" . __("Map Fields", "gravityformsaweber") . "</h6>" . __("Associate your AWeber fields to the appropriate Gravity Form fields by selecting the appropriate form field from the list.", "gravityformsaweber"),
            "aweber_optin_condition" => "<h6>" . __("Opt-In Condition", "gravityformsaweber") . "</h6>" . __("When the opt-in condition is enabled, form submissions will only be exported to AWeber when the condition is met. When disabled all form submissions will be exported.", "gravityformsaweber")
        );
        return array_merge($tooltips, $aweber_tooltips);
    }

    //Creates AWeber left nav menu under Forms
    public static function create_menu($menus){

        // Adding submenu if user has access
        $permission = self::has_access("gravityforms_aweber");
        if(!empty($permission))
            $menus[] = array("name" => "gf_aweber", "label" => __("AWeber", "gravityformsaweber"), "callback" =>  array("GFAWeber", "aweber_page"), "permission" => $permission);

        return $menus;
    }

    public static function settings_page(){

        if(!class_exists("RGAWeberUpgrade"))
            require_once("plugin-upgrade.php");

        $settings = get_option("gf_aweber_settings");

        if(isset($_POST["uninstall"])){
            check_admin_referer("uninstall", "gf_aweber_uninstall");
            self::uninstall();

            ?>
            <div class="updated fade" style="padding:20px;"><?php _e(sprintf("Gravity Forms AWeber Add-On have been successfully uninstalled. It can be re-activated from the %splugins page%s.", "<a href='plugins.php'>","</a>"), "gravityformsaweber")?></div>
            <?php
            return;
        }
        else if(isset($_POST["gf_aweber_submit"])){
            check_admin_referer("update", "gf_aweber_update");
            $gf_aweber_api_credentials = $_POST["gf_aweber_api_credentials"];
            $current_api_credentials = $settings["api_credentials"];
            if($current_api_credentials != $gf_aweber_api_credentials)
            {
                $aweber_token = self::get_aweber_tokens($gf_aweber_api_credentials);
                $settings = array("api_credentials" => $gf_aweber_api_credentials, "access_token"=>$aweber_token["token"], "access_token_secret"=>$aweber_token["secret"]);
                update_option("gf_aweber_settings", $settings);
            }

            $is_valid = self::is_valid_key();

        }
        else{
            $is_valid = self::is_valid_key();
        }

        $message = "";
        if($is_valid)
        {
            $message = "Valid API Credentials.";
        }
        else
        {
            $message = "Invalid API Credentials. Please try another.";
        }

        ?>
        <style>
            .valid_credentials{color:green;}
            .invalid_credentials{color:red;}
            .size-1{width:400px;}
        </style>

        <form method="post" action="">
            <?php wp_nonce_field("update", "gf_aweber_update") ?>

            <h3><?php _e("AWeber Account Information", "gravityformsaweber") ?></h3>
            <p style="text-align: left;">
                <?php _e(sprintf("AWeber is an email marketing software for designers and their clients. Use Gravity Forms to collect customer information and automatically add them to your client's AWeber subscription list. If you don't have a AWeber account, you can %ssign up for one here%s", "<a href='http://www.aweber.com' target='_blank'>" , "</a>"), "gravityformsaweber") ?>
            </p>

            <p style="text-align: left;">
                <a onclick="window.open(this.href,'','resizable=yes,location=no,width=750,height=525,status'); return false" href="https://auth.aweber.com/1.0/oauth/authorize_app/2ad0d7d5"><?php _e("Click here to retrieve your Authorization code", "gravityformsaweber") ?></a>.
                </br>
                <?php _e("You will need to log in to your AWeber account. Upon a successful login, a string will be returned. Copy the whole string and paste into the text box below.", "gravityformsaweber") ?>
            </p>

            <table class="form-table">
                <tr>
                    <th scope="row"><label for="gf_aweber_username"><?php _e("Authorization code", "gravityformsaweber"); ?></label> </th>
                    <td width="88%">
                        <input type="text" class="size-1" id="gf_aweber_api_credentials" name="gf_aweber_api_credentials" value="<?php echo esc_attr($settings["api_credentials"]) ?>" />
                        <img src="<?php echo self::get_base_url() ?>/images/<?php echo $is_valid ? "tick.png" : "stop.png" ?>" border="0" alt="<?php $message ?>" title="<?php echo $message ?>" style="display:<?php echo empty($message) ? 'none;' : 'inline;' ?>" />
                        <br/>
                        <small><?php _e("You can find your unique Authorization code by clicking on the link above and login into your AWeber account.", "gravityformsaweber") ?></small>
                    </td>
                </tr>

                <tr>
                    <td colspan="2" ><input type="submit" name="gf_aweber_submit" class="button-primary" value="<?php _e("Save Settings", "gravityformsaweber") ?>" /></td>
                </tr>

            </table>
            <div>

            </div>
        </form>

         <form action="" method="post">
            <?php wp_nonce_field("uninstall", "gf_aweber_uninstall") ?>
            <?php if(GFCommon::current_user_can_any("gravityforms_aweber_uninstall")){ ?>
                <div class="hr-divider"></div>

                <h3><?php _e("Uninstall AWeber Add-On", "gravityformsaweber") ?></h3>
                <div class="delete-alert"><?php _e("Warning! This operation deletes ALL AWeber Feeds.", "gravityformsaweber") ?>
                    <?php
                    $uninstall_button = '<input type="submit" name="uninstall" value="' . __("Uninstall AWeber Add-On", "gravityformsaweber") . '" class="button" onclick="return confirm(\'' . __("Warning! ALL AWeber Feeds will be deleted. This cannot be undone. \'OK\' to delete, \'Cancel\' to stop", "gravityformsaweber") . '\');"/>';
                    echo apply_filters("gform_aweber_uninstall_button", $uninstall_button);
                    ?>
                </div>
            <?php } ?>
        </form>

        <?php
    }

    public static function aweber_page(){
        $view = rgar($_GET, "view");
        if($view == "edit")
            self::edit_page();
        else
            self::list_page();
    }

    //Displays the aweber feeds list page
    private static function list_page(){
        if(!self::is_gravityforms_supported()){
            die(__(sprintf("AWeber Add-On requires Gravity Forms %s. Upgrade automatically on the %sPlugin page%s.", self::$min_gravityforms_version, "<a href='plugins.php'>", "</a>"), "gravityformsaweber"));
        }

        if(rgpost("action") == "delete"){
            check_admin_referer("list_action", "gf_aweber_list");

            $id = absint($_POST["action_argument"]);
            GFAWeberData::delete_feed($id);
            ?>
            <div class="updated fade" style="padding:6px"><?php _e("Feed deleted.", "gravityformsaweber") ?></div>
            <?php
        }
        else if (!empty($_POST["bulk_action"])){
            check_admin_referer("list_action", "gf_aweber_list");
            $selected_feeds = $_POST["feed"];
            if(is_array($selected_feeds)){
                foreach($selected_feeds as $feed_id)
                    GFAWeberData::delete_feed($feed_id);
            }
            ?>
            <div class="updated fade" style="padding:6px"><?php _e("Feeds deleted.", "gravityformsaweber") ?></div>
            <?php
        }

        ?>
        <div class="wrap">
            <img alt="<?php _e("AWeber Feeds", "gravityformsaweber") ?>" src="<?php echo self::get_base_url()?>/images/aweber_wordpress_icon_32.png" style="float:left; margin:15px 7px 0 0;"/>
            <h2><?php _e("AWeber Feeds", "gravityformsaweber"); ?>
            <a class="button add-new-h2" href="admin.php?page=gf_aweber&view=edit&id=0"><?php _e("Add New", "gravityformsaweber") ?></a>
            </h2>

            <form id="feed_form" method="post">
                <?php wp_nonce_field('list_action', 'gf_aweber_list') ?>
                <input type="hidden" id="action" name="action"/>
                <input type="hidden" id="action_argument" name="action_argument"/>

                <div class="tablenav">
                    <div class="alignleft actions" style="padding:8px 0 7px 0">
                        <label class="hidden" for="bulk_action"><?php _e("Bulk action", "gravityformsaweber") ?></label>
                        <select name="bulk_action" id="bulk_action">
                            <option value=''> <?php _e("Bulk action", "gravityformsaweber") ?> </option>
                            <option value='delete'><?php _e("Delete", "gravityformsaweber") ?></option>
                        </select>
                        <?php
                        echo '<input type="submit" class="button" value="' . __("Apply", "gravityformsaweber") . '" onclick="if( jQuery(\'#bulk_action\').val() == \'delete\' && !confirm(\'' . __("Delete selected feeds? ", "gravityformsaweber") . __("\'Cancel\' to stop, \'OK\' to delete.", "gravityformsaweber") .'\')) { return false; } return true;"/>';
                        ?>
                    </div>
                </div>
                <table class="widefat fixed" cellspacing="0">
                    <thead>
                        <tr>
                            <th scope="col" id="cb" class="manage-column column-cb check-column" style=""><input type="checkbox" /></th>
                            <th scope="col" id="active" class="manage-column check-column"></th>
                            <th scope="col" class="manage-column"><?php _e("Form", "gravityformsaweber") ?></th>
                            <th scope="col" class="manage-column"><?php _e("AWeber Account", "gravityformsaweber") ?></th>
                            <th scope="col" class="manage-column"><?php _e("AWeber List", "gravityformsaweber") ?></th>
                        </tr>
                    </thead>

                    <tfoot>
                        <tr>
                            <th scope="col" id="cb" class="manage-column column-cb check-column" style=""><input type="checkbox" /></th>
                            <th scope="col" id="active" class="manage-column check-column"></th>
                            <th scope="col" class="manage-column"><?php _e("Form", "gravityformsaweber") ?></th>
                            <th scope="col" class="manage-column"><?php _e("AWeber Account", "gravityformsaweber") ?></th>
                            <th scope="col" class="manage-column"><?php _e("AWeber List", "gravityformsaweber") ?></th>
                        </tr>
                    </tfoot>

                    <tbody class="list:user user-list">
                        <?php

                        $settings = GFAWeberData::get_feeds();
                        if(is_array($settings) && sizeof($settings) > 0){
                            foreach($settings as $setting){
                                ?>
                                <tr valign="top">
                                    <th scope="row" class="check-column"><input type="checkbox" name="feed[]" value="<?php echo $setting["id"] ?>"/></th>
                                    <td><img src="<?php echo self::get_base_url() ?>/images/active<?php echo intval($setting["is_active"]) ?>.png" alt="<?php echo $setting["is_active"] ? __("Active", "gravityformsaweber") : __("Inactive", "gravityformsaweber");?>" title="<?php echo $setting["is_active"] ? __("Active", "gravityformsaweber") : __("Inactive", "gravityformsaweber");?>" onclick="ToggleActive(this, <?php echo $setting['id'] ?>); " /></td>
                                    <td class="column-title">
                                        <a href="admin.php?page=gf_aweber&view=edit&id=<?php echo $setting["id"] ?>" title="<?php _e("Edit", "gravityformsaweber") ?>"><?php echo $setting["form_title"] ?></a>
                                        <div class="row-actions">
                                            <span class="edit">
                                            <a title="Edit this setting" href="admin.php?page=gf_aweber&view=edit&id=<?php echo $setting["id"] ?>" title="<?php _e("Edit", "gravityformsaweber") ?>"><?php _e("Edit", "gravityformsaweber") ?></a>
                                            |
                                            </span>

                                            <span class="edit">
                                            <a title="<?php _e("Delete", "gravityformsaweber") ?>" href="javascript: if(confirm('<?php _e("Delete this feed? ", "gravityformsaweber") ?> <?php _e("\'Cancel\' to stop, \'OK\' to delete.", "gravityformsaweber") ?>')){ DeleteSetting(<?php echo $setting["id"] ?>);}"><?php _e("Delete", "gravityformsaweber")?></a>

                                            </span>
                                        </div>
                                    </td>
                                    <td><?php echo $setting["meta"]["client_name"] ?></td>
                                    <td><?php echo $setting["meta"]["contact_list_name"] ?></td>
                                </tr>
                                <?php
                            }
                        }
                        else if(self::is_valid_key()){
                            ?>
                            <tr>
                                <td colspan="5" style="padding:20px;">
                                    <?php _e(sprintf("You don't have any AWeber feeds configured. Let's go %screate one%s!", '<a href="admin.php?page=gf_aweber&view=edit&id=0">', "</a>"), "gravityformsaweber"); ?>
                                </td>
                            </tr>
                            <?php
                        }
                        else{
                            ?>
                            <tr>
                                <td colspan="5" style="padding:20px;">
                                    <?php _e(sprintf("To get started, please configure your %sAWeber Settings%s.", '<a href="admin.php?page=gf_settings&addon=AWeber">', "</a>"), "gravityformsaweber"); ?>
                                </td>
                            </tr>
                            <?php
                        }
                        ?>
                    </tbody>
                </table>
            </form>
        </div>
        <script type="text/javascript">
            function DeleteSetting(id){
                jQuery("#action_argument").val(id);
                jQuery("#action").val("delete");
                jQuery("#feed_form")[0].submit();
            }
            function ToggleActive(img, feed_id){
                var is_active = img.src.indexOf("active1.png") >=0
                if(is_active){
                    img.src = img.src.replace("active1.png", "active0.png");
                    jQuery(img).attr('title','<?php _e("Inactive", "gravityformsaweber") ?>').attr('alt', '<?php _e("Inactive", "gravityformsaweber") ?>');
                }
                else{
                    img.src = img.src.replace("active0.png", "active1.png");
                    jQuery(img).attr('title','<?php _e("Active", "gravityformsaweber") ?>').attr('alt', '<?php _e("Active", "gravityformsaweber") ?>');
                }

                var mysack = new sack( ajaxurl );
                mysack.execute = 1;
                mysack.method = 'POST';
                mysack.setVar( "action", "rg_update_feed_active" );
                mysack.setVar( "rg_update_feed_active", "<?php echo wp_create_nonce("rg_update_feed_active") ?>" );
                mysack.setVar( "feed_id", feed_id );
                mysack.setVar( "is_active", is_active ? 0 : 1 );
                mysack.encVar( "cookie", document.cookie, false );
                mysack.onError = function() { alert('<?php _e("Ajax error while updating feed", "gravityformsaweber" ) ?>' )};
                mysack.runAJAX();

                return true;
            }
        </script>
        <?php
    }

    public static function edit_page(){
        ?>
        <style>
            .aweber_col_heading{padding-bottom:2px; border-bottom: 1px solid #ccc; font-weight: bold;}
            .aweber_field_cell {padding: 6px 17px 0 0; margin-right:15px;}
            .left_header{float:left; width:200px;}
            .margin_vertical_10{margin: 10px 0;}
            #aweber_resubscribe_warning{padding-left: 5px; padding-bottom:4px; font-size: 10px;}
            .gfield_required{color:red;}
            .feeds_validation_error{ background-color:#FFDFDF;}
            .feeds_validation_error td{ margin-top:4px; margin-bottom:6px; padding-top:6px; padding-bottom:6px; border-top:1px dotted #C89797; border-bottom:1px dotted #C89797}
        </style>
        <script type="text/javascript">
            var form = Array();
        </script>
        <div class="wrap">
            <img alt="<?php _e("AWeber", "gravityformsaweber") ?>" style="margin: 15px 7px 0pt 0pt; float: left;" src="<?php echo self::get_base_url() ?>/images/aweber_wordpress_icon_32.png"/>
            <h2><?php _e("AWeber Feed", "gravityformsaweber") ?></h2>

        <?php

        //ensures valid credentials were entered in the settings page
        if(!self::is_valid_key()){
            ?>
            <div style='padding:15px' class='error'><?php echo sprintf(__("We are unable to login to AWeber with the provided Authorization code. Please make sure you have entered a valid Authorization code in the %sSettings Page%s", "gravityformsaweber"), "<a href='?page=gf_settings&addon=AWeber'>", "</a>"); ?></div>
            <?php
            return;
        }

        //getting setting id (0 when creating a new one)
        $id = !empty($_POST["aweber_setting_id"]) ? $_POST["aweber_setting_id"] : absint($_GET["id"]);
        $config = empty($id) ? array("is_active" => true) : GFAWeberData::get_feed($id);
        if(!isset($config["meta"]))
            $config["meta"] = array();

        //updating meta information
        if(rgpost("gf_aweber_submit")){

            list($client_id, $client_name) = rgexplode("|:|", stripslashes($_POST["gf_aweber_client"]), 2);
            $config["meta"]["client_id"] = $client_id;
            $config["meta"]["client_name"] = $client_name;

            list($list_id, $list_name) = rgexplode("|:|", stripslashes($_POST["gf_aweber_list"]), 2);
            $config["meta"]["contact_list_id"] = $list_id;
            $config["meta"]["contact_list_name"] = $list_name;
            $config["form_id"] = absint($_POST["gf_aweber_form"]);

            $merge_vars = self::get_custom_fields($list_id,$client_id);
            $field_map = array();

            foreach($merge_vars as $var){
                $field_name = "aweber_map_field_" . self::get_field_key($var);
                $mapped_field = stripslashes($_POST[$field_name]);
                if(!empty($mapped_field))
                    $field_map[self::get_field_key($var)] = $mapped_field;
            }
            $config["meta"]["field_map"] = $field_map;

            $config["meta"]["optin_enabled"] = rgpost("aweber_optin_enable") ? true : false;
            if($config["meta"]["optin_enabled"]){
                $config["meta"]["optin_field_id"] = rgpost("aweber_optin_field_id");
                $config["meta"]["optin_operator"] = rgpost("aweber_optin_operator");
                $config["meta"]["optin_value"] = rgpost("aweber_optin_value");
            }

            $is_valid = !empty($field_map["email"]);
            if($is_valid){
                $id = GFAWeberData::update_feed($id, $config["form_id"], $config["is_active"], $config["meta"]);
                ?>
                <div class="updated fade" style="padding:6px"><?php echo sprintf(__("Feed Updated. %sback to list%s", "gravityformsaweber"), "<a href='?page=gf_aweber'>", "</a>") ?></div>
                <input type="hidden" name="aweber_setting_id" value="<?php echo $id ?>"/>
                <?php
            }
            else{
                ?>
                <div class="error" style="padding:6px"><?php echo __("Feed could not be updated. Please enter all required information below.", "gravityformsaweber") ?></div>
                <?php
            }
        }

        if(empty($merge_vars)){
            //getting merge vars from selected list (if one was selected)
            $merge_vars = empty($config["meta"]["contact_list_id"]) ? array() : self::get_custom_fields($config["meta"]["contact_list_id"],$config["meta"]["client_id"]);
        }
        ?>
        <form method="post" action="">
            <input type="hidden" name="aweber_setting_id" value="<?php echo $id ?>"/>
            <div class="margin_vertical_10">

                <?php

                $aweber = self::get_aweber_object();
                self::log_debug("Getting account list.");
                $accounts = $aweber->loadFromUrl('https://api.aweber.com/1.0/accounts');

                if (!$accounts){
                    self::log_debug("Could not get account list from AWeber.");
                    _e("Could not get account list from AWeber.", "gravityformsaweber");
                }
                else{
                	self::log_debug("Account list successfully retrieved.");

                    if($accounts->data["total_size"] == 1)
                    {
                        $client_id = $accounts->data["entries"]["0"]["id"];
                        ?>
                        <input id="gf_aweber_client" type="hidden" name="gf_aweber_client" value="<?php echo esc_attr($client_id) . "|:|" . esc_attr($client_id) ?>">
                        <?php
                    }
                    else
                    {

                         $client_id = isset($config["meta"]["client_id"]) ? $config["meta"]["client_id"] : "";
                        ?>
                        <label for="gf_aweber_client" class="left_header"><?php _e("Account", "gravityformsaweber"); ?> <?php gform_tooltip("aweber_client") ?></label>
                        <select id="gf_aweber_client" name="gf_aweber_client" onchange="SelectClient(jQuery(this).val());">
                            <option value=""><?php _e("Select a Client", "gravityformsaweber"); ?></option>
                        <?php

                        foreach ($accounts as $client){
                            $selected = $client->id == $client_id ? "selected='selected'" : "";
                            ?>
                            <option value="<?php echo esc_attr($client->id) . "|:|" . esc_attr($client->id) ?>" <?php echo $selected ?>><?php echo esc_html($client->id) ?></option>
                            <?php
                        }

                        ?>
                        </select>
                        &nbsp;&nbsp;
                        <img src="<?php echo self::get_base_url() ?>/images/loading.gif" id="aweber_wait_client" style="display: none;"/>
                    <?php
                    }
                }
                ?>
            </div>

            <div id="gf_aweber_list_container" class="margin_vertical_10" <?php echo empty($client_id) ? "style='display:none;'" : "" ?>>
                <label for="gf_aweber_list" class="left_header"><?php _e("Contact list", "gravityformsaweber"); ?> <?php gform_tooltip("aweber_contact_list") ?></label>

                <select id="gf_aweber_list" name="gf_aweber_list" onchange="SelectList(jQuery(this).val());">
                    <?php
                    if(!empty($client_id)){
                        $lists = self::get_lists($client_id, rgar($config["meta"],"contact_list_id"));
                        echo $lists;
                    }
                    ?>
                </select>
            </div>

            <div id="aweber_form_container" valign="top" class="margin_vertical_10" <?php echo empty($client_id) || empty($config["meta"]["contact_list_id"]) ? "style='display:none;'" : "" ?>>
                <label for="gf_aweber_form" class="left_header"><?php _e("Gravity Form", "gravityformsaweber"); ?> <?php gform_tooltip("aweber_gravity_form") ?></label>

                <select id="gf_aweber_form" name="gf_aweber_form" onchange="SelectForm(jQuery('#gf_aweber_list').val(), jQuery(this).val(),jQuery('#gf_aweber_client').val());">
                <option value=""><?php _e("Select a Form", "gravityformsaweber"); ?></option>
                <?php
                $forms = RGFormsModel::get_forms();
                foreach($forms as $form){
                    $selected = absint($form->id) == rgar($config,"form_id") ? "selected='selected'" : "";
                    ?>
                    <option value="<?php echo absint($form->id) ?>"  <?php echo $selected ?>><?php echo esc_html($form->title) ?></option>
                    <?php
                }
                ?>
                </select>
                &nbsp;&nbsp;
                <img src="<?php echo self::get_base_url() ?>/images/loading.gif" id="aweber_wait_form" style="display: none;"/>
            </div>

            <div id="aweber_field_group" valign="top" <?php echo empty($client_id) || empty($config["meta"]["contact_list_id"]) || empty($config["form_id"]) ? "style='display:none;'" : "" ?>>

                <div id="aweber_field_container" valign="top" class="margin_vertical_10" >
                    <label for="aweber_fields" class="left_header"><?php _e("Map Fields", "gravityformsaweber"); ?> <?php gform_tooltip("aweber_map_fields") ?></label>

                    <div id="aweber_field_list">
                    <?php
                    if(!empty($config["form_id"])){

                        //getting list of all AWeber merge variables for the selected contact list
                        if(empty($merge_vars))
                        {
                        	self::log_debug("Retrieving Merge_Vars for list " . $list_id);
                            $merge_vars = $api->listMergeVars($list_id);
                            self::log_debug("Merge_Vars retrieved: " . print_r($merge_vars,true));
						}

                        //getting field map UI
                        echo self::get_field_mapping($config, $config["form_id"], $merge_vars);

                        //getting list of selection fields to be used by the optin
                        $form_meta = RGFormsModel::get_form_meta($config["form_id"]);
                    }
                    ?>
                    </div>
                </div>

                <div id="aweber_optin_container" valign="top" class="margin_vertical_10">
                    <label for="aweber_optin" class="left_header"><?php _e("Opt-In Condition", "gravityformsaweber"); ?> <?php gform_tooltip("aweber_optin_condition") ?></label>
                    <div id="aweber_optin">
                        <table>
                            <tr>
                                <td>
                                    <input type="checkbox" id="aweber_optin_enable" name="aweber_optin_enable" value="1" onclick="if(this.checked){jQuery('#aweber_optin_condition_field_container').show('slow');} else{jQuery('#aweber_optin_condition_field_container').hide('slow');}" <?php echo rgar($config["meta"],"optin_enabled") ? "checked='checked'" : ""?>/>
                                    <label for="aweber_optin_enable"><?php _e("Enable", "gravityformsaweber"); ?></label>
                                </td>
                            </tr>
                            <tr>
                                <td>
                                    <div id="aweber_optin_condition_field_container" <?php echo !rgar($config["meta"],"optin_enabled") ? "style='display:none'" : ""?>>
                                        <div id="aweber_optin_condition_fields" style="display:none">
                                            <?php _e("Export to AWeber if ", "gravityformsaweber") ?>

                                            <select id="aweber_optin_field_id" name="aweber_optin_field_id" class='optin_select' onchange='jQuery("#aweber_optin_value_container").html(GetFieldValues(jQuery(this).val(), "", 20));'></select>
                                            <select id="aweber_optin_operator" name="aweber_optin_operator" >
                                                <option value="is" <?php echo rgar($config["meta"],"optin_operator") == "is" ? "selected='selected'" : "" ?>><?php _e("is", "gravityformsaweber") ?></option>
                                                <option value="isnot" <?php echo rgar($config["meta"],"optin_operator") == "isnot" ? "selected='selected'" : "" ?>><?php _e("is not", "gravityformsaweber") ?></option>
                                                <option value=">" <?php echo rgar($config['meta'], 'optin_operator') == ">" ? "selected='selected'" : "" ?>><?php _e("greater than", "gravityformsaweber") ?></option>
                                                <option value="<" <?php echo rgar($config['meta'], 'optin_operator') == "<" ? "selected='selected'" : "" ?>><?php _e("less than", "gravityformsaweber") ?></option>
                                                <option value="contains" <?php echo rgar($config['meta'], 'optin_operator') == "contains" ? "selected='selected'" : "" ?>><?php _e("contains", "gravityformsaweber") ?></option>
                                                <option value="starts_with" <?php echo rgar($config['meta'], 'optin_operator') == "starts_with" ? "selected='selected'" : "" ?>><?php _e("starts with", "gravityformsaweber") ?></option>
                                                <option value="ends_with" <?php echo rgar($config['meta'], 'optin_operator') == "ends_with" ? "selected='selected'" : "" ?>><?php _e("ends with", "gravityformsaweber") ?></option>
                                            </select>
                                            <div id="aweber_optin_value_container" name="aweber_optin_value_container" style="display:inline;"></div>
                                        </div>
                                        <div id="aweber_optin_condition_message" style="display:none">
                                            <?php _e("To create an Opt-In condition, your form must have a field supported by conditional logic.", "gravityform") ?>
                                        </div>
                                    </div>
                                </td>
                            </tr>
                        </table>
                    </div>

                    <script type="text/javascript">
                        <?php
                        if(!empty($config["form_id"])){
                            ?>
                            //creating Javascript form object
                            form = <?php echo GFCommon::json_encode($form_meta)?>;

                            //initializing drop downs
                            jQuery(document).ready(function(){
                                var selectedField = "<?php echo str_replace('"', '\"', rgar($config["meta"], "optin_field_id")) ?>";
                                var selectedValue = "<?php echo str_replace('"', '\"', rgar($config["meta"], "optin_value")) ?>";
                                SetOptin(selectedField, selectedValue);
                            });
                        <?php
                        }
                        ?>
                    </script>
                </div>

                <div id="aweber_submit_container" class="margin_vertical_10">
                    <input type="submit" name="gf_aweber_submit" value="<?php echo empty($id) ? __("Save", "gravityformsaweber") : __("Update", "gravityformsaweber"); ?>" class="button-primary"/>
                    <input type="button" value="<?php _e("Cancel", "gravityformsaweber"); ?>" class="button" onclick="javascript:document.location='admin.php?page=gf_aweber'" />
                </div>
            </div>
        </form>
        </div>
        <script type="text/javascript">

            function SelectClient(clientId){
                jQuery("#gf_aweber_list_container").slideUp();
                SelectList();

                if(!clientId)
                    return;

                jQuery("#aweber_wait_client").show();

                var mysack = new sack(ajaxurl);
                mysack.execute = 1;
                mysack.method = 'POST';
                mysack.setVar( "action", "gf_select_aweber_client" );
                mysack.setVar( "gf_select_aweber_client", "<?php echo wp_create_nonce("gf_select_aweber_client") ?>" );
                mysack.setVar( "client_id", clientId);
                mysack.encVar( "cookie", document.cookie, false );
                mysack.onError = function() {jQuery("#aweber_wait_client").hide(); alert('<?php _e("Ajax error while selecting an account", "gravityformsaweber") ?>' )};
                mysack.runAJAX();

                return true;
            }

            function EndSelectClient(lists){
                if(lists){

                    jQuery("#gf_aweber_list").html(lists);
                    jQuery("#gf_aweber_list_container").slideDown();

                }
                else{
                    jQuery("#gf_aweber_list_container").slideUp();
                    jQuery("#aweber_list").html("");
                }
                jQuery("#aweber_wait_client").hide();
            }


            function SelectList(listId){

                EndSelectForm("");

                if(listId){
                    jQuery("#aweber_form_container").slideDown();
                    jQuery("#gf_aweber_form").val("");
                }
                else{
                    jQuery("#aweber_form_container").slideUp();
                }

            }

            function SelectForm(listId, formId, clientId){
                if(!formId){
                    jQuery("#aweber_field_group").slideUp();
                    return;
                }

                jQuery("#aweber_wait_form").show();
                jQuery("#aweber_field_group").slideUp();

                var mysack = new sack("<?php bloginfo( 'wpurl' ); ?>/wp-admin/admin-ajax.php" );
                mysack.execute = 1;
                mysack.method = 'POST';
                mysack.setVar( "action", "gf_select_aweber_form" );
                mysack.setVar( "gf_select_aweber_form", "<?php echo wp_create_nonce("gf_select_aweber_form") ?>" );
                mysack.setVar( "list_id", listId);
                mysack.setVar( "form_id", formId);
                mysack.setVar( "client_id", clientId);
                mysack.encVar( "cookie", document.cookie, false );
                mysack.onError = function() {jQuery("#aweber_wait_form").hide(); alert('<?php _e("Ajax error while selecting a form", "gravityformsaweber") ?>' )};
                mysack.runAJAX();

                return true;
            }

            function SetOptin(selectedField, selectedValue){

                //load form fields
                jQuery("#aweber_optin_field_id").html(GetSelectableFields(selectedField, 20));

                var optinConditionField = jQuery("#aweber_optin_field_id").val();

                if(optinConditionField){
                    jQuery("#aweber_optin_condition_message").hide();
                    jQuery("#aweber_optin_condition_fields").show();

                    jQuery("#aweber_optin_value_container").html(GetFieldValues(optinConditionField, selectedValue, 20));
					jQuery("#aweber_optin_value").val(selectedValue);
                }
                else{
                    jQuery("#aweber_optin_condition_message").show();
                    jQuery("#aweber_optin_condition_fields").hide();
                }

            }

            function EndSelectForm(fieldList, form_meta){

                //setting global form object
                form = form_meta;

                if(fieldList){

                    SetOptin("","");

                    jQuery("#aweber_field_list").html(fieldList);
                    jQuery("#aweber_field_group").slideDown();

                }
                else{
                    jQuery("#aweber_field_group").slideUp();
                    jQuery("#aweber_field_list").html("");
                }
                jQuery("#aweber_wait_form").hide();
            }

            function GetFieldValues(fieldId, selectedValue, labelMaxCharacters){
                if(!fieldId)
                    return "";

                var str = "";
                var field = GetFieldById(fieldId);
                if(!field)
                    return "";

                var isAnySelected = false;

                if(field["type"] == "post_category" && field["displayAllCategories"]){
					str += '<?php $dd = wp_dropdown_categories(array("class"=>"optin_select", "orderby"=> "name", "id"=> "aweber_optin_value", "name"=> "aweber_optin_value", "hierarchical"=>true, "hide_empty"=>0, "echo"=>false)); echo str_replace("\n","", str_replace("'","\\'",$dd)); ?>';
				}
				else if(field.choices){
					str += '<select id="aweber_optin_value" name="aweber_optin_value" class="optin_select">'

	                for(var i=0; i<field.choices.length; i++){
	                    var fieldValue = field.choices[i].value ? field.choices[i].value : field.choices[i].text;
	                    var isSelected = fieldValue == selectedValue;
	                    var selected = isSelected ? "selected='selected'" : "";
	                    if(isSelected)
	                        isAnySelected = true;

	                    str += "<option value='" + fieldValue.replace(/'/g, "&#039;") + "' " + selected + ">" + TruncateMiddle(field.choices[i].text, labelMaxCharacters) + "</option>";
	                }

	                if(!isAnySelected && selectedValue){
	                    str += "<option value='" + selectedValue.replace(/'/g, "&#039;") + "' selected='selected'>" + TruncateMiddle(selectedValue, labelMaxCharacters) + "</option>";
	                }
	                str += "</select>";
				}
				else
				{
					selectedValue = selectedValue ? selectedValue.replace(/'/g, "&#039;") : "";
					//create a text field for fields that don't have choices (i.e text, textarea, number, email, etc...)
					str += "<input type='text' placeholder='<?php _e("Enter value", "gravityforms"); ?>' id='aweber_optin_value' name='aweber_optin_value' value='" + selectedValue.replace(/'/g, "&#039;") + "'>";
				}

                return str;
            }

            function GetFieldById(fieldId){
                for(var i=0; i<form.fields.length; i++){
                    if(form.fields[i].id == fieldId)
                        return form.fields[i];
                }
                return null;
            }

            function TruncateMiddle(text, maxCharacters){
                if(text.length <= maxCharacters)
                    return text;
                var middle = parseInt(maxCharacters / 2);
                return text.substr(0, middle) + "..." + text.substr(text.length - middle, middle);
            }

            function GetSelectableFields(selectedFieldId, labelMaxCharacters){
                var str = "";
                var inputType;
                for(var i=0; i<form.fields.length; i++){
                    fieldLabel = form.fields[i].adminLabel ? form.fields[i].adminLabel : form.fields[i].label;
                    inputType = form.fields[i].inputType ? form.fields[i].inputType : form.fields[i].type;
                    if (IsConditionalLogicField(form.fields[i])) {
                        var selected = form.fields[i].id == selectedFieldId ? "selected='selected'" : "";
                        str += "<option value='" + form.fields[i].id + "' " + selected + ">" + TruncateMiddle(fieldLabel, labelMaxCharacters) + "</option>";
                    }
                }
                return str;
            }

            function IsConditionalLogicField(field){
			    inputType = field.inputType ? field.inputType : field.type;
			    var supported_fields = ["checkbox", "radio", "select", "text", "website", "textarea", "email", "hidden", "number", "phone", "multiselect", "post_title",
			                            "post_tags", "post_custom_field", "post_content", "post_excerpt"];

			    var index = jQuery.inArray(inputType, supported_fields);

			    return index >= 0;
			}

        </script>

        <?php

    }

    public static function get_lists($account_id, $list_id = ""){

            $aweber = self::get_aweber_object();
            self::log_debug("Getting account list for drop down.");
            $account = $aweber->loadFromUrl('https://api.aweber.com/1.0/accounts/' . $account_id);
            self::log_debug("Account list retrieved: " . print_r($account,true));
			$str = "<option value=''>" . __("Select a List", "gravityformsaweber") . "</option>";

            foreach($account->lists as $list){
                $selected = $list->id == $list_id ? "selected='selected'" : "";
                $str .= "<option value='" . esc_attr($list->id) . "|:|" . esc_attr($list->name) . "' " . $selected . " >" . esc_html($list->name) . "</option>";
            }

        return $str;
    }

    public static function select_client(){

        check_ajax_referer("gf_select_aweber_client", "gf_select_aweber_client");
        list($client_id, $client_name) =  rgexplode("|:|", $_POST["client_id"], 2);

        $lists = self::get_lists($client_id);

        die("EndSelectClient(\"" . $lists . "\");");
    }

    private static function get_custom_fields($list_id,$account_id){

        $custom_fields = array(array("FieldName" => "Email Address", "Key" => "[email]"), array("FieldName" => "Full Name", "Key" => "[fullname]"));

        $aweber = self::get_aweber_object();
        self::log_debug("Function get_custom_fields: Getting account list for account {$account_id} and list {$list_id}");
        $account = $aweber->loadFromUrl('https://api.aweber.com/1.0/accounts/' . $account_id);
        $list = $account->loadFromUrl("/accounts/{$account_id}/lists/{$list_id}");
        self::log_debug("Account List retrieved: " . print_r($list, true));

        $aweber_custom_fields = $list->custom_fields;

        foreach($aweber_custom_fields as $cf){
            $custom_fields[] = array("FieldName" => $cf->data["name"], "Key" => "[cf_".$cf->data["id"]."]");
        }

        return $custom_fields;

    }

    private static function get_field_key($custom_field){
        $key = str_replace("]", "",str_replace("[", "", $custom_field["Key"]));
        return $key;
    }

    public static function select_form(){

        check_ajax_referer("gf_select_aweber_form", "gf_select_aweber_form");
        $form_id =  intval(rgpost("form_id"));
        list($list_id, $dummy) =  rgexplode("|:|", rgpost("list_id"),2);
        list($account_id, $dummy2) =  rgexplode("|:|", rgpost("client_id"),2);
        $setting_id =  intval(rgpost("setting_id"));

        if(!self::is_valid_key())
            die("EndSelectForm();");

        $custom_fields = self::get_custom_fields($list_id,$account_id);

        //getting configuration
        $config = GFAWeberData::get_feed($setting_id);

        //getting field map UI
        $str = self::get_field_mapping($config, $form_id, $custom_fields);


        //fields meta
        $form = RGFormsModel::get_form_meta($form_id);
        //$fields = $form["fields"];
        die("EndSelectForm(\"$str\", " . GFCommon::json_encode($form) . ");");
    }

    private static function get_field_mapping($config, $form_id, $merge_vars){

        //getting list of all fields for the selected form
        $form_fields = self::get_form_fields($form_id);

        $str = "<table cellpadding='0' cellspacing='0'><tr><td class='aweber_col_heading'>" . __("List Fields", "gravityformsaweber") . "</td><td class='aweber_col_heading'>" . __("Form Fields", "gravityformsaweber") . "</td></tr>";
        foreach($merge_vars as $var){
            $meta = rgar($config, "meta");
            if(!is_array($meta))
                $meta = array("field_map"=>"");

            $selected_field = rgar($meta["field_map"], self::get_field_key($var));
            $required = self::get_field_key($var) == "email" ? "<span class='gfield_required'>*</span> " : "";
            $error_class = self::get_field_key($var) == "email" && empty($selected_field) && !rgempty("gf_aweber_submit") ? " feeds_validation_error" : "";
            $str .= "<tr class='$error_class'><td class='aweber_field_cell'>" . esc_html($var["FieldName"]) . " $required</td><td class='aweber_field_cell'>" . self::get_mapped_field_list(self::get_field_key($var), $selected_field, $form_fields) . "</td></tr>";
        }
        $str .= "</table>";

        return $str;
    }

    public static function get_form_fields($form_id){
        $form = RGFormsModel::get_form_meta($form_id);
        $fields = array();

        //Adding default fields
        array_push($form["fields"],array("id" => "date_created" , "label" => __("Entry Date", "gravityformsaweber")));
        array_push($form["fields"],array("id" => "ip" , "label" => __("User IP", "gravityformsaweber")));
        array_push($form["fields"],array("id" => "source_url" , "label" => __("Source Url", "gravityformsaweber")));

        if(is_array($form["fields"])){
            foreach($form["fields"] as $field){
                if(is_array(rgar($field,"inputs"))){

                    //If this is a name or checkbox field, add full name to the list
                    if(RGFormsModel::get_input_type($field) == "name")
                        $fields[] =  array($field["id"], GFCommon::get_label($field) . " (" . __("Full" , "gravityformsaweber") . ")");
                    else if(RGFormsModel::get_input_type($field) == "checkbox")
                        $fields[] =  array($field["id"], GFCommon::get_label($field));
                        else if(RGFormsModel::get_input_type($field) == "address")
                        $fields[] =  array($field["id"], GFCommon::get_label($field) . " (" . __("Full" , "gravityformsmailchimp") . ")");

                    foreach($field["inputs"] as $input)
                        $fields[] =  array($input["id"], GFCommon::get_label($field, $input["id"]));
                }
                else if(!rgar($field,"displayOnly")){
                    $fields[] =  array($field["id"], GFCommon::get_label($field));
                }
            }
        }
        return $fields;
    }

    private static function get_address($entry, $field_id){
        $street_value = str_replace("  ", " ", trim($entry[$field_id . ".1"]));
        $street2_value = str_replace("  ", " ", trim($entry[$field_id . ".2"]));
        $city_value = str_replace("  ", " ", trim($entry[$field_id . ".3"]));
        $state_value = str_replace("  ", " ", trim($entry[$field_id . ".4"]));
        $zip_value = trim($entry[$field_id . ".5"]);
        $country_value = GFCommon::get_country_code(trim($entry[$field_id . ".6"]));

        $address = $street_value;
        $address .= !empty($address) && !empty($street2_value) ? "  $street2_value" : $street2_value;
        $address .= !empty($address) && (!empty($city_value) || !empty($state_value)) ? ", $city_value," : $city_value;
        $address .= !empty($address) && !empty($city_value) && !empty($state_value) ? "  $state_value" : $state_value;
        $address .= !empty($address) && !empty($zip_value) ? "  $zip_value," : $zip_value;
        $address .= !empty($address) && !empty($country_value) ? "  $country_value" : $country_value;


        return $address;
    }

    public static function get_mapped_field_list($variable_name, $selected_field, $fields){
        $field_name = "aweber_map_field_" . $variable_name;
        $str = "<select name='$field_name' id='$field_name'><option value=''></option>";
        foreach($fields as $field){
            $field_id = $field[0];
            $field_label = esc_html(GFCommon::truncate_middle($field[1], 40));

            $selected = $field_id == $selected_field ? "selected='selected'" : "";
            $str .= "<option value='" . $field_id . "' ". $selected . ">" . $field_label . "</option>";
        }
        $str .= "</select>";
        return $str;
    }

    public static function export($entry, $form, $is_fulfilled = false){

        if(!self::is_valid_key())
            return;

        $paypal_config = self::get_paypal_config($form["id"], $entry);

        //if configured to only subscribe users when payment is received, delay subscription until the payment is received.
        if($paypal_config && rgar($paypal_config["meta"], "delay_aweber_subscription") && !$is_fulfilled){
        	self::log_debug("Subscription delayed pending PayPal payment received for entry " . $entry["id"]);
            return;
        }

        //loading data class
        require_once(self::get_base_path() . "/data.php");

        //getting all active feeds
        $feeds = GFAWeberData::get_feed_by_form($form["id"], true);
        foreach($feeds as $feed){
            //only export if user has opted in
            if(self::is_optin($form, $feed)){
                self::export_feed($entry, $form, $feed);
                self::log_debug("Marking entry " . $entry["id"] . " as subscribed");
                //updating meta to indicate this entry has already been subscribed to Aweber. This will be used to prevent duplicate subscriptions.
        		gform_update_meta($entry["id"], "aweber_is_subscribed", true);
			}
			else{
				self::log_debug("Opt-in condition not met; not subscribing entry " . $entry["id"]);
			}
        }
    }

    public static function export_feed($entry, $form, $feed){

        $email = $entry[$feed["meta"]["field_map"]["email"]];
        $name = "";
        if(!empty($feed["meta"]["field_map"]["fullname"]))
            $name = self::get_name($entry, $feed["meta"]["field_map"]["fullname"]);

        $account_id = $feed["meta"]["client_id"];
        $list_id = $feed["meta"]["contact_list_id"];


        $aweber = self::get_aweber_object();
        self::log_debug("Function export_feed - Getting account lists.");
        $account = $aweber->loadFromUrl('https://api.aweber.com/1.0/accounts/' . $account_id);
        self::log_debug("Function export_feed - Getting list for account {$account_id} with id {$list_id}");
        $list = $account->loadFromUrl("/accounts/{$account_id}/lists/{$list_id}");

        $merge_vars = array('');
        foreach($feed["meta"]["field_map"] as $var_tag => $field_id){

            $field = RGFormsModel::get_field($form, $field_id);
            if($field_id == intval($field_id) && RGFormsModel::get_input_type($field) == "address") //handling full address
                $merge_vars[$var_tag] = self::get_address($entry, $field_id);
            if($field_id == intval($field_id) && RGFormsModel::get_input_type($field) == "name") //handling full name
                $merge_vars[$var_tag] = self::get_name($entry, $field_id);
            else if($var_tag != "email" && $var_tag != "fullname" ) //ignoring email and full name fields as it will be handled separatelly
                $merge_vars[$var_tag] = rgar($entry, $field_id);
        }

        $custom_fields = self::get_custom_fields($list_id,$account_id);
        // removing email and full name from list of custom fields as they are handled separatelly
        unset($custom_fields[0]);
        unset($custom_fields[1]);
        $custom_fields= array_values($custom_fields);

        $list_custom_fields = array();
        foreach($custom_fields as $cf)
        {
            $key = $cf["Key"];
            $key = str_replace("[", "", $key);
            $key = str_replace("]", "", $key);
            $list_custom_fields[$cf["FieldName"]]= $merge_vars[$key];
        }

        $params = array(
            'email' => $email,
            'name' => $name,
            'ad_tracking' => apply_filters("gform_aweber_ad_tracking_{$form["id"]}", apply_filters("gform_aweber_ad_tracking", $form["title"], $entry, $form, $feed), $entry, $form, $feed)
        );


        if(!empty($list_custom_fields))
            $params["custom_fields"] = $list_custom_fields;


        //ad tracking has a max size of 20 characters
        if(strlen($params["ad_tracking"]) > 20)
            $params["ad_tracking"] = substr($params["ad_tracking"], 0, 20);

        try
        {
            $subscribers = $list->subscribers;
            self::log_debug("Creating subscriber: " . print_r($params,true));
            $new_subscriber = $subscribers->create($params);
            self::log_debug("Subscriber created.");
        }
        catch(AWeberAPIException $exc){
        	self::log_error("Unable to create subscriber: {$exc}");
        }

    }

    private static function get_name($entry, $field_id){

        //If field is aweber (one input), simply return full content
        $name = rgar($entry,$field_id);
        if(!empty($name))
            return $name;

        //Complex field (multiple inputs). Join all pieces and create name
        $prefix = trim(rgar($entry,$field_id . ".2"));
        $first = trim(rgar($entry,$field_id . ".3"));
        $last = trim(rgar($entry,$field_id . ".6"));
        $suffix = trim(rgar($entry,$field_id . ".8"));

        $name = $prefix;
        $name .= !empty($name) && !empty($first) ? " $first" : $first;
        $name .= !empty($name) && !empty($last) ? " $last" : $last;
        $name .= !empty($name) && !empty($suffix) ? " $suffix" : $suffix;
        return $name;
    }

    public static function is_optin($form, $settings){
        $config = $settings["meta"];
        $operator = isset($config["optin_operator"]) ? $config["optin_operator"] : "";

        $field = RGFormsModel::get_field($form, rgar($config,"optin_field_id"));
        $field_value = RGFormsModel::get_field_value($field, array());

        if(empty($field) || !$config["optin_enabled"])
            return true;

        $is_value_match = RGFormsModel::is_value_match($field_value, rgar($config,"optin_value"), $operator);

        return  $is_value_match ;
    }

    public static function add_permissions(){
        global $wp_roles;
        $wp_roles->add_cap("administrator", "gravityforms_aweber");
        $wp_roles->add_cap("administrator", "gravityforms_aweber_uninstall");
    }

    //Target of Member plugin filter. Provides the plugin with Gravity Forms lists of capabilities
    public static function members_get_capabilities( $caps ) {
        return array_merge($caps, array("gravityforms_aweber", "gravityforms_aweber_uninstall"));
    }

    public static function uninstall(){

        //loading data lib
        require_once(self::get_base_path() . "/data.php");

        if(!GFAWeber::has_access("gravityforms_aweber_uninstall"))
            die(__("You don't have adequate permission to uninstall the AWeber Add-On.", "gravityformsaweber"));

        //droping all tables
        GFAWeberData::drop_tables();

        //removing options
        delete_option("gf_aweber_settings");
        delete_option("gf_aweber_version");

        //Deactivating plugin
        $plugin = "gravityformsaweber/aweber.php";
        deactivate_plugins($plugin);
        update_option('recently_activated', array($plugin => time()) + (array)get_option('recently_activated'));
    }


    private static function get_aweber_tokens($api_credentials){

        list($application_key, $application_secret, $request_token, $request_token_secret, $oauth_verifier) = split('[|]', $api_credentials);
        self::include_api();

        self::log_debug("Getting tokens for key {$application_key}");
        $aweber = new AWeberAPI($application_key,$application_secret);
        $aweber->user->tokenSecret = $request_token_secret;
        $aweber->user->requestToken = $request_token;
        $aweber->user->verifier = $oauth_verifier;

        try {
        	self::log_debug("Getting tokens.");
            list($access_token, $access_token_secret) = $aweber->getAccessToken();
        } catch (AWeberException $e) {
			self::log_error("Unable to retrieve tokens: {$e}");
        }

        return array("token" => $access_token, "secret" => $access_token_secret);

    }

    private static function is_valid_key(){

        $aweber = self::get_aweber_object();

        $access_token = self::get_access_token();
        $access_token_secret = self::get_access_token_secret();

        try {
        	self::log_debug("Validating API credentials.");
            $account = $aweber->getAccount($access_token, $access_token_secret);
        } catch (AWeberException $e) {
        	self::log_error("Unable to validate API credentials: {$e}");
            $account = null;

        }

        if ($account)
        {
        	self::log_debug("Credentials validated.");
            return true;
        }
        else
        {
            return false;
        }

    }

    private static function get_aweber_object(){
        self::include_api();
        $tokens = self::get_api_tokens();
        $aweber = new AWeberAPI($tokens["application_key"], $tokens["application_secret"]);
        $aweber->user->requestToken = $tokens["request_token"];
        $aweber->user->verifier = $tokens["oauth_verifier"];
        $aweber->user->accessToken = self::get_access_token();
        $aweber->user->tokenSecret = self::get_access_token_secret();
        return $aweber;
    }

    private static function get_aweber_account(){

        $aweber = self::get_aweber_object();

        try {
        	self::log_debug("Getting account.");
            $account = $aweber->getAccount($access_token, $access_token_secret);
        } catch (AWeberException $e) {
        	self::log_error("Unable to get account information: {$e}");
            $account = null;
        }

        return $account;

    }

    private static function get_api_tokens(){
        $settings = get_option("gf_aweber_settings");
        $api_credentials = $settings["api_credentials"];
        list($application_key, $application_secret, $request_token, $request_token_secret, $oauth_verifier) = split('[|]', $api_credentials);
        $api_tokens = array("application_key" => $application_key, "application_secret" => $application_secret, "request_token" => $request_token, "request_token_secret" => $request_token_secret, "oauth_verifier" => $oauth_verifier );
        return $api_tokens;
    }

    private static function get_access_token(){
        $settings = get_option("gf_aweber_settings");
        $access_token = $settings["access_token"];
        return $access_token;
    }

    private static function get_access_token_secret(){
        $settings = get_option("gf_aweber_settings");
        $access_token_secret = $settings["access_token_secret"];
        return $access_token_secret;
    }

    private static function include_api(){

        if(!class_exists('AWeberServiceProvider'))
        	require_once self::get_base_path() . "/api/aweber_api.php";

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
        $folder = basename(dirname(__FILE__));
        return WP_PLUGIN_DIR . "/" . $folder;
    }

    function set_logging_supported($plugins)
	{
		$plugins[self::$slug] = "AWeber";
		return $plugins;
	}

	private static function log_error($message){
		if(class_exists("GFLogging"))
		{
			GFLogging::include_logger();
			GFLogging::log_message(self::$slug, $message, KLogger::ERROR);
		}
	}

	private static function log_debug($message){
		if(class_exists("GFLogging"))
		{
			GFLogging::include_logger();
			GFLogging::log_message(self::$slug, $message, KLogger::DEBUG);
		}
	}

	public static function add_paypal_settings($config, $form) {

		$settings_style = self::has_aweber(rgar($form, "id")) ? "" : "display:none;";

		$aweber_feeds = array();
        foreach(GFAWeberData::get_feeds() as $feed) {
            $aweber_feeds[] = $feed['form_id'];
        }
        ?>
        <li style="<?php echo $settings_style?>" id="gf_delay_aweber_subscription_container">
            <input type="checkbox" name="gf_paypal_delay_aweber_subscription" id="gf_paypal_delay_aweber_subscription" value="1" <?php echo rgar($config['meta'], 'delay_aweber_subscription') ? "checked='checked'" : ""?> />
            <label class="inline" for="gf_paypal_delay_aweber_subscription">
                <?php
                _e("Subscribe user to AWeber only when payment is received.", "gravityformsaweber");
                ?>
            </label>
        </li>

        <script type="text/javascript">
            jQuery(document).ready(function($){
                jQuery(document).bind('paypalFormSelected', function(event, form) {

                    var aweber_form_ids = <?php echo json_encode($aweber_feeds); ?>;
                    var has_registration = false;

                    if(jQuery.inArray(String(form['id']), aweber_form_ids) != -1)
                        has_registration = true;

                    if(has_registration == true) {
                        jQuery("#gf_delay_aweber_subscription_container").show();
                    } else {
                        jQuery("#gf_delay_aweber_subscription_container").hide();
                    }
                });
            });
        </script>
        <?php
    }

    public static function save_paypal_settings($config) {
        $config["meta"]["delay_aweber_subscription"] = rgpost("gf_paypal_delay_aweber_subscription");
        return $config;
    }

    public static function paypal_fulfillment($entry, $config, $transaction_id, $amount) {

        //has this entry been already subscribed?
        $is_subscribed = gform_get_meta($entry["id"], "aweber_is_subscribed");

        if(!$is_subscribed){
            $form = RGFormsModel::get_form_meta($entry['form_id']);
            self::export($entry, $form, true);
        }
    }

    public static function has_aweber($form_id){
        if(!class_exists("GFAWeberData"))
            require_once(self::get_base_path() . "/data.php");

        //Getting Aweber settings associated with this form
        $config = GFAWeberData::get_feed_by_form($form_id);

        if(!$config)
            return false;

        return true;
    }

    private static function get_paypal_config($form_id, $entry){
        if(!class_exists('GFPayPal'))
            return false;

        if(method_exists("GFPayPal", "get_config_by_entry")){
            return GFPayPal::get_config_by_entry($entry);
        }
        else{
            return GFPayPal::get_config($form_id);
        }
    }

}


if(!function_exists("rgget")){
function rgget($name, $array=null){
    if(!isset($array))
        $array = $_GET;

    if(isset($array[$name]))
        return $array[$name];

    return "";
}
}

if(!function_exists("rgpost")){
function rgpost($name, $do_stripslashes=true){
    if(isset($_POST[$name]))
        return $do_stripslashes ? stripslashes_deep($_POST[$name]) : $_POST[$name];

    return "";
}
}

if(!function_exists("rgar")){
function rgar($array, $name){
    if(isset($array[$name]))
        return $array[$name];

    return '';
}
}


if(!function_exists("rgempty")){
function rgempty($name, $array = null){
    if(!$array)
        $array = $_POST;

    $val = rgget($name, $array);
    return empty($val);
}
}


if(!function_exists("rgblank")){
function rgblank($text){
    return empty($text) && strval($text) != "0";
}
}


if(!function_exists("rgobj")){
function rgobj($obj, $name){
    if(isset($obj->$name))
        return $obj->$name;

    return '';
}
}
if(!function_exists("rgexplode")){
function rgexplode($sep, $string, $count){
    $ary = explode($sep, $string);
    while(count($ary) < $count)
        $ary[] = "";

    return $ary;
}
}
