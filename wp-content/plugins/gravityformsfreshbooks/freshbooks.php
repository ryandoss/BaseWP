<?php
/*
Plugin Name: Gravity Forms FreshBooks Add-On
Plugin URI: http://www.gravityforms.com
Description: Integrates Gravity Forms with FreshBooks allowing form submissions to be automatically sent to your FreshBooks account, creating clients, invoices and estimates
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

add_action('init',  array('GFFreshBooks', 'init'));
register_activation_hook( __FILE__, array("GFFreshBooks", "add_permissions"));

class GFFreshBooks {

    private static $path = "gravityformsfreshbooks/freshbooks.php";
    private static $url = "http://www.gravityforms.com";
    private static $slug = "gravityformsfreshbooks";
    private static $version = "1.4";
    private static $min_gravityforms_version = "1.3.9";
    private static $supported_fields = array("checkbox", "radio", "select", "text", "website", "textarea", "email", "hidden", "number", "phone", "multiselect", "post_title",
		                            "post_tags", "post_custom_field", "post_content", "post_excerpt");


    //Plugin starting point. Will load appropriate files
    public static function init(){
    	//supports logging
		add_filter("gform_logging_supported", array("GFFreshBooks", "set_logging_supported"));

        if(basename($_SERVER['PHP_SELF']) == "plugins.php"){
            //loading translations
            load_plugin_textdomain('gravityformsfreshbooks', FALSE, '/gravityformsfreshbooks/languages' );

            add_action('after_plugin_row_' . self::$path, array('GFFreshBooks', 'plugin_row') );

            //force new remote request for version info on the plugin page
            self::flush_version_info();
        }

        if(!self::is_gravityforms_supported()){
           return;
        }

        if(is_admin()){
            //loading translations
            load_plugin_textdomain('gravityformsfreshbooks', FALSE, '/gravityformsfreshbooks/languages' );

            add_filter("transient_update_plugins", array('GFFreshBooks', 'check_update'));
            add_filter("site_transient_update_plugins", array('GFFreshBooks', 'check_update'));

            add_action('install_plugins_pre_plugin-information', array('GFFreshBooks', 'display_changelog'));

            //creates a new Settings page on Gravity Forms' settings screen
            if(self::has_access("gravityforms_freshbooks")){
                RGForms::add_settings_page("FreshBooks", array("GFFreshBooks", "settings_page"), self::get_base_url() . "/images/freshbooks_wordpress_icon_32.png");
            }
        }
        else{
            // ManageWP premium update filters
            add_filter( 'mwp_premium_update_notification', array('GFFreshBooks', 'premium_update_push') );
            add_filter( 'mwp_premium_perform_update', array('GFFreshBooks', 'premium_update') );
        }

        //integrating with Members plugin
        if(function_exists('members_get_capabilities'))
            add_filter('members_get_capabilities', array("GFFreshBooks", "members_get_capabilities"));

        //creates the subnav left menu
        add_filter("gform_addon_navigation", array('GFFreshBooks', 'create_menu'));

        if(self::is_freshbooks_page()){

            //enqueueing sack for AJAX requests
            wp_enqueue_script(array("sack"));

            //loading data lib
            require_once(self::get_base_path() . "/data.php");

            //loading upgrade lib
            if(!class_exists("RGFreshbooksUpgrade"))
                require_once("plugin-upgrade.php");

            //loading Gravity Forms tooltips
            require_once(GFCommon::get_base_path() . "/tooltips.php");
            add_filter('gform_tooltips', array('GFFreshBooks', 'tooltips'));

            //runs the setup when version changes
            self::setup();

         }
         else if(in_array(RG_CURRENT_PAGE, array("admin-ajax.php"))){

            //loading data class
            require_once(self::get_base_path() . "/data.php");

            add_action('wp_ajax_rg_update_feed_active', array('GFFreshBooks', 'update_feed_active'));
            add_action('wp_ajax_gf_select_freshbooks_form', array('GFFreshBooks', 'select_form'));

        }
        else{
             //handling post submission.
            add_action("gform_post_submission", array('GFFreshBooks', 'export'), 10, 2);
        }


    }

    public static function update_feed_active(){
        check_ajax_referer('rg_update_feed_active','rg_update_feed_active');
        $id = $_POST["feed_id"];
        $feed = GFFreshBooksData::get_feed($id);
        GFFreshBooksData::update_feed($id, $feed["form_id"], $_POST["is_active"], $feed["meta"]);
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
        if(!class_exists("RGFreshbooksUpgrade"))
            require_once("plugin-upgrade.php");

        RGFreshbooksUpgrade::set_version_info(false);
    }

    public static function plugin_row(){
        if(!self::is_gravityforms_supported()){
            $message = sprintf(__("Gravity Forms " . self::$min_gravityforms_version . " is required. Activate it now or %spurchase it today!%s"), "<a href='http://www.gravityforms.com'>", "</a>");
            RGFreshbooksUpgrade::display_plugin_message($message, true);
        }
        else{
            $version_info = RGFreshbooksUpgrade::get_version_info(self::$slug, self::get_key(), self::$version);

            if(!$version_info["is_valid_key"]){
                $new_version = version_compare(self::$version, $version_info["version"], '<') ? __('There is a new version of Gravity Forms FreshBooks Add-On available.', 'gravityformsfreshbooks') .' <a class="thickbox" title="Gravity Forms FreshBooks Add-On" href="plugin-install.php?tab=plugin-information&plugin=' . self::$slug . '&TB_iframe=true&width=640&height=808">'. sprintf(__('View version %s Details', 'gravityformsfreshbooks'), $version_info["version"]) . '</a>. ' : '';
                $message = $new_version . sprintf(__('%sRegister%s your copy of Gravity Forms to receive access to automatic upgrades and support. Need a license key? %sPurchase one now%s.', 'gravityformsfreshbooks'), '<a href="admin.php?page=gf_settings">', '</a>', '<a href="http://www.gravityforms.com">', '</a>') . '</div></td>';
                RGFreshbooksUpgrade::display_plugin_message($message);
            }
        }
    }

    //Displays current version details on Plugin's page
    public static function display_changelog(){
        if($_REQUEST["plugin"] != self::$slug)
            return;

        //loading upgrade lib
        if(!class_exists("RGFreshbooksUpgrade"))
            require_once("plugin-upgrade.php");

        RGFreshbooksUpgrade::display_changelog(self::$slug, self::get_key(), self::$version);
    }

    public static function check_update($update_plugins_option){
        if(!class_exists("RGFreshbooksUpgrade"))
            require_once("plugin-upgrade.php");

        return RGFreshbooksUpgrade::check_update(self::$path, self::$slug, self::$url, self::$slug, self::get_key(), self::$version, $update_plugins_option);
    }

    private static function get_key(){
        if(self::is_gravityforms_supported())
            return GFCommon::get_key();
        else
            return "";
    }
    //---------------------------------------------------------------------------------------

    //Returns true if the current page is an Feed pages. Returns false if not
    private static function is_freshbooks_page(){
        $current_page = trim(strtolower(rgget("page")));
        $freshbooks_pages = array("gf_freshbooks");

        return in_array($current_page, $freshbooks_pages);
    }

    //Creates or updates database tables. Will only run when version changes
    private static function setup(){

        if(get_option("gf_freshbooks_version") != self::$version)
            GFFreshBooksData::update_table();

        update_option("gf_freshbooks_version", self::$version);
    }

    //Adds feed tooltips to the list of tooltips
    public static function tooltips($tooltips){
        $freshbooks_tooltips = array(
            "freshbooks_gravity_form" => "<h6>" . __("Gravity Form", "gravityforms_feed") . "</h6>" . __("Select the Gravity Form you would like to integrate with FreshBooks. Contacts generated by this form will be automatically added as clients to your FreshBooks account.", "gravityforms_feed"),
            "freshbooks_also_create" => "<h6>" . __("Also Create", "gravityforms_feed") . "</h6>" . __("Select invoice or estimate to automatically create them in your FreshBooks account in addition to creating the client.", "gravityforms_feed"),
            "freshbooks_po_number" => "<h6>" . __("PO Number", "gravityforms_feed") . "</h6>" . __("Map the PO number to the appropriate form field.", "gravityforms_feed"),
            "freshbooks_discount" => "<h6>" . __("Discount", "gravityforms_feed") . "</h6>" . __("When creating an invoice or estimate, this discount will be applied to the total invoice/estimate cost.", "gravityforms_feed"),
            "freshbooks_line_items" => "<h6>" . __("Line Items", "gravityforms_feed") . "</h6>" . __("Create one or more line items to your invoice or estimate.", "gravityforms_feed"),
            "freshbooks_fixed_cost_quantity" => "<h6>" . __("Fixed Cost and Quantity", "gravityforms_feed") . "</h6>" . __("Enter fixed cost and quantity for your line items.", "gravityforms_feed"),
            "freshbooks_dynamic_cost_quantity" => "<h6>" . __("Dynamic Cost and Quantity", "gravityforms_feed") . "</h6>" . __("Allow line item cost and quantity to be populated from a form field.", "gravityforms_feed"),
            "freshbooks_update_client" => "<h6>" . __("Update existing client", "gravityforms_feed") . "</h6>" . __("When this box is checked and a client already exists in your FreshBooks account, it will be updated with the newly entered information. When this box is unchecked, a new client will be created for every form submission.", "gravityforms_feed"),
            "freshbooks_optin_condition" => "<h6>" . __("Export Condition", "gravityforms_feed") . "</h6>" . __("When the export condition is enabled, form submissions will only be exported to FreshBooks when the condition is met. When disabled all form submissions will be exported.", "gravityforms_feed"),

        );
        return array_merge($tooltips, $freshbooks_tooltips);
    }

    //Creates FreshBooks left nav menu under Forms
    public static function create_menu($menus){

        // Adding submenu if user has access
        $permission = self::has_access("gravityforms_freshbooks");
        if(!empty($permission))
            $menus[] = array("name" => "gf_freshbooks", "label" => __("FreshBooks", "gravityformsfreshbooks"), "callback" =>  array("GFFreshBooks", "freshbooks_page"), "permission" => $permission);

        return $menus;
    }

    public static function settings_page(){

        if(!class_exists("RGFreshbooksUpgrade"))
            require_once("plugin-upgrade.php");

        if(rgpost("uninstall")){
            check_admin_referer("uninstall", "gf_freshbooks_uninstall");
            self::uninstall();

            ?>
            <div class="updated fade" style="padding:20px;"><?php _e(sprintf("Gravity Forms FreshBooks Add-On have been successfully uninstalled. It can be re-activated from the %splugins page%s.", "<a href='plugins.php'>","</a>"), "gravityformsfreshbooks")?></div>
            <?php
            return;
        }
        else if(rgpost("gf_freshbooks_submit")){
            //checking nonce for security.
            check_admin_referer("update", "gf_freshbooks_settings");

            //Saving filename. Using WP option to save it.
            $site_name = stripslashes($_POST["gf_freshbooks_site_name"]);
            $auth_token = stripslashes($_POST["gf_freshbooks_auth_token"]);

            update_option("gf_freshbooks_site_name", $site_name);
            update_option("gf_freshbooks_auth_token", $auth_token);

        }
        else{
            //reading file name from option
            $site_name = get_option("gf_freshbooks_site_name");
            $auth_token = get_option("gf_freshbooks_auth_token");
        }

        if(self::is_valid_credentials()){
            $message = "Valid site name and authorization token.";
            $class = "valid_credentials";
        }
        else if(!empty($site_name) || !empty($auth_token)){
            $message = "Invalid site name and/or authorization token. Please try another combination.";
            $class = "invalid_credentials";
        }

        ?>
       <style>
            .valid_credentials{color:green;}
            .invalid_credentials{color:red;}
        </style>
        <form method="post" id="gf_freshbooks_form">
            <?php wp_nonce_field("update", "gf_freshbooks_settings") ?>

            <h3><?php _e("FreshBooks Account Information", "gravityformsfreshbooks") ?></h3>
            <p style="text-align: left;">
                <?php _e(sprintf("FreshBooks is a fast, painless way to track time and invoice your clients. Use Gravity Forms to collect customer information and automatically create FreshBooks client profiles as well as invoices and estimates. If you don't have a FreshBooks account, you can %ssign up for one here%s", "<a href='http://www.freshbooks.com' target='_blank'>" , "</a>"), "gravityformsfreshbooks") ?>
            </p>

            <table class="form-table">
                <tr>
                    <th scope="row"><label for="gf_freshbooks_site_name"><?php _e("Site Name:", "gravityforms_feed") ?></label> </th>
                    <td><input type="text" name="gf_freshbooks_site_name" id="gf_freshbooks_site_name" value="<?php echo esc_attr($site_name) ?>"/> .freshbooks.com</td>
                </tr>
                <tr>
                    <th scope="row"><label for="gf_freshbooks_auth_token"><?php _e("Authorization Token:", "gravityforms_feed") ?></label> </th>
                    <td><input type="text" name="gf_freshbooks_auth_token" size="40" id="gf_freshbooks_auth_token" value="<?php echo esc_attr($auth_token) ?>"/></td>
                </tr>
                <tr>
                    <td colspan="2" class="<?php echo !empty($class) ? $class : "" ?>"><?php echo !empty($message) ? $message : "" ?></td>
                </tr>
                <tr>
                    <td colspan="2" ><input type="submit" class="button-primary" name="gf_freshbooks_submit" value="<?php _e("Update Settings", "gravityforms_feed") ?>" /></td>
                </tr>
            </table>
        </form>

        <form action="" method="post">
            <?php wp_nonce_field("uninstall", "gf_freshbooks_uninstall") ?>
            <?php if(GFCommon::current_user_can_any("gravityforms_freshbooks_uninstall")){ ?>
                <div class="hr-divider"></div>

                <h3><?php _e("Uninstall FreshBooks Add-On", "gravityformsfreshbooks") ?></h3>
                <div class="delete-alert"><?php _e("Warning! This operation deletes ALL FreshBooks Feeds.", "gravityformsfreshbooks") ?>
                    <?php
                    $uninstall_button = '<input type="submit" name="uninstall" value="' . __("Uninstall FreshBooks Add-On", "gravityformsfreshbooks") . '" class="button" onclick="return confirm(\'' . __("Warning! ALL FreshBooks Feeds will be deleted. This cannot be undone. \'OK\' to delete, \'Cancel\' to stop", "gravityformsfreshbooks") . '\');"/>';
                    echo apply_filters("gform_freshbooks_uninstall_button", $uninstall_button);
                    ?>
                </div>
            <?php } ?>
        </form>
        <?php
    }

    public static function freshbooks_page(){
        $view = rgget("view");
        if($view == "edit")
            self::edit_page($_GET["id"]);
        else
            self::list_page();
    }

    //Displays the freshbooks feeds list page
    private static function list_page(){
        if(!self::is_gravityforms_supported()){
            die(__(sprintf("FreshBooks Add-On requires Gravity Forms %s. Upgrade automatically on the %sPlugin page%s.", self::$min_gravityforms_version, "<a href='plugins.php'>", "</a>"), "gravityformsfreshbooks"));
        }

        if(rgpost('action') == "delete"){
            check_admin_referer("list_action", "gf_freshbooks_list");

            $id = absint($_POST["action_argument"]);
            GFFreshBooksData::delete_feed($id);
            ?>
            <div class="updated fade" style="padding:6px"><?php _e("Feed deleted.", "gravityformsfreshbooks") ?></div>
            <?php
        }
        else if (!empty($_POST["bulk_action"])){
            check_admin_referer("list_action", "gf_freshbooks_list");
            $selected_feeds = $_POST["feed"];
            if(is_array($selected_feeds)){
                foreach($selected_feeds as $feed_id)
                    GFFreshBooksData::delete_feed($feed_id);
            }
            ?>
            <div class="updated fade" style="padding:6px"><?php _e("Feeds deleted.", "gravityformsfreshbooks") ?></div>
            <?php
        }

        ?>
        <div class="wrap">
            <img alt="<?php _e("FreshBooks Feeds", "gravityformsfreshbooks") ?>" src="<?php echo self::get_base_url()?>/images/freshbooks_wordpress_icon_32.png" style="float:left; margin:15px 7px 0 0;"/>
            <h2><?php _e("FreshBooks Feeds", "gravityformsfreshbooks"); ?>
            <a class="button add-new-h2" href="admin.php?page=gf_freshbooks&view=edit&id=0"><?php _e("Add New", "gravityformsfreshbooks") ?></a>
            </h2>


            <form id="feed_form" method="post">
                <?php wp_nonce_field('list_action', 'gf_freshbooks_list') ?>
                <input type="hidden" id="action" name="action"/>
                <input type="hidden" id="action_argument" name="action_argument"/>

                <div class="tablenav">
                    <div class="alignleft actions" style="padding:8px 0 7px; 0">
                        <label class="hidden" for="bulk_action"><?php _e("Bulk action", "gravityformsfreshbooks") ?></label>
                        <select name="bulk_action" id="bulk_action">
                            <option value=''> <?php _e("Bulk action", "gravityformsfreshbooks") ?> </option>
                            <option value='delete'><?php _e("Delete", "gravityformsfreshbooks") ?></option>
                        </select>
                        <?php
                        echo '<input type="submit" class="button" value="' . __("Apply", "gravityformsfreshbooks") . '" onclick="if( jQuery(\'#bulk_action\').val() == \'delete\' && !confirm(\'' . __("Delete selected feeds? ", "gravityformsfreshbooks") . __("\'Cancel\' to stop, \'OK\' to delete.", "gravityformsfreshbooks") .'\')) { return false; } return true;"/>';
                        ?>
                    </div>
                </div>
                <table class="widefat fixed" cellspacing="0">
                    <thead>
                        <tr>
                            <th scope="col" id="cb" class="manage-column column-cb check-column" style=""><input type="checkbox" /></th>
                            <th scope="col" id="active" class="manage-column check-column"></th>
                            <th scope="col" class="manage-column"><?php _e("Form", "gravityformsfreshbooks") ?></th>
                            <th scope="col" class="manage-column"><?php _e("Client", "gravityformsfreshbooks") ?></th>
                            <th scope="col" class="manage-column"><?php _e("Invoice", "gravityformsfreshbooks") ?></th>
                            <th scope="col" class="manage-column"><?php _e("Estimate", "gravityformsfreshbooks") ?></th>
                        </tr>
                    </thead>

                    <tfoot>
                        <tr>
                            <th scope="col" id="cb" class="manage-column column-cb check-column" style=""><input type="checkbox" /></th>
                            <th scope="col" id="active" class="manage-column check-column"></th>
                            <th scope="col" class="manage-column"><?php _e("Form", "gravityformsfreshbooks") ?></th>
                            <th scope="col" class="manage-column"><?php _e("Client", "gravityformsfreshbooks") ?></th>
                            <th scope="col" class="manage-column"><?php _e("Invoice", "gravityformsfreshbooks") ?></th>
                            <th scope="col" class="manage-column"><?php _e("Estimate", "gravityformsfreshbooks") ?></th>
                        </tr>
                    </tfoot>

                    <tbody class="list:user user-list">
                        <?php

                        $settings = GFFreshBooksData::get_feeds();
                        if(is_array($settings) && !empty($settings)){

                            foreach($settings as $setting){
                                ?>
                                <tr class='author-self status-inherit' valign="top">
                                    <th scope="row" class="check-column"><input type="checkbox" name="feed[]" value="<?php echo $setting["id"] ?>"/></th>
                                    <td><img src="<?php echo self::get_base_url() ?>/images/active<?php echo intval($setting["is_active"]) ?>.png" alt="<?php echo $setting["is_active"] ? __("Active", "gravityformsfreshbooks") : __("Inactive", "gravityformsfreshbooks");?>" title="<?php echo $setting["is_active"] ? __("Active", "gravityformsfreshbooks") : __("Inactive", "gravityformsfreshbooks");?>" onclick="ToggleActive(this, <?php echo $setting['id'] ?>); " /></td>
                                    <td class="column-title">
                                        <a href="admin.php?page=gf_freshbooks&view=edit&id=<?php echo $setting["id"] ?>" title="<?php _e("Edit", "gravityformsfreshbooks") ?>"><?php echo $setting["form_title"] ?></a>
                                        <div class="row-actions">
                                            <span class="edit">
                                            <a href="admin.php?page=gf_freshbooks&view=edit&id=<?php echo $setting["id"] ?>" title="<?php _e("Edit", "gravityformsfreshbooks") ?>"><?php _e("Edit", "gravityformsfreshbooks") ?></a>
                                            |
                                            </span>

                                            <span class="trash">
                                            <a title="<?php _e("Delete", "gravityformsfreshbooks") ?>" href="javascript: if(confirm('<?php _e("Delete this feed? ", "gravityformsfreshbooks") ?> <?php _e("\'Cancel\' to stop, \'OK\' to delete.", "gravityformsfreshbooks") ?>')){ DeleteSetting(<?php echo $setting["id"] ?>);}"><?php _e("Delete", "gravityformsfreshbooks")?></a>

                                            </span>
                                        </div>
                                    </td>
                                    <td class="column-date"><img src="<?php echo self::get_base_url()?>/images/tick.png" /></td>
                                    <td class="column-date"><?php echo $setting["meta"]["alsocreate"] == "invoice" ? "<img src='" . self::get_base_url() . "/images/tick.png' />" : "" ?></td>
                                    <td class="column-date"><?php echo $setting["meta"]["alsocreate"] == "estimate" ? "<img src='" . self::get_base_url() . "/images/tick.png' />" : "" ?></td>
                                </tr>
                                <?php
                            }
                        }
                        else if(self::is_valid_credentials()){
                            ?>
                            <tr>
                                <td colspan="4" style="padding:20px;">
                                    <?php _e(sprintf("You don't have any FreshBooks feeds configured. Let's go %screate one%s!", '<a href="admin.php?page=gf_freshbooks&view=edit&id=0">', "</a>"), "gravityformsfreshbooks"); ?>
                                </td>
                            </tr>
                            <?php
                        }
                        else{
                            ?>
                            <tr>
                                <td colspan="4" style="padding:20px;">
                                    <?php _e(sprintf("To get started, please configure your %sFreshBooks Settings%s.", '<a href="admin.php?page=gf_settings&addon=FreshBooks">', "</a>"), "gravityformsfreshbooks"); ?>
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
                    jQuery(img).attr('title','<?php _e("Inactive", "gravityformsfreshbooks") ?>').attr('alt', '<?php _e("Inactive", "gravityformsfreshbooks") ?>');
                }
                else{
                    img.src = img.src.replace("active0.png", "active1.png");
                    jQuery(img).attr('title','<?php _e("Active", "gravityformsfreshbooks") ?>').attr('alt', '<?php _e("Active", "gravityformsfreshbooks") ?>');
                }

                var mysack = new sack(ajaxurl);
                mysack.execute = 1;
                mysack.method = 'POST';
                mysack.setVar( "action", "rg_update_feed_active" );
                mysack.setVar( "rg_update_feed_active", "<?php echo wp_create_nonce("rg_update_feed_active") ?>" );
                mysack.setVar( "feed_id", feed_id );
                mysack.setVar( "is_active", is_active ? 0 : 1 );
                mysack.encVar( "cookie", document.cookie, false );
                mysack.onError = function() { alert('<?php _e("Ajax error while updating feed", "gravityformsfreshbooks" ) ?>' )};
                mysack.runAJAX();

                return true;
            }
        </script>
        <?php
    }

    private static function get_api(){
        if(!class_exists("MCAPI"))
            require_once("api/MCAPI.class.php");

        //global freshbooks settings
        $settings = get_option("gf_freshbooks_settings");
        if(!empty($settings["username"]) && !empty($settings["password"])){
            $api = new MCAPI($settings["username"], $settings["password"]);

            if($api->errorCode)
                return null;
        }
        return $api;
    }

    private static function edit_page(){
        ?>
        <style>
            .left_header{float:left; width:150px;}
            .margin_vertical_10{margin: 10px 0;}
            .gf_freshbooks_lineitem_table { margin-top: 10px;}
            .freshbooks_notes { width: 400px; }
            .feeds_validation_error{ background-color:#FFDFDF; margin-top:4px; margin-bottom:6px; padding:6px 6px 4px 6px; border:1px dotted #C89797}
            .feeds_required {color:red;}


            .feeds_validation_error td{ margin-top:4px; margin-bottom:6px; padding-top:6px; padding-bottom:6px; border-top:1px dotted #C89797; border-bottom:1px dotted #C89797}
            .freshbooks_col_heading{padding-bottom:2px; border-bottom: 1px solid #ccc; font-weight:bold;}
            .freshbooks_field_cell {padding: 6px 17px 0 0; margin-right:15px;}
            .gfield_required{color:red;}

            .freshbooks_selector_container{display:inline; padding-left: 10px;}
        </style>
        <div class="wrap">
            <img alt="<?php _e("FreshBooks", "gravityformsfreshbooks") ?>" style="margin: 15px 7px 0pt 0pt; float: left;" src="<?php echo self::get_base_url() ?>/images/freshbooks_wordpress_icon_32.png"/>
            <h2><?php _e("FreshBooks Feed", "gravityformsfreshbooks") ?></h2>

        <?php

        //ensures valid credentials were entered in the settings page
        if(!self::is_valid_credentials()){
            ?>
            <div class="error" style="padding:6px"><?php echo sprintf(__("We are unable to login to FreshBooks with the provided username and password. Please make sure they are valid in the %sSettings Page%s", "gravityformsfreshbooks"), "<a href='?page=gf_settings&addon=FreshBooks'>", "</a>"); ?></div>
            <?php
            return;
        }

        //Getting current feed ID. Will be 0 when creating a new one
        $id = !empty($_POST["freshbooks_setting_id"]) ? $_POST["freshbooks_setting_id"] : absint($_GET["id"]);

        //Loading current feed configuration (empty when creating a new one)
        if(empty($id)){
            $config = array("is_active" => true, "meta" => array()); //empty configuration (active by default)
        }
        else{
            /* Getting current feed configuration from database.
            *  The class GFFreshBooksData handles feed configuration for all providers.
            *  The get_feed() function gets the information for the current feed.
            *  The feed information is stored in the wp_rg_feed table.
            */
            $config = GFFreshBooksData::get_feed($id);
        }

        //Saving feed configuration
        if(rgpost("gf_freshbooks_submit")){

            //Reading form id
            $config["form_id"] = stripslashes($_POST["gf_freshbooks_form_id"]);

            /* Reading file name and placing it in the meta array. The meta array can be used to store
            *  extra feed settings in an array format. See FreshBooks provider for a more comprehensive use of the feed meta.
            */
            $config["meta"]["email"] = stripslashes($_POST["gf_freshbooks_email"]);
            $config["meta"]["first_name"] = stripslashes($_POST["gf_freshbooks_first_name"]);
            $config["meta"]["last_name"] = stripslashes($_POST["gf_freshbooks_last_name"]);
            $config["meta"]["organization"] = stripslashes($_POST["gf_freshbooks_organization"]);

            $is_valid = !empty($config["meta"]["email"]) &&  !empty($config["meta"]["first_name"]) &&  !empty($config["meta"]["last_name"]) &&  !empty($config["meta"]["organization"]);
            $email_validation_class = empty($config["meta"]["email"]) ? " feeds_validation_error" : "";
            $fname_validation_class = empty($config["meta"]["first_name"]) ? " feeds_validation_error" : "";
            $lname_validation_class = empty($config["meta"]["last_name"]) ? " feeds_validation_error" : "";
            $organization_validation_class = empty($config["meta"]["organization"]) ? " feeds_validation_error" : "";

            if($is_valid){
                $config["meta"]["address"] = stripslashes($_POST["gf_freshbooks_address"]);
                $config["meta"]["phone"] = stripslashes($_POST["gf_freshbooks_phone"]);
                $config["meta"]["fax"] = stripslashes($_POST["gf_freshbooks_fax"]);
                $config["meta"]["notes"] = stripslashes($_POST["gf_freshbooks_notes"]);
                $config["meta"]["alsocreate"] = stripslashes($_POST["gf_freshbooks_alsocreate"]);

                $config["meta"]["ponumber"] = $config["meta"]["alsocreate"] == "neither" ? "" : stripslashes($_POST["gf_freshbooks_ponumber"]);
                $config["meta"]["discount"] = $config["meta"]["alsocreate"] == "neither" ? "" : stripslashes($_POST["gf_freshbooks_discount"]);
                $config["meta"]["notes2"] = $config["meta"]["alsocreate"] == "neither" ? "" : stripslashes($_POST["gf_freshbooks_notes2"]);
                $config["meta"]["terms"] = $config["meta"]["alsocreate"] == "neither" ? "" : stripslashes($_POST["gf_freshbooks_terms"]);
                $config["meta"]["is_fixed_cost"] = rgpost("gf_freshbooks_is_fixed");
                $config["meta"]["update_client"] = rgpost("gf_freshbooks_update_client") == "1";

                $config["meta"]["optin_enabled"] = rgpost("freshbooks_optin_enable") ? true : false;
                $config["meta"]["optin_field_id"] = $config["meta"]["optin_enabled"] ? $_POST["freshbooks_optin_field_id"] : "";
                $config["meta"]["optin_operator"] = $config["meta"]["optin_enabled"] ? $_POST["freshbooks_optin_operator"] : "";
                $config["meta"]["optin_value"] = $config["meta"]["optin_enabled"] ? $_POST["freshbooks_optin_value"] : "";

                $line_items = array();
                $item_count = sizeof($_POST["gf_freshbooks_lineitem"]);
                for($i = 0; $i<$item_count; $i++){
                    $item_id = $_POST["gf_freshbooks_lineitem"][$i];
                    if(!empty($item_id)){
                        $cost = $_POST["gf_freshbooks_is_fixed"] == "1" ? $_POST["gf_freshbooks_item_cost"][$i] : $_POST["gf_freshbooks_item_cost_field"][$i];
                        $quantity = $_POST["gf_freshbooks_is_fixed"] == "1" ? $_POST["gf_freshbooks_item_quantity"][$i] : $_POST["gf_freshbooks_item_quantity_field"][$i];
                        $line_items[] = array(  "item_id" => $_POST["gf_freshbooks_lineitem"][$i],
                                                "description"=> $_POST["gf_freshbooks_item_description"][$i],
                                                "cost"=> $cost,
                                                "quantity" => $quantity);
                    }
                }
                $config["meta"]["items"] = $line_items;

                /* The update_feed() function updates an existing feed (if $id > 0) or creates a new one (if $id == 0).
                *  It returns the $id of the newly updated/created feed (useful when creating a new one)*/
                $id = GFFreshBooksData::update_feed($id, $config["form_id"], $config["is_active"], $config["meta"]);
                ?>

                <div class="updated fade" style="padding:6px"><?php echo sprintf(__("Feed Updated. %sback to list%s", "gravityforms_feed"), "<a href='?page=gf_freshbooks'>", "</a>") ?></div>
                <?php
            }
            else{
                ?>
                <div class="error" style="padding:6px"><?php echo __("Feed could not be updated. Please enter all required information below.", "gravityforms_feed") ?></div>
                <?php
            }
        }


        ?>
        <form method="post" action="">
            <input type="hidden" name="freshbooks_setting_id" value="<?php echo $id ?>"/>

            <div class="margin_vertical_10">
                <label for="gf_freshbooks_form_id" class="left_header"><?php _e("Gravity Form", "gravityforms_feed"); ?> <?php gform_tooltip("freshbooks_gravity_form") ?></label>
                <select name="gf_freshbooks_form_id" id="gf_freshbooks_form_id" onchange="SelectForm(jQuery(this).val());">
                    <option value=""><?php _e("Select a form", "gravityforms_feed"); ?> </option>
                    <?php
                    $forms = RGFormsModel::get_forms(true);
                    foreach($forms as $form){
                        $selected = absint($form->id) == rgar($config, "form_id") ? "selected='selected'" : "";
                        ?>
                        <option value="<?php echo absint($form->id) ?>"  <?php echo $selected ?>><?php echo esc_html($form->title) ?></option>
                        <?php
                    }
                    ?>
                </select>
                &nbsp;&nbsp;
                <img src="<?php echo GFFreshBooks::get_base_url() ?>/images/loading.gif" id="freshbooks_wait_form" style="display: none;"/>
            </div>
            <?php
            if(!empty($config["form_id"])){
                $form_fields = self::get_form_fields($config["form_id"]);
                $display = "block";

                //getting list of selection fields to be used by the optin
                $form_meta = RGFormsModel::get_form_meta($config["form_id"]);
            }
            else{
            	$form_fields = "";
                $display = "none";
            }
            ?>
            <div id="freshbooks_options_container" style="display: <?php echo $display ?>;">
                <div class="margin_vertical_10<?php echo !empty($email_validation_class) ? $email_validation_class : "" ?>">
                    <label for="gf_freshbooks_email" class="left_header"><?php _e("Email", "gravityforms_feed"); ?> <span class='feeds_required'>*</span></label>
                    <?php echo self::get_field_drop_down("gf_freshbooks_email", $form_fields, rgar($config['meta'], 'email')) ?>

                    <input type="checkbox" name="gf_freshbooks_update_client" id="gf_freshbooks_update_client" value="1" <?php echo rgar($config['meta'], 'update_client') ? "checked='checked'" : "" ?> />
                    <label for="gf_freshbooks_update_client"><?php _e("Update an exisiting client if email addresses match", "gravityforms_feed"); ?> <?php gform_tooltip("freshbooks_update_client") ?></label>
                </div>
                <div class="margin_vertical_10<?php echo !empty($fname_validation_class) ? $fname_validation_class : ""?>">
                    <label for="gf_freshbooks_first_name" class="left_header"><?php _e("First Name", "gravityforms_feed"); ?> <span class='feeds_required'>*</span></label>
                    <?php echo self::get_field_drop_down("gf_freshbooks_first_name", $form_fields, rgar($config["meta"], "first_name")) ?>
                </div>
                <div class="margin_vertical_10<?php echo !empty($lname_validation_class) ? $lname_validation_class : "" ?>">
                    <label for="gf_freshbooks_last_name" class="left_header"><?php _e("Last Name", "gravityforms_feed"); ?> <span class='feeds_required'>*</span></label>
                    <?php echo self::get_field_drop_down("gf_freshbooks_last_name", $form_fields, rgar($config["meta"],"last_name")) ?>
                </div>
                <div class="margin_vertical_10<?php echo !empty($organization_validation_class) ? $organization_validation_class : ""?>">
                    <label for="gf_freshbooks_organization" class="left_header"><?php _e("Organization", "gravityforms_feed"); ?> <span class='feeds_required'>*</span></label>
                    <?php echo self::get_field_drop_down("gf_freshbooks_organization", $form_fields, rgar($config["meta"],"organization")) ?>
                </div>
                <div class="margin_vertical_10">
                    <label for="gf_freshbooks_address" class="left_header"><?php _e("Address", "gravityforms_feed"); ?></label>
                    <?php echo self::get_field_drop_down("gf_freshbooks_address", $form_fields, rgar($config["meta"],"address")) ?>
                </div>
                <div class="margin_vertical_10">
                    <label for="gf_freshbooks_phone" class="left_header"><?php _e("Phone", "gravityforms_feed"); ?></label>
                    <?php echo self::get_field_drop_down("gf_freshbooks_phone", $form_fields, rgar($config["meta"],"phone")) ?>
                </div>
                <div class="margin_vertical_10">
                    <label for="gf_freshbooks_fax" class="left_header"><?php _e("Fax", "gravityforms_feed"); ?></label>
                    <?php echo self::get_field_drop_down("gf_freshbooks_fax", $form_fields, rgar($config["meta"],"fax")) ?>
                </div>
                <div class="margin_vertical_10">
                    <label for="gf_freshbooks_notes" class="left_header"><?php _e("Notes", "gravityforms_feed"); ?></label>
                    <textarea class="freshbooks_notes" name="gf_freshbooks_notes" id="gf_freshbooks_notes"><?php echo rgar($config["meta"],"notes") ?></textarea>
                </div>
                <?php
                $also_create = empty($config["meta"]["alsocreate"]) ? "neither" : $config["meta"]["alsocreate"];
                ?>
                <div class="margin_vertical_10">
                    <label for="gf_freshbooks_invoice" class="left_header"><?php _e("Also Create", "gravityforms_feed"); ?> <?php gform_tooltip("freshbooks_also_create") ?></label>
                    <label for="gf_freshbooks_invoice"><?php _e("invoice", "gravityforms_feed"); ?></label>
                    <input type="radio" name="gf_freshbooks_alsocreate" id="gf_freshbooks_invoice" value="invoice" <?php echo $also_create == "invoice" ? "checked='checked'" : "" ?> onclick="ToggleAlsoCreate();"/>
                    &nbsp;&nbsp;
                    <label for="gf_freshbooks_estimate"><?php _e("estimate", "gravityforms_feed"); ?></label>
                    <input type="radio" name="gf_freshbooks_alsocreate" id="gf_freshbooks_estimate" value="estimate" <?php echo $also_create == "estimate" ? "checked='checked'" : "" ?> onclick="ToggleAlsoCreate();"/>
                    &nbsp;&nbsp;
                    <label for="gf_freshbooks_neither"><?php _e("neither", "gravityforms_feed"); ?></label>
                    <input type="radio" name="gf_freshbooks_alsocreate" id="gf_freshbooks_neither" value="neither" <?php echo $also_create == "neither" ? "checked='checked'" : "" ?> onclick="ToggleAlsoCreate();"/>
                </div>

                <div id="gf_freshbooks_invoice_container" <?php echo $also_create == "neither" ? "style='display:none;'" : "" ?>>
                    <div class="margin_vertical_10">
                        <label for="gf_freshbooks_ponumber" class="left_header"><?php _e("PO Number", "gravityforms_feed"); ?> <?php gform_tooltip("freshbooks_po_number") ?></label>
                        <?php echo self::get_field_drop_down("gf_freshbooks_ponumber", $form_fields, rgar($config["meta"],"ponumber")) ?>
                    </div>
                    <div class="margin_vertical_10">
                        <label for="gf_freshbooks_discount" class="left_header"><?php _e("Discount", "gravityforms_feed"); ?> <?php gform_tooltip("freshbooks_discount") ?></label>
                        <input type="text" size="10" name="gf_freshbooks_discount" id="gf_freshbooks_discount" value="<?php echo rgar($config["meta"],"discount") ?>"/> %
                    </div>
                    <?php
                    $is_fixed_cost = rgar($config["meta"],"is_fixed_cost") == "1" || rgar($config["meta"],"is_fixed_cost") === null;
                    ?>
                    <div class="margin_vertical_10">
                        <label for="gf_freshbooks_lineitems_container" class="left_header" style="height: 30px;"><?php _e("Line Items", "gravityforms_feed"); ?> <?php gform_tooltip("freshbooks_line_items") ?></label>
                        <div id="gf_freshbooks_lineitem_container">
                            <div class="freshbooks_selector_container">
                                <input type="radio" name="gf_freshbooks_is_fixed" id="gf_freshbooks_fixed_cost" value="1" <?php echo $is_fixed_cost ? "checked='checked'" : "" ?> onclick="ToggleFixedCost();"/>
                                <label for="gf_freshbooks_fixed_cost"><?php _e("Fixed Costs and Quantities", "gravityforms_feed"); ?> <?php gform_tooltip("freshbooks_fixed_cost_quantity") ?></label>
                            </div>

                            <div class="freshbooks_selector_container">
                                <input type="radio" name="gf_freshbooks_is_fixed" id="gf_freshbooks_pricing_fields" value="2" <?php echo rgar($config["meta"],"is_fixed_cost") == "2" ? "checked='checked'" : "" ?> onclick="ToggleFixedCost();"/>
                                <label for="gf_freshbooks_pricing_fields"><?php _e("Use Pricing Fields", "gravityforms_feed"); ?> <?php gform_tooltip("freshbooks_pricing_fields") ?></label>
                            </div>

                            <?php
                            $style = rgar($config["meta"],"is_fixed_cost") != "0" ? "style='display:none;'" : ""
                            ?>
                            <div class="freshbooks_selector_container" <?php echo $style?>>
                                <input type="radio" name="gf_freshbooks_is_fixed" id="gf_freshbooks_dynamic_cost" value="0" <?php echo rgar($config["meta"],"is_fixed_cost") == "0" ? "checked='checked'" : "" ?> onclick="ToggleFixedCost();"/>
                                <label for="gf_freshbooks_dynamic_cost"><?php _e("Pull Costs and Quantities from Form Fields", "gravityforms_feed"); ?> <?php gform_tooltip("freshbooks_dynamic_cost_quantity") ?></label>
                            </div>
                        </div>
                        <?php
                        $style = rgar($config["meta"],"is_fixed_cost") == "2" ? "style='display:none;'" : "";
                        ?>
                        <div class='gf_freshbooks_lineitem_container' <?php echo $style?>>
                            <table class='gf_freshbooks_lineitem_table'>
                                <tr>
                                    <td><?php _e("Line Item", "gravityforms_feed") ?></td>
                                    <td><?php _e("Description", "gravityforms_feed") ?></td>
                                    <td><?php _e("Unit Cost", "gravityforms_feed") ?></td>
                                    <td><?php _e("Quantity", "gravityforms_feed") ?></td>
                                </tr>
                                <?php

                            //adding one blank item
                            if(!is_array(rgar($config["meta"],"items")) || sizeof(rgar($config["meta"],"items")) == 0)
                                $config["meta"]["items"] = array(array());

                            foreach($config["meta"]["items"] as $item){
                                $cost_fields = self::get_field_drop_down_items($form_fields, $is_fixed_cost ? "" : rgar($item,"cost"));
                                $quantity_fields = self::get_field_drop_down_items($form_fields, $is_fixed_cost ? "" : rgar($item,"quantity"));
                                $items = self::get_items(rgar($item,"item_id"));
                                echo self::line_item($items, $cost_fields, $quantity_fields, rgar($item,"description"), $is_fixed_cost ? rgar($item,"cost") : "", $is_fixed_cost ? rgar($item,"quantity") : "", $is_fixed_cost);
                            }

                                ?>
                            </table>
                        </div>

                        <br style="clear: both;"/>
                    </div>
                    <div class="margin_vertical_10">
                        <label for="gf_freshbooks_notes2" class="left_header"><?php _e("Notes", "gravityforms_feed"); ?></label>
                        <textarea class="freshbooks_notes" name="gf_freshbooks_notes2" id="gf_freshbooks_notes2"><?php echo rgar($config["meta"],"notes2") ?></textarea>
                    </div>
                    <div class="margin_vertical_10">
                        <label for="gf_freshbooks_terms" class="left_header"><?php _e("Terms", "gravityforms_feed"); ?></label>
                        <textarea class="freshbooks_notes" name="gf_freshbooks_terms" id="gf_freshbooks_terms"><?php echo rgar($config["meta"],"terms") ?></textarea>
                    </div>
                </div>

                <div id="freshbooks_optin_container" valign="top" class="margin_vertical_10">
                    <label for="freshbooks_optin" class="left_header"><?php _e("Export Condition", "gravityforms_feed"); ?> <?php gform_tooltip("freshbooks_optin_condition") ?></label>
                    <div id="freshbooks_optin">
                        <table>
                            <tr>
                                <td>
                                    <input type="checkbox" id="freshbooks_optin_enable" name="freshbooks_optin_enable" value="1" onclick="if(this.checked){jQuery('#freshbooks_optin_condition_field_container').show('slow');} else{jQuery('#freshbooks_optin_condition_field_container').hide('slow');}" <?php echo rgar($config["meta"],"optin_enabled") ? "checked='checked'" : ""?>/>
                                    <label for="freshbooks_optin_enable"><?php _e("Enable", "gravityforms_feed"); ?></label>
                                </td>
                            </tr>
                            <tr>
                                <td>
                                    <div id="freshbooks_optin_condition_field_container" <?php echo !rgar($config["meta"],"optin_enabled") ? "style='display:none'" : ""?>>
                                        <div id="freshbooks_optin_condition_fields" style="display:none">
                                            <?php _e("Export to FreshBooks if ", "gravityforms_feed") ?>

                                            <select id="freshbooks_optin_field_id" name="freshbooks_optin_field_id" class='optin_select' onchange='jQuery("#freshbooks_optin_value_container").html(GetFieldValues(jQuery(this).val(), "", 25));'></select>
                                            <select id="freshbooks_optin_operator" name="freshbooks_optin_operator" >
                                                <option value="is" <?php echo rgar($config["meta"],"optin_operator") == "is" ? "selected='selected'" : "" ?>><?php _e("is", "gravityforms_feed") ?></option>
                                                <option value="isnot" <?php echo rgar($config["meta"],"optin_operator") == "isnot" ? "selected='selected'" : "" ?>><?php _e("is not", "gravityforms_feed") ?></option>
                                                <option value=">" <?php echo rgar($config['meta'], 'optin_operator') == ">" ? "selected='selected'" : "" ?>><?php _e("greater than", "gravityforms_feed") ?></option>
                                                <option value="<" <?php echo rgar($config['meta'], 'optin_operator') == "<" ? "selected='selected'" : "" ?>><?php _e("less than", "gravityforms_feed") ?></option>
                                                <option value="contains" <?php echo rgar($config['meta'], 'optin_operator') == "contains" ? "selected='selected'" : "" ?>><?php _e("contains", "gravityforms_feed") ?></option>
                                                <option value="starts_with" <?php echo rgar($config['meta'], 'optin_operator') == "starts_with" ? "selected='selected'" : "" ?>><?php _e("starts with", "gravityforms_feed") ?></option>
                                                <option value="ends_with" <?php echo rgar($config['meta'], 'optin_operator') == "ends_with" ? "selected='selected'" : "" ?>><?php _e("ends with", "gravityforms_feed") ?></option>
                                            </select>
                                            <div id="freshbooks_optin_value_container" name="freshbooks_optin_value_container" style="display:inline;"></div>
                                        </div>
                                        <div id="freshbooks_optin_condition_message" style="display:none">
                                            <?php _e("To create an export condition, your form must have a field supported by conditional logic.", "gravityformfreshbooks") ?>
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
                                var selectedField = "<?php echo str_replace('"', '\"', rgar($config["meta"],"optin_field_id"))?>";
                                var selectedValue = "<?php echo str_replace('"', '\"', rgar($config["meta"],"optin_value"))?>";
                                SetOptin(selectedField, selectedValue);
                            });
                        <?php
                        }
                        ?>
                    </script>
                </div>

                <div class="margin_vertical_10">
                    <input type="submit" class="button-primary" name="gf_freshbooks_submit" value="<?php echo empty($id) ? __("Save", "gravityformsfreshbooks") : __("Update", "gravityformsfreshbooks"); ?>" />
                    <input type="button" value="<?php _e("Cancel", "gravityformsfreshbooks"); ?>" class="button" onclick="javascript:document.location='admin.php?page=gf_freshbooks'" />
                </div>
            </div>
        </form>
        </div>
        <script type="text/javascript">
            function SelectForm(formId){
                if(!formId){
                    jQuery("#freshbooks_options_container").slideUp();
                    return;
                }

                jQuery("#freshbooks_wait_form").show();
                jQuery("#freshbooks_options_container").slideUp();

                var mysack = new sack(ajaxurl);
                mysack.execute = 1;
                mysack.method = 'POST';
                mysack.setVar( "action", "gf_select_freshbooks_form" );
                mysack.setVar( "gf_select_freshbooks_form", "<?php echo wp_create_nonce("gf_select_freshbooks_form") ?>" );
                mysack.setVar( "form_id", formId);
                mysack.encVar( "cookie", document.cookie, false );
                mysack.onError = function() {jQuery("#freshbooks_wait_form").hide(); alert('<?php _e("Ajax error while selecting a form", "gravityforms_feed") ?>' )};
                mysack.runAJAX();

                return true;
            }

            function EndSelectForm(fields, form_meta){
                jQuery("#gf_freshbooks_email").html(fields);
                jQuery("#gf_freshbooks_first_name").html(fields);
                jQuery("#gf_freshbooks_last_name").html(fields);
                jQuery("#gf_freshbooks_organization").html(fields);
                jQuery("#gf_freshbooks_address").html(fields);
                jQuery("#gf_freshbooks_phone").html(fields);
                jQuery("#gf_freshbooks_fax").html(fields);
                jQuery("#gf_freshbooks_ponumber").html(fields);
                jQuery(".gf_freshbooks_item_field").html(fields);
                jQuery("#freshbooks_options_container").slideDown();
                jQuery("#freshbooks_wait_form").hide();

                form = form_meta;
                SetOptin("","");
            }

            function ToggleAlsoCreate(){
                var isNeither = jQuery("#gf_freshbooks_neither").is(":checked");
                if(isNeither)
                    jQuery("#gf_freshbooks_invoice_container").slideUp();
                else
                    jQuery("#gf_freshbooks_invoice_container").slideDown();
            }

            function ToggleFixedCost(){

                if(jQuery("#gf_freshbooks_fixed_cost").is(":checked")){
                    jQuery(".gf_freshbooks_lineitem_container").slideDown();
                    jQuery(".gf_freshbooks_item_field").hide();
                    jQuery(".gf_freshbooks_item_value").show();
                }
                else if(jQuery("#gf_freshbooks_dynamic_cost").is(":checked")){
                    jQuery(".gf_freshbooks_lineitem_container").slideDown();
                    jQuery(".gf_freshbooks_item_field").show();
                    jQuery(".gf_freshbooks_item_value").hide();
                }
                else{
                    jQuery(".gf_freshbooks_lineitem_container").slideUp();
                }
            }

            function AddLineItem(element){
                var new_row = "<tr class='gf_freshbooks_lineitem_row gf_freshbooks_new_row'>" + jQuery('.gf_freshbooks_lineitem_row:first').html() + "</tr>";
                jQuery(element).parents('.gf_freshbooks_lineitem_row').after(new_row);
                jQuery('.gf_freshbooks_new_row input, .gf_freshbooks_new_row select').val('');
                jQuery('.gf_freshbooks_new_row').removeClass('gf_freshbooks_new_row');
            }

            function DeleteLineItem(element){

                //don't allow deleting the last item
                if(jQuery('.gf_freshbooks_lineitem_row').length == 1)
                    return;

                jQuery(element).parents('.gf_freshbooks_lineitem_row').remove();
            }

            function SetOptin(selectedField, selectedValue){

                //load form fields
                jQuery("#freshbooks_optin_field_id").html(GetSelectableFields(selectedField, 20));
                var optinConditionField = jQuery("#freshbooks_optin_field_id").val();

                if(optinConditionField){
                    jQuery("#freshbooks_optin_condition_message").hide();
                    jQuery("#freshbooks_optin_condition_fields").show();
                    jQuery("#freshbooks_optin_value_container").html(GetFieldValues(optinConditionField, selectedValue, 25));
                    jQuery("#freshbooks_optin_value").val(selectedValue);
                }
                else{
                    jQuery("#freshbooks_optin_condition_message").show();
                    jQuery("#freshbooks_optin_condition_fields").hide();
                }
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
					str += '<?php $dd = wp_dropdown_categories(array("class"=>"optin_select", "orderby"=> "name", "id"=> "freshbooks_optin_value", "name"=> "freshbooks_optin_value", "hierarchical"=>true, "hide_empty"=>0, "echo"=>false)); echo str_replace("\n","", str_replace("'","\\'",$dd)); ?>';
				}
				else if(field.choices){
					str += '<select id="freshbooks_optin_value" name="freshbooks_optin_value" class="optin_select">'

	                for(var i=0; i<field.choices.length; i++){
	                    var fieldValue = field.choices[i].value ? field.choices[i].value : field.choices[i].text;
	                    var isSelected = fieldValue == selectedValue;
	                    var selected = isSelected ? "selected='selected'" : "";
	                    if(isSelected)
	                        isAnySelected = true;

	                    str += "<option value='" + fieldValue.replace("'", "&#039;") + "' " + selected + ">" + TruncateMiddle(field.choices[i].text, labelMaxCharacters) + "</option>";
	                }

	                if(!isAnySelected && selectedValue){
	                    str += "<option value='" + selectedValue.replace("'", "&#039;") + "' selected='selected'>" + TruncateMiddle(selectedValue, labelMaxCharacters) + "</option>";
	                }
	                str += "</select>";
				}
				else
				{
					selectedValue = selectedValue ? selectedValue.replace(/'/g, "&#039;") : "";
					//create a text field for fields that don't have choices (i.e text, textarea, number, email, etc...)
					str += "<input type='text' placeholder='<?php _e("Enter value", "gravityforms"); ?>' id='freshbooks_optin_value' name='freshbooks_optin_value' value='" + selectedValue.replace(/'/g, "&#039;") + "'>";
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

    public static function add_permissions(){
        global $wp_roles;
        $wp_roles->add_cap("administrator", "gravityforms_freshbooks");
        $wp_roles->add_cap("administrator", "gravityforms_freshbooks_uninstall");
    }

    //Target of Member plugin filter. Provides the plugin with Gravity Forms lists of capabilities
    public static function members_get_capabilities( $caps ) {
        return array_merge($caps, array("gravityforms_freshbooks", "gravityforms_freshbooks_uninstall"));
    }

    public static function disable_freshbooks(){
        delete_option("gf_freshbooks_settings");
    }

    public static function export($entry, $form){
        self::init_api();

        //Login to FreshBooks
        if(!self::is_valid_credentials())
            return;

        //loading data class
        require_once(self::get_base_path() . "/data.php");

        //getting all active feeds
        $feeds = GFFreshBooksData::get_feed_by_form($form["id"], true);
        foreach($feeds as $feed){
            //only export if export condition is met
            if(self::is_optin($form, $feed))
                self::export_feed($entry, $form, $feed);
        }
    }

    public static function export_feed($entry, $form, $settings){

        $name_fields = array();
        foreach($form["fields"] as $field)
            if(RGFormsModel::get_input_type($field) == "name")
                $name_fields[] = $field;

        //Creating client
        self::log_debug("Checking to see if client exists or a new client needs to be created.");
        $client = self::get_client($form, $entry, $settings, $name_fields);

        //if client could not be created, ignore invoice and estimate
        if(!$client)
        {
        	self::log_debug("Unable to create client, not creating invoice/estimate.");
            return;
		}

        if($settings["meta"]["alsocreate"] == "invoice")
            $invoice_estimate = new FreshBooks_Invoice();
        else if($settings["meta"]["alsocreate"] == "estimate")
            $invoice_estimate = new FreshBooks_Estimate();
        else
            return; //don't create invoice or estimate

        $invoice_estimate->poNumber = self::get_entry_value($settings["meta"]["ponumber"], $entry, $name_fields);
        $invoice_estimate->discount = $settings["meta"]["discount"];
        $invoice_estimate->notes = $settings["meta"]["notes"];
        $invoice_estimate->terms = $settings["meta"]["terms"];

        $total = 0;
        $lines = array();
        if($settings["meta"]["is_fixed_cost"] == "2"){

            //creating line items based on pricing fields
            $products = GFCommon::get_product_fields($form, $entry, true, false);

            foreach($products["products"] as $product){
                $product_name = $product["name"];
                $price = GFCommon::to_number($product["price"]);
                if(!empty($product["options"])){
                    $product_name .= " (";
                    $options = array();
                    foreach($product["options"] as $option){
                        $price += GFCommon::to_number($option["price"]);
                        $options[] = $option["option_name"];
                    }
                    $product_name .= implode(", ", $options) . ")";
                }
                $subtotal = floatval($product["quantity"]) * $price;
                $total += $subtotal;

                $lines[] = array(   "name" => $product["name"],
                                    "description"=> $product_name,
                                    "unitCost"=> $price,
                                    "quantity" => $product["quantity"],
                                    "amount" => $subtotal
                                    );
            }
            //adding shipping if form has shipping
            if(!empty($products["shipping"]["name"])){
                $total += floatval($products["shipping"]["price"]);
                $lines[] = array(   "name" => $products["shipping"]["name"],
                                    "description"=> $products["shipping"]["name"],
                                    "unitCost"=> $products["shipping"]["price"],
                                    "quantity" => 1,
                                    "amount" => $products["shipping"]["price"]
                                    );
            }
        }
        else{
            //creating line items based on fixed cost or mapped fields
            foreach($settings["meta"]["items"] as $item){
                $cost = $settings["meta"]["is_fixed_cost"] ? $item["cost"] : self::get_entry_value($item["cost"], $entry, $name_fields);
                $cost = self::get_number($cost);

                $quantity = $settings["meta"]["is_fixed_cost"] ? $item["quantity"] : self::get_entry_value($item["quantity"], $entry, $name_fields);
                $amount = $quantity * $cost;
                $total += $amount;
                $lines[] = array(   "name" => $item["item_id"],
                                    "description"=> $item["description"],
                                    "unitCost"=> $cost,
                                    "quantity" => $quantity,
                                    "amount" => $amount
                                    );
            }
        }

        $invoice_estimate->amount = $total;
        $invoice_estimate->clientId = $client->clientId;
        $invoice_estimate->firstName = $client->firstName;
        $invoice_estimate->lastName = $client->lastName;
        $invoice_estimate->lines = $lines;
        $invoice_estimate->organization = $client->organization;
        $invoice_estimate->pStreet1 = $client->pStreet1;
        $invoice_estimate->pStreet2 = $client->pStreet2;
        $invoice_estimate->pCity = $client->pCity;
        $invoice_estimate->pState = $client->pState;
        $invoice_estimate->pCode = $client->pCode;
        $invoice_estimate->pCountry = $client->pCountry;
        self::log_debug("Creating invoice/estimate.");
        $invoice_estimate->create();
        $lastError = $invoice_estimate->lastError;
        if (empty($lastError))
        {
        	self::log_debug("Invoice/estimate created.");
		}
		else
		{
			self::log_error("The following error occurred when trying to create an invoice/estimate: {$lastError}");
		}
    }

    public static function uninstall(){

        //loading data lib
        require_once(self::get_base_path() . "/data.php");

        if(!GFFreshBooks::has_access("gravityforms_freshbooks_uninstall"))
            die(__("You don't have adequate permission to uninstall the FreshBooks Add-On.", "gravityformsfreshbooks"));

        //droping all tables
        GFFreshBooksData::drop_tables();

        //removing options
        delete_option("gf_freshbooks_site_name");
        delete_option("gf_freshbooks_auth_token");
        delete_option("gf_freshbooks_version");

        //Deactivating plugin
        $plugin = "gravityformsfreshbooks/freshbooks.php";
        deactivate_plugins($plugin);
        update_option('recently_activated', array($plugin => time()) + (array)get_option('recently_activated'));
    }

    public static function is_optin($form, $settings){
        $config = $settings["meta"];
        $operator = isset($config["optin_operator"]) ? $config["optin_operator"] : "";

        $field = RGFormsModel::get_field($form, $config["optin_field_id"]);
        $field_value = RGFormsModel::get_field_value($field, array());

        if(empty($field) || !$config["optin_enabled"])
            return true;

        $is_value_match = RGFormsModel::is_value_match($field_value, rgar($config,"optin_value"), $operator);

        return $is_value_match;
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

    private static function init_api(){
        require_once GFFreshBooks::get_base_path() . '/api/Client.php';
        require_once GFFreshBooks::get_base_path() . '/api/Invoice.php';
        require_once GFFreshBooks::get_base_path() . '/api/Estimate.php';
        require_once GFFreshBooks::get_base_path() . '/api/Item.php';

        $url = "https://" . get_option("gf_freshbooks_site_name") . ".freshbooks.com/api/2.1/xml-in";
        $authtoken = get_option("gf_freshbooks_auth_token");
		self::log_debug("Initializing API - url: {$url} - token: {$authtoken}");
        FreshBooks_HttpClient::init($url,$authtoken);
        self::log_debug("API Initialized.");
    }

    private static function is_valid_credentials(){
        self::log_debug("Validating credentials.");
        self::init_api();
        $items = new FreshBooks_Item();

        $dummy = array();
        $return_val = $items->listing($dummy, $dummy);
        if ($return_val)
        {
			self::log_debug("Valid site name and authorization token.");
        }
        else
        {
			self::log_error("Invalid site name and/or authorization token.");
        }
        return $return_val;
    }

    private static function get_items($selected_item){
        self::init_api();
        $items = new FreshBooks_Item();

        $result = array();
        $result_info = array();
        $items->listing($result, $result_info);
        $str = "<option value=''></option>";
        foreach($result as $line_item){
            $selected = $line_item->itemId == $selected_item ? "selected='selected'" : "";
            $str .= "<option value='$line_item->itemId' $selected>$line_item->name</option>";
        }

        return $str;
    }

    private static function get_number($number){


        //Removing all non-numeric characters
        $array = str_split($number);
        foreach($array as $char)
            if (($char >= '0' && $char <= '9') || $char=="," || $char==".")
                $clean_number .= $char;


        //Removing thousand separators but keeping decimal point
        $array = str_split($clean_number);
        for($i=0, $count = sizeof($array); $i<$count; $i++)
        {
            $char = $array[$i];
            if ($char >= '0' && $char <= '9')
                $float_number .= $char;
            else if(($char == "." || $char == ",") && strlen($clean_number) - $i <= 3)
                $float_number .= ".";
        }

        return $float_number;

    }

    private static function line_item($line_items, $cost_fields, $quantity_fields, $description, $cost, $quantity, $is_fixed){

        $select_display = $is_fixed ? "none" : "block";
        $text_display = $is_fixed ? "block" : "none";

        $str = "<tr class='gf_freshbooks_lineitem_row'>
                    <td><select name='gf_freshbooks_lineitem[]' class='gf_freshbooks_lineitem'>$line_items</select></td>
                    <td><input type='text' name='gf_freshbooks_item_description[]' value='$description'/></td>
                    <td>
                        <select name='gf_freshbooks_item_cost_field[]' class='gf_freshbooks_item_field' style='display:$select_display'>$cost_fields</select>
                        <input type='text' name='gf_freshbooks_item_cost[]' class='gf_freshbooks_item_value' value='$cost' style='display:$text_display'/>
                    </td>
                    <td>
                        <select name='gf_freshbooks_item_quantity_field[]' class='gf_freshbooks_item_field' style='display:$select_display'>$quantity_fields</select>
                        <input type='text' name='gf_freshbooks_item_quantity[]' class='gf_freshbooks_item_value' value='$quantity' style='display:$text_display'/>
                    </td>
                    <td>
                        <input type='image' src='" . GFFreshBooks::get_base_url() . "/images/remove.png' onclick='DeleteLineItem(this); return false;' alt='Delete' title='Delete' />
                        <input type='image' src='" . GFFreshBooks::get_base_url() . "/images/add.png' onclick='AddLineItem(this); return false;' alt='Add line item' title='Add line item' />
                    </td>
                </tr>";
        return $str;
    }

    private static function get_entry_value($field_id, $entry, $name_fields){
        foreach($name_fields as $name_field){
            if($field_id == $name_field["id"]){
                $value = RGFormsModel::get_lead_field_value($entry, $name_field);
                return GFCommon::get_lead_field_display($name_field, $value);
            }
        }

        return $entry[$field_id];
    }

    private static function get_client($form, $entry, $settings, $name_fields){
        $client = new FreshBooks_Client();
        $email = strtolower($entry[$settings["meta"]["email"]]);

        $is_new = true;
        if($settings["meta"]["update_client"]){

            //is there an existing client with the same email? If so, use it, if not, create one
            $client->listing($all_clients, $result_info);
            foreach($all_clients as $current_client){
                if(strtolower($current_client->email) == $email){
                    $client = $current_client;
                    $is_new = false;
                    break;
                }
            }
        }

        $client->email = self::get_entry_value($settings["meta"]["email"], $entry, $name_fields);
        $client->firstName = self::get_entry_value($settings["meta"]["first_name"], $entry, $name_fields);
        $client->lastName = self::get_entry_value($settings["meta"]["last_name"], $entry, $name_fields);
        $client->organization = self::get_entry_value($settings["meta"]["organization"], $entry, $name_fields);

        $address_field = $settings["meta"]["address"];
        $client->pStreet1 = $entry[$address_field . ".1"];
        $client->pStreet2 = $entry[$address_field . ".2"];
        $client->pCity = $entry[$address_field . ".3"];
        $client->pState = $entry[$address_field . ".4"];
        $client->pCode = $entry[$address_field . ".5"];
        $client->pCountry = $entry[$address_field . ".6"];

        $client->workPhone = self::get_entry_value($settings["meta"]["phone"], $entry, $name_fields);
        $client->fax = self::get_entry_value($settings["meta"]["fax"], $entry, $name_fields);
        $client->notes = $settings["meta"]["notes"];

        if($is_new)
        {
         	self::log_debug("Client not found; creating new client with email address {$email}.");
            $client->create();
            $lastError = $client->lastError;
            if (empty($lastError))
            {
            	self::log_debug("New client created.");
			}
			else
			{
				self::log_error("The following error occurred when trying to create a new client: {$lastError}");
			}
		}
        else{
        	self::log_debug("Existing client found with email address {$email}, not creating new one.");
            $id = $client->clientId;
            $client->update();
            $client->clientId = $id;
        }

        return $client;
    }

    public static function select_form(){
        check_ajax_referer("gf_select_freshbooks_form", "gf_select_freshbooks_form");
        $form_id =  intval($_POST["form_id"]);

        $form_fields = self::get_form_fields($form_id);

        $drop_down_items = self::get_field_drop_down_items($form_fields, "");

        $form = RGFormsModel::get_form_meta($form_id);
        die("EndSelectForm('" . str_replace("'", "\'", $drop_down_items) . "', " . GFCommon::json_encode($form) . ");");

        //die("EndSelectForm(\"$drop_down_items\");");
    }

    private static function get_form_fields($form_id){
        $form = RGFormsModel::get_form_meta($form_id);
        $fields = array();

        if(is_array($form["fields"])){
            foreach($form["fields"] as $field){
                if(is_array($field["inputs"])){

                    //If this is an address field, add full name to the list
                    if(RGFormsModel::get_input_type($field) == "address" || RGFormsModel::get_input_type($field) == "name")
                        $fields[] =  array($field["id"], GFCommon::get_label($field) . " (" . __("Full" , "gravityforms_feed") . ")");

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

    private static function get_field_drop_down($field_name, $fields, $selected_field){
        $str = "<select name='$field_name' id='$field_name'>";
        $str .= self::get_field_drop_down_items($fields, $selected_field);
        $str .= "</select>";
        return $str;
    }

    private static function get_field_drop_down_items($fields, $selected_field){
        $str = "<option value=''></option>";
        if(is_array($fields)){
            foreach($fields as $field){
                $field_id = $field[0];
                $field_label = $field[1];
                $selected = $field_id == $selected_field ? "selected='selected'" : "";
                $str .= "<option value='" . $field_id . "' ". $selected . ">" . GFCommon::truncate_middle($field_label, 25) . "</option>";
            }
        }
        return $str;
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
		$plugins[self::$slug] = "FreshBooks";
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
?>
