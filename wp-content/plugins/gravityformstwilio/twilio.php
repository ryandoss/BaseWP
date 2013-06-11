<?php
/*
Plugin Name: Gravity Forms Twilio Add-On
Plugin URI: http://www.gravityforms.com
Description: Integrates Gravity Forms with Twilio allowing SMS messages to be sent upon submitting a Gravity Form
Version: 1.0
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

add_action('init',  array('GFTwilio', 'init'));
register_activation_hook( __FILE__, array("GFTwilio", "add_permissions"));

class GFTwilio {

    private static $path = "gravityformstwilio/twilio.php";
    private static $url = "http://www.gravityforms.com";
    private static $slug = "gravityformstwilio";
    private static $version = "1.0";
    private static $min_gravityforms_version = "1.5";

    //Plugin starting point. Will load appropriate files
    public static function init(){

        if(RG_CURRENT_PAGE == "plugins.php"){
            //loading translations
            load_plugin_textdomain('gravityformstwilio', FALSE, '/gravityformstwilio/languages' );

            add_action('after_plugin_row_' . self::$path, array('GFTwilio', 'plugin_row') );

           //force new remote request for version info on the plugin page
            self::flush_version_info();
        }

        if(!self::is_gravityforms_supported()){
           return;
        }

        if(is_admin()){
            //loading translations
            load_plugin_textdomain('gravityformstwilio', FALSE, '/gravityformstwilio/languages' );

            add_filter("transient_update_plugins", array('GFTwilio', 'check_update'));
            add_filter("site_transient_update_plugins", array('GFTwilio', 'check_update'));
            add_action('install_plugins_pre_plugin-information', array('GFTwilio', 'display_changelog'));

            // paypal plugin integration hooks
            add_action("gform_paypal_add_option_group", array("GFTwilio", "add_paypal_twilio_options"), 10, 2);
            add_filter("gform_paypal_save_config", array("GFTwilio", "save_paypal_twilio_config"));

            //creates a new Settings page on Gravity Forms' settings screen
            if(self::has_access("gravityforms_twilio")){
                RGForms::add_settings_page("Twilio", array("GFTwilio", "settings_page"), self::get_base_url() . "/images/twilio_wordpress_icon_32.png");
            }
        }

        //integrating with Members plugin
        if(function_exists('members_get_capabilities'))
            add_filter('members_get_capabilities', array("GFTwilio", "members_get_capabilities"));

        //creates the subnav left menu
        add_filter("gform_addon_navigation", array('GFTwilio', 'create_menu'));

        if(self::is_twilio_page()){

            //loading data lib
            require_once(self::get_base_path() . "/data.php");

            //loading upgrade lib
            require_once("plugin-upgrade.php");

            //loading Gravity Forms tooltips
            require_once(GFCommon::get_base_path() . "/tooltips.php");
            add_filter('gform_tooltips', array('GFTwilio', 'tooltips'));

            //runs the setup when version changes
            self::setup();

        }
        else if(in_array(RG_CURRENT_PAGE, array("admin-ajax.php"))){

            //loading data class
            require_once(self::get_base_path() . "/data.php");

            add_action('wp_ajax_rg_update_feed_active', array('GFTwilio', 'update_feed_active'));
            add_action('wp_ajax_gf_select_twilio_form', array('GFTwilio', 'select_twilio_form'));

        }
        else{
            //handling post submission.
            add_action("gform_post_submission", array('GFTwilio', 'handle_form_submission'), 10, 2);
            add_action("gform_paypal_fulfillment", array("GFTwilio", "handle_fulfillment"), 10, 4);
        }

    }

    public static function add_paypal_twilio_options($config, $form) {
        //loading data lib
        require_once(self::get_base_path() . "/data.php");

        // activate user registration tooltips for integration with PayPal plugin
        add_filter('gform_tooltips', array('GFTwilio', 'tooltips'));

        $has_feed = false;
        $form_ids = array();
        $feeds = GFTwilioData::get_feeds();
        foreach($feeds as $feed){
            if($feed["form_id"] == $config["form_id"])
                $has_feed = true;

            $form_ids[] = $feed["form_id"];
        }

        ?>
        <script type="text/javascript">
            jQuery(document).ready(function(){
                jQuery(document).bind('paypalFormSelected', function(event, form) {

                    var twilio_form_ids = <?php echo GFCommon::json_encode($form_ids) ?>;
                    var has_twilio_feed = jQuery.inArray(String(form['id']), twilio_form_ids) > -1;
                    if(has_twilio_feed) {
                        jQuery("#gf_paypal_twilio_options").show();
                    } else {
                        jQuery("#gf_paypal_twilio_options").hide();
                    }
                });
            });
        </script>

        <div id="gf_paypal_twilio_options" class="margin_vertical_10" style="display:<?php echo $has_feed ? "block" : "none" ?>;">
            <label class="left_header"><?php _e("Twilio Options", "gravityformstwilio"); ?> <?php gform_tooltip("twilio_paypal_options") ?></label>

            <div style="overflow:hidden;">
                <input type="checkbox" name="gf_paypal_delay_twilio" id="gf_paypal_delay_registration" value="1" <?php echo $config["meta"]["delay_twilio"] ? "checked='checked'" : ""?> />
                <label class="inline" for="gf_paypal_delay_twilio">
                    <?php _e("Send SMS only when a payment is received.", "gravityformstwilio");?>
                </label>
            </div>
        </div>
        <?php
    }

    public static function save_paypal_twilio_config($config) {

        $config["meta"]["delay_twilio"] = rgpost("gf_paypal_delay_twilio");
        return $config;
    }

    public static function update_feed_active(){
        check_ajax_referer('rg_update_feed_active','rg_update_feed_active');
        $id = rgpost("feed_id");
        $feed = GFTwilioData::get_feed($id);
        GFTwilioData::update_feed($id, $feed["form_id"], rgpost("is_active"), $feed["meta"]);
    }

    //--------------   Automatic upgrade ---------------------------------------------------

    public static function flush_version_info(){
        require_once("plugin-upgrade.php");
        RGTwilioUpgrade::set_version_info(false);
    }

    public static function plugin_row(){
        if(!self::is_gravityforms_supported()){
            $message = sprintf(__("Gravity Forms " . self::$min_gravityforms_version . " is required. Activate it now or %spurchase it today!%s"), "<a href='http://www.gravityforms.com'>", "</a>");
            RGTwilioUpgrade::display_plugin_message($message, true);
        }
        else{
            $version_info = RGTwilioUpgrade::get_version_info(self::$slug, self::get_key(), self::$version);

            if(!$version_info["is_valid_key"]){
                $new_version = version_compare(self::$version, $version_info["version"], '<') ? __('There is a new version of Gravity Forms Twilio Add-On available.', 'gravityformstwilio') .' <a class="thickbox" title="Gravity Forms Twilio Add-On" href="plugin-install.php?tab=plugin-information&plugin=' . self::$slug . '&TB_iframe=true&width=640&height=808">'. sprintf(__('View version %s Details', 'gravityformstwilio'), $version_info["version"]) . '</a>. ' : '';
                $message = $new_version . sprintf(__('%sRegister%s your copy of Gravity Forms to receive access to automatic upgrades and support. Need a license key? %sPurchase one now%s.', 'gravityformstwilio'), '<a href="admin.php?page=gf_settings">', '</a>', '<a href="http://www.gravityforms.com">', '</a>') . '</div></td>';
                RGTwilioUpgrade::display_plugin_message($message);
            }
        }
    }

    //Displays current version details on Plugin's page
    public static function display_changelog(){
        if($_REQUEST["plugin"] != self::$slug)
            return;

        //loading upgrade lib
        require_once("plugin-upgrade.php");

        RGTwilioUpgrade::display_changelog(self::$slug, self::get_key(), self::$version);
    }

    public static function check_update($update_plugins_option){
        require_once("plugin-upgrade.php");

        return RGTwilioUpgrade::check_update(self::$path, self::$slug, self::$url, self::$slug, self::get_key(), self::$version, $update_plugins_option);
    }

    private static function get_key(){
        if(self::is_gravityforms_supported())
            return GFCommon::get_key();
        else
            return "";
    }
    //---------------------------------------------------------------------------------------

    //Returns true if the current page is an Feed pages. Returns false if not
    private static function is_twilio_page(){
        $current_page = trim(strtolower(rgget("page")));
        $twilio_pages = array("gf_twilio");

        return in_array($current_page, $twilio_pages);
    }

    //Creates or updates database tables. Will only run when version changes
    private static function setup(){

        if(get_option("gf_twilio_version") != self::$version)
            GFTwilioData::update_table();

        update_option("gf_twilio_version", self::$version);
    }

    //Adds feed tooltips to the list of tooltips
    public static function tooltips($tooltips){
        $twilio_tooltips = array(
            "twilio_gravity_form" => "<h6>" . __("Gravity Form", "gravityformstwilio") . "</h6>" . __("Select the Gravity Form you would like to integrate with Twilio.", "gravityformstwilio"),
            "twilio_message" => "<h6>" . __("Message", "gravityformstwilio") . "</h6>" . __("Write the message you would like to be sent. You can insert fields submitted by the user by selecting them from the 'Insert merge code' drop down. SMS message are limited to 160 characters. Messages larger than 160 characters will automatically be split into multiple SMS messages.", "gravityformstwilio"),
            "twilio_from" => "<h6>" . __("From Number", "gravityformstwilio") . "</h6>" . __("Phone number that the message will be sent FROM.", "gravityformstwilio"),
            "twilio_to" => "<h6>" . __("To Number", "gravityformstwilio") . "</h6>" . __("Phone number to send this message to. For Twilio trial accounts, you can only send SMS messages to validated numbers. To validate a number, login to your Twilio account and navigate to the 'Numbers' tab.", "gravityformstwilio"),
            "twilio_shorten_url" => "<h6>" . __("Shorten URLs", "gravityformstwilio") . "</h6>" . __("Enable this option to automatically shorten all URLs in your SMS message.", "gravityformstwilio")

        );
        return array_merge($tooltips, $twilio_tooltips);
    }

    //Creates Twilio left nav menu under Forms
    public static function create_menu($menus){

        // Adding submenu if user has access
        $permission = self::has_access("gravityforms_twilio");
        if(!empty($permission))
            $menus[] = array("name" => "gf_twilio", "label" => __("Twilio", "gravityformstwilio"), "callback" =>  array("GFTwilio", "twilio_page"), "permission" => $permission);

        return $menus;
    }

    public static function settings_page(){

        require_once("plugin-upgrade.php");

        if(!rgempty("uninstall")){
            check_admin_referer("uninstall", "gf_twilio_uninstall");
            self::uninstall();

            ?>
            <div class="updated fade" style="padding:20px;"><?php _e(sprintf("Gravity Forms Twilio Add-On have been successfully uninstalled. It can be re-activated from the %splugins page%s.", "<a href='plugins.php'>","</a>"), "gravityformstwilio")?></div>
            <?php
            return;
        }
        else if(!rgempty("gf_twilio_submit")){
            check_admin_referer("update", "gf_twilio_update");
            $settings = array("account_sid" => rgpost("gf_twilio_account_sid"), "auth_token" => rgpost("gf_twilio_auth_token"), "bitly_login" => rgpost("gf_bitly_login"), "bitly_apikey" => rgpost("gf_bitly_apikey") );

            update_option("gf_twilio_settings", $settings);
        }
        else{
            $settings = get_option("gf_twilio_settings");
        }


        $img = "";
        if(!empty($settings["account_sid"]) || !empty($settings["auth_token"])){
            $img = self::is_valid_credentials() ? "<img src='" . self::get_base_url() . "/images/tick.png' alt='" . __("Valid Twilio credentials", "gravityformstwilio") . "' />" : "<img src='" . self::get_base_url() . "/images/stop.png' alt='" . __("Invalid Twilio credentials", "gravityformstwilio") . "' />";
        }

        $bitly_img = "";
        if(!empty($settings["bitly_login"]) || !empty($settings["bitly_apikey"])){
            $bitly_img = self::is_valid_bitly_credentials() ? "<img src='" . self::get_base_url() . "/images/tick.png' alt='" . __("Valid Bitly credentials", "gravityformstwilio") . "' />" : "<img src='" . self::get_base_url() . "/images/stop.png' alt='" . __("Invalid Bitly credentials", "gravityformstwilio") . "' />";
        }

        ?>

        <form method="post" action="">
            <?php wp_nonce_field("update", "gf_twilio_update") ?>
            <h3><?php _e("Twilio Account Information", "gravityformstwilio") ?></h3>
            <p>
                <?php _e("Twilio provides a web-service API for businesses to build scalable and reliable communication apps.", "gravityformstwilio") ?> <a href='http://www.twilio.com'><?php _e("Sign up for a Twilio account", "gravityformstwilio") ?></a> to receive SMS messages when a Gravity Form is submitted.
            </p>
            <table class="form-table">
                <tr>
                    <th scope="row"><label for="gf_twilio_account_sid"><?php _e("Account SID", "gravityformstwilio"); ?></label> </th>
                    <td width="340">
                        <input type="text" id="gf_twilio_account_sid" name="gf_twilio_account_sid" value="<?php echo esc_attr($settings["account_sid"]) ?>" size="50"/>
                    </td>
                    <td rowspan="2" valign="middle">
                        <?php echo $img ?>
                    </td>
                </tr>
                <tr>
                    <th scope="row"><label for="gf_twilio_auth_token"><?php _e("Auth Token", "gravityformstwilio"); ?></label> </th>
                    <td width="340">
                        <input type="text" id="gf_twilio_auth_token" name="gf_twilio_auth_token" value="<?php echo esc_attr($settings["auth_token"]) ?>" size="50"/>
                    </td>

                </tr>

            </table>

            <br/>
            <h3><?php _e("Bitly Account Information", "gravityformstwilio") ?></h3>
            <p>
                <?php printf(__("Bitly helps you shorten, track and analize your links. Enter your Bitly account information below to automatically shorten URLs in your SMS message. If you don't have a Bitly account, %ssign-up for one here%s", "gravityformstwilio"), "<a href='http://bit.ly'>", "</a>") ?>.
            </p>
            <table class="form-table">
                <tr>
                    <th scope="row"><label for="gf_bitly_login"><?php _e("Login", "gravityformstwilio"); ?></label> </th>
                    <td width="340">
                        <input type="text" id="gf_bitly_login" name="gf_bitly_login" value="<?php echo esc_attr($settings["bitly_login"]) ?>" size="50"/>
                    </td>
                    <td rowspan="2">
                        <?php echo $bitly_img ?>
                    </td>
                </tr>
                <tr>
                    <th scope="row"><label for="gf_bitly_apikey"><?php _e("API Key", "gravityformstwilio"); ?></label> </th>
                    <td width="340">
                        <input type="text" id="gf_bitly_apikey" name="gf_bitly_apikey" value="<?php echo esc_attr($settings["bitly_apikey"]) ?>" size="50"/>
                    </td>

                </tr>

            </table>

            <div style="margin-top:30px;">
                <input type="submit" name="gf_twilio_submit" class="button-primary" value="<?php _e("Save Settings", "gravityformstwilio") ?>" />
            </div>
        </form>

        <form action="" method="post">
            <?php wp_nonce_field("uninstall", "gf_twilio_uninstall") ?>
            <?php
            if(GFCommon::current_user_can_any("gravityforms_twilio_uninstall")){ ?>
                <div class="hr-divider"></div>

                <h3><?php _e("Uninstall Twilio Add-On", "gravityformstwilio") ?></h3>
                <div class="delete-alert"><?php _e("Warning! This operation deletes ALL Twilio Feeds.", "gravityformstwilio") ?>
                    <input type="submit" name="uninstall" value="<?php _e("Uninstall Twilio Add-On", "gravityformstwilio") ?>" class="button" onclick="return confirm('<?php _e("Warning! ALL Twilio Feeds will be deleted. This cannot be undone. \'OK\' to delete, \'Cancel\' to stop", "gravityformstwilio") ?>'); "/>
                </div>
            <?php
            } ?>
        </form>
        <?php
    }

    public static function twilio_page(){
        $view = rgget("view");
        if($view == "edit")
            self::edit_page($_GET["id"]);
        else
            self::list_page();
    }

    //Displays the twilio feeds list page
    private static function list_page(){
        if(!self::is_gravityforms_supported()){
            die(__(sprintf("Twilio Add-On requires Gravity Forms %s. Upgrade automatically on the %sPlugin page%s.", self::$min_gravityforms_version, "<a href='plugins.php'>", "</a>"), "gravityformstwilio"));
        }

        if(rgpost("action") == "delete"){
            check_admin_referer("list_action", "gf_twilio_list");

            $id = absint(rgpost("action_argument"));
            GFTwilioData::delete_feed($id);
            ?>
            <div class="updated fade" style="padding:6px"><?php _e("Feed deleted.", "gravityformstwilio") ?></div>
            <?php
        }
        else if (!rgempty("bulk_action")){
            check_admin_referer("list_action", "gf_twilio_list");
            $selected_feeds = rgpost("feed");
            if(is_array($selected_feeds)){
                foreach($selected_feeds as $feed_id)
                    GFTwilioData::delete_feed($feed_id);
            }
            ?>
            <div class="updated fade" style="padding:6px"><?php _e("Feeds deleted.", "gravityformstwilio") ?></div>
            <?php
        }

        $settings = get_option("gf_twilio_settings");
        $is_configured = !empty($settings["account_sid"]) && !empty($settings["auth_token"]);
        ?>
        <div class="wrap">
            <img alt="<?php _e("Twilio Feeds", "gravityformstwilio") ?>" src="<?php echo self::get_base_url()?>/images/twilio_wordpress_icon_32.png" style="float:left; margin:15px 7px 0 0;"/>
            <h2><?php _e("Twilio Feeds", "gravityformstwilio");
                if($is_configured){
                    ?>
                    <a class="button add-new-h2" href="admin.php?page=gf_twilio&view=edit&id=0"><?php _e("Add New", "gravityformstwilio") ?></a>
                    <?php
                }
                ?>
            </h2>

            <form id="feed_form" method="post">
                <?php wp_nonce_field('list_action', 'gf_twilio_list') ?>
                <input type="hidden" id="action" name="action"/>
                <input type="hidden" id="action_argument" name="action_argument"/>

                <div class="tablenav">
                    <div class="alignleft actions" style="padding:8px 0 7px 0;">
                        <label class="hidden" for="bulk_action"><?php _e("Bulk action", "gravityformstwilio") ?></label>
                        <select name="bulk_action" id="bulk_action">
                            <option value=''> <?php _e("Bulk action", "gravityformstwilio") ?> </option>
                            <option value='delete'><?php _e("Delete", "gravityformstwilio") ?></option>
                        </select>
                        <input type="submit" class="button" value="<?php _e("Apply", "gravityformstwilio") ?>" onclick="if( jQuery('#bulk_action').val() == 'delete' && !confirm('<?php  echo __("Delete selected feeds? \'Cancel\' to stop, \'OK\' to delete.", "gravityformstwilio") ?>')) { return false; } return true;" />
                    </div>
                </div>
                <table class="widefat fixed" cellspacing="0">
                    <thead>
                        <tr>
                            <th scope="col" id="cb" class="manage-column column-cb check-column" style=""><input type="checkbox" /></th>
                            <th scope="col" id="active" class="manage-column check-column"></th>
                            <th scope="col" class="manage-column"><?php _e("Form", "gravityformstwilio") ?></th>
                            <th scope="col" class="manage-column"><?php _e("From Number", "gravityformstwilio") ?></th>
                            <th scope="col" class="manage-column"><?php _e("To Number", "gravityformstwilio") ?></th>
                        </tr>
                    </thead>

                    <tfoot>
                        <tr>
                            <th scope="col" id="cb" class="manage-column column-cb check-column" style=""><input type="checkbox" /></th>
                            <th scope="col" id="active" class="manage-column check-column"></th>
                            <th scope="col" class="manage-column"><?php _e("Form", "gravityformstwilio") ?></th>
                            <th scope="col" class="manage-column"><?php _e("From Number", "gravityformstwilio") ?></th>
                            <th scope="col" class="manage-column"><?php _e("To Number", "gravityformstwilio") ?></th>
                        </tr>
                    </tfoot>

                    <tbody class="list:user user-list">
                        <?php

                        $feeds = GFTwilioData::get_feeds();
                        if(is_array($feeds) && sizeof($feeds) > 0){
                            foreach($feeds as $feed){
                                ?>
                                <tr class='author-self status-inherit' valign="top">
                                    <th scope="row" class="check-column"><input type="checkbox" name="feed[]" value="<?php echo $feed["id"] ?>"/></th>
                                    <td><img src="<?php echo self::get_base_url() ?>/images/active<?php echo intval($feed["is_active"]) ?>.png" alt="<?php echo $feed["is_active"] ? __("Active", "gravityformstwilio") : __("Inactive", "gravityformstwilio");?>" title="<?php echo $feed["is_active"] ? __("Active", "gravityformstwilio") : __("Inactive", "gravityformstwilio");?>" onclick="ToggleActive(this, <?php echo $feed['id'] ?>); " /></td>
                                    <td class="column-title">
                                        <a href="admin.php?page=gf_twilio&view=edit&id=<?php echo $feed["id"] ?>" title="<?php _e("Edit", "gravityformstwilio") ?>"><?php echo $feed["form_title"] ?></a>
                                        <div class="row-actions">
                                            <span class="edit">
                                            <a title="Edit this setting" href="admin.php?page=gf_twilio&view=edit&id=<?php echo $feed["id"] ?>" title="<?php _e("Edit", "gravityformstwilio") ?>"><?php _e("Edit", "gravityformstwilio") ?></a>
                                            |
                                            </span>

                                            <span class="edit">
                                            <a title="<?php _e("Delete", "gravityformstwilio") ?>" href="javascript: if(confirm('<?php _e("Delete this feed? ", "gravityformstwilio") ?> <?php _e("\'Cancel\' to stop, \'OK\' to delete.", "gravityformstwilio") ?>')){ DeleteSetting(<?php echo $feed["id"] ?>);}"><?php _e("Delete", "gravityformstwilio")?></a>

                                            </span>
                                        </div>
                                    </td>
                                    <td class="column-date"><?php echo esc_html($feed["meta"]["from"]) ?></td>
                                    <td class="column-date"><?php echo esc_html($feed["meta"]["to"]) ?></td>
                                </tr>
                                <?php
                            }
                        }
                        else if($is_configured){
                            ?>
                            <tr>
                                <td colspan="4" style="padding:20px;">
                                    <?php _e(sprintf("You don't have any Twilio feeds configured. Let's go %screate one%s!", '<a href="admin.php?page=gf_twilio&view=edit&id=0">', "</a>"), "gravityformstwilio"); ?>
                                </td>
                            </tr>
                            <?php
                        }
                        else{
                            ?>
                            <tr>
                                <td colspan="4" style="padding:20px;">
                                    <?php _e(sprintf("To get started, please configure your %sTwilio Settings%s.", '<a href="admin.php?page=gf_settings&addon=Twilio">', "</a>"), "gravityformstwilio"); ?>
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
                    jQuery(img).attr('title','<?php _e("Inactive", "gravityformstwilio") ?>').attr('alt', '<?php _e("Inactive", "gravityformstwilio") ?>');
                }
                else{
                    img.src = img.src.replace("active0.png", "active1.png");
                    jQuery(img).attr('title','<?php _e("Active", "gravityformstwilio") ?>').attr('alt', '<?php _e("Active", "gravityformstwilio") ?>');
                }

                jQuery.post(ajaxurl,{action:"rg_update_feed_active", rg_update_feed_active:"<?php echo wp_create_nonce("rg_update_feed_active") ?>",
                                    feed_id: feed_id,
                                    is_active: is_active ? 0 : 1,
                                    cookie: encodeURIComponent(document.cookie)});

                return true;
            }

        </script>
        <?php
    }

    private static function edit_page(){
        ?>
        <style>
            .left_header{float:left; width:200px;}
            .margin_vertical_10{margin: 10px 0; clear:both;}
            #twilio_field_group div{float:left;}
        </style>
        <script type="text/javascript">
            var form = Array();
        </script>
        <div class="wrap">
            <img alt="<?php _e("Twilio", "gravityformstwilio") ?>" style="margin: 15px 7px 0pt 0pt; float: left;" src="<?php echo self::get_base_url() ?>/images/twilio_wordpress_icon_32.png"/>
            <h2><?php _e("Twilio Feed", "gravityformstwilio") ?></h2>

        <?php

        //getting setting id (0 when creating a new one)
        $id = !rgempty("twilio_setting_id") ? rgpost("twilio_setting_id") : absint(rgget("id"));
        $config = empty($id) ? array("is_active" => true, "meta" => array()) : GFTwilioData::get_feed($id);

        //updating meta information
        if(!rgempty("gf_twilio_submit")){

            $config["form_id"] = absint(rgpost("gf_twilio_form"));
            $config["meta"]["from"]= rgpost("gf_twilio_from");
            $config["meta"]["to"]= rgpost("gf_twilio_to");
            $config["meta"]["message"]= rgpost("gf_twilio_message");
            $config["meta"]["shorten_url"]= rgpost("gf_twilio_shorten_url");

            $id = GFTwilioData::update_feed($id, $config["form_id"], $config["is_active"], $config["meta"]);
            ?>
            <div class="updated fade" style="padding:6px"><?php echo sprintf(__("Feed Updated. %sback to list%s", "gravityformstwilio"), "<a href='?page=gf_twilio'>", "</a>") ?></div>
            <input type="hidden" name="twilio_setting_id" value="<?php echo $id ?>"/>
            <?php
        }

        $twilio = self::get_account_info();

        if(!$twilio){
            ?>
            <div class="error" style="padding:6px"><?php _e("Ooops. There was a problem contacting Twilio. Please try again in a few minutes.", "gravityformstwilio") ?></div>
            <?php
        }
        else if(empty($twilio["incoming_numbers"])){
            ?>
            <div class="error" style="padding:6px"><?php printf(__("You must add incoming numbers to your %sTwilio%s account in order to send SMS messages", "gravityformstwilio"), "<a href='http://www.twilio.com' target='_blank'>", "</a>") ?></div>
            <?php
        }
        else if($twilio["is_sandbox"] && empty($twilio["outgoing_numbers"])){
            ?>
            <div class="error" style="padding:6px"><?php printf(__("You must add outgoing caller IDs to your %sTwilio%s account in order to send SMS messages. To add an outgoing caller ID, login to your %sTwilio%s account and navigate to the 'Numbers' tab", "gravityformstwilio"), "<a href='http://www.twilio.com' target='_blank'>", "</a>", "<a href='http://www.twilio.com' target='_blank'>", "</a>") ?></div>
            <?php
        }
        else{
            ?>
            <form method="post" action="">
                <input type="hidden" name="twilio_setting_id" value="<?php echo $id ?>"/>

                <div id="twilio_form_container" valign="top" class="margin_vertical_10">
                    <label for="gf_twilio_form" class="left_header"><?php _e("Gravity Form", "gravityformstwilio"); ?> <?php gform_tooltip("twilio_gravity_form") ?></label>
                    <select id="gf_twilio_form" name="gf_twilio_form" onchange="SelectForm(jQuery(this).val());">
                        <option value=""><?php _e("Select a form", "gravityformstwilio"); ?> </option>
                        <?php
                        $forms = RGFormsModel::get_forms();
                        foreach($forms as $form){
                            $selected = absint($form->id) == $config["form_id"] ? "selected='selected'" : "";
                            ?>
                            <option value="<?php echo absint($form->id) ?>"  <?php echo $selected ?>><?php echo esc_html($form->title) ?></option>
                            <?php
                        }
                        ?>
                    </select>
                    &nbsp;&nbsp;
                    <img src="<?php echo GFTwilio::get_base_url() ?>/images/loading.gif" id="twilio_wait" style="display: none;"/>
                </div>
                <div id="twilio_field_group" valign="top" <?php echo empty($config["form_id"]) ? "style='display:none;'" : "" ?>>

                    <div class="margin_vertical_10">
                        <label for="gf_twilio_from" class="left_header"><?php _e("From Number", "gravityformstwilio"); ?> <?php gform_tooltip("twilio_from") ?></label>

                        <select id="gf_twilio_from" name="gf_twilio_from">
                            <?php
                                foreach($twilio["incoming_numbers"] as $number){
                                    $selected = $number == $config["meta"]["from"] ? "selected='selected'" : "";
                                    ?>
                                    <option value="<?php echo $number ?>" <?php echo $selected ?>><?php echo $number ?></option>
                                    <?php
                                }
                            ?>
                        </select>

                    </div>

                    <div class="margin_vertical_10">
                        <label for="gf_twilio_to" class="left_header"><?php _e("To Number", "gravityformstwilio"); ?> <?php gform_tooltip("twilio_to") ?></label>
                        <?php
                        if($twilio["is_sandbox"]){
                            ?>
                            <select id="gf_twilio_to" name="gf_twilio_to">
                                <?php
                                    foreach($twilio["outgoing_numbers"] as $number){
                                        $selected = $number == $config["meta"]["to"] ? "selected='selected'" : "";
                                        ?>
                                        <option value="<?php echo $number ?>" <?php echo $selected ?>><?php echo $number ?></option>
                                        <?php
                                    }
                                ?>
                            </select><br/>
                            <?php
                        }
                        else{
                            ?>
                            <input type="text" name="gf_twilio_to" value="<?php echo esc_attr($config["meta"]["to"]) ?>">
                            <?php
                        }
                        ?>
                    </div>

                    <div class="margin_vertical_10">
                        <label for="gf_twilio_message" class="left_header"><?php _e("Message", "gravityformstwilio"); ?> <?php gform_tooltip("twilio_message") ?></label>
                        <div style="float:left; ">
                            <select id="gf_twilio_message_variable_select" onchange="InsertVariable('gf_twilio_message');">
                                <?php
                                    if(!empty($config["form_id"])){
                                        $form_meta = RGFormsModel::get_form_meta($config["form_id"]);
                                        echo self::get_form_fields($form_meta);
                                    }
                                ?>
                            </select><br/>
                            <textarea id="gf_twilio_message" name="gf_twilio_message" style="height: 150px; width:410px;"><?php echo rgget("message", $config["meta"])?></textarea>
                            <br/>
                            <div style="display:<?php echo self::is_valid_bitly_credentials() ? "block" : "none" ?>;">
                                <input type="checkbox" name="gf_twilio_shorten_url" id="gf_twilio_shorten_url" value="1" <?php echo rgget("shorten_url", $config["meta"]) ? "checked='checked'" : "" ?> />
                                <label for="gf_twilio_shorten_url"><?php _e("Shorten URLs", "gravityformstwilio"); ?> <?php gform_tooltip("twilio_shorten_url") ?></label>
                            </div>

                        </div>
                    </div>

                    <div id="twilio_submit_container" class="margin_vertical_10" style="clear:both;">
                        <input type="submit" name="gf_twilio_submit" value="<?php echo empty($id) ? __("Save Feed", "gravityformstwilio") : __("Update Feed", "gravityformstwilio"); ?>" class="button-primary"/>
                    </div>
                </div>
            </form>
            <?php
        }
        ?>
        </div>
        <script type="text/javascript">

            function SelectForm(formId){
                if(!formId){
                    jQuery("#twilio_field_group").slideUp();
                    return;
                }

                jQuery("#twilio_wait").show();
                jQuery("#twilio_field_group").slideUp();
                jQuery.post(ajaxurl,{action:"gf_select_twilio_form", gf_select_twilio_form:"<?php echo wp_create_nonce("gf_select_twilio_form") ?>",
                                    form_id: formId,
                                    cookie: encodeURIComponent(document.cookie)},

                                    function(data){
                                        //setting global form object
                                        form = data.form;
                                        fields = data["fields"];
                                        jQuery("#gf_twilio_message_variable_select").html(fields);

                                        jQuery("#twilio_field_group").slideDown();
                                        jQuery("#twilio_wait").hide();
                                    }, "json"
                );
            }

            function InsertVariable(element_id, callback, variable){
                if(!variable)
                    variable = jQuery('#' + element_id + '_variable_select').val();

                var messageElement = jQuery("#" + element_id);

                if(document.selection) {
                    // Go the IE way
                    messageElement[0].focus();
                    document.selection.createRange().text=variable;
                }
                else if(messageElement[0].selectionStart) {
                    // Go the Gecko way
                    obj = messageElement[0]
                    obj.value = obj.value.substr(0, obj.selectionStart) + variable + obj.value.substr(obj.selectionEnd, obj.value.length);
                }
                else {
                    messageElement.val(variable + messageElement.val());
                }

                jQuery('#' + element_id + '_variable_select')[0].selectedIndex = 0;

                if(callback && window[callback])
                    window[callback].call();
            }

        </script>

        <?php

    }

    public static function add_permissions(){
        global $wp_roles;
        $wp_roles->add_cap("administrator", "gravityforms_twilio");
        $wp_roles->add_cap("administrator", "gravityforms_twilio_uninstall");
    }

    //Target of Member plugin filter. Provides the plugin with Gravity Forms lists of capabilities
    public static function members_get_capabilities( $caps ) {
        return array_merge($caps, array("gravityforms_twilio", "gravityforms_twilio_uninstall"));
    }

    public static function disable_twilio(){
        delete_option("gf_twilio_settings");
    }

    public static function select_twilio_form(){

        check_ajax_referer("gf_select_twilio_form", "gf_select_twilio_form");
        $form_id =  intval($_POST["form_id"]);

        //fields meta
        $form = RGFormsModel::get_form_meta($form_id);
        $fields = self::get_form_fields($form);

        $result = array("form" => $form, "fields" => $fields);
        die(GFCommon::json_encode($result));
    }

    public static function get_form_fields($form){

        $str = "<option value=''>". __("Insert merge code", "gravityforms") ."</option>";

        $required_fields = array();
        $optional_fields = array();
        $pricing_fields = array();

        foreach($form["fields"] as $field){
            if($field["displayOnly"])
                continue;

            $input_type = RGFormsModel::get_input_type($field);

            //skip field types that should be excluded
            if(is_array($exclude) && in_array($input_type, $exclude))
                continue;

            if($field["isRequired"]){

                switch($input_type){
                    case "name" :
                        if($field["nameFormat"] == "extended"){
                            $prefix = GFCommon::get_input($field, $field["id"] + 0.2);
                            $suffix = GFCommon::get_input($field, $field["id"] + 0.8);
                            $optional_field = $field;
                            $optional_field["inputs"] = array($prefix, $suffix);

                            //Add optional name fields to the optional list
                            $optional_fields[] = $optional_field;

                            //Remove optional name field from required list
                            unset($field["inputs"][0]);
                            unset($field["inputs"][3]);
                        }

                        $required_fields[] = $field;
                    break;


                    default:
                        $required_fields[] = $field;
                }
            }
            else{
               $optional_fields[] = $field;
            }

            if(GFCommon::is_pricing_field($field["type"])){
                $pricing_fields[] = $field;
            }

        }

        if(!empty($required_fields)){
            $str .="<optgroup label='". __("Required form fields", "gravityforms") ."'>";

            foreach($required_fields as $field){
                $str .= self::get_field_variable($field);
            }

            $str .="</optgroup>";
        }

        if(!empty($optional_fields)){
            $str .="<optgroup label='". __("Optional form fields", "gravityforms") ."'>";
            foreach($optional_fields as $field){
                $str .= self::get_field_variable($field);
            }
            $str .="</optgroup>";
        }

        if(!empty($pricing_fields)){
            $str .="<optgroup label='".  __("Pricing form fields", "gravityforms") ."'>";

            foreach($pricing_fields as $field){
                $str .= self::get_field_variable($field);
            }
            $str .="</optgroup>";
        }

        $str .="<optgroup label='".  __("Other", "gravityforms") ."'>
                <option value='{ip}'>".  __("Client IP Address", "gravityforms") ."</option>
                <option value='{date_mdy}'>".  __("Date", "gravityforms") ." (mm/dd/yyyy)</option>
                <option value='{date_dmy}'>".  __("Date", "gravityforms") ." (dd/mm/yyyy)</option>
                <option value='{embed_post:ID}'>".  __("Embed Post/Page Id", "gravityforms") ."</option>
                <option value='{embed_post:post_title}'>".  __("Embed Post/Page Title", "gravityforms") ."</option>
                <option value='{embed_url}'>".  __("Embed URL", "gravityforms") ."</option>
                <option value='{entry_id}'>".  __("Entry Id", "gravityforms") ."</option>
                <option value='{entry_url}'>".  __("Entry URL", "gravityforms") ."</option>
                <option value='{form_id}'>".  __("Form Id", "gravityforms") ."</option>
                <option value='{form_title}'>".  __("Form Title", "gravityforms") ."</option>
                <option value='{user_agent}'>".  __("HTTP User Agent", "gravityforms") ."</option>";

        if(GFCommon::has_post_field($form["fields"])){
            $str .="<option value='{post_id}'>".  __("Post Id", "gravityforms") ."</option>
                    <option value='{post_edit_url}'>". __("Post Edit URL", "gravityforms") ."</option>";
        }

        $str .="<option value='{user:display_name}'>".  __("User Display Name", "gravityforms") ."</option>
                <option value='{user:user_email}'>".  __("User Email", "gravityforms") ."</option>
                <option value='{user:user_login}'>".  __("User Login", "gravityforms") ."</option>
        </optgroup>";

        return $str;
    }

    public static function get_field_variable($field, $max_label_size=40){
        $str = "";
        if(is_array($field["inputs"]))
        {
            foreach($field["inputs"] as $input){
                $str .="<option value='{" . esc_attr(GFCommon::get_label($field, $input["id"])) . ":" . $input["id"] . "}'>" . esc_html(GFCommon::truncate_middle(GFCommon::get_label($field, $input["id"]), $max_label_size)) ."</option>";
            }
        }
        else{
            $str .="<option value='{" . esc_html(GFCommon::get_label($field)) . ":" . $field["id"] . "}'>" . esc_html(GFCommon::truncate_middle(GFCommon::get_label($field), $max_label_size)) . "</option>";
        }

        return $str;
    }

    public static function handle_fulfillment($entry, $config, $transaction_id, $amount) {
        if(!class_exists('GFPayPal'))
            return;

        $paypal_config = GFPayPal::get_config($entry['form_id']);

        //Only send SMS if delay twilio is ON
        if(rgget('delay_twilio', $paypal_config['meta'])){
            self::export($entry, RGFormsModel::get_form_meta($entry["form_id"]));
        }
    }

    public static function handle_form_submission($entry, $form){

        if(class_exists('GFPayPal')) {
            $paypal_config = GFPayPal::get_config($form['id']);

            //don't send SMS if PayPal delay twilio setting is ON
            if(rgget('delay_twilio', $paypal_config['meta'])){
                $order_total = GFCommon::get_order_total($form, $entry);
                if($order_total != 0)
                    return;
            }
        }

        self::export($entry, $form);
    }


    public static function export($entry, $form){

        //loading data class
        require_once(self::get_base_path() . "/data.php");

        //getting all active feeds
        $feeds = GFTwilioData::get_feed_by_form($form["id"], true);
        foreach($feeds as $feed){
            self::export_feed($entry, $form, $feed);
        }
    }

    public static function export_feed($entry, $form, $feed){
       $body = GFCommon::replace_variables($feed["meta"]["message"], $form, $entry);

       self::send_sms($feed["meta"]["from"], $feed["meta"]["to"], $body, $feed["meta"]["shorten_url"]);
    }

    public static function uninstall(){

        //loading data lib
        require_once(self::get_base_path() . "/data.php");

        if(!GFTwilio::has_access("gravityforms_twilio_uninstall"))
            die(__("You don't have adequate permission to uninstall Twilio Add-On.", "gravityformstwilio"));

        //droping all tables
        GFTwilioData::drop_tables();

        //removing options
        delete_option("gf_twilio_settings");
        delete_option("gf_twilio_version");

        //Deactivating plugin
        $plugin = "gravityformstwilio/twilio.php";
        deactivate_plugins($plugin);
        update_option('recently_activated', array($plugin => time()) + (array)get_option('recently_activated'));
    }

    private static function is_valid_bitly_credentials(){
        $url = self::shorten_url("http://www.google.com");
        return $url != "http://www.google.com";
    }

    public static function shorten_url($url){
        $encoded_url = urlencode($url);
        $settings = get_option("gf_twilio_settings");
        $login = urlencode($settings["bitly_login"]);
        $apikey = urlencode($settings["bitly_apikey"]);

        $request_url = "http://api.j.mp/v3/shorten?login={$login}&apiKey={$apikey}&longUrl={$encoded_url}&format=txt";
        $response = wp_remote_get($request_url);

        if(is_wp_error($response) || $response["response"]["code"] != 200)
            return $url;

        $is_valid = substr(trim($response["body"]), 0, 4) == "http";
        return $is_valid ? trim($response["body"]) : $url;
    }

    private static function is_valid_credentials(){
        $api = self::get_api();

        $response = $api->request("{$api->base_path}");
        return !$response->IsError;

    }

    private static function get_account_info(){
        require_once(self::get_base_path() . "/xml.php");

        $api = self::get_api();

        $response = $api->request("{$api->base_path}");
        if($response->IsError)
            return false;

        $options = array("OutgoingCallerId" => array("unserialize_as_array" => true));
        $xml = new RGXML($options);

        $response_object = $xml->unserialize($response->ResponseText);
        $is_trial = strtolower($response_object["Account"]["Type"]) == "trial";

        $incoming_numbers = array();
        $outgoing_numbers = array();
        if($is_trial){

            //Getting Sandbox phone number
            $response = $api->request("{$api->base_path}/Sandbox");
            if($response->IsError)
                return false;

            $response_object = $xml->unserialize($response->ResponseText);
            $incoming_numbers[] = $response_object["TwilioSandbox"]["PhoneNumber"];

            //Getting validated outgoing phone numbers
            $response = $api->request("{$api->base_path}/OutgoingCallerIds");
            if($response->IsError)
                return false;

            $response_object = $xml->unserialize($response->ResponseText);
            foreach($response_object["OutgoingCallerIds"] as $caller_id){
                if(is_array($caller_id) && isset($caller_id["PhoneNumber"]))
                    $outgoing_numbers[] = $caller_id["PhoneNumber"];
            }
        }
        else{
            //Getting incoming phone numbers
            $response = $api->request("{$api->base_path}/IncomingPhoneNumbers");
            if($response->IsError)
                return false;

            $response_object = $xml->unserialize($response->ResponseText);
            foreach($response_object["IncomingPhoneNumbers"] as $number){
                if(is_array($number) && isset($number["PhoneNumber"]))
                    $incoming_numbers[] = $number["PhoneNumber"];
            }
        }

        return array("is_sandbox" => $is_trial, "incoming_numbers" => $incoming_numbers, "outgoing_numbers" => $outgoing_numbers);

    }

    private static function send_sms($from, $to, $body, $shorten_urls=false){

        $api = self::get_api();
        $messages = self::prepare_message($body, $shorten_urls);

        for($i=0, $count = count($messages); $i<$count; $i++){
            $body = $messages[$i];

            //Add ... to all messages except last one
            if($i < $count-1)
                $body .=" ...";

            $to = preg_replace('|[^\d\+]|', "", $to);
            $data = array("From" => $from, "To" => $to, "Body" => $body);
            $response = $api->request("{$api->base_path}/SMS/Messages", "POST", $data);
        }
    }

    public static function prepare_message($text, $shorten_urls=false){
        if($shorten_urls){
            $text = preg_replace_callback('~(https?|ftp):\/\/\S+~', create_function('$matches','return GFTwilio::shorten_url($matches[0]);'), $text);
        }
        return str_split($text, 156);
    }

    private static function get_api(){
        require_once(self::get_base_path() . "/api/twilio.php");

        // Twilio REST API version
        $ApiVersion = "2010-04-01";

        // Set our AccountSid and AuthToken
        $settings = get_option("gf_twilio_settings");

        // Instantiate a new Twilio Rest Client
        $client = new TwilioRestClient($settings["account_sid"], $settings["auth_token"]);
        $client->base_path = "{$ApiVersion}/Accounts/{$settings["account_sid"]}";
        return $client;
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
}

?>
