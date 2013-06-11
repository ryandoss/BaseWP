<?php
class GFPayPalData{

    public static function update_table(){
        global $wpdb;
        $table_name = self::get_paypal_table_name();

        if ( ! empty($wpdb->charset) )
            $charset_collate = "DEFAULT CHARACTER SET $wpdb->charset";
        if ( ! empty($wpdb->collate) )
            $charset_collate .= " COLLATE $wpdb->collate";

        require_once(ABSPATH . '/wp-admin/includes/upgrade.php');

        $sql = "CREATE TABLE $table_name (
              id mediumint(8) unsigned not null auto_increment,
              form_id mediumint(8) unsigned not null,
              is_active tinyint(1) not null default 1,
              meta longtext,
              PRIMARY KEY  (id),
              KEY form_id (form_id)
            )$charset_collate;";

        dbDelta($sql);

        $table_name = self::get_transaction_table_name();
        $sql = "CREATE TABLE $table_name (
              id mediumint(8) unsigned not null auto_increment,
              entry_id int(10) unsigned not null,
              transaction_type varchar(15),
              subscription_id varchar(50),
              transaction_id varchar(50),
              parent_transaction_id varchar(50),
              is_renewal tinyint(1) not null default 0,
              amount decimal(19,2),
              date_created datetime,
              PRIMARY KEY  (id),
              KEY txn_id (transaction_id)
            )$charset_collate;";

        dbDelta($sql);

    }

    public static function insert_transaction($entry_id, $transaction_type, $subscription_id, $transaction_id, $parent_transaction_id, $amount){
        global $wpdb;
        $table_name = self::get_transaction_table_name();

        if(empty($parent_transaction_id))
            $parent_transaction_id = $transaction_id;

        $existing_transaction_id = $wpdb->get_var($wpdb->prepare("SELECT id FROM $table_name WHERE transaction_id=%s", $transaction_id));

        if(!$existing_transaction_id){

            $is_renewal = 0;
            if(!empty($subscription_id)){
                $count = $wpdb->get_var($wpdb->prepare("SELECT count(id) FROM $table_name WHERE subscription_id=%s", $transaction_id));
                if($count > 0)
                    $is_renewal = 1;
            }

            $sql = $wpdb->prepare(" INSERT INTO $table_name (entry_id, transaction_type, subscription_id, transaction_id, parent_transaction_id, amount, is_renewal, date_created)
                                    values(%d, %s, %s, %s, %s, %f, %d, utc_timestamp())", $entry_id, $transaction_type, $subscription_id, $transaction_id, $parent_transaction_id, $amount, $is_renewal);
            $wpdb->query($sql);
            $id = $wpdb->insert_id;
        }
        else{
            $id = $existing_transaction_id;
        }

        return $id;
    }


    public static function get_transaction_totals($form_id){
        global $wpdb;
        $lead_table_name = RGFormsModel::get_lead_table_name();
        $transaction_table_name = self::get_transaction_table_name();

        $sql = $wpdb->prepare(" SELECT t.transaction_type, sum(t.amount) revenue, count(t.id) transactions
                                 FROM {$transaction_table_name} t
                                 INNER JOIN {$lead_table_name} l ON l.id = t.entry_id
                                 WHERE l.form_id={$form_id}
                                 GROUP BY t.transaction_type", $form_id);

        $results = $wpdb->get_results($sql, ARRAY_A);
        $totals = array();
        if(is_array($results)){
            foreach($results as $result){
                $totals[$result["transaction_type"]] = array("revenue" => empty($result["revenue"]) ? 0 : $result["revenue"] , "transactions" => empty($result["transactions"]) ? 0 : $result["transactions"]);
            }
        }

        return $totals;
    }

    public static function get_paypal_table_name(){
        global $wpdb;
        return $wpdb->prefix . "rg_paypal";
    }

    public static function get_transaction_table_name(){
        global $wpdb;
        return $wpdb->prefix . "rg_paypal_transaction";
    }

    public static function get_feeds(){
        global $wpdb;
        $table_name = self::get_paypal_table_name();
        $form_table_name = RGFormsModel::get_form_table_name();
        $sql = "SELECT s.id, s.is_active, s.form_id, s.meta, f.title as form_title
                FROM $table_name s
                INNER JOIN $form_table_name f ON s.form_id = f.id";

        $results = $wpdb->get_results($sql, ARRAY_A);

        $count = sizeof($results);
        for($i=0; $i<$count; $i++){
            $results[$i]["meta"] = maybe_unserialize($results[$i]["meta"]);
        }

        return $results;
    }

    public static function delete_feed($id){
        global $wpdb;
        $table_name = self::get_paypal_table_name();
        $wpdb->query($wpdb->prepare("DELETE FROM $table_name WHERE id=%s", $id));
    }

    public static function get_feed_by_form($form_id, $only_active = false){
        global $wpdb;
        $table_name = self::get_paypal_table_name();
        $active_clause = $only_active ? " AND is_active=1" : "";
        $sql = $wpdb->prepare("SELECT id, form_id, is_active, meta FROM $table_name WHERE form_id=%d $active_clause", $form_id);
        $results = $wpdb->get_results($sql, ARRAY_A);
        if(empty($results))
            return array();

        //Deserializing meta
        $count = sizeof($results);
        for($i=0; $i<$count; $i++){
            $results[$i]["meta"] = maybe_unserialize($results[$i]["meta"]);
        }
        return apply_filters("gform_paypal_get_feeds_{$form_id}", apply_filters('gform_paypal_get_feeds', $results, $form_id), $form_id);
    }

    public static function get_feed($id){
        global $wpdb;
        $table_name = self::get_paypal_table_name();
        $sql = $wpdb->prepare("SELECT id, form_id, is_active, meta FROM $table_name WHERE id=%d", $id);
        $results = $wpdb->get_results($sql, ARRAY_A);
        if(empty($results))
            return array();

        $result = $results[0];
        $result["meta"] = maybe_unserialize($result["meta"]);
        return $result;
    }

    public static function update_feed($id, $form_id, $is_active, $setting){
        global $wpdb;
        $table_name = self::get_paypal_table_name();
        $setting = maybe_serialize($setting);
        if($id == 0){
            //insert
            $wpdb->insert($table_name, array("form_id" => $form_id, "is_active"=> $is_active, "meta" => $setting), array("%d", "%d", "%s"));
            $id = $wpdb->get_var("SELECT LAST_INSERT_ID()");
        }
        else{
            //update
            $wpdb->update($table_name, array("form_id" => $form_id, "is_active"=> $is_active, "meta" => $setting), array("id" => $id), array("%d", "%d", "%s"), array("%d"));
        }

        return $id;
    }

    public static function drop_tables(){
        global $wpdb;
        $wpdb->query("DROP TABLE IF EXISTS " . self::get_paypal_table_name());
    }

    // get forms that are not assigned to feeds
    public static function get_available_forms($active_form = ''){

        $forms = RGFormsModel::get_forms();
        $available_forms = array();

        foreach($forms as $form) {
            $available_forms[] = $form;
        }

        return $available_forms;
    }
}
?>
