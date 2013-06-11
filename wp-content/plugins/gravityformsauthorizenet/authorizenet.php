<?php
/*
Plugin Name: Gravity Forms Authorize.Net Add-On
Plugin URI: http://www.gravityforms.com
Description: Integrates Gravity Forms with Authorize.Net, enabling end users to purchase goods and services through Gravity Forms.
Version: 1.3
Author: rocketgenius
Author URI: http://www.rocketgenius.com

------------------------------------------------------------------------
Copyright 2009 rocketgenius
last updated: October 20, 2010

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

add_action('init',  array('GFAuthorizeNet', 'init'));

//limits currency to US Dollars
add_filter("gform_currency", create_function("","return 'USD';"));
add_action("renewal_cron", array("GFAuthorizeNet", "process_renewals"));

register_activation_hook( __FILE__, array("GFAuthorizeNet", "add_permissions"));

class GFAuthorizeNet {

    private static $path = "gravityformsauthorizenet/authorizenet.php";
    private static $url = "http://www.gravityforms.com";
    private static $slug = "gravityformsauthorizenet";
    private static $version = "1.3";
    private static $min_gravityforms_version = "1.6.4.2.5";
    private static $transaction_response = "";
    private static $supported_fields = array("checkbox", "radio", "select", "text", "website", "textarea", "email", "hidden", "number", "phone", "multiselect", "post_title",
                                    "post_tags", "post_custom_field", "post_content", "post_excerpt");

    //Plugin starting point. Will load appropriate files
    public static function init(){
        self::process_renewals();
        //supports logging
        add_filter("gform_logging_supported", array("GFAuthorizeNet", "set_logging_supported"));

        self::setup_cron();

        if(basename($_SERVER['PHP_SELF']) == "plugins.php") {

            //loading translations
            load_plugin_textdomain('gravityformsauthorizenet', FALSE, '/gravityformsauthorizenet/languages' );

            add_action('after_plugin_row_' . self::$path, array('GFAuthorizeNet', 'plugin_row') );

            //force new remote request for version info on the plugin page
            self::flush_version_info();
        }

        if(!self::is_gravityforms_supported())
           return;

        if(is_admin()){

            //runs the setup when version changes
            self::setup();

            //loading translations
            load_plugin_textdomain('gravityformsauthorizenet', FALSE, '/gravityformsauthorizenet/languages' );

            //automatic upgrade hooks
            add_filter("transient_update_plugins", array('GFAuthorizeNet', 'check_update'));
            add_filter("site_transient_update_plugins", array('GFAuthorizeNet', 'check_update'));
            add_action('install_plugins_pre_plugin-information', array('GFAuthorizeNet', 'display_changelog'));

            //integrating with Members plugin
            if(function_exists('members_get_capabilities'))
                add_filter('members_get_capabilities', array("GFAuthorizeNet", "members_get_capabilities"));

            //creates the subnav left menu
            add_filter("gform_addon_navigation", array('GFAuthorizeNet', 'create_menu'));

            //enables credit card field
            add_filter("gform_enable_credit_card_field", "__return_true");

            if(self::is_authorizenet_page()){

                //enqueueing sack for AJAX requests
                wp_enqueue_script(array("sack"));

                //loading data lib
                require_once(self::get_base_path() . "/data.php");

                //loading upgrade lib
                if(!class_exists("RGAuthorizeNetUpgrade"))
                    require_once("plugin-upgrade.php");

                //loading Gravity Forms tooltips
                require_once(GFCommon::get_base_path() . "/tooltips.php");
                add_filter('gform_tooltips', array('GFAuthorizeNet', 'tooltips'));

            }
            else if(in_array(RG_CURRENT_PAGE, array("admin-ajax.php"))){

                //loading data class
                require_once(self::get_base_path() . "/data.php");

                add_action('wp_ajax_gf_authorizenet_update_feed_active', array('GFAuthorizeNet', 'update_feed_active'));
                add_action('wp_ajax_gf_select_authorizenet_form', array('GFAuthorizeNet', 'select_authorizenet_form'));
                add_action('wp_ajax_gf_cancel_authorizenet_subscription', array('GFAuthorizeNet', 'cancel_authorizenet_subscription'));

            }
            else if(RGForms::get("page") == "gf_settings"){
                RGForms::add_settings_page("Authorize.Net", array("GFAuthorizeNet", "settings_page"), self::get_base_url() . "/images/authorizenet_wordpress_icon_32.png");
                add_filter("gform_currency_setting_message", create_function("","echo '<div class=\'gform_currency_message\'>Authorize.Net only supports US Dollars.</div>';"));
                add_filter("gform_currency_disabled", "__return_true");
            }
            else if(RGForms::get("page") == "gf_entries"){
                add_action('gform_entry_info',array("GFAuthorizeNet", "authorizenet_entry_info"), 10, 2);
            }
        }
        else{
            //loading data class
            require_once(self::get_base_path() . "/data.php");

            //handling post submission.
            add_filter('gform_validation',array("GFAuthorizeNet", "authorizenet_validation"), 10, 4);
            add_action('gform_after_submission',array("GFAuthorizeNet", "authorizenet_after_submission"), 10, 2);
        }
    }

    public static function setup_cron(){
       if(!wp_next_scheduled("renewal_cron"))
           wp_schedule_event(time(), "daily", "renewal_cron");
    }

    public static function update_feed_active(){
        check_ajax_referer('gf_authorizenet_update_feed_active','gf_authorizenet_update_feed_active');
        $id = $_POST["feed_id"];
        $feed = GFAuthorizeNetData::get_feed($id);
        GFAuthorizeNetData::update_feed($id, $feed["form_id"], $_POST["is_active"], $feed["meta"]);
    }

    //-------------- Automatic upgrade ---------------------------------------
    public static function flush_version_info(){
        if(!class_exists("RGAuthorizeNetUpgrade"))
            require_once("plugin-upgrade.php");

        RGAuthorizeNetUpgrade::set_version_info(false);
    }

    public static function plugin_row(){
        if(!self::is_gravityforms_supported()){
            $message = sprintf(__("Gravity Forms " . self::$min_gravityforms_version . " is required. Activate it now or %spurchase it today!%s", "gravityformsauthorizenet"), "<a href='http://www.gravityforms.com'>", "</a>");
            RGAuthorizeNetUpgrade::display_plugin_message($message, true);
        }
        else{
            $version_info = RGAuthorizeNetUpgrade::get_version_info(self::$slug, self::get_key(), self::$version);

            if(!$version_info["is_valid_key"]){
                $new_version = version_compare(self::$version, $version_info["version"], '<') ? __('There is a new version of Gravity Forms Authorize.Net Add-On available.', 'gravityformsauthorizenet') .' <a class="thickbox" title="Gravity Forms Authorize.Net Add-On" href="plugin-install.php?tab=plugin-information&plugin=' . self::$slug . '&TB_iframe=true&width=640&height=808">'. sprintf(__('View version %s Details', 'gravityformsauthorizenet'), $version_info["version"]) . '</a>. ' : '';
                $message = $new_version . sprintf(__('%sRegister%s your copy of Gravity Forms to receive access to automatic upgrades and support. Need a license key? %sPurchase one now%s.', 'gravityformsauthorizenet'), '<a href="admin.php?page=gf_settings">', '</a>', '<a href="http://www.gravityforms.com">', '</a>') . '</div></td>';
                RGAuthorizeNetUpgrade::display_plugin_message($message);
            }
        }
    }

    //Displays current version details on Plugin's page
    public static function display_changelog(){
        if($_REQUEST["plugin"] != self::$slug)
            return;

        //loading upgrade lib
        if(!class_exists("RGAuthorizeNetUpgrade"))
            require_once("plugin-upgrade.php");

        RGAuthorizeNetUpgrade::display_changelog(self::$slug, self::get_key(), self::$version);
    }

    public static function check_update($update_plugins_option){
        if(!class_exists("RGAuthorizeNetUpgrade"))
            require_once("plugin-upgrade.php");

        return RGAuthorizeNetUpgrade::check_update(self::$path, self::$slug, self::$url, self::$slug, self::get_key(), self::$version, $update_plugins_option);
    }

    private static function get_key(){
        if(self::is_gravityforms_supported())
            return GFCommon::get_key();
        else
            return "";
    }

    //------------------------------------------------------------------------

    //Creates AuthorizeNet left nav menu under Forms
    public static function create_menu($menus){

        // Adding submenu if user has access
        $permission = self::has_access("gravityforms_authorizenet");
        if(!empty($permission))
            $menus[] = array("name" => "gf_authorizenet", "label" => __("Authorize.Net", "gravityformsauthorizenet"), "callback" =>  array("GFAuthorizeNet", "authorizenet_page"), "permission" => $permission);

        return $menus;
    }

    //Creates or updates database tables. Will only run when version changes
    private static function setup(){
        if(get_option("gf_authorizenet_version") != self::$version){
            require_once(self::get_base_path() . "/data.php");
            GFAuthorizeNetData:: update_table();
        }

        update_option("gf_authorizenet_version", self::$version);
    }

    //Adds feed tooltips to the list of tooltips
    public static function tooltips($tooltips){
        $authorizenet_tooltips = array(
            "authorizenet_transaction_type" => "<h6>" . __("Transaction Type", "gravityformsauthorizenet") . "</h6>" . __("Select which Authorize.Net transaction type should be used. Products and Services, Donations or Subscription.", "gravityformsauthorizenet"),
            "authorizenet_gravity_form" => "<h6>" . __("Gravity Form", "gravityformsauthorizenet") . "</h6>" . __("Select which Gravity Forms you would like to integrate with Authorize.Net.", "gravityformsauthorizenet"),
            "authorizenet_customer" => "<h6>" . __("Customer", "gravityformsauthorizenet") . "</h6>" . __("Map your Form Fields to the available Authorize.Net customer information fields.", "gravityformsauthorizenet"),
            "authorizenet_options" => "<h6>" . __("Options", "gravityformsauthorizenet") . "</h6>" . __("Turn on or off the available Authorize.Net checkout options.", "gravityformsauthorizenet"),
            "authorizenet_recurring_amount" => "<h6>" . __("Recurring Amount", "gravityformsauthorizenet") . "</h6>" . __("Select which field determines the recurring payment amount.", "gravityformsauthorizenet"),
            "authorizenet_billing_cycle" => "<h6>" . __("Billing Cycle", "gravityformsauthorizenet") . "</h6>" . __("Select your billing cycle.  This determines how often the recurring payment should occur.", "gravityformsauthorizenet"),
            "authorizenet_recurring_times" => "<h6>" . __("Recurring Times", "gravityformsauthorizenet") . "</h6>" . __("Select how many times the recurring payment should be made.  The default is to bill the customer until the subscription is canceled.", "gravityformsauthorizenet"),
            "authorizenet_trial_period_enable" => "<h6>" . __("Trial Period", "gravityformsauthorizenet") . "</h6>" . __("Enable a trial period.  The users recurring payment will not begin until after this trial period.", "gravityformsauthorizenet"),
            "authorizenet_trial_amount" => "<h6>" . __("Trial Amount", "gravityformsauthorizenet") . "</h6>" . __("Enter the trial period amount or leave it blank for a free trial.", "gravityformsauthorizenet"),
            "authorizenet_trial_period" => "<h6>" . __("Trial Recurring Times", "gravityformsauthorizenet") . "</h6>" . __("Select the number of billing occurrences or payments in the trial period.", "gravityformsauthorizenet"),
            "authorizenet_conditional" => "<h6>" . __("Authorize.Net Condition", "gravityformsauthorizenet") . "</h6>" . __("When the Authorize.Net condition is enabled, form submissions will only be sent to Authorize.Net when the condition is met. When disabled all form submissions will be sent to Authorize.Net.", "gravityformsauthorizenet"),
            "authorizenet_setup_fee_enable" => "<h6>" . __("Setup Fee", "gravityformspaypalpro") . "</h6>" . __("Enable setup fee to charge a one time fee before the recurring payments begin.", "gravityformsauthorizenet")
        );
        return array_merge($tooltips, $authorizenet_tooltips);
    }

    public static function authorizenet_page(){
        $view = rgget("view");
        if($view == "edit")
            self::edit_page(rgget("id"));
        else if($view == "stats")
            self::stats_page(rgget("id"));
        else
            self::list_page();
    }

    //Displays the authorizenet feeds list page
    private static function list_page(){
        if(!self::is_gravityforms_supported()){
            die(__(sprintf("Authorize.Net Add-On requires Gravity Forms %s. Upgrade automatically on the %sPlugin page%s.", self::$min_gravityforms_version, "<a href='plugins.php'>", "</a>"), "gravityformsauthorizenet"));
        }

        if(rgpost('action') == "delete"){
            check_admin_referer("list_action", "gf_authorizenet_list");

            $id = absint($_POST["action_argument"]);
            GFAuthorizeNetData::delete_feed($id);
            ?>
            <div class="updated fade" style="padding:6px"><?php _e("Feed deleted.", "gravityformsauthorizenet") ?></div>
            <?php
        }
        else if (!empty($_POST["bulk_action"])){
            check_admin_referer("list_action", "gf_authorizenet_list");
            $selected_feeds = $_POST["feed"];
            if(is_array($selected_feeds)){
                foreach($selected_feeds as $feed_id)
                    GFAuthorizeNetData::delete_feed($feed_id);
            }
            ?>
            <div class="updated fade" style="padding:6px"><?php _e("Feeds deleted.", "gravityformsauthorizenet") ?></div>
            <?php
        }

        ?>
        <div class="wrap">
            <img alt="<?php _e("Authorize.Net Transactions", "gravityformsauthorizenet") ?>" src="<?php echo self::get_base_url()?>/images/authorizenet_wordpress_icon_32.png" style="float:left; margin:15px 7px 0 0;"/>
            <h2><?php
            _e("Authorize.Net Forms", "gravityformsauthorizenet");
                ?>
                <a class="button add-new-h2" href="admin.php?page=gf_authorizenet&view=edit&id=0"><?php _e("Add New", "gravityformsauthorizenet") ?></a>

            </h2>

            <form id="feed_form" method="post">
                <?php wp_nonce_field('list_action', 'gf_authorizenet_list') ?>
                <input type="hidden" id="action" name="action"/>
                <input type="hidden" id="action_argument" name="action_argument"/>

                <div class="tablenav">
                    <div class="alignleft actions" style="padding:8px 0 7px 0;">
                        <label class="hidden" for="bulk_action"><?php _e("Bulk action", "gravityformsauthorizenet") ?></label>
                        <select name="bulk_action" id="bulk_action">
                            <option value=''> <?php _e("Bulk action", "gravityformsauthorizenet") ?> </option>
                            <option value='delete'><?php _e("Delete", "gravityformsauthorizenet") ?></option>
                        </select>
                        <?php
                        echo '<input type="submit" class="button" value="' . __("Apply", "gravityformsauthorizenet") . '" onclick="if( jQuery(\'#bulk_action\').val() == \'delete\' && !confirm(\'' . __("Delete selected feeds? ", "gravityformsauthorizenet") . __("\'Cancel\' to stop, \'OK\' to delete.", "gravityformsauthorizenet") .'\')) { return false; } return true;"/>';
                        ?>
                    </div>
                </div>
                <table class="widefat fixed" cellspacing="0">
                    <thead>
                        <tr>
                            <th scope="col" id="cb" class="manage-column column-cb check-column" style=""><input type="checkbox" /></th>
                            <th scope="col" id="active" class="manage-column check-column"></th>
                            <th scope="col" class="manage-column"><?php _e("Form", "gravityformsauthorizenet") ?></th>
                            <th scope="col" class="manage-column"><?php _e("Transaction Type", "gravityformsauthorizenet") ?></th>
                        </tr>
                    </thead>

                    <tfoot>
                        <tr>
                            <th scope="col" id="cb" class="manage-column column-cb check-column" style=""><input type="checkbox" /></th>
                            <th scope="col" id="active" class="manage-column check-column"></th>
                            <th scope="col" class="manage-column"><?php _e("Form", "gravityformsauthorizenet") ?></th>
                            <th scope="col" class="manage-column"><?php _e("Transaction Type", "gravityformsauthorizenet") ?></th>
                        </tr>
                    </tfoot>

                    <tbody class="list:user user-list">
                        <?php


                        $settings = GFAuthorizeNetData::get_feeds();
                        if(!self::is_valid_key()){
                            ?>
                            <tr>
                                <td colspan="4" style="padding:20px;">
                                    <?php echo sprintf(__("To get started, please configure your %sAuthorize.Net Settings%s.", "gravityformsauthorizenet"), '<a href="admin.php?page=gf_settings&addon=Authorize.Net">', "</a>"); ?>
                                </td>
                            </tr>
                            <?php
                        }
                        else if(is_array($settings) && sizeof($settings) > 0){
                            foreach($settings as $setting){
                                ?>
                                <tr class='author-self status-inherit' valign="top">
                                    <th scope="row" class="check-column"><input type="checkbox" name="feed[]" value="<?php echo $setting["id"] ?>"/></th>
                                    <td><img src="<?php echo self::get_base_url() ?>/images/active<?php echo intval($setting["is_active"]) ?>.png" alt="<?php echo $setting["is_active"] ? __("Active", "gravityformsauthorizenet") : __("Inactive", "gravityformsauthorizenet");?>" title="<?php echo $setting["is_active"] ? __("Active", "gravityformsauthorizenet") : __("Inactive", "gravityformsauthorizenet");?>" onclick="ToggleActive(this, <?php echo $setting['id'] ?>); " /></td>
                                    <td class="column-title">
                                        <a href="admin.php?page=gf_authorizenet&view=edit&id=<?php echo $setting["id"] ?>" title="<?php _e("Edit", "gravityformsauthorizenet") ?>"><?php echo $setting["form_title"] ?></a>
                                        <div class="row-actions">
                                            <span class="edit">
                                            <a title="<?php _e("Edit", "gravityformsauthorizenet")?>" href="admin.php?page=gf_authorizenet&view=edit&id=<?php echo $setting["id"] ?>" title="<?php _e("Edit", "gravityformsauthorizenet") ?>"><?php _e("Edit", "gravityformsauthorizenet") ?></a>
                                            |
                                            </span>
                                            <span>
                                            <a title="<?php _e("View Stats", "gravityformsauthorizenet")?>" href="admin.php?page=gf_authorizenet&view=stats&id=<?php echo $setting["id"] ?>" title="<?php _e("View Stats", "gravityformsauthorizenet") ?>"><?php _e("Stats", "gravityformsauthorizenet") ?></a>
                                            |
                                            </span>
                                            <span>
                                            <a title="<?php _e("View Entries", "gravityformsauthorizenet")?>" href="admin.php?page=gf_entries&view=entries&id=<?php echo $setting["form_id"] ?>" title="<?php _e("View Entries", "gravityformsauthorizenet") ?>"><?php _e("Entries", "gravityformsauthorizenet") ?></a>
                                            |
                                            </span>
                                            <span>
                                            <a title="<?php _e("Delete", "gravityformsauthorizenet") ?>" href="javascript: if(confirm('<?php _e("Delete this feed? ", "gravityformsauthorizenet") ?> <?php _e("\'Cancel\' to stop, \'OK\' to delete.", "gravityformsauthorizenet") ?>')){ DeleteSetting(<?php echo $setting["id"] ?>);}"><?php _e("Delete", "gravityformsauthorizenet")?></a>
                                            </span>
                                        </div>
                                    </td>
                                    <td class="column-date">
                                        <?php
                                            switch($setting["meta"]["type"]){
                                                case "product" :
                                                    _e("Product and Services", "gravityformsauthorizenet");
                                                break;

                                                case "subscription" :
                                                    _e("Subscription", "gravityformsauthorizenet");
                                                break;
                                            }
                                        ?>
                                    </td>
                                </tr>
                                <?php
                            }
                        }
                        else{
                            ?>
                            <tr>
                                <td colspan="4" style="padding:20px;">
                                    <?php echo sprintf(__("You don't have any Authorize.Net feeds configured. Let's go %screate one%s!", "gravityformsauthorizenet"), '<a href="admin.php?page=gf_authorizenet&view=edit&id=0">', "</a>"); ?>
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
                    jQuery(img).attr('title','<?php _e("Inactive", "gravityformsauthorizenet") ?>').attr('alt', '<?php _e("Inactive", "gravityformsauthorizenet") ?>');
                }
                else{
                    img.src = img.src.replace("active0.png", "active1.png");
                    jQuery(img).attr('title','<?php _e("Active", "gravityformsauthorizenet") ?>').attr('alt', '<?php _e("Active", "gravityformsauthorizenet") ?>');
                }

                var mysack = new sack("<?php echo admin_url("admin-ajax.php")?>" );
                mysack.execute = 1;
                mysack.method = 'POST';
                mysack.setVar( "action", "gf_authorizenet_update_feed_active" );
                mysack.setVar( "gf_authorizenet_update_feed_active", "<?php echo wp_create_nonce("gf_authorizenet_update_feed_active") ?>" );
                mysack.setVar( "feed_id", feed_id );
                mysack.setVar( "is_active", is_active ? 0 : 1 );
                mysack.encVar( "cookie", document.cookie, false );
                mysack.onError = function() { alert('<?php _e("Ajax error while updating feed", "gravityformsauthorizenet" ) ?>' )};
                mysack.runAJAX();

                return true;
            }


        </script>
        <?php
    }

    public static function settings_page(){

        if(!class_exists("RGAuthorizeNetUpgrade"))
            require_once("plugin-upgrade.php");

        if(isset($_POST["uninstall"])){
            check_admin_referer("uninstall", "gf_authorizenet_uninstall");
            self::uninstall();

            ?>
            <div class="updated fade" style="padding:20px;"><?php _e(sprintf("Gravity Forms Authorize.Net Add-On have been successfully uninstalled. It can be re-activated from the %splugins page%s.", "<a href='plugins.php'>","</a>"), "gravityformsauthorizenet")?></div>
            <?php
            return;
        }
        else if(isset($_POST["gf_authorizenet_submit"])){
            check_admin_referer("update", "gf_authorizenet_update");
            $settings = array(  "transaction_key" => rgpost("gf_authorizenet_transaction_key"),
                                "login_id" => rgpost("gf_authorizenet_login_id"),
                                "mode" => rgpost("gf_authorizenet_mode"),
                                "arb_configured" => rgpost("gf_arb_configured")
                                );


            update_option("gf_authorizenet_settings", $settings);
        }
        else{
            $settings = get_option("gf_authorizenet_settings");
        }

        $is_valid = self::is_valid_key();

        $message = "";
        if($is_valid)
            $message = "Valid API Login Id and Transaction Key.";
        else if(!empty($settings["transaction_key"]))
            $message = "Invalid API Login Id and/or Transaction Key. Please try again.";


        ?>
        <style>
            .valid_credentials{color:green;}
            .invalid_credentials{color:red;}
            .size-1{width:400px;}
        </style>

        <form method="post" action="">
            <?php wp_nonce_field("update", "gf_authorizenet_update") ?>

            <h3><?php _e("Authorize.Net Account Information", "gravityformsauthorizenet") ?></h3>
            <p style="text-align: left;">
                <?php _e(sprintf("Authorize.Net is a payment gateway for merchants. Use Gravity Forms to collect payment information and automatically integrate to your client's Authorize.Net account. If you don't have a Authorize.Net account, you can %ssign up for one here%s", "<a href='http://www.authorizenet.com' target='_blank'>" , "</a>"), "gravityformsauthorizenet") ?>
            </p>

            <table class="form-table">
                <tr>
                    <th scope="row" nowrap="nowrap"><label for="gf_authorizenet_mode"><?php _e("Mode", "gravityformsauthorizenet"); ?></label> </th>
                    <td width="88%">
                        <input type="radio" name="gf_authorizenet_mode" id="gf_authorizenet_mode_production" value="production" <?php echo rgar($settings, 'mode') != "test" ? "checked='checked'" : "" ?>/>
                        <label class="inline" for="gf_authorizenet_mode_production"><?php _e("Production", "gravityformsauthorizenet"); ?></label>
                        &nbsp;&nbsp;&nbsp;
                        <input type="radio" name="gf_authorizenet_mode" id="gf_authorizenet_mode_test" value="test" <?php echo rgar($settings, 'mode') == "test" ? "checked='checked'" : "" ?>/>
                        <label class="inline" for="gf_authorizenet_mode_test"><?php _e("Test", "gravityformsauthorizenet"); ?></label>
                    </td>
                </tr>
                <tr>
                    <th scope="row" nowrap="nowrap"><label for="gf_authorizenet_username"><?php _e("API Login ID", "gravityformsauthorizenet"); ?></label> </th>
                    <td width="88%">
                        <input class="size-1" id="gf_authorizenet_login_id" name="gf_authorizenet_login_id" value="<?php echo esc_attr(rgar($settings,"login_id")) ?>" />
                        <img src="<?php echo self::get_base_url() ?>/images/<?php echo $is_valid ? "tick.png" : "stop.png" ?>" border="0" alt="<?php $message ?>" title="<?php echo $message ?>" style="display:<?php echo empty($message) ? 'none;' : 'inline;' ?>" />
                        <br/>
                        <small><?php _e("You can find your unique <strong>API Login ID</strong> by clicking on the 'Account' link at the Authorize.Net Merchant Interface. Then click 'API Login ID and Transaction Key'. Your API Login ID will be displayed.", "gravityformsauthorizenet") ?></small>
                    </td>
                </tr>
                <tr>
                    <th scope="row" nowrap="nowrap"><label for="gf_authorizenet_username"><?php _e("Transaction Key", "gravityformsauthorizenet"); ?></label> </th>
                    <td width="88%">
                        <input type="text" class="size-1" id="gf_authorizenet_transaction_key" name="gf_authorizenet_transaction_key" value="<?php echo esc_attr(rgar($settings,"transaction_key")) ?>" />
                        <img src="<?php echo self::get_base_url() ?>/images/<?php echo $is_valid ? "tick.png" : "stop.png" ?>" border="0" alt="<?php $message ?>" title="<?php echo $message ?>" style="display:<?php echo empty($message) ? 'none;' : 'inline;' ?>" />
                        <br/>
                        <small><?php _e("You can find your unique <strong>Transaction Key</strong> by clicking on the 'Account' link at the Authorize.Net Merchant Interface. Then click 'API Login ID and Transaction Key'. For security reasons, you cannot view your Transaction Key, but you will be able to generate a new one.", "gravityformsauthorizenet") ?></small>
                    </td>
                </tr>
                <tr>
                    <td colspan="2">
                        <h3><?php _e("Automated Recurring Billing Setup", "gravityformsauthorizenet") ?></h3>
                        <p style="text-align: left;">
                            <?php _e("To create recurring payments, you must have Automated Recurring Billing (ARB) setup in your Authorize.Net account.", "gravityformsauthorizenet") ?>
                        </p>
                    </td>
                </tr>
                <tr>
                    <td colspan="2">
                        <input type="checkbox" name="gf_arb_configured" id="gf_arb_configured" <?php echo $settings["arb_configured"] ? "checked='checked'" : ""?>/>
                        <label for="gf_arb_configured" class="inline"><?php _e("ARB is setup in my Authorize.Net account.", "gravityformsauthorizenet") ?></label>
                    </td>
                </tr>
                <tr>
                    <td colspan="2" ><input type="submit" name="gf_authorizenet_submit" class="button-primary" value="<?php _e("Save Settings", "gravityformsauthorizenet") ?>" /></td>
                </tr>

            </table>

        </form>

         <form action="" method="post">
            <?php wp_nonce_field("uninstall", "gf_authorizenet_uninstall") ?>
            <?php if(GFCommon::current_user_can_any("gravityforms_authorizenet_uninstall")){ ?>
                <div class="hr-divider"></div>

                <h3><?php _e("Uninstall Authorize.Net Add-On", "gravityformsauthorizenet") ?></h3>
                <div class="delete-alert"><?php _e("Warning! This operation deletes ALL Authorize.Net Feeds.", "gravityformsauthorizenet") ?>
                    <?php
                    $uninstall_button = '<input type="submit" name="uninstall" value="' . __("Uninstall Authorize.Net Add-On", "gravityformsauthorizenet") . '" class="button" onclick="return confirm(\'' . __("Warning! ALL Authorize.Net Feeds will be deleted. This cannot be undone. \'OK\' to delete, \'Cancel\' to stop", "gravityformsauthorizenet") . '\');"/>';
                    echo apply_filters("gform_authorizenet_uninstall_button", $uninstall_button);
                    ?>
                </div>
            <?php } ?>
        </form>

        <!--<form action="" method="post">
                <div class="hr-divider"></div>
                <div class="delete-alert">
                    <input type="submit" name="cron" value="Cron" class="button"/>
                </div>
        </form>-->

        <?php
    }

    private static function is_valid_key($local_api_settings = array()){
        $settings = get_option("gf_authorizenet_settings");
        $is_sandbox = rgar($settings, "mode") == "test";

        $auth = self::get_aim($local_api_settings);

        $response = $auth->AuthorizeOnly();
        $failure = $response->error;
        $response_reason_code = $response->response_reason_code;
        if($failure && ($response_reason_code == 13 || $response_reason_code == 103) )
        {
            return false;
        }
        else
        {
            return true;
        }
    }

    private static function get_aim($local_api_settings = array()){
        self::include_api();
        $api_settings = self::get_api_settings($local_api_settings);
        $is_sandbox = $api_settings['mode'] == "test";

        $aim = new AuthorizeNetAIM($api_settings['login_id'], $api_settings['transaction_key']);
        $aim->setSandbox($is_sandbox);
        return $aim;
    }

    private static function get_arb($local_api_settings = array()){
        self::include_api();
        $api_settings = self::get_api_settings($local_api_settings);
        $is_sandbox = $api_settings['mode'] == "test";

        $arb = new AuthorizeNetARB($api_settings["login_id"], $api_settings["transaction_key"]);
        $arb->setSandbox($is_sandbox);
        return $arb;
    }

    private static function get_api_settings($local_api_settings){
        $custom_api_settings = false;
        if(!empty($local_api_settings))
            $custom_api_settings = true;
        else
            $settings = get_option("gf_authorizenet_settings");

        $login_id = $custom_api_settings ? rgar($local_api_settings, "login") : rgar($settings, "login_id");
        $transaction_key = $custom_api_settings ? rgar($local_api_settings, "key") : rgar($settings, "transaction_key");
        $mode = $custom_api_settings ? rgar($local_api_settings, "mode") : rgar($settings, "mode");

        return array("login_id" => $login_id, "transaction_key" => $transaction_key, "mode" => $mode);
    }


    private static function include_api(){
        if(!class_exists('AuthorizeNetRequest'))
            require_once self::get_base_path() . "/api/AuthorizeNet.php";
    }

    private static function get_product_field_options($productFields, $selectedValue){
        $options = "<option value=''>" . __("Select a product", "gravityformsauthorizenet") . "</option>";
        foreach($productFields as $field){
            $label = GFCommon::truncate_middle($field["label"], 30);
            $selected = $selectedValue == $field["id"] ? "selected='selected'" : "";
            $options .= "<option value='{$field["id"]}' {$selected}>{$label}</option>";
        }

        return $options;
    }

    private static function stats_page(){
        ?>
        <style>
          .authorizenet_graph_container{clear:both; padding-left:5px; min-width:789px; margin-right:50px;}
        .authorizenet_message_container{clear: both; padding-left:5px; text-align:center; padding-top:120px; border: 1px solid #CCC; background-color: #FFF; width:100%; height:160px;}
        .authorizenet_summary_container {margin:30px 60px; text-align: center; min-width:740px; margin-left:50px;}
        .authorizenet_summary_item {width:160px; background-color: #FFF; border: 1px solid #CCC; padding:14px 8px; margin:6px 3px 6px 0; display: -moz-inline-stack; display: inline-block; zoom: 1; *display: inline; text-align:center;}
        .authorizenet_summary_value {font-size:20px; margin:5px 0; font-family:Georgia,"Times New Roman","Bitstream Charter",Times,serif}
        .authorizenet_summary_title {}
        #authorizenet_graph_tooltip {border:4px solid #b9b9b9; padding:11px 0 0 0; background-color: #f4f4f4; text-align:center; -moz-border-radius: 4px; -webkit-border-radius: 4px; border-radius: 4px; -khtml-border-radius: 4px;}
        #authorizenet_graph_tooltip .tooltip_tip {width:14px; height:14px; background-image:url(<?php echo self::get_base_url() ?>/images/tooltip_tip.png); background-repeat: no-repeat; position: absolute; bottom:-14px; left:68px;}

        .authorizenet_tooltip_date {line-height:130%; font-weight:bold; font-size:13px; color:#21759B;}
        .authorizenet_tooltip_sales {line-height:130%;}
        .authorizenet_tooltip_revenue {line-height:130%;}
            .authorizenet_tooltip_revenue .authorizenet_tooltip_heading {}
            .authorizenet_tooltip_revenue .authorizenet_tooltip_value {}
            .authorizenet_trial_disclaimer {clear:both; padding-top:20px; font-size:10px;}
        </style>
        <script type="text/javascript" src="<?php echo self::get_base_url() ?>/flot/jquery.flot.min.js"></script>
        <script type="text/javascript" src="<?php echo self::get_base_url() ?>/js/currency.js"></script>

        <div class="wrap">
            <img alt="<?php _e("Authorize.Net", "gravityformsauthorizenet") ?>" style="margin: 15px 7px 0pt 0pt; float: left;" src="<?php echo self::get_base_url() ?>/images/authorizenet_wordpress_icon_32.png"/>
            <h2><?php _e("Authorize.Net Stats", "gravityformsauthorizenet") ?></h2>

            <form method="post" action="">
                <ul class="subsubsub">
                    <li><a class="<?php echo (!RGForms::get("tab") || RGForms::get("tab") == "daily") ? "current" : "" ?>" href="?page=gf_authorizenet&view=stats&id=<?php echo $_GET["id"] ?>"><?php _e("Daily", "gravityforms"); ?></a> | </li>
                    <li><a class="<?php echo RGForms::get("tab") == "weekly" ? "current" : ""?>" href="?page=gf_authorizenet&view=stats&id=<?php echo $_GET["id"] ?>&tab=weekly"><?php _e("Weekly", "gravityforms"); ?></a> | </li>
                    <li><a class="<?php echo RGForms::get("tab") == "monthly" ? "current" : ""?>" href="?page=gf_authorizenet&view=stats&id=<?php echo $_GET["id"] ?>&tab=monthly"><?php _e("Monthly", "gravityforms"); ?></a></li>
                </ul>
                <?php
                $config = GFAuthorizeNetData::get_feed(RGForms::get("id"));

                switch(RGForms::get("tab")){
                    case "monthly" :
                        $chart_info = self::monthly_chart_info($config);
                    break;

                    case "weekly" :
                        $chart_info = self::weekly_chart_info($config);
                    break;

                    default :
                        $chart_info = self::daily_chart_info($config);
                    break;
                }

                if(!$chart_info["series"]){
                    ?>
                    <div class="authorizenet_message_container"><?php _e("No payments have been made yet.", "gravityformsauthorizenet") ?> <?php echo $config["meta"]["trial_period_enabled"] && empty($config["meta"]["trial_amount"]) ? " **" : ""?></div>
                    <?php
                }
                else{
                    ?>
                    <div class="authorizenet_graph_container">
                        <div id="graph_placeholder" style="width:100%;height:300px;"></div>
                    </div>

                    <script type="text/javascript">
                        var authorizenet_graph_tooltips = <?php echo $chart_info["tooltips"]?>;
                        jQuery.plot(jQuery("#graph_placeholder"), <?php echo $chart_info["series"] ?>, <?php echo $chart_info["options"] ?>);
                        jQuery(window).resize(function(){
                            jQuery.plot(jQuery("#graph_placeholder"), <?php echo $chart_info["series"] ?>, <?php echo $chart_info["options"] ?>);
                        });

                        var previousPoint = null;
                        jQuery("#graph_placeholder").bind("plothover", function (event, pos, item) {
                            startShowTooltip(item);
                        });

                        jQuery("#graph_placeholder").bind("plotclick", function (event, pos, item) {
                            startShowTooltip(item);
                        });

                        function startShowTooltip(item){
                            if (item) {
                                if (!previousPoint || previousPoint[0] != item.datapoint[0]) {
                                    previousPoint = item.datapoint;

                                    jQuery("#authorizenet_graph_tooltip").remove();
                                    var x = item.datapoint[0].toFixed(2),
                                        y = item.datapoint[1].toFixed(2);

                                    showTooltip(item.pageX, item.pageY, authorizenet_graph_tooltips[item.dataIndex]);
                                }
                            }
                            else {
                                jQuery("#authorizenet_graph_tooltip").remove();
                                previousPoint = null;
                            }
                        }
                        function showTooltip(x, y, contents) {
                            jQuery('<div id="authorizenet_graph_tooltip">' + contents + '<div class="tooltip_tip"></div></div>').css( {
                                position: 'absolute',
                                display: 'none',
                                opacity: 0.90,
                                width:'150px',
                                height:'<?php echo $config["meta"]["type"] == "subscription" ? "75px" : "60px" ;?>',
                                top: y - <?php echo $config["meta"]["type"] == "subscription" ? "100" : "89" ;?>,
                                left: x - 79
                            }).appendTo("body").fadeIn(200);
                        }
                        function convertToMoney(number){
                            var currency = getCurrentCurrency();
                            return currency.toMoney(number);
                        }
                        function formatWeeks(number){
                            number = number + "";
                            return "<?php _e("Week ", "gravityformsauthorizenet") ?>" + number.substring(number.length-2);
                        }
                        function getCurrentCurrency(){
                            <?php
                            if(!class_exists("RGCurrency"))
                                require_once(ABSPATH . "/" . PLUGINDIR . "/gravityforms/currency.php");

                            $current_currency = RGCurrency::get_currency(GFCommon::get_currency());
                            ?>
                            var currency = new Currency(<?php echo GFCommon::json_encode($current_currency)?>);
                            return currency;
                        }
                    </script>
                <?php
                }
                $payment_totals = RGFormsModel::get_form_payment_totals($config["form_id"]);
                $transaction_totals = GFAuthorizeNetData::get_transaction_totals($config["form_id"]);

                switch($config["meta"]["type"]){
                    case "product" :
                        $total_sales = $payment_totals["orders"];
                        $sales_label = __("Total Orders", "gravityformsauthorizenet");
                    break;

                    case "donation" :
                        $total_sales = $payment_totals["orders"];
                        $sales_label = __("Total Donations", "gravityformsauthorizenet");
                    break;

                    case "subscription" :
                        $total_sales = $payment_totals["active"];
                        $sales_label = __("Active Subscriptions", "gravityformsauthorizenet");
                    break;
                }

                $total_revenue = empty($transaction_totals["payment"]["revenue"]) ? 0 : $transaction_totals["payment"]["revenue"];
                ?>
                <div class="authorizenet_summary_container">
                    <div class="authorizenet_summary_item">
                        <div class="authorizenet_summary_title"><?php _e("Total Revenue", "gravityformsauthorizenet")?></div>
                        <div class="authorizenet_summary_value"><?php echo GFCommon::to_money($total_revenue) ?></div>
                    </div>
                    <div class="authorizenet_summary_item">
                        <div class="authorizenet_summary_title"><?php echo $chart_info["revenue_label"]?></div>
                        <div class="authorizenet_summary_value"><?php echo $chart_info["revenue"] ?></div>
                    </div>
                    <div class="authorizenet_summary_item">
                        <div class="authorizenet_summary_title"><?php echo $sales_label?></div>
                        <div class="authorizenet_summary_value"><?php echo $total_sales ?></div>
                    </div>
                    <div class="authorizenet_summary_item">
                        <div class="authorizenet_summary_title"><?php echo $chart_info["sales_label"] ?></div>
                        <div class="authorizenet_summary_value"><?php echo $chart_info["sales"] ?></div>
                    </div>
                </div>
                <?php
                if(!$chart_info["series"] && $config["meta"]["trial_period_enabled"] && empty($config["meta"]["trial_amount"])){
                    ?>
                    <div class="authorizenet_trial_disclaimer"><?php _e("** Free trial transactions will only be reflected in the graph after the first payment is made (i.e. after trial period ends)", "gravityformsauthorizenet") ?></div>
                    <?php
                }
                ?>
            </form>
        </div>
        <?php
    }

    private static function get_graph_timestamp($local_datetime){
        $local_timestamp = mysql2date("G", $local_datetime); //getting timestamp with timezone adjusted
        $local_date_timestamp = mysql2date("G", gmdate("Y-m-d 23:59:59", $local_timestamp)); //setting time portion of date to midnight (to match the way Javascript handles dates)
        $timestamp = ($local_date_timestamp - (24 * 60 * 60) + 1) * 1000; //adjusting timestamp for Javascript (subtracting a day and transforming it to milliseconds
        $date = gmdate("Y-m-d",$timestamp);
        return $timestamp;
    }

    private static function matches_current_date($format, $js_timestamp){
        $target_date = $format == "YW" ? $js_timestamp : date($format, $js_timestamp / 1000);

        $current_date = gmdate($format, GFCommon::get_local_timestamp(time()));
        return $target_date == $current_date;
    }

    private static function daily_chart_info($config){
        global $wpdb;

        $tz_offset = self::get_mysql_tz_offset();

        $results = $wpdb->get_results("SELECT CONVERT_TZ(t.date_created, '+00:00', '" . $tz_offset . "') as date, sum(t.amount) as amount_sold, sum(is_renewal) as renewals, sum(is_renewal=0) as new_sales
                                        FROM {$wpdb->prefix}rg_lead l
                                        INNER JOIN {$wpdb->prefix}rg_authorizenet_transaction t ON l.id = t.entry_id
                                        WHERE form_id={$config["form_id"]} AND t.transaction_type='payment'
                                        GROUP BY date(date)
                                        ORDER BY payment_date desc
                                        LIMIT 30");

        $sales_today = 0;
        $revenue_today = 0;
        $tooltips = "";
        $series = "";
        $options ="";
        if(!empty($results)){

            $data = "[";

            foreach($results as $result){
                $timestamp = self::get_graph_timestamp($result->date);
                if(self::matches_current_date("Y-m-d", $timestamp)){
                    $sales_today += $result->new_sales;
                    $revenue_today += $result->amount_sold;
                }
                $data .="[{$timestamp},{$result->amount_sold}],";

                if($config["meta"]["type"] == "subscription"){
                    $sales_line = " <div class='authorizenet_tooltip_subscription'><span class='authorizenet_tooltip_heading'>" . __("New Subscriptions", "gravityformsauthorizenet") . ": </span><span class='authorizenet_tooltip_value'>" . $result->new_sales . "</span></div><div class='authorizenet_tooltip_subscription'><span class='authorizenet_tooltip_heading'>" . __("Renewals", "gravityformsauthorizenet") . ": </span><span class='authorizenet_tooltip_value'>" . $result->renewals . "</span></div>";
                }
                else{
                    $sales_line = "<div class='authorizenet_tooltip_sales'><span class='authorizenet_tooltip_heading'>" . __("Orders", "gravityformsauthorizenet") . ": </span><span class='authorizenet_tooltip_value'>" . $result->new_sales . "</span></div>";
                }

                $tooltips .= "\"<div class='authorizenet_tooltip_date'>" . GFCommon::format_date($result->date, false, "", false) . "</div>{$sales_line}<div class='authorizenet_tooltip_revenue'><span class='authorizenet_tooltip_heading'>" . __("Revenue", "gravityformsauthorizenet") . ": </span><span class='authorizenet_tooltip_value'>" . GFCommon::to_money($result->amount_sold) . "</span></div>\",";
            }
            $data = substr($data, 0, strlen($data)-1);
            $tooltips = substr($tooltips, 0, strlen($tooltips)-1);
            $data .="]";

            $series = "[{data:" . $data . "}]";
            $month_names = self::get_chart_month_names();
            $options ="
            {
                xaxis: {mode: 'time', monthnames: $month_names, timeformat: '%b %d', minTickSize:[1, 'day']},
                yaxis: {tickFormatter: convertToMoney},
                bars: {show:true, align:'right', barWidth: (24 * 60 * 60 * 1000) - 10000000},
                colors: ['#a3bcd3', '#14568a'],
                grid: {hoverable: true, clickable: true, tickColor: '#F1F1F1', backgroundColor:'#FFF', borderWidth: 1, borderColor: '#CCC'}
            }";
        }
        switch($config["meta"]["type"]){
            case "product" :
                $sales_label = __("Orders Today", "gravityformsauthorizenet");
            break;

            case "donation" :
                $sales_label = __("Donations Today", "gravityformsauthorizenet");
            break;

            case "subscription" :
                $sales_label = __("Subscriptions Today", "gravityformsauthorizenet");
            break;
        }
        $revenue_today = GFCommon::to_money($revenue_today);
        return array("series" => $series, "options" => $options, "tooltips" => "[$tooltips]", "revenue_label" => __("Revenue Today", "gravityformsauthorizenet"), "revenue" => $revenue_today, "sales_label" => $sales_label, "sales" => $sales_today);
    }

    private static function weekly_chart_info($config){
            global $wpdb;

            $tz_offset = self::get_mysql_tz_offset();

            $results = $wpdb->get_results("SELECT yearweek(CONVERT_TZ(t.date_created, '+00:00', '" . $tz_offset . "')) week_number, sum(t.amount) as amount_sold, sum(is_renewal) as renewals, sum(is_renewal=0) as new_sales
                                            FROM {$wpdb->prefix}rg_lead l
                                            INNER JOIN {$wpdb->prefix}rg_authorizenet_transaction t ON l.id = t.entry_id
                                            WHERE form_id={$config["form_id"]} AND t.transaction_type='payment'
                                            GROUP BY week_number
                                            ORDER BY week_number desc
                                            LIMIT 30");
            $sales_week = 0;
            $revenue_week = 0;
            if(!empty($results))
            {
                $data = "[";

                foreach($results as $result){
                    if(self::matches_current_date("YW", $result->week_number)){
                        $sales_week += $result->new_sales;
                        $revenue_week += $result->amount_sold;
                    }
                    $data .="[{$result->week_number},{$result->amount_sold}],";

                    if($config["meta"]["type"] == "subscription"){
                        $sales_line = " <div class='authorizenet_tooltip_subscription'><span class='authorizenet_tooltip_heading'>" . __("New Subscriptions", "gravityformsauthorizenet") . ": </span><span class='authorizenet_tooltip_value'>" . $result->new_sales . "</span></div><div class='authorizenet_tooltip_subscription'><span class='authorizenet_tooltip_heading'>" . __("Renewals", "gravityformsauthorizenet") . ": </span><span class='authorizenet_tooltip_value'>" . $result->renewals . "</span></div>";
                    }
                    else{
                        $sales_line = "<div class='authorizenet_tooltip_sales'><span class='authorizenet_tooltip_heading'>" . __("Orders", "gravityformsauthorizenet") . ": </span><span class='authorizenet_tooltip_value'>" . $result->new_sales . "</span></div>";
                    }

                    $tooltips .= "\"<div class='authorizenet_tooltip_date'>" . substr($result->week_number, 0, 4) . ", " . __("Week",  "gravityformsauthorizenet") . " " . substr($result->week_number, strlen($result->week_number)-2, 2) . "</div>{$sales_line}<div class='authorizenet_tooltip_revenue'><span class='authorizenet_tooltip_heading'>" . __("Revenue", "gravityformsauthorizenet") . ": </span><span class='authorizenet_tooltip_value'>" . GFCommon::to_money($result->amount_sold) . "</span></div>\",";
                }
                $data = substr($data, 0, strlen($data)-1);
                $tooltips = substr($tooltips, 0, strlen($tooltips)-1);
                $data .="]";

                $series = "[{data:" . $data . "}]";
                $month_names = self::get_chart_month_names();
                $options ="
                {
                    xaxis: {tickFormatter: formatWeeks, tickDecimals: 0},
                    yaxis: {tickFormatter: convertToMoney},
                    bars: {show:true, align:'center', barWidth:0.95},
                    colors: ['#a3bcd3', '#14568a'],
                    grid: {hoverable: true, clickable: true, tickColor: '#F1F1F1', backgroundColor:'#FFF', borderWidth: 1, borderColor: '#CCC'}
                }";
            }

            switch($config["meta"]["type"]){
                case "product" :
                    $sales_label = __("Orders this Week", "gravityformsauthorizenet");
                break;

                case "donation" :
                    $sales_label = __("Donations this Week", "gravityformsauthorizenet");
                break;

                case "subscription" :
                    $sales_label = __("Subscriptions this Week", "gravityformsauthorizenet");
                break;
            }
            $revenue_week = GFCommon::to_money($revenue_week);

            return array("series" => $series, "options" => $options, "tooltips" => "[$tooltips]", "revenue_label" => __("Revenue this Week", "gravityformsauthorizenet"), "revenue" => $revenue_week, "sales_label" => $sales_label , "sales" => $sales_week);
    }

    private static function monthly_chart_info($config){
            global $wpdb;
            $tz_offset = self::get_mysql_tz_offset();

            $results = $wpdb->get_results("SELECT date_format(CONVERT_TZ(t.date_created, '+00:00', '" . $tz_offset . "'), '%Y-%m-02') date, sum(t.amount) as amount_sold, sum(is_renewal) as renewals, sum(is_renewal=0) as new_sales
                                            FROM {$wpdb->prefix}rg_lead l
                                            INNER JOIN {$wpdb->prefix}rg_authorizenet_transaction t ON l.id = t.entry_id
                                            WHERE form_id={$config["form_id"]} AND t.transaction_type='payment'
                                            group by date
                                            order by date desc
                                            LIMIT 30");

            $sales_month = 0;
            $revenue_month = 0;
            if(!empty($results)){

                $data = "[";

                foreach($results as $result){
                    $timestamp = self::get_graph_timestamp($result->date);
                    if(self::matches_current_date("Y-m", $timestamp)){
                        $sales_month += $result->new_sales;
                        $revenue_month += $result->amount_sold;
                    }
                    $data .="[{$timestamp},{$result->amount_sold}],";

                    if($config["meta"]["type"] == "subscription"){
                        $sales_line = " <div class='authorizenet_tooltip_subscription'><span class='authorizenet_tooltip_heading'>" . __("New Subscriptions", "gravityformsauthorizenet") . ": </span><span class='authorizenet_tooltip_value'>" . $result->new_sales . "</span></div><div class='authorizenet_tooltip_subscription'><span class='authorizenet_tooltip_heading'>" . __("Renewals", "gravityformsauthorizenet") . ": </span><span class='authorizenet_tooltip_value'>" . $result->renewals . "</span></div>";
                    }
                    else{
                        $sales_line = "<div class='authorizenet_tooltip_sales'><span class='authorizenet_tooltip_heading'>" . __("Orders", "gravityformsauthorizenet") . ": </span><span class='authorizenet_tooltip_value'>" . $result->new_sales . "</span></div>";
                    }

                    $tooltips .= "\"<div class='authorizenet_tooltip_date'>" . GFCommon::format_date($result->date, false, "F, Y", false) . "</div>{$sales_line}<div class='authorizenet_tooltip_revenue'><span class='authorizenet_tooltip_heading'>" . __("Revenue", "gravityformsauthorizenet") . ": </span><span class='authorizenet_tooltip_value'>" . GFCommon::to_money($result->amount_sold) . "</span></div>\",";
                }
                $data = substr($data, 0, strlen($data)-1);
                $tooltips = substr($tooltips, 0, strlen($tooltips)-1);
                $data .="]";

                $series = "[{data:" . $data . "}]";
                $month_names = self::get_chart_month_names();
                $options ="
                {
                    xaxis: {mode: 'time', monthnames: $month_names, timeformat: '%b %y', minTickSize: [1, 'month']},
                    yaxis: {tickFormatter: convertToMoney},
                    bars: {show:true, align:'center', barWidth: (24 * 60 * 60 * 30 * 1000) - 130000000},
                    colors: ['#a3bcd3', '#14568a'],
                    grid: {hoverable: true, clickable: true, tickColor: '#F1F1F1', backgroundColor:'#FFF', borderWidth: 1, borderColor: '#CCC'}
                }";
            }
            switch($config["meta"]["type"]){
                case "product" :
                    $sales_label = __("Orders this Month", "gravityformsauthorizenet");
                break;

                case "donation" :
                    $sales_label = __("Donations this Month", "gravityformsauthorizenet");
                break;

                case "subscription" :
                    $sales_label = __("Subscriptions this Month", "gravityformsauthorizenet");
                break;
            }
            $revenue_month = GFCommon::to_money($revenue_month);
            return array("series" => $series, "options" => $options, "tooltips" => "[$tooltips]", "revenue_label" => __("Revenue this Month", "gravityformsauthorizenet"), "revenue" => $revenue_month, "sales_label" => $sales_label, "sales" => $sales_month);
    }

    private static function get_mysql_tz_offset(){
        $tz_offset = get_option("gmt_offset");

        //add + if offset starts with a number
        if(is_numeric(substr($tz_offset, 0, 1)))
            $tz_offset = "+" . $tz_offset;

        return $tz_offset . ":00";
    }

    private static function get_chart_month_names(){
        return "['" . __("Jan", "gravityformsauthorizenet") ."','" . __("Feb", "gravityformsauthorizenet") ."','" . __("Mar", "gravityformsauthorizenet") ."','" . __("Apr", "gravityformsauthorizenet") ."','" . __("May", "gravityformsauthorizenet") ."','" . __("Jun", "gravityformsauthorizenet") ."','" . __("Jul", "gravityformsauthorizenet") ."','" . __("Aug", "gravityformsauthorizenet") ."','" . __("Sep", "gravityformsauthorizenet") ."','" . __("Oct", "gravityformsauthorizenet") ."','" . __("Nov", "gravityformsauthorizenet") ."','" . __("Dec", "gravityformsauthorizenet") ."']";
    }

    // Edit Page
    private static function edit_page(){
        require_once(GFCommon::get_base_path() . "/currency.php");
        ?>
        <style>
            #authorizenet_submit_container{clear:both;}
            .authorizenet_col_heading{padding-bottom:2px; border-bottom: 1px solid #ccc; font-weight:bold; width:120px;}
            .authorizenet_field_cell {padding: 6px 17px 0 0; margin-right:15px;}

            .authorizenet_validation_error{ background-color:#FFDFDF; margin-top:4px; margin-bottom:6px; padding-top:6px; padding-bottom:6px; border:1px dotted #C89797;}
            .authorizenet_validation_error span {color: red;}
            .left_header{float:left; width:200px;}
            .margin_vertical_10{margin: 10px 0; padding-left:5px;}
            .margin_vertical_30{margin: 30px 0; padding-left:5px;}
            .width-1{width:300px;}
            .gf_authorizenet_invalid_form{margin-top:30px; background-color:#FFEBE8;border:1px solid #CC0000; padding:10px; width:600px;}
        </style>

        <script type="text/javascript" src="<?php echo GFCommon::get_base_url()?>/js/gravityforms.js"> </script>
        <script type="text/javascript">
            var form = Array();

            window['gf_currency_config'] = <?php echo json_encode(RGCurrency::get_currency("USD")) ?>;
            function FormatCurrency(element){
                var val = jQuery(element).val();
                jQuery(element).val(gformFormatMoney(val));
            }

            function ToggleSetupFee(){
                if(jQuery('#gf_authorizenet_setup_fee').is(':checked')){
                    jQuery('#authorizenet_setup_fee_container').show('slow');
                    jQuery('#authorizenet_enable_trial_container, #authorizenet_trial_period_container').slideUp();
                }
                else{
                    jQuery('#authorizenet_setup_fee_container').hide('slow');
                    jQuery('#authorizenet_enable_trial_container').slideDown();
                    ToggleTrial();
                }
            }

            function ToggleTrial(){
                if(jQuery('#gf_authorizenet_trial_period').is(':checked'))
                    jQuery('#authorizenet_trial_period_container').show('slow');
                else
                    jQuery('#authorizenet_trial_period_container').hide('slow');
            }

        </script>

        <div class="wrap">
            <img alt="<?php _e("Authorize.Net", "gravityformsauthorizenet") ?>" style="margin: 15px 7px 0pt 0pt; float: left;" src="<?php echo self::get_base_url() ?>/images/authorizenet_wordpress_icon_32.png"/>
            <h2><?php _e("Authorize.Net Transaction Settings", "gravityformsauthorizenet") ?></h2>

        <?php

        //getting setting id (0 when creating a new one)
        $id = !empty($_POST["authorizenet_setting_id"]) ? $_POST["authorizenet_setting_id"] : absint($_GET["id"]);
        $config = empty($id) ? array("meta" => array(), "is_active" => true) : GFAuthorizeNetData::get_feed($id);
        $is_validation_error = false;

        //updating meta information
        if(rgpost("gf_authorizenet_submit")){

            $config["form_id"] = absint(rgpost("gf_authorizenet_form"));
            $config["meta"]["type"] = rgpost("gf_authorizenet_type");
            $config["meta"]["enable_receipt"] = rgpost('gf_authorizenet_enable_receipt');
            $config["meta"]["update_post_action"] = rgpost('gf_authorizenet_update_action');

            // authorizenet conditional
            $config["meta"]["authorizenet_conditional_enabled"] = rgpost('gf_authorizenet_conditional_enabled');
            $config["meta"]["authorizenet_conditional_field_id"] = rgpost('gf_authorizenet_conditional_field_id');
            $config["meta"]["authorizenet_conditional_operator"] = rgpost('gf_authorizenet_conditional_operator');
            $config["meta"]["authorizenet_conditional_value"] = rgpost('gf_authorizenet_conditional_value');

            //recurring fields
            $config["meta"]["recurring_amount_field"] = rgpost("gf_authorizenet_recurring_amount");
            $config["meta"]["billing_cycle_number"] = rgpost("gf_authorizenet_billing_cycle_number");
            $config["meta"]["billing_cycle_type"] = rgpost("gf_authorizenet_billing_cycle_type");
            $config["meta"]["recurring_times"] = rgpost("gf_authorizenet_recurring_times");
            $config["meta"]["recurring_retry"] = rgpost('gf_authorizenet_recurring_retry');
            $config["meta"]["setup_fee_enabled"] = rgpost('gf_authorizenet_setup_fee');
            $config["meta"]["setup_fee_amount_field"] = rgpost('gf_authorizenet_setup_fee_amount');

            $has_setup_fee = $config["meta"]["setup_fee_enabled"];
            $config["meta"]["trial_period_enabled"] = $has_setup_fee ? false : rgpost('gf_authorizenet_trial_period');
            $config["meta"]["trial_amount"] = $has_setup_fee ? "" : rgpost('gf_authorizenet_trial_amount');
            $config["meta"]["trial_period_number"] = "1"; //$has_setup_fee ? "" : rgpost('gf_authorizenet_trial_period_number');

            //api settings fields
            $config["meta"]["api_settings_enabled"] = rgpost('gf_authorizenet_api_settings');
            $config["meta"]["api_mode"] = rgpost('gf_authorizenet_api_mode');
            $config["meta"]["api_login"] = rgpost('gf_authorizenet_api_login');
            $config["meta"]["api_key"] = rgpost('gf_authorizenet_api_key');

            if(!empty($config["meta"]["api_settings_enabled"]))
            {
                $is_valid = self::is_valid_key(self::get_local_api_settings($config));
                if($is_valid)
                {
                    $config["meta"]["api_valid"] = true;
                    $config["meta"]["api_message"] = "Valid PayPal Payments Pro credentials.";
                }
                else
                {
                    $config["meta"]["api_valid"] = false;
                    $config["meta"]["api_message"] = "Invalid PayPal Payments Pro credentials.";
                }
            }

            //-----------------

            $customer_fields = self::get_customer_fields();
            $config["meta"]["customer_fields"] = array();
            foreach($customer_fields as $field){
                $config["meta"]["customer_fields"][$field["name"]] = $_POST["authorizenet_customer_field_{$field["name"]}"];
            }

            $config = apply_filters('gform_authorizenet_save_config', $config);

            $is_validation_error = apply_filters("gform_authorizenet_config_validation", false, $config);

            if(!$is_validation_error){
                $id = GFAuthorizeNetData::update_feed($id, $config["form_id"], $config["is_active"], $config["meta"]);
                ?>
                <div class="updated fade" style="padding:6px"><?php echo sprintf(__("Feed Updated. %sback to list%s", "gravityformsauthorizenet"), "<a href='?page=gf_authorizenet'>", "</a>") ?></div>
                <?php
            }
            else{
                $is_validation_error = true;
            }
        }

        $form = isset($config["form_id"]) && $config["form_id"] ? $form = RGFormsModel::get_form_meta($config["form_id"]) : array();
        $settings = get_option("gf_authorizenet_settings");
        ?>
        <form method="post" action="">
            <input type="hidden" name="authorizenet_setting_id" value="<?php echo $id ?>" />

            <div class="margin_vertical_10 <?php echo $is_validation_error ? "authorizenet_validation_error" : "" ?>">
                <?php
                if($is_validation_error){
                    ?>
                    <span><?php _e('There was an issue saving your feed. Please address the errors below and try again.'); ?></span>
                    <?php
                }
                ?>
            </div> <!-- / validation message -->

            <?php
            if($settings["arb_configured"]=="on") {
            ?>
            <div class="margin_vertical_10">
                <label class="left_header" for="gf_authorizenet_type"><?php _e("Transaction Type", "gravityformsauthorizenet"); ?> <?php gform_tooltip("authorizenet_transaction_type") ?></label>

                <select id="gf_authorizenet_type" name="gf_authorizenet_type" onchange="SelectType(jQuery(this).val());">
                    <option value=""><?php _e("Select a transaction type", "gravityformsauthorizenet") ?></option>
                    <option value="product" <?php echo rgar($config['meta'], 'type') == "product" ? "selected='selected'" : "" ?>><?php _e("Products and Services", "gravityformsauthorizenet") ?></option>
                    <option value="subscription" <?php echo rgar($config['meta'], 'type') == "subscription" ? "selected='selected'" : "" ?>><?php _e("Subscriptions", "gravityformsauthorizenet") ?></option>
                </select>
            </div>
            <?php } else {$config["meta"]["type"]= "product" ?>

                  <input id="gf_authorizenet_type" type="hidden" name="gf_authorizenet_type" value="product">


            <?php } ?>
            <div id="authorizenet_form_container" valign="top" class="margin_vertical_10" <?php echo empty($config["meta"]["type"]) ? "style='display:none;'" : "" ?>>
                <label for="gf_authorizenet_form" class="left_header"><?php _e("Gravity Form", "gravityformsauthorizenet"); ?> <?php gform_tooltip("authorizenet_gravity_form") ?></label>

                <select id="gf_authorizenet_form" name="gf_authorizenet_form" onchange="SelectForm(jQuery('#gf_authorizenet_type').val(), jQuery(this).val(), '<?php echo rgar($config, 'id') ?>');">
                    <option value=""><?php _e("Select a form", "gravityformsauthorizenet"); ?> </option>
                    <?php

                    $active_form = rgar($config, 'form_id');
                    $available_forms = GFAuthorizeNetData::get_available_forms($active_form);

                    foreach($available_forms as $current_form) {
                        $selected = absint($current_form->id) == rgar($config, 'form_id') ? 'selected="selected"' : '';
                        ?>

                            <option value="<?php echo absint($current_form->id) ?>" <?php echo $selected; ?>><?php echo esc_html($current_form->title) ?></option>

                        <?php
                    }
                    ?>
                </select>
                &nbsp;&nbsp;
                <img src="<?php echo GFAuthorizeNet::get_base_url() ?>/images/loading.gif" id="authorizenet_wait" style="display: none;"/>

                <div id="gf_authorizenet_invalid_product_form" class="gf_authorizenet_invalid_form"  style="display:none;">
                    <?php _e("The form selected does not have any Product fields. Please add a Product field to the form and try again.", "gravityformsauthorizenet") ?>
                </div>
                <div id="gf_authorizenet_invalid_creditcard_form" class="gf_authorizenet_invalid_form" style="display:none;">
                    <?php _e("The form selected does not have a credit card field. Please add a credit card field to the form and try again.", "gravityformsauthorizenet") ?>
                </div>
            </div>
            <div id="authorizenet_field_group" valign="top" <?php echo strlen(rgars($config,"meta/type")) == 0 || empty($config["form_id"]) ? "style='display:none;'" : "" ?>>

                <div id="authorizenet_field_container_subscription" class="authorizenet_field_container" valign="top" <?php echo rgars($config,"meta/type") != "subscription" ? "style='display:none;'" : ""?>>
                    <div class="margin_vertical_10">
                        <label class="left_header" for="gf_authorizenet_recurring_amount"><?php _e("Recurring Amount", "gravityformsauthorizenet"); ?> <?php gform_tooltip("authorizenet_recurring_amount") ?></label>
                        <select id="gf_authorizenet_recurring_amount" name="gf_authorizenet_recurring_amount">
                            <?php echo self::get_product_options($form, rgar($config["meta"],"recurring_amount_field"),true) ?>
                        </select>
                    </div>

                    <div class="margin_vertical_10">
                        <label class="left_header" for="gf_authorizenet_billing_cycle_number"><?php _e("Billing Cycle", "gravityformsauthorizenet"); ?> <?php gform_tooltip("authorizenet_billing_cycle") ?></label>
                        <select id="gf_authorizenet_billing_cycle_number" name="gf_authorizenet_billing_cycle_number">
                            <?php
                            for($i=1; $i<=100; $i++){
                            ?>
                                <option value="<?php echo $i ?>" <?php echo rgar($config["meta"],"billing_cycle_number") == $i ? "selected='selected'" : "" ?>><?php echo $i ?></option>
                            <?php
                            }
                            ?>
                        </select>&nbsp;
                        <select id="gf_authorizenet_billing_cycle_type" name="gf_authorizenet_billing_cycle_type" onchange="SetPeriodNumber('#gf_authorizenet_billing_cycle_number', jQuery(this).val());">
                            <option value="D" <?php echo rgars($config,"meta/billing_cycle_type") == "D" ? "selected='selected'" : "" ?>><?php _e("day(s)", "gravityformsauthorizenet") ?></option>
                            <option value="M" <?php echo rgars($config,"meta/billing_cycle_type") == "M" || strlen(rgars($config,"meta/billing_cycle_type")) == 0 ? "selected='selected'" : "" ?>><?php _e("month(s)", "gravityformsauthorizenet") ?></option>
                        </select>
                    </div>

                    <div class="margin_vertical_10">
                        <label class="left_header" for="gf_authorizenet_recurring_times"><?php _e("Recurring Times", "gravityformsauthorizenet"); ?> <?php gform_tooltip("authorizenet_recurring_times") ?></label>
                        <select id="gf_authorizenet_recurring_times" name="gf_authorizenet_recurring_times">
                            <option><?php _e("Infinite", "gravityformsauthorizenet") ?></option>
                            <?php
                            for($i=2; $i<=100; $i++){
                                $selected = ($i == rgar($config["meta"],"recurring_times")) ? 'selected="selected"' : '';
                                ?>
                                <option value="<?php echo $i ?>" <?php echo $selected; ?>><?php echo $i ?></option>
                                <?php
                            }
                            ?>
                        </select>&nbsp;&nbsp;

                    </div>

                    <div class="margin_vertical_10">
                        <label class="left_header" for="gf_authorizenet_setup_fee"><?php _e("Setup Fee", "gravityformsauthorizenet"); ?> <?php gform_tooltip("authorizenet_setup_fee_enable") ?></label>
                        <input type="checkbox" onchange="if(this.checked) {jQuery('#gf_paypalpro_setup_fee_amount').val('Select a field');}" name="gf_authorizenet_setup_fee" id="gf_authorizenet_setup_fee" value="1" onclick="ToggleSetupFee();" <?php echo rgars($config, "meta/setup_fee_enabled") ? "checked='checked'" : ""?> />
                        <label class="inline" for="gf_authorizenet_setup_fee"><?php _e("Enable", "gravityformsauthorizenet"); ?></label>
                        &nbsp;&nbsp;&nbsp;
                        <span id="authorizenet_setup_fee_container" <?php echo rgars($config, "meta/setup_fee_enabled") ? "" : "style='display:none;'" ?>>
                            <select id="gf_authorizenet_setup_fee_amount" name="gf_authorizenet_setup_fee_amount">
                                <?php echo self::get_product_options($form, rgar($config["meta"],"setup_fee_amount_field"),false) ?>
                            </select>
                        </span>
                    </div>

                    <div id="authorizenet_enable_trial_container" class="margin_vertical_10" <?php echo rgars($config, "meta/setup_fee_enabled") ? "style='display:none;'" : "" ?>>
                        <label class="left_header" for="gf_authorizenet_trial_period"><?php _e("Trial Period", "gravityformsauthorizenet"); ?> <?php gform_tooltip("authorizenet_trial_period_enable") ?></label>
                        <input type="checkbox" name="gf_authorizenet_trial_period" id="gf_authorizenet_trial_period" value="1" onclick="ToggleTrial();" <?php echo rgars($config,"meta/trial_period_enabled") ? "checked='checked'" : ""?> />
                        <label class="inline" for="gf_authorizenet_trial_period"><?php _e("Enable", "gravityformsauthorizenet"); ?></label>
                    </div>

                    <div id="authorizenet_trial_period_container" <?php echo rgars($config,"meta/trial_period_enabled")  && !rgars($config, "meta/setup_fee_enabled") ? "" : "style='display:none;'" ?>>
                        <div class="margin_vertical_10">
                            <label class="left_header" for="gf_authorizenet_trial_amount"><?php _e("Trial Amount", "gravityformsauthorizenet"); ?> <?php gform_tooltip("authorizenet_trial_amount") ?></label>
                            <input type="text" name="gf_authorizenet_trial_amount" id="gf_authorizenet_trial_amount" value="<?php echo rgar($config["meta"],"trial_amount") ?>" onchange="FormatCurrency(this);"/>
                        </div>
                        <!--<div class="margin_vertical_10">
                            <label class="left_header" for="gf_authorizenet_trial_period_number"><?php _e("Trial Recurring Times", "gravityformsauthorizenet"); ?> <?php gform_tooltip("authorizenet_trial_period") ?></label>
                            <select id="gf_authorizenet_trial_period_number" name="gf_authorizenet_trial_period_number">
                                <?php
                                for($i=1; $i<=99; $i++){
                                ?>
                                    <option value="<?php echo $i ?>" <?php echo rgars($config,"meta/trial_period_number") == $i ? "selected='selected'" : "" ?>><?php echo $i ?></option>
                                <?php
                                }
                                ?>
                            </select>
                        </div>-->

                    </div>

                </div>

                <div class="margin_vertical_10">
                    <label class="left_header"><?php _e("Billing Information", "gravityformsauthorizenet"); ?> <?php gform_tooltip("authorizenet_customer") ?></label>

                    <div id="authorizenet_customer_fields">
                        <?php
                            if(!empty($form))
                                echo self::get_customer_information($form, $config);
                        ?>
                    </div>
                </div>


                <div class="margin_vertical_10">
                    <label class="left_header"><?php _e("Options", "gravityformsauthorizenet"); ?> <?php gform_tooltip("authorizenet_options") ?></label>

                    <ul style="overflow:hidden;">
                        <li id="authorizenet_enable_receipt">
                            <input type="checkbox" name="gf_authorizenet_enable_receipt" id="gf_authorizenet_enable_receipt" <?php echo rgar($config["meta"], 'enable_receipt') ? "checked='checked'"  : "value='1'" ?> />
                            <label class="inline" for="gf_authorizenet_enable_receipt"><?php _e("Send Authorize.Net email receipt.", "gravityformsauthorizenet"); ?> <?php gform_tooltip("authorizenet_disable_user_notification") ?></label>
                        </li>
                        <?php
                        $display_post_fields = !empty($form) ? GFCommon::has_post_field($form["fields"]) : false;
                        ?>
                        <li id="authorizenet_post_update_action" <?php echo $display_post_fields && $config["meta"]["type"] == "subscription" ? "" : "style='display:none;'" ?>>
                            <input type="checkbox" name="gf_authorizenet_update_post" id="gf_authorizenet_update_post" value="1" <?php echo rgar($config["meta"],"update_post_action") ? "checked='checked'" : ""?> onclick="var action = this.checked ? 'draft' : ''; jQuery('#gf_authorizenet_update_action').val(action);" />
                            <label class="inline" for="gf_authorizenet_update_post"><?php _e("Update Post when subscription is cancelled.", "gravityformsauthorizenet"); ?> <?php gform_tooltip("authorizenet_update_post") ?></label>
                            <select id="gf_authorizenet_update_action" name="gf_authorizenet_update_action" onchange="var checked = jQuery(this).val() ? 'checked' : false; jQuery('#gf_authorizenet_update_post').attr('checked', checked);">
                                <option value=""></option>
                                <option value="draft" <?php echo rgar($config["meta"],"update_post_action") == "draft" ? "selected='selected'" : ""?>><?php _e("Mark Post as Draft", "gravityformsauthorizenet") ?></option>
                                <option value="delete" <?php echo rgar($config["meta"],"update_post_action") == "delete" ? "selected='selected'" : ""?>><?php _e("Delete Post", "gravityformsauthorizenet") ?></option>
                            </select>
                        </li>

                        <?php do_action("gform_authorizenet_action_fields", $config, $form) ?>
                    </ul>
                </div>

                <?php do_action("gform_authorizenet_add_option_group", $config, $form); ?>

                <div id="gf_authorizenet_conditional_section" valign="top" class="margin_vertical_10">
                    <label for="gf_authorizenet_conditional_optin" class="left_header"><?php _e("Authorize.Net Condition", "gravityformsauthorizenet"); ?> <?php gform_tooltip("authorizenet_conditional") ?></label>

                    <div id="gf_authorizenet_conditional_option">
                        <table cellspacing="0" cellpadding="0">
                            <tr>
                                <td>
                                    <input type="checkbox" id="gf_authorizenet_conditional_enabled" name="gf_authorizenet_conditional_enabled" value="1" onclick="if(this.checked){jQuery('#gf_authorizenet_conditional_container').fadeIn('fast');} else{ jQuery('#gf_authorizenet_conditional_container').fadeOut('fast'); }" <?php echo rgar($config['meta'], 'authorizenet_conditional_enabled') ? "checked='checked'" : ""?>/>
                                    <label for="gf_authorizenet_conditional_enable"><?php _e("Enable", "gravityformsauthorizenet"); ?></label>
                                </td>
                            </tr>
                            <tr>
                                <td>
                                    <div id="gf_authorizenet_conditional_container" <?php echo !rgar($config['meta'], 'authorizenet_conditional_enabled') ? "style='display:none'" : ""?>>
                                        <div id="gf_authorizenet_conditional_fields" style="display:none">
                                            <?php _e("Send to Authorize.Net if ", "gravityformsauthorizenet") ?>
                                            <select id="gf_authorizenet_conditional_field_id" name="gf_authorizenet_conditional_field_id" class="optin_select" onchange='jQuery("#gf_authorizenet_conditional_value_container").html(GetFieldValues(jQuery(this).val(), "", 20));'></select>
                                            <select id="gf_authorizenet_conditional_operator" name="gf_authorizenet_conditional_operator">
                                                <option value="is" <?php echo rgar($config['meta'], 'authorizenet_conditional_operator') == "is" ? "selected='selected'" : "" ?>><?php _e("is", "gravityformsauthorizenet") ?></option>
                                                <option value="isnot" <?php echo rgar($config['meta'], 'authorizenet_conditional_operator') == "isnot" ? "selected='selected'" : "" ?>><?php _e("is not", "gravityformsauthorizenet") ?></option>
                                                <option value=">" <?php echo rgar($config['meta'], 'authorizenet_conditional_operator') == ">" ? "selected='selected'" : "" ?>><?php _e("greater than", "gravityformsauthorizenet") ?></option>
                                                <option value="<" <?php echo rgar($config['meta'], 'authorizenet_conditional_operator') == "<" ? "selected='selected'" : "" ?>><?php _e("less than", "gravityformsauthorizenet") ?></option>
                                                <option value="contains" <?php echo rgar($config['meta'], 'authorizenet_conditional_operator') == "contains" ? "selected='selected'" : "" ?>><?php _e("contains", "gravityformsauthorizenet") ?></option>
                                                <option value="starts_with" <?php echo rgar($config['meta'], 'authorizenet_conditional_operator') == "starts_with" ? "selected='selected'" : "" ?>><?php _e("starts with", "gravityformsauthorizenet") ?></option>
                                                <option value="ends_with" <?php echo rgar($config['meta'], 'authorizenet_conditional_operator') == "ends_with" ? "selected='selected'" : "" ?>><?php _e("ends with", "gravityformsauthorizenet") ?></option>
                                            </select>
                                            <div id="gf_authorizenet_conditional_value_container" name="gf_authorizenet_conditional_value_container" style="display:inline;"></div>
                                        </div>
                                        <div id="gf_authorizenet_conditional_message" style="display:none">
                                            <?php _e("To create a registration condition, your form must have a field supported by conditional logic.", "gravityform"); ?>
                                        </div>
                                    </div>
                                </td>
                            </tr>
                        </table>
                    </div>

                </div> <!-- / authorizenet conditional -->

                <div class="margin_vertical_10">
                        <label class="left_header" for="gf_authorizenet_api_settings"><?php _e("API Settings", "gravityformsauthorizenet"); ?> <?php gform_tooltip("authorizenet_api_settings_enable") ?></label>
                        <input type="checkbox" name="gf_authorizenet_api_settings" id="gf_authorizenet_api_settings" value="1" onclick="if(jQuery(this).is(':checked')) jQuery('#authorizenet_api_settings_container').show('slow'); else jQuery('#authorizenet_api_settings_container').hide('slow');" <?php echo rgars($config, "meta/api_settings_enabled") ? "checked='checked'" : ""?> />
                        <label class="inline" for="gf_authorizenet_api_settings"><?php _e("Override Default Settings", "gravityformsauthorizenet"); ?></label>
                </div>

                <div id="authorizenet_api_settings_container" <?php echo rgars($config, "meta/api_settings_enabled") ? "" : "style='display:none;'" ?>>

                    <div class="margin_vertical_10">
                        <label class="left_header" for="gf_authorizenet_api_mode"><?php _e("Mode", "gravityformsauthorizenet"); ?> <?php gform_tooltip("authorizenet_api_mode") ?></label>
                        <input type="radio" name="gf_authorizenet_api_mode" value="production" <?php echo rgar($config["meta"],"api_mode") != "test" ? "checked='checked'" : "" ?>/>
                        <label class="inline" for="gf_authorizenet_api_mode_production"><?php _e("Production", "gravityformsauthorizenet"); ?></label>
                        &nbsp;&nbsp;&nbsp;
                        <input type="radio" name="gf_authorizenet_api_mode" value="test" <?php echo rgar($config["meta"],"api_mode") == "test" ? "checked='checked'" : "" ?>/>
                        <label class="inline" for="gf_authorizenet_api_mode_test"><?php _e("Test", "gravityformsauthorizenet"); ?></label>
                    </div>

                    <div class="margin_vertical_10">
                        <label class="left_header" for="gf_authorizenet_api_login"><?php _e("API Login ID", "gravityformsauthorizenet"); ?> <?php gform_tooltip("authorizenet_api_login") ?></label>
                        <input class="size-1" id="gf_authorizenet_api_login" name="gf_authorizenet_api_login" value="<?php echo rgar($config["meta"],"api_login") ?>" />
                        <img src="<?php echo self::get_base_url() ?>/images/<?php echo $config["meta"]["api_valid"] ? "tick.png" : "stop.png" ?>" border="0" alt="<?php echo $config["meta"]["api_message"]  ?>" title="<?php echo $config["meta"]["api_message"] ?>" style="display:<?php echo empty($config["meta"]["api_message"]) ? 'none;' : 'inline;' ?>" />
                    </div>

                    <div class="margin_vertical_10">
                        <label class="left_header" for="gf_authorizenet_api_key"><?php _e("Transaction Key", "gravityformsauthorizenet"); ?> <?php gform_tooltip("paypalpro_api_key") ?></label>
                        <input class="size-1" id="gf_authorizenet_api_key" name="gf_authorizenet_api_key" value="<?php echo rgar($config["meta"],"api_key") ?>" />
                        <img src="<?php echo self::get_base_url() ?>/images/<?php echo $config["meta"]["api_valid"] ? "tick.png" : "stop.png" ?>" border="0" alt="<?php echo $config["meta"]["api_message"] ?>" title="<?php echo $config["meta"]["api_message"] ?>" style="display:<?php echo empty($config["meta"]["api_message"]) ? 'none;' : 'inline;' ?>" />
                    </div>

                </div>

                <div id="authorizenet_submit_container" class="margin_vertical_30">
                    <input type="submit" name="gf_authorizenet_submit" value="<?php echo empty($id) ? __("  Save  ", "gravityformsauthorizenet") : __("Update", "gravityformsauthorizenet"); ?>" class="button-primary"/>
                    <input type="button" value="<?php _e("Cancel", "gravityformsauthorizenet"); ?>" class="button" onclick="javascript:document.location='admin.php?page=gf_authorizenet'" />
                </div>
            </div>
        </form>
        </div>

        <script type="text/javascript">
            jQuery(document).ready(function(){
                SetPeriodNumber('#gf_authorizenet_billing_cycle_number', jQuery("#gf_authorizenet_billing_cycle_type").val());
            });

            <?php
            if(!empty($config["form_id"])){
                ?>

                // initiliaze form object
                form = <?php echo GFCommon::json_encode($form)?> ;

                // initializing registration condition drop downs
                jQuery(document).ready(function(){
                    var selectedField = "<?php echo str_replace('"', '\"', $config["meta"]["authorizenet_conditional_field_id"])?>";
                    var selectedValue = "<?php echo str_replace('"', '\"', $config["meta"]["authorizenet_conditional_value"])?>";
                    SetAuthorizeNetCondition(selectedField, selectedValue);
                });

                <?php
            }
            ?>

            function SelectType(type){
                jQuery("#authorizenet_field_group").slideUp();

                jQuery("#authorizenet_field_group input[type=\"text\"], #authorizenet_field_group select").val("");
                jQuery("#gf_authorizenet_trial_period_type, #gf_authorizenet_billing_cycle_type").val("M");

                jQuery("#authorizenet_field_group input:checked").attr("checked", false);

                if(type){
                    jQuery("#authorizenet_form_container").slideDown();
                    jQuery("#gf_authorizenet_form").val("");
                }
                else{
                    jQuery("#authorizenet_form_container").slideUp();
                }
            }

            function SelectForm(type, formId, settingId){
                if(!formId){
                    jQuery("#authorizenet_field_group").slideUp();
                    return;
                }

                jQuery("#authorizenet_wait").show();
                jQuery("#authorizenet_field_group").slideUp();

                var mysack = new sack("<?php bloginfo( 'wpurl' ); ?>/wp-admin/admin-ajax.php" );
                mysack.execute = 1;
                mysack.method = 'POST';
                mysack.setVar( "action", "gf_select_authorizenet_form" );
                mysack.setVar( "gf_select_authorizenet_form", "<?php echo wp_create_nonce("gf_select_authorizenet_form") ?>" );
                mysack.setVar( "type", type);
                mysack.setVar( "form_id", formId);
                mysack.setVar( "setting_id", settingId);
                mysack.encVar( "cookie", document.cookie, false );
                mysack.onError = function() {jQuery("#authorizenet_wait").hide(); alert('<?php _e("Ajax error while selecting a form", "gravityformsauthorizenet") ?>' )};
                mysack.runAJAX();

                return true;
            }

            function EndSelectForm(form_meta, customer_fields, recurring_amount_options, product_field_options){
                //setting global form object
                form = form_meta;

                var type = jQuery("#gf_authorizenet_type").val();

                jQuery(".gf_authorizenet_invalid_form").hide();
                if( (type == "product" || type =="subscription") && GetFieldsByType(["product"]).length == 0){
                    jQuery("#gf_authorizenet_invalid_product_form").show();
                    jQuery("#authorizenet_wait").hide();
                    return;
                }
                else if( (type == "product" || type =="subscription") && GetFieldsByType(["creditcard"]).length == 0){
                    jQuery("#gf_authorizenet_invalid_creditcard_form").show();
                    jQuery("#authorizenet_wait").hide();
                    return;
                }

                jQuery(".authorizenet_field_container").hide();
                jQuery("#authorizenet_customer_fields").html(customer_fields);
                jQuery("#gf_authorizenet_recurring_amount").html(recurring_amount_options);

                jQuery("#gf_authorizenet_setup_fee_amount").html(product_field_options);

                var post_fields = GetFieldsByType(["post_title", "post_content", "post_excerpt", "post_category", "post_custom_field", "post_image", "post_tag"]);
                if(type == "subscription" && post_fields.length > 0){
                    jQuery("#authorizenet_post_update_action").show();
                }
                else{
                    jQuery("#gf_authorizenet_update_post").attr("checked", false);
                    jQuery("#authorizenet_post_update_action").hide();
                }

                SetPeriodNumber('#gf_authorizenet_billing_cycle_number', jQuery("#gf_authorizenet_billing_cycle_type").val());

                //Calling callback functions
                jQuery(document).trigger('authorizenetFormSelected', [form]);

                jQuery("#gf_authorizenet_conditional_enabled").attr('checked', false);
                SetAuthorizeNetCondition("","");

                jQuery("#authorizenet_field_container_" + type).show();
                jQuery("#authorizenet_field_group").slideDown();
                jQuery("#authorizenet_wait").hide();
            }

            function SetPeriodNumber(element, type){
                var prev = jQuery(element).val();

                var min = 1;
                var max = 0;
                switch(type){
                    case "D" :
                        min = 7;
                        max = 365;
                    break;
                    case "M" :
                        max = 12;
                    break;
                }
                var str="";
                for(var i=min; i<=max; i++){
                    var selected = prev == i ? "selected='selected'" : "";
                    str += "<option value='" + i + "' " + selected + ">" + i + "</option>";
                }
                jQuery(element).html(str);
            }

            function GetFieldsByType(types){
                var fields = new Array();
                for(var i=0; i<form["fields"].length; i++){
                    if(IndexOf(types, form["fields"][i]["type"]) >= 0)
                        fields.push(form["fields"][i]);
                }
                return fields;
            }

            function IndexOf(ary, item){
                for(var i=0; i<ary.length; i++)
                    if(ary[i] == item)
                        return i;

                return -1;
            }

            function SetAuthorizeNetCondition(selectedField, selectedValue){
                // load form fields
                jQuery("#gf_authorizenet_conditional_field_id").html(GetSelectableFields(selectedField, 20));
                var optinConditionField = jQuery("#gf_authorizenet_conditional_field_id").val();
                var checked = jQuery("#gf_authorizenet_conditional_enabled").attr('checked');

                if(optinConditionField){
                    jQuery("#gf_authorizenet_conditional_message").hide();
                    jQuery("#gf_authorizenet_conditional_fields").show();
                    jQuery("#gf_authorizenet_conditional_value_container").html(GetFieldValues(optinConditionField, selectedValue, 20));
                    jQuery("#gf_authorizenet_conditional_value").val(selectedValue);
                }
                else{
                    jQuery("#gf_authorizenet_conditional_message").show();
                    jQuery("#gf_authorizenet_conditional_fields").hide();
                }

                if(!checked) jQuery("#gf_authorizenet_conditional_container").hide();

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
                    str += '<?php $dd = wp_dropdown_categories(array("class"=>"optin_select", "orderby"=> "name", "id"=> "gf_authorizenet_conditional_value", "name"=> "gf_authorizenet_conditional_value", "hierarchical"=>true, "hide_empty"=>0, "echo"=>false)); echo str_replace("\n","", str_replace("'","\\'",$dd)); ?>';
                }
                else if(field.choices){
                    str += '<select id="gf_authorizenet_conditional_value" name="gf_authorizenet_conditional_value" class="optin_select">'

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
                    str += "<input type='text' placeholder='<?php _e("Enter value", "gravityforms"); ?>' id='gf_authorizenet_conditional_value' name='gf_authorizenet_conditional_value' value='" + selectedValue.replace(/'/g, "&#039;") + "'>";
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
                    fieldLabel = typeof fieldLabel == 'undefined' ? '' : fieldLabel;
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

    public static function select_authorizenet_form(){

        check_ajax_referer("gf_select_authorizenet_form", "gf_select_authorizenet_form");

        $type = $_POST["type"];
        $form_id =  intval($_POST["form_id"]);
        $setting_id =  intval($_POST["setting_id"]);

        //fields meta
        $form = RGFormsModel::get_form_meta($form_id);

        $customer_fields = self::get_customer_information($form);
        $recurring_amount_fields = self::get_product_options($form, "",true);
        $product_fields = self::get_product_options($form, "",false);

        die("EndSelectForm(" . GFCommon::json_encode($form) . ", '" . str_replace("'", "\'", $customer_fields) . "', '" . str_replace("'", "\'", $recurring_amount_fields) . "', '" . str_replace("'", "\'", $product_fields) . "');");
    }

    public static function add_permissions(){
        global $wp_roles;
        $wp_roles->add_cap("administrator", "gravityforms_authorizenet");
        $wp_roles->add_cap("administrator", "gravityforms_authorizenet_uninstall");
    }

    //Target of Member plugin filter. Provides the plugin with Gravity Forms lists of capabilities
    public static function members_get_capabilities( $caps ) {
        return array_merge($caps, array("gravityforms_authorizenet", "gravityforms_authorizenet_uninstall"));
    }

    public static function has_authorizenet_condition($form, $config) {

        $config = $config["meta"];

        $operator = $config["authorizenet_conditional_operator"];
        $field = RGFormsModel::get_field($form, $config["authorizenet_conditional_field_id"]);

        if(empty($field) || !$config["authorizenet_conditional_enabled"])
            return true;

        // if conditional is enabled, but the field is hidden, ignore conditional
        $is_visible = !RGFormsModel::is_field_hidden($form, $field, array());

        $field_value = RGFormsModel::get_field_value($field, array());
        $is_value_match = RGFormsModel::is_value_match($field_value, $config["authorizenet_conditional_value"], $operator);
        $go_to_authorizenet = $is_value_match && $is_visible;

        return  $go_to_authorizenet;
    }

    public static function get_config($form){
        if(!class_exists("GFAuthorizeNetData"))
            require_once(self::get_base_path() . "/data.php");

        //Getting authorizenet settings associated with this transaction
        $configs = GFAuthorizeNetData::get_feed_by_form($form["id"]);
        if(!$configs)
            return false;

        foreach($configs as $config){
            if(self::has_authorizenet_condition($form, $config))
                return $config;
        }

        return false;
    }

    public static function get_creditcard_field($form){
        $fields = GFCommon::get_fields_by_type($form, array("creditcard"));
        return empty($fields) ? false : $fields[0];
    }

    private static function is_ready_for_capture($validation_result){

        //if form has already failed validation or this is not the last page, abort
        if($validation_result["is_valid"] == false || !self::is_last_page($validation_result["form"]))
            return false;

        //getting config that matches condition (if conditions are enabled)
        $config = self::get_config($validation_result["form"]);
        if(!$config)
            return false;

        //making sure credit card field is visible
        $creditcard_field = self::get_creditcard_field($validation_result["form"]);
        if(RGFormsModel::is_field_hidden($validation_result["form"], $creditcard_field, array()))
            return false;

        return $config;
    }

    private static function is_last_page($form){
        $current_page = GFFormDisplay::get_source_page($form["id"]);
        $target_page = GFFormDisplay::get_target_page($form, $current_page, rgpost("gform_field_values"));
        return $target_page == 0;
    }

    private static function get_trial_info($config){

        $trial_amount = false;
        $trial_occurrences = 0;
        if($config["meta"]["trial_period_enabled"] == 1)
        {
            $trial_occurrences = $config["meta"]["trial_period_number"];
            $trial_amount = $config["meta"]["trial_amount"];
            if(empty($trial_amount))
                $trial_amount = 0;
        }
        $trial_enabled = $trial_amount !== false;

        if($trial_enabled && !empty($trial_amount))
            $trial_amount = GFCommon::to_number($trial_amount);

        return array("trial_enabled" => $trial_enabled, "trial_amount" => $trial_amount, "trial_occurrences" => $trial_occurrences);
    }

    private static function get_local_api_settings($config)
    {
        if(rgar($config["meta"],"api_settings_enabled") == 1)
            $local_api_settings = array("mode" => $config["meta"]["api_mode"], "login" => $config["meta"]["api_login"], "key" =>  $config["meta"]["api_key"]);
        else
            $local_api_settings = array();

        return $local_api_settings;
    }

    public static function authorizenet_validation($validation_result){

        $config = self::is_ready_for_capture($validation_result);
        if(!$config)
            return $validation_result;

        if($config["meta"]["type"] == "product"){
            //making one time payment
            $validation_result = self::make_product_payment($config, $validation_result);
            return $validation_result;
        }
        else
        {
            // creating subscription
            $validation_result = self::start_subscription($config, $validation_result);
            return $validation_result;
        }
    }

    private static function has_visible_products($form){

        foreach($form["fields"] as $field){
            if($field["type"] == "product" && !RGFormsModel::is_field_hidden($form, $field, ""))
                return true;
        }
        return false;
    }

    private static function make_product_payment($config, $validation_result){

        $form = $validation_result["form"];

        self::log_debug("Starting to make a product payment for form: {$form["id"]}");

        $form_data = self::get_form_data($form, $config);
        $transaction = self::get_initial_transaction($form_data, $config);

        //don't process payment if total is 0, but act as if the transaction was successful
        if($form_data["amount"] == 0){
            self::log_debug("Amount is 0. No need to process payment, but act as if transaction was successful");

            //blank out credit card field if this is the last page
            if(self::is_last_page($form)){
                $card_field = self::get_creditcard_field($form);
                $_POST["input_{$card_field["id"]}_1"] = "";
            }

            //creating dummy transaction response if there are any visible product fields in the form
            if(self::has_visible_products($form)){
                self::$transaction_response = array("transaction_id" => "N/A", "amount" => 0, "transaction_type" => 1);
            }

            return $validation_result;
        }

        self::log_debug("Sending an authorizeAndCapture() transaction.");

        $transaction = apply_filters("gform_authorizenet_before_single_payment", $transaction, $form_data, $config, $form);

        //capture funds
        $response = $transaction->authorizeAndCapture();

        self::log_debug(print_r($response, true));

        if($response->approved )
        {
            self::log_debug("Transaction approved. ID: {$response->transaction_id} - Amount: {$response->amount}");

            self::$transaction_response = array("transaction_id" => $response->transaction_id, "amount" => $response->amount, "transaction_type" => 1, "invoice_number" => $response->invoice_number);

            $validation_result["is_valid"] = true;
            return $validation_result;
        }
        else
        {
            self::log_error("Transaction failed");
            self::log_error(print_r($response, true));

            // Payment for single transaction was not successful
            return self::set_validation_result($validation_result, $_POST, $response, "aim");
        }
    }

    private static function start_subscription($config, $validation_result){

        $form = $validation_result["form"];

        self::log_debug("Starting subscription for form: {$form["id"]}");

        $form_data = self::get_form_data($form, $config);
        $invoice_number = uniqid();
        $transaction = self::get_initial_transaction($form_data, $config, $invoice_number);

        $initial_transaction_amount = $form_data["amount"] + $form_data["fee_amount"];
        $regular_amount = $form_data["amount"];

        //getting trial information
        $trial_info = self::get_trial_info($config);

        if($trial_info["trial_enabled"] && $trial_info["trial_amount"] == 0)
        {
            self::log_debug("Free trial. Authorizing credit card");

            //Free trial. Just authorize the credit card to make sure the information is correct
            $aim_response = $transaction->authorizeOnly();
        }
        else if($trial_info["trial_enabled"]){

            self::log_debug("Paid trial. Capturing trial amount");

            //Paid trial. Capture trial amount
            $transaction->amount = $trial_info["trial_amount"];
            $transaction = apply_filters("gform_authorizenet_before_trial_payment", $transaction, $form_data, $config, $form);

            $aim_response = $transaction->authorizeAndCapture();
        }
        else{

            self::log_debug("No trial. Capturing payment for first cycle");

            //No trial. Capture payment for first cycle
            $aim_response = $transaction->authorizeAndCapture();
        }

        self::log_debug(print_r($aim_response, true));

        //If first transaction was successful, move on to create subscription.
        if($aim_response->approved ){

            //Create subscription.
            $subscription = self::get_subscription($config, $form_data, $trial_info, $invoice_number);

            //Send subscription request.
            $request = self::get_arb(self::get_local_api_settings($config));

            self::log_debug("Sending create subscription request");

            $subscription = apply_filters("gform_authorizenet_before_start_subscription", $subscription, $form_data, $config, $form);

            $arb_response = $request->createSubscription($subscription);

            self::log_debug(print_r($arb_response, true));

            if($arb_response->isOk())
            {
                self::log_debug("Subscription created successfully");

                $subscription_id = $arb_response->getSubscriptionId();

                do_action("gform_authorizenet_after_subscription_created", $subscription_id, $regular_amount, $initial_transaction_amount);

                self::$transaction_response = array("transaction_id" => $subscription_id, "amount" => $initial_transaction_amount, "transaction_type" => 2, "regular_amount" => $regular_amount, "invoice_number" => $invoice_number );
                if($trial_info["trial_enabled"])
                    self::$transaction_response["trial_amount"] = $trial_info["trial_amount"];
                if($form_data["fee_amount"] > 0)
                    self::$transaction_response["fee_amount"] = $form_data["fee_amount"];

                $validation_result["is_valid"] = true;
                return $validation_result;
            }
            else
            {

                $void = self::get_aim(self::get_local_api_settings($config));
                $void->setFields(
                    array(
                    'amount' => $form_data["amount"],
                    'card_num' => $form_data["card_number"],
                    'trans_id' => $aim_response->transaction_id,
                    )
                );

                self::log_error("Subscription failed. Voiding first payment.");
                self::log_error(print_r($arb_response, true));

                $void_response = $void->Void();

                self::log_debug(print_r($void_response, true));

                return self::set_validation_result($validation_result, $_POST, $arb_response, "arb");
            }
        }
        else
        {
            self::log_error("Initial payment failed. Aborting subscription.");
            self::log_error(print_r($aim_response, true));

            // First payment was not succesfull, subscription was not created, need to display error message
            return self::set_validation_result($validation_result, $_POST, $aim_response, "aim");
        }
    }

    private static function get_form_data($form, $config){

        // get products
        $tmp_lead = RGFormsModel::create_lead($form);
        $products = GFCommon::get_product_fields($form, $tmp_lead);
        $form_data = array();

        // getting billing information
        $form_data["form_title"] = $form["title"];
        $form_data["email"] = rgpost('input_'. str_replace(".", "_",$config["meta"]["customer_fields"]["email"]));
        $form_data["address1"] = rgpost('input_'. str_replace(".", "_",$config["meta"]["customer_fields"]["address1"]));
        $form_data["address2"] = rgpost('input_'. str_replace(".", "_",$config["meta"]["customer_fields"]["address2"]));
        $form_data["city"] = rgpost('input_'. str_replace(".", "_",$config["meta"]["customer_fields"]["city"]));
        $form_data["state"] = rgpost('input_'. str_replace(".", "_",$config["meta"]["customer_fields"]["state"]));
        $form_data["zip"] = rgpost('input_'. str_replace(".", "_",$config["meta"]["customer_fields"]["zip"]));
        $form_data["country"] = rgpost('input_'. str_replace(".", "_",$config["meta"]["customer_fields"]["country"]));

        $card_field = self::get_creditcard_field($form);
        $form_data["card_number"] = rgpost("input_{$card_field["id"]}_1");
        $form_data["expiration_date"] = rgpost("input_{$card_field["id"]}_2");
        $form_data["security_code"] = rgpost("input_{$card_field["id"]}_3");
        $form_data["card_name"] = rgpost("input_{$card_field["id"]}_5");
        $names = explode(" ", $form_data["card_name"]);
        $form_data["first_name"] = rgar($names,0);
        $form_data["last_name"] = "";
        if(count($names) > 0){
            unset($names[0]);
            $form_data["last_name"] = implode(" ", $names);
        }

        if(!empty($config["meta"]["setup_fee_enabled"]))
            $order_info = self::get_order_info($products, rgar($config["meta"],"recurring_amount_field"), rgar($config["meta"],"setup_fee_amount_field"));
        else
            $order_info = self::get_order_info($products, rgar($config["meta"],"recurring_amount_field"), "");

        $form_data["line_items"] = $order_info["line_items"];
        $form_data["amount"] = $order_info["amount"];
        $form_data["fee_amount"] = $order_info["fee_amount"];

        // need an easy way to filter the the order info as it is not modifiable once it is added to the transaction object
        $form_data = apply_filters("gform_authorizenet_form_data_{$form['id']}", apply_filters('gform_authorizenet_form_data', $form_data, $form, $config), $form, $config);

        return $form_data;
    }

    private static function get_order_info($products, $recurring_field, $setup_fee_field){

        $amount = 0;
        $line_items = array();
        $item = 1;
        $fee_amount = 0;
        foreach($products["products"] as $field_id => $product)
        {


            $quantity = $product["quantity"] ? $product["quantity"] : 1;
            $product_price = GFCommon::to_number($product['price']);

            $options = array();
            if(is_array(rgar($product, "options"))){
                foreach($product["options"] as $option){
                    $options[] = $option["option_label"];
                    $product_price += $option["price"];
                }
            }

            if(!empty($setup_fee_field) && $setup_fee_field == $field_id)
                $fee_amount += $product_price * $quantity;
            else
            {
                if(is_numeric($recurring_field) && $recurring_field != $field_id)
                continue;

                $amount += $product_price * $quantity;

                $description = "";
                if(!empty($options))
                    $description = __("options: ", "gravityformsauthorizenet") . " " . implode(", ", $options);

                if($product_price >= 0){
                    $line_items[] = array("item_id" =>'Item ' . $item, "item_name"=>$product["name"], "item_description" =>$description, "item_quantity" =>$quantity, "item_unit_price"=>$product["price"], "item_taxable"=>"Y");
                    $item++;
                }
            }
        }

        if(!empty($products["shipping"]["name"]) && !is_numeric($recurring_field)){
            $line_items[] = array("item_id" =>'Item ' . $item, "item_name"=>$products["shipping"]["name"], "item_description" =>"", "item_quantity" =>1, "item_unit_price"=>$products["shipping"]["price"], "item_taxable"=>"Y");
            $amount += $products["shipping"]["price"];
        }

        return array("amount" => $amount, "fee_amount" => $fee_amount, "line_items" => $line_items);
    }

    private static function get_initial_transaction($form_data, $config, $invoice_number=""){

        // processing products and services single transaction and first payment of subscription transaction
        $transaction = self::get_aim(self::get_local_api_settings($config));

        $transaction->amount = $form_data["amount"] + $form_data["fee_amount"];
        $transaction->card_num = $form_data["card_number"];
        $exp_date = str_pad($form_data["expiration_date"][0], 2, "0", STR_PAD_LEFT) . "-" . $form_data["expiration_date"][1];
        $transaction->exp_date = $exp_date;
        $transaction->card_code = $form_data["security_code"];
        $transaction->first_name = $form_data["first_name"];
        $transaction->last_name = $form_data["last_name"];
        $transaction->address = trim($form_data["address1"] . " " . $form_data["address2"]);
        $transaction->city = $form_data["city"];
        $transaction->state = $form_data["state"];
        $transaction->zip = $form_data["zip"];
        $transaction->country = $form_data["country"];
        $transaction->email = $form_data["email"];
        $transaction->email_customer = "true";
        $transaction->description = $form_data["form_title"];
        $transaction->email_customer = $config["meta"]["enable_receipt"] == 1 ? "true" : "false";
        $transaction->duplicate_window = 5;
        $transaction->invoice_num = empty($invoice_number) ? uniqid() : $invoice_number;

        foreach($form_data["line_items"] as $line_item)
            $transaction->addLineItem($line_item["item_id"], self::truncate($line_item["item_name"], 31), self::truncate($line_item["item_description"], 255), $line_item["item_quantity"], GFCommon::to_number($line_item["item_unit_price"]), $line_item["item_taxable"]);

        return $transaction;
    }

    private static function truncate($text, $max_chars){
        if(strlen($text) <= $max_chars)
            return $text;

        return substr($text, 0, $max_chars);
    }

    private static function get_subscription($config, $form_data, $trial_info, $invoice_number){

        $subscription = new AuthorizeNet_Subscription;

        $total_occurrences = $config["meta"]["recurring_times"] == "Infinite" ? "9999" : $config["meta"]["recurring_times"];
        if($total_occurrences <> "9999")
            $total_occurrences += $trial_info["trial_occurrences"];

        $interval_length = $config["meta"]["billing_cycle_number"];
        $interval_unit = $config["meta"]["billing_cycle_type"] == "D" ? "days" : "months";

        //setting subscription start date
        $is_free_trial = $trial_info["trial_enabled"] && $trial_info["trial_amount"] == 0;
        if($is_free_trial){
            $subscription_start_date = gmdate("Y-m-d");
        }
        else{
            //first payment has been made already, so start subscription on the next cycle
            $subscription_start_date = gmdate("Y-m-d", strtotime("+ " . $interval_length . $interval_unit));

            //removing one from total occurrences because first payment has been made
            $total_occurrences = $total_occurrences <> "9999" ? $total_occurrences - 1 : "9999";
            $trial_info["trial_occurrences"] = $trial_info["trial_enabled"] ? $trial_info["trial_occurrences"] -1 : null;
        }

        //setting trial properties
        if($trial_info["trial_enabled"]){
            $subscription->trialOccurrences = $trial_info["trial_occurrences"];
            $subscription->trialAmount = $trial_info["trial_amount"];
        }

        $subscription->name = $form_data["first_name"] . " " . $form_data["last_name"];
        $subscription->intervalLength = $interval_length;
        $subscription->intervalUnit = $interval_unit;
        $subscription->startDate = $subscription_start_date;
        $subscription->totalOccurrences = $total_occurrences;
        $subscription->amount = $form_data["amount"];
        $subscription->creditCardCardNumber = $form_data["card_number"];
        $exp_date = $form_data["expiration_date"][1] . "-" . str_pad($form_data["expiration_date"][0], 2, "0", STR_PAD_LEFT);
        $subscription->creditCardExpirationDate = $exp_date;
        $subscription->creditCardCardCode = $form_data["security_code"];
        $subscription->billToFirstName = $form_data["first_name"];
        $subscription->billToLastName = $form_data["last_name"];

        $subscription->customerEmail = $form_data["email"];
        $subscription->billToAddress = $form_data["address1"];
        $subscription->billToCity = $form_data["city"];
        $subscription->billToState = $form_data["state"];
        $subscription->billToZip = $form_data["zip"];
        $subscription->billToCountry = $form_data["country"];
        $subscription->orderInvoiceNumber = $invoice_number;

        return $subscription;
    }

    public static function authorizenet_after_submission($entry,$form){
        $entry_id = $entry["id"];

        if(!empty(self::$transaction_response))
        {
            //Current Currency
            $currency = GFCommon::get_currency();
            $transaction_id = self::$transaction_response["transaction_id"];
            $transaction_type = self::$transaction_response["transaction_type"];
            $amount = self::$transaction_response["amount"];
            $payment_date = gmdate("Y-m-d H:i:s");
            $entry["currency"] = $currency;
            if($transaction_type == "1")
            {
                $entry["payment_status"] = "Approved";
                $entry["payment_amount"] = $amount;
            }
            else
            {
                $entry["payment_status"] = "Active";
                $entry["payment_amount"] = rgar(self::$transaction_response, "regular_amount");
            }

            $entry["payment_date"] = $payment_date;
            $entry["transaction_id"] = $transaction_id;
            $entry["transaction_type"] = $transaction_type;
            $entry["is_fulfilled"] = true;

            RGFormsModel::update_lead($entry);

            //saving feed id
            $config = self::get_config($form);
            gform_update_meta($entry_id, "authorize.net_feed_id", $config["id"]);
            //updating form meta with current payment gateway
            gform_update_meta($entry_id, "payment_gateway", "authorize.net");

            //creating invoice number meta
            gform_update_meta($entry["id"], "invoice_number", self::$transaction_response["invoice_number"]);

            $subscriber_id = "";
            if($transaction_type == "2")
            {
                $subscriber_id = $transaction_id;
                $regular_amount = rgar(self::$transaction_response, "regular_amount");
                $trial_amount = rgar(self::$transaction_response, "trial_amount");
                $fee_amount = rgar(self::$transaction_response, "fee_amount");

                //Add subsciption creation and initial payment note
                $str_trial_amount = strval($trial_amount);
                if($str_trial_amount != "")
                {

                    if($trial_amount > 0)
                    {
                        RGFormsModel::add_note($entry_id, 0, "System", sprintf(__("Subscription has been created and initial payment has been made. Amount: %s. Subscriber Id: %s", "gravityforms"), GFCommon::to_money($trial_amount, $entry["currency"]),$subscriber_id));

                        GFAuthorizeNetData::insert_transaction($entry["id"], "payment", $subscriber_id, $transaction_id, $trial_amount);
                    }
                    else
                        RGFormsModel::add_note($entry_id, 0, "System", sprintf(__("Subscription has been created. Subscriber Id: %s", "gravityforms"), $subscriber_id));
                }
                else
                {
                    RGFormsModel::add_note($entry_id, 0, "System", sprintf(__("Subscription has been created and initial payment has been made. Amount: %s. Subscriber Id: %s", "gravityforms"), GFCommon::to_money($regular_amount, $entry["currency"]),$subscriber_id));
                    GFAuthorizeNetData::insert_transaction($entry["id"], "payment", $subscriber_id, $transaction_id, $amount);
                }
                //Add note for setup fee payment if completed
                if(!empty($fee_amount) && $fee_amount > 0)
                    RGFormsModel::add_note($entry_id, 0, "System", sprintf(__("Setup fee payment has been made. Amount: %s. Subscriber Id: %s", "gravityforms"), GFCommon::to_money($fee_amount, $entry["currency"]),$subscriber_id));

                gform_update_meta($entry["id"], "subscription_regular_amount",$regular_amount);
                gform_update_meta($entry["id"], "subscription_trial_amount",$trial_amount);
                gform_update_meta($entry["id"], "subscription_payment_count","1");
                gform_update_meta($entry["id"], "subscription_payment_date",$payment_date);

            }
            else
            {
                GFAuthorizeNetData::insert_transaction($entry["id"], "payment", $subscriber_id, $transaction_id, $amount);
            }
        }

    }

    private static function set_validation_result($validation_result,$post,$response,$responsetype){

        if($responsetype == "aim")
        {
            $code = $response->response_reason_code;
            switch($code){
                case "2" :
                case "3" :
                case "4" :
                case "41" :
                    $message = __("This credit card has been declined by your bank. Please use another form of payment.", "gravityformsauthorizenet");
                break;

                case "8" :
                    $message = __("The credit card has expired.", "gravityformsauthorizenet");
                break;

                case "17" :
                case "28" :
                    $message = __("The merchant does not accept this type of credit card.", "gravityformsauthorizenet");
                break;

                case "7" :
                case "44" :
                case "45" :
                case "65" :
                case "78" :
                case "6" :
                case "37" :
                case "27" :
                case "78" :
                case "45" :
                case "200" :
                case "201" :
                case "202" :
                    $message = __("There was an error processing your credit card. Please verify the information and try again.", "gravityformsauthorizenet");
                break;

                default :
                    $message = __("There was an error processing your credit card. Please verify the information and try again.", "gravityformsauthorizenet");

            }
        }
        else
        {
            $code = $response->getMessageCode();
            switch($code)
            {
                case "E00012" :
                    $message = __("A duplicate subscription already exists.", "gravityformsauthorizenet");
                break;
                case "E00018" :
                    $message = __("The credit card expires before the subscription start date. Please use another form of payment.", "gravityformsauthorizenet");
                break;
                default :
                    $message = __("There was an error processing your credit card. Please verify the information and try again.", "gravityformsauthorizenet");
            }
        }

        $message = "<!-- Error: " . $code . " -->" . $message;

        $credit_card_page = 0;
        foreach($validation_result["form"]["fields"] as &$field)
        {
            if($field["type"] == "creditcard")
            {
                $field["failed_validation"] = true;
                $field["validation_message"] = $message;
                $credit_card_page = $field["pageNumber"];
                break;
             }

        }
        $validation_result["is_valid"] = false;

        GFFormDisplay::set_current_page($validation_result["form"]["id"], $credit_card_page);

        return $validation_result;
    }

    public static function process_renewals(){

        if(!self::is_gravityforms_supported())
            return;

        // getting user information
        $user_id = 0;
        $user_name = "System";

        //loading data lib
        require_once(self::get_base_path() . "/data.php");

        // loading authorizenet api and getting credentials
        self::include_api();

        // getting all authorize.net subscription feeds
        $recurring_feeds = GFAuthorizeNetData::get_feeds();
        foreach($recurring_feeds as $feed)
        {
            // process renewalls if authorize.net feed is subscription feed
            if($feed["meta"]["type"]=="subscription")
            {
                $form_id = $feed["form_id"];

                // getting billig cycle information
                $billing_cycle_number = $feed["meta"]["billing_cycle_number"];
                $billing_cycle_type = $feed["meta"]["billing_cycle_type"];
                $billing_cycle = $billing_cycle_number;
                if($billing_cycle_type == "M")
                    $billing_cycle = $billing_cycle_number . " month";
                else
                    $billing_cycle = $billing_cycle_number . " day";

                $querytime = strtotime(gmdate("Y-m-d") . "-" . $billing_cycle);
                $querydate = gmdate("Y-m-d", $querytime) . " 00:00:00";

                // finding leads with a late payment date
                global $wpdb;
                $results = $wpdb->get_results("SELECT l.id, l.transaction_id, m.meta_value as payment_date
                                                FROM {$wpdb->prefix}rg_lead l
                                                INNER JOIN {$wpdb->prefix}rg_lead_meta m ON l.id = m.lead_id
                                                WHERE form_id={$form_id}
                                                AND payment_status = 'Active'
                                                AND meta_key = 'subscription_payment_date'
                                                AND meta_value < '{$querydate}'");

                foreach($results as $result)
                {
                    $entry_id = $result->id;
                    $subscription_id = $result->transaction_id;

                    $entry = RGFormsModel::get_lead($entry_id);

                    // Get the subscription status
                    $status_request = self::get_arb(self::get_local_api_settings($feed));
                    $status_response = $status_request->getSubscriptionStatus($subscription_id);
                    $status = $status_response->getSubscriptionStatus();

                    switch(strtolower($status)){
                        case "active" :
                            // getting feed trial information
                            $trial_period_enabled = $feed["meta"]["trial_period_enabled"];
                            $trial_period_occurrences = $feed["meta"]["trial_period_number"];

                            // finding payment date
                            $new_payment_time =  strtotime($result->payment_date . "+" . $billing_cycle);
                            $new_payment_date = gmdate( 'Y-m-d H:i:s' , $new_payment_time );

                            // finding payment amount
                            $payment_count = gform_get_meta($entry_id, "subscription_payment_count");
                            $new_payment_amount = gform_get_meta($entry_id, "subscription_regular_amount");
                            $new_payment_count = $payment_count + 1;
                            if($trial_period_enabled == 1)
                            {
                                 if($trial_period_occurrences > $payment_count)
                                    $new_payment_amount = gform_get_meta($entry_id, "subscription_trial_amount");
                            }

                            // update subscription payment and lead information
                            gform_update_meta($entry_id, "subscription_payment_count",$new_payment_count);
                            gform_update_meta($entry_id, "subscription_payment_date",$new_payment_date);
                            RGFormsModel::add_note($entry_id, $user_id, $user_name, sprintf(__("Subscription payment has been made. Amount: %s. Subscriber Id: %s", "gravityforms"), GFCommon::to_money($new_payment_amount, $entry["currency"]),$subscription_id));
                            $transaction_id = $subscription_id;
                            GFAuthorizeNetData::insert_transaction($entry_id, "payment", $subscription_id, $transaction_id, $new_payment_amount);

                            do_action("gform_authorizenet_after_recurring_payment", $entry, $subscription_id, $transaction_id, $new_payment_amount);

                         break;

                         case "expired" :
                               $entry["payment_status"] = "Expired";
                               RGFormsModel::update_lead($entry);
                               RGFormsModel::add_note($entry["id"], $user_id, $user_name, sprintf(__("Subscription has successfully completed its billing schedule. Subscriber Id: %s", "gravityforms"), $subscription_id));

                               do_action("gform_authorizenet_subscription_ended", $entry, $subscription_id, $transaction_id, $new_payment_amount);
                         break;

                         case "suspended":
                               $entry["payment_status"] = "Failed";
                               RGFormsModel::update_lead($entry);
                               RGFormsModel::add_note($entry["id"], $user_id, $user_name, sprintf(__("Subscription is currently suspended due to a transaction decline, rejection, or error. Suspended subscriptions must be reactivated before the next scheduled transaction or the subscription will be terminated by the payment gateway. Subscriber Id: %s", "gravityforms"), $subscription_id));

                               do_action("gform_authorizenet_subscription_suspended", $entry, $subscription_id, $transaction_id, $new_payment_amount);
                         break;

                         case "terminated":
                         case "canceled":
                              self::cancel_subscription($entry);
                              RGFormsModel::add_note($entry_id, $user_id, $user_name, sprintf(__("Subscription has been canceled. Subscriber Id: %s", "gravityforms"), $subscription_id));

                              do_action("gform_authorizenet_subscription_canceled", $entry, $subscription_id, $transaction_id, $new_payment_amount);
                         break;

                         default:
                              $entry["payment_status"] = "Failed";
                              RGFormsModel::update_lead($entry);
                              RGFormsModel::add_note($entry["id"], $user_id, $user_name, sprintf(__("Subscription is currently suspended due to a transaction decline, rejection, or error. Suspended subscriptions must be reactivated before the next scheduled transaction or the subscription will be terminated by the payment gateway. Subscriber Id: %s", "gravityforms"), $subscription_id));

                              do_action("gform_authorizenet_subscription_suspended", $entry, $subscription_id, $transaction_id, $new_payment_amount);
                         break;
                    }
                }

            }
        }

    }

    public static function authorizenet_entry_info($form_id, $lead) {

        // adding cancel subscription button and script to entry info section
        $lead_id = $lead["id"];
        $payment_status = $lead["payment_status"];
        $transaction_type = $lead["transaction_type"];
        $gateway = gform_get_meta($lead_id, "payment_gateway");
        $cancelsub_button = "";
        if($transaction_type == 2 && $payment_status <> "Canceled" && $gateway == "authorize.net")
        {
            $cancelsub_button .= '<input id="cancelsub" type="button" name="cancelsub" value="' . __("Cancel Subscription", "gravityformsauthorizenet") . '" class="button" onclick=" if( confirm(\'' . __("Warning! This Authorize.Net Subscription will be canceled. This cannot be undone. \'OK\' to cancel subscription, \'Cancel\' to stop", "gravityformsauthorizenet") . '\')){cancel_authorizenet_subscription();};"/>';

            $cancelsub_button .= '<img src="'. GFAuthorizeNet::get_base_url() . '/images/loading.gif" id="authorizenet_wait" style="display: none;"/>';

            $cancelsub_button .= '<script type="text/javascript">
                function cancel_authorizenet_subscription(){
                    jQuery("#authorizenet_wait").show();
                    jQuery("#cancelsub").attr("disabled", true);
                    var lead_id = ' . $lead_id  .'
                    jQuery.post(ajaxurl, {
                            action:"gf_cancel_authorizenet_subscription",
                            leadid:lead_id,
                            gf_cancel_subscription: "' . wp_create_nonce('gf_cancel_subscription') . '"},
                            function(response){

                                jQuery("#authorizenet_wait").hide();

                                if(response == "1")
                                {
                                    jQuery("#gform_payment_status").html("' . __("Canceled", "gravityformsauthorizenet") . '");
                                    jQuery("#cancelsub").hide();
                                }
                                else
                                {
                                    jQuery("#cancelsub").attr("disabled", false);
                                    alert("' . __("The subscription could not be canceled. Please try again later.") . '");
                                }
                            }
                            );
                }
            </script>';

            echo $cancelsub_button;
        }
    }

    public static function cancel_authorizenet_subscription() {
        check_ajax_referer("gf_cancel_subscription","gf_cancel_subscription");

        $lead_id = $_POST["leadid"];
        $lead = RGFormsModel::get_lead($lead_id);
        // loading authorizenet api and getting credentials
        self::include_api();

        //Getting feed config
        $form = RGFormsModel::get_form_meta($lead["form_id"]);
        $config = self::get_config($form);

        // cancel the subscription
        $cancellation = self::get_arb(self::get_local_api_settings($config));
        $cancel_response = $cancellation->cancelSubscription($lead["transaction_id"]);
        if($cancel_response->isOk())
        {
            self::cancel_subscription($lead);
            die("1");
        }
        else
        {
            die("0");
        }

    }

    private static function cancel_subscription($lead){

        $lead["payment_status"] = "Canceled";
        RGFormsModel::update_lead($lead);

        //loading data class
        $feed_id = gform_get_meta($lead["id"], "authorize.net_feed_id");

        require_once(self::get_base_path() . "/data.php");
        $config = GFAuthorizeNetData::get_feed($feed_id);
        if(!$config)
            return;

        //1- delete post or mark it as a draft based on configuration
        if(rgars($config, "meta/update_post_action") == "draft" && !rgempty("post_id", $lead)){
            $post = get_post($lead["post_id"]);
            $post->post_status = 'draft';
            wp_update_post($post);
        }
        else if(rgars($config, "meta/update_post_action") == "delete" && !rgempty("post_id", $lead)){
            wp_delete_post($lead["post_id"]);
        }

        //2- call subscription canceled hook
        do_action("gform_subscription_canceled", $lead, $config, $lead["transaction_id"], "authorize.net");

    }

    public static function delete_authorizenet_meta() {
        // delete lead meta data
        global $wpdb;
        $table_name = RGFormsModel::get_lead_meta_table_name();
        $wpdb->query($wpdb->prepare("DELETE FROM $table_name WHERE meta_key in ('subscription_regular_amount','subscription_trial_amount','subscription_payment_count','subscription_payment_date')"));

    }

    public static function uninstall(){
        //loading data lib
        require_once(self::get_base_path() . "/data.php");

        if(!GFAuthorizeNet::has_access("gravityforms_authorizenet_uninstall"))
            die(__("You don't have adequate permission to uninstall the Authorize.Net Add-On.", "gravityformsauthorizenet"));

        //droping all tables
        GFAuthorizeNetData::drop_tables();

        //removing options
        delete_option("gf_authorizenet_site_name");
        delete_option("gf_authorizenet_auth_token");
        delete_option("gf_authorizenet_version");
        delete_option("gf_authorizenet_settings");

        //delete lead meta data
        self::delete_authorizenet_meta();

        //Deactivating plugin
        $plugin = "gravityformsauthorizenet/authorizenet.php";
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

    private static function get_customer_information($form, $config=null){

        //getting list of all fields for the selected form
        $form_fields = self::get_form_fields($form);

        $str = "<table cellpadding='0' cellspacing='0'><tr><td class='authorizenet_col_heading'>" . __("Authorize.Net Fields", "gravityformsauthorizenet") . "</td><td class='authorizenet_col_heading'>" . __("Form Fields", "gravityformsauthorizenet") . "</td></tr>";
        $customer_fields = self::get_customer_fields();
        foreach($customer_fields as $field){
            $selected_field = $config ? $config["meta"]["customer_fields"][$field["name"]] : "";
            $str .= "<tr><td class='authorizenet_field_cell'>" . $field["label"]  . "</td><td class='authorizenet_field_cell'>" . self::get_mapped_field_list($field["name"], $selected_field, $form_fields) . "</td></tr>";
        }
        $str .= "</table>";

        return $str;
    }

    private static function get_customer_fields(){
        return
        array(array("name" => "email" , "label" =>__("Email", "gravityformsauthorizenet")), array("name" => "address1" , "label" =>__("Address", "gravityformsauthorizenet")), array("name" => "address2" , "label" =>__("Address 2", "gravityformsauthorizenet")),
        array("name" => "city" , "label" =>__("City", "gravityformsauthorizenet")), array("name" => "state" , "label" =>__("State", "gravityformsauthorizenet")), array("name" => "zip" , "label" =>__("Zip", "gravityformsauthorizenet")),
        array("name" => "country" , "label" =>__("Country", "gravityformsauthorizenet")));
    }

    private static function get_mapped_field_list($variable_name, $selected_field, $fields){
        $field_name = "authorizenet_customer_field_" . $variable_name;
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

    private static function get_product_options($form, $selected_field, $form_total){
        $str = "<option value=''>" . __("Select a field", "gravityformsauthorizenet") ."</option>";
        $fields = GFCommon::get_fields_by_type($form, array("product"));
        foreach($fields as $field){
            $field_id = $field["id"];
            $field_label = RGFormsModel::get_label($field);

            $selected = $field_id == $selected_field ? "selected='selected'" : "";
            $str .= "<option value='" . $field_id . "' ". $selected . ">" . $field_label . "</option>";
        }

        if($form_total){
            $selected = $selected_field == 'all' ? "selected='selected'" : "";
            $str .= "<option value='all' " . $selected . ">" . __("Form Total", "gravityformspaypalpro") ."</option>";
        }

        return $str;
    }

    private static function get_form_fields($form){
        $fields = array();

        if(is_array($form["fields"])){
            foreach($form["fields"] as $field){
                if(is_array(rgar($field,"inputs"))){

                    foreach($field["inputs"] as $input)
                        $fields[] =  array($input["id"], GFCommon::get_label($field, $input["id"]));
                }
                else if(!rgar($field, 'displayOnly')){
                    $fields[] =  array($field["id"], GFCommon::get_label($field));
                }
            }
        }
        return $fields;
    }

    private static function is_authorizenet_page(){
        $current_page = trim(strtolower(RGForms::get("page")));
        return in_array($current_page, array("gf_authorizenet"));
    }

    //Returns the url of the plugin's root folder
    private static function get_base_url(){
        return plugins_url(null, __FILE__);
    }

    //Returns the physical path of the plugin's root folder
    private static function get_base_path(){
        $folder = basename(dirname(__FILE__));
        return WP_PLUGIN_DIR . "/" . $folder;
    }

    function set_logging_supported($plugins)
    {
        $plugins[self::$slug] = "Authorize.Net";
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

if(!function_exists("rgars")){
function rgars($array, $name){
    $names = explode("/", $name);
    $val = $array;
    foreach($names as $current_name){
        $val = rgar($val, $current_name);
    }
    return $val;
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

?>
