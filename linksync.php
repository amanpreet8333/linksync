<?php
/*
  Plugin Name: linksync for WooCommerce
  Plugin URI: http://www.linksync.com/integrate/woocommerce
  Description:  WooCommerce extension for syncing inventory and order data with other apps, including Xero, QuickBooks Online, Vend, Saasu and other WooCommerce sites.
  Author: linksync
  Author URI: http://www.linksync.com
  Version: 2.2.9
 */
// RE-CONNECT because it's wp set on mysqli
@mysql_connect(DB_HOST, DB_USER, DB_PASSWORD);
@mysql_select_db(DB_NAME);
@mysql_set_charset('utf8');
/*
 * To handle all languages
 */
mb_internal_encoding('UTF-8');
mb_http_output('UTF-8');
mb_http_input('UTF-8');
mb_language('uni');
mb_regex_encoding('UTF-8'); 
//include_once(dirname(__FILE__) . '/linksync_add_action_product.php'); #POST Product hook file
if (!@include_once(dirname(__FILE__) . '/linksync_add_action_order.php')) {
    echo ("<p style='text-align:center;'>Linksync : linksync_add_action_order.php File Not Found !</p>");
}  // GET Order hook file
if (!@include_once(dirname(__FILE__) . '/linksync_add_action_delete.php')) {
    echo ("<p style='text-align:center;'>Linksync : linksync_add_action_delete.php File Not Found !</p>");
}
if (!@include_once(dirname(__FILE__) . '/linksync_add_action_product_QB.php')) {
    echo ("<p style='text-align:center;'>Linksync : linksync_add_action_product_QB.php File Not Found !</p>");
}

class linksync {

    public function __construct() {
        add_action('plugins_loaded', array('linksync', 'check_required_plugins')); # In order to check WooCommerce Plugin existence  
        add_action('admin_menu', array(&$this, 'linksync_add_menu'), 99); # To create custom menu in Wordpress Side Bar  
        add_action('admin_menu', array(&$this, 'linksync_wooVersion_check'));
        add_action('admin_notices', array('linksync', 'linksync_show_message'));
        // add_action('wp', array('linksync', 'linksync_dailyCron'));
        // add_action('linksync_logs_clear', array('linksync', 'clearLogsDetails'));
        add_action('admin_notices', array('linksync', 'linksync_video_message'));
        add_filter('contextual_help', array('linksync', 'linksync_help'));
        add_filter('plugin_action_links_' . plugin_basename(__FILE__), array(&$this, 'plugin_action_links'));
    }

    public static function activate() { 
        global $wpdb;
        require_once( ABSPATH . 'wp-admin/includes/upgrade.php');
        $check_laid=get_option('linksync_laid');
        if(isset($check_laid)&&!empty($check_laid)){
            self::checkForConnection($check_laid);
        }
        add_option('linksync_laid', "");
        add_option('linksync_status', "");
        add_option('linksync_frequency', "");
        add_option('linksync_connected_url', '');
        add_option('linksync_test', 'off');
        add_option('linksync_last_test_time', "");
        add_option('linksync_version', '');
        add_option('linksync_time_offset', '');
        add_option('linksync_connectedto', '');
        add_option('is_linksync_cron_running', "0");
        add_option('linksync_full_stock_import', 'yes');
        add_option('linksync_addedfile', '');
        add_option('linksync_connectionwith', '');
        add_option('linksync_current_stock_index', 0);
        add_option('linksync_current_stock_status', 0);
        add_option('linksync_updated_products_count', "0");
        add_option('linksync_stock_updated_time', "1900-01-01 00:00:00");
        add_option('linksync_option', '');
        add_option('hide_this_notice', 'on');
        // Product Sync Settings 
        add_option('product_sync_type', 'disabled_sync'); # Two-way ,Vend to WooCommerce,WooCommerce to Vend,Disabled
        add_option('ps_name_title', 'on');
        add_option('ps_description', 'on');
        add_option('ps_desc_copy', '');
        add_option('ps_price', 'on');
        add_option('excluding_tax', 'on');
        add_option('tax_class', '');
        add_option('price_book', 'off');
        add_option('price_book_identifier', '');
        add_option('ps_categories', 'off');
        add_option('ps_quantity', 'on');
        add_option('ps_outlet', 'on');
        add_option('ps_unpublish', 'on');
        add_option('ps_brand', 'on');
        add_option('ps_tags', 'off');
        add_option('cat_radio', 'ps_cat_tags');
        add_option('ps_imp_by_tag', 'off');
        add_option('import_by_tags_list', '');
        add_option('ps_images', 'off');
        add_option('ps_import_image_radio', 'Enable');
        add_option('ps_create_new', 'on');
        add_option('ps_delete', '');
        add_option('prod_update_req', '');
        add_option('prod_update_suc', NULL);
        add_option('ps_outlet_details', '');
        add_option('ps_wc_to_vend_outlet', 'on');
        add_option('wc_to_vend_outlet_detail', '');
        add_option('ps_pending', '');
        add_option('price_field', 'regular_price');
        add_option('ps_attribute', 'on');
        add_option('linksync_visiable_attr', '1');
        add_option('linksync_woocommerce_tax_option', 'on');
        //Order sync Add options
        add_option('order_sync_type', 'disabled');
        add_option('order_time_req', null);
        add_option('order_time_suc', null);
        add_option('order_status_wc_to_vend', 'wc-processing');
        add_option('wc_to_vend_outlet', '');
        add_option('wc_to_vend_register', '');
        add_option('wc_to_vend_user', '');
        add_option('wc_to_vend_tax', '');
        add_option('wc_to_vend_payment', '');
        add_option('wc_to_vend_export', '');
        // Vend To WC
        add_option('order_vend_to_wc', 'wc-completed');
        add_option('vend_to_wc_tax', '');
        add_option('vend_to_wc_payments', '');
        add_option('vend_to_wc_customer', '');
        add_option('laid_message', null);
        add_option('prod_last_page', '');
        //QuickBook Options
        add_option('product_sync_type_QBO', 'disabled_sync');
        add_option('order_sync_type_QBO', 'disabled');
        add_option('order_status_wc_to_QBO', 'wc-processing');
        add_option('wc_to_QBO_tax', '');
        add_option('wc_to_QBO_payment', '');
        add_option('wc_to_QBO_export', '');
        add_option('order_QBO_to_wc', 'wc-completed');
        add_option('QBO_to_wc_tax', '');
        add_option('QBO_to_wc_payments', '');
        add_option('QBO_to_wc_customer', '');
        add_option('ps_account_expense', '');
        add_option('ps_account_revenue', '');
        add_option('ps_account_asset', '');
        add_option('order_account', '');
        add_option('QBO_class', '');
        add_option('QBO_locations', '');
        add_option('product_import', 'no');
        add_option('order_import', 'no');
        //order id
        add_option('linksync_sent_order_id', '');
        add_option('Vend_orderIDs', '');
        //product details
        add_option('product_detail', '');
        //order details
        add_option('order_detail', '');
        //Woo Version Checker
        add_option('linksync_wooVersion', 'off');
        //user activity
        add_option('linksync_user_activity', time());
        add_option('linksync_user_activity_daily', time());
        //update notic 
        add_option('linksync_update_notic', 'off');
        //Post product 
        add_option('post_product', 0);
        // syncing Status
        add_option('linksync_sycning_status', NULL);
        // display_retail_price_tax_inclusive
        add_option('linksync_tax_inclusive', '');
// WEBHOOK CONCEPT 
        $webhook_url_code = linksync::linksync_autogenerate_code();
        add_option('webhook_url_code', $webhook_url_code);
        // Logs Record Table 
        $sql1 = 'CREATE TABLE IF NOT EXISTS `' . $wpdb->prefix . 'linksync_log`(
			`id_linksync_log` int(10) unsigned NOT NULL auto_increment,
			`method` varchar(200),
			`result` varchar(200),
                        `laid` varchar(50),
			`message` text,
			`date_add` datetime,
			PRIMARY KEY (`id_linksync_log`)) DEFAULT CHARSET=utf8';
        $sql2 = 'CREATE TABLE IF NOT EXISTS `' . $wpdb->prefix . 'linksync_laidKey`(
			`id` int(10) unsigned NOT NULL auto_increment,
			`api_key` varchar(200),
                        `status` varchar(50),
                        `date_add` date,
			PRIMARY KEY (`id`)) DEFAULT CHARSET=utf8';
        dbDelta($sql1);
        dbDelta($sql2);
        // Removing Spaces b/w  sku value of product 
        // linksync::linksync_removespaces_of_exists_product();
    }

    public static function linksync_wooVersion_check() {
        $plugin_file = WP_PLUGIN_DIR . '/woocommerce/woocommerce.php';
        $data = get_plugin_data($plugin_file, $markup = true, $translate = true);
        if (isset($data)) {
            if (isset($data['Version']) && !empty($data['Version'])) {
                $check = '2.2';
                if ($data['Version'] <= $check) {
                    update_option('linksync_wooVersion', 'on');
                    ?>  <div class="error">
                        <p><?php echo 'WooCommerce ' . $data['Version'] . ' detected - linksync WooCommerce requires WooCommerce 2.2.x or higher. Please upgrade your version of WooCommerce to use this plugin.'; ?></p>
                    </div> <?php
                } else {
                    update_option('linksync_wooVersion', 'off');
                }
            }
        }
    }

    public static function clearLogsDetails() {
        $fileName = dirname(__FILE__) . '/classes/raw-log.txt';
        if (file_exists($fileName)) {
            $linecount = 0;
            $handle = fopen($fileName, "r");
            if ($handle) {
                while (!feof($handle)) {
                    $lines[] = fgets($handle);
                    $linecount++;
                }
                if ($linecount > 10000) {
                    for ($lineNum = 1; $lineNum <= 10000; $lineNum++) {
                        unset($lines[$lineNum]);
                    }
                    $fp = fopen($fileName, 'w+');
                    if ($fp) {
                        foreach ($lines as $line) {
                            fwrite($fp, $line);
                        }
                        fclose($fp);
                    }
                    linksync_class::add('Daily Cron', 'Success', 'Older 10000 Lines Removed!', '-');
                }
            }
        }
    }

    public function linksync_add_menu() {
        if (get_option('linksync_wooVersion') == 'off') {
            $my_admin_page = add_submenu_page('woocommerce', 'linksync', 'linksync', 'manage_options', 'linksync', array(&$this, 'linksync_settings'));
        }
    }

    public static function linksync_help() {
        $screen = get_current_screen();
        // Add my_help_tab if current screen is My Admin Page
        $screen->add_help_tab(array(
            'id' => 'my_help_tab',
            'title' => __(' Documentation '),
            'content' => '<br><p>' . __('Thank you for using linksync for WooCommerce. Should you need help configuring and using the plugin, please review our documentation.') . '</p><p><a target="_blank" href="https://www.linksync.com/help/vend-woocommerce" class="button button-primary">Linsync WooCommerce Documentation</a></p>
<p>Watch the 3-minute "getting started guide" for linksync for WooCommerce.<br><br><a href="//fast.wistia.net/embed/iframe/mfwv2hb8wx?popover=true" class="wistia-popover[height=576,playerColor=5aaddd,width=1024]"><img src="https://embed-ssl.wistia.com/deliveries/92d5bedfb2638333806b598616d315640b701a95.jpg?image_play_button=true&image_play_button_color=5aaddde0&image_crop_resized=200x113" alt="" /></a>
<script charset="ISO-8859-1" src="//fast.wistia.com/assets/external/popover-v1.js"></script></p>                
',
        ));
    }

    public function linksync_settings() {
        include(sprintf("%s/settings.php", dirname(__FILE__)));
    }

    public static function check_required_plugins() {
        if (!in_array('woocommerce/woocommerce.php', apply_filters('active_plugins', get_option('active_plugins')))) {
            add_action('admin_notices', array('linksync', 'dependent_plugin_error'));
        }
    }

    public function plugin_action_links($links) {
        $action_links = array(
            'settings' => '<a href="' . admin_url('admin.php?page=linksync') . '" title="' . esc_attr(__('View Linksync Settings', 'linksync')) . '">' . __('Settings', 'linksync') . '</a>',
        );

        return array_merge($action_links, $links);
    }

    public static function dependent_plugin_error() {
        ?>
        <div class="error">
            <p><?php echo 'linksync for WooCommerce requires WooCommerce Plugin. Please Activate Or <a target="_blank" href="http://wordpress.org/plugins/woocommerce/">Install it</a>.'; ?></p>
        </div>
        <?php
    }

    public static function linksync_autogenerate_code($length = 6) {
        $characters = 'abcdefghijklmnopqrstuvwxyzABCDEFGHIJKLMNOPQRSTUVWXYZ0123456789';
        $randomString = '';
        for ($i = 0; $i < $length; $i++) {
            $randomString .= $characters[rand(0, strlen($characters) - 1)];
        }
        return $randomString;
    }

    public function linksync_logs() {
        include_once(dirname(__FILE__) . '/classes/Class.linksync.php');
        linksync_class::printallLogs();
    }

    public static function appid_app($app_id) {
        $connected_app = array(
            '4' => 'Xero',
            '7' => 'MYOB RetailManager',
            '8' => 'Saasu',
            '13' => 'WooCommerce',
            '15' => 'QuickBooks Online',
            '18' => 'Vend'
        );
        if (array_key_exists($app_id, $connected_app)) {
            $result['success'] = $connected_app[$app_id];
        } else {
            $result['error'] = 'The supplied API Key is not valid for use with linksync for WooCommerce.';
        }
        return $result;
    }

    public static function linksync_show_message() {
        $laid_message = get_option('laid_message');
        if (isset($laid_message) && !empty($laid_message)) {
            ?>
            <div  class="error" style=" border-color: #007AB1;margin-bottom: 10px;
                  margin-top: 10px;
                  ">
                <p><?php echo @$laid_message; ?></p>
            </div> 
            <?php
        }
    }

    public static function linksync_video_message() {
        if (get_option('linksync_wooVersion') == 'off') {
            if (isset($_POST['hide'])) {
                update_option('hide_this_notice', 'off');
            }
            if (get_option('hide_this_notice') == 'on') {
                ?>
                <div class="updated">
                    <p><?php echo '<form method="POST"><input style="float:right;cursor:pointer" type="submit" class="add-new-h2"   name="hide" value="Hide this notice"></form>Watch the 3-minute "getting started guide" for linksync for WooCommerce.<br><br><a href="//fast.wistia.net/embed/iframe/mfwv2hb8wx?popover=true" class="wistia-popover[height=576,playerColor=5aaddd,width=1024]"><img src="https://embed-ssl.wistia.com/deliveries/92d5bedfb2638333806b598616d315640b701a95.jpg?image_play_button=true&image_play_button_color=5aaddde0&image_crop_resized=200x113" alt="" /></a>
<script charset="ISO-8859-1" src="//fast.wistia.com/assets/external/popover-v1.js"></script>
'; ?></p>
                    <style>
                        .add-new-h2:hover {
                            background: #2ea2cc;
                            color: #fff;
                        }
                        .add-new-h2{ margin-left: 4px;
                                     padding: 4px 8px;
                                     position: relative;
                                     top: -3px;
                                     color: #0074a2;
                                     text-decoration: none;
                                     border: none;
                                     -webkit-border-radius: 2px;
                                     border-radius: 2px;
                                     background: #e0e0e0;
                                     text-shadow: none;
                                     font-weight: 600;
                                     font-size: 13px;}</style>

                    </div> 

                    <?php
                }
            }
        }

        public function upgrade_notify_linksync() {
            require_once ABSPATH . 'wp-admin/includes/plugin.php';
            include_once(dirname(__FILE__) . '/classes/Class.linksync.php');
            $laidkey = get_option('linksync_laid');
            $testMode = get_option('linksync_test');
            if (!empty($laidkey)) {
                $linksync_class = new linksync_class($laidkey, $testMode);
                $laidinfo = $linksync_class->upgrade_notification();
                if (isset($laidinfo) && !empty($laidinfo)) {
                    if (!isset($laidinfo['errorCode'])) {
                        if ($laidinfo['connected_app'] == '13') {
                            $linksync_version = $laidinfo['connected_app_version'];
                        } elseif ($laidinfo['app'] == '13') {
                            $linksync_version = $laidinfo['app_version'];
                        } else {
                            $linksync_version = NULL;
                        }
                        update_option('linksync_version', $linksync_version);
                        $plugin_file = dirname(__FILE__) . '/linksync.php';
                        $plugin_data = get_plugin_data($plugin_file, $markup = true, $translate = true);
                        $running_version = $plugin_data['Version'];
                        if ($linksync_version > $running_version) {
                            update_option('linksync_update_notic', 'on');
                        } else {
                            update_option('linksync_update_notic', 'off');
                        }
                        update_option('laid_message', isset($laidinfo['message']) ? $laidinfo['message'] : null);
                    }
                }
            }
        }

        public static function update_notification_message() {
            ?>
            <div class="updated">
            <p><?php echo 'linksync for WooCommerce <b>' . get_option('linksync_version') . '</b> is available! Please <a target="_blank" href="https://www.linksync.com/help/releases/vend-woocommerce">Update now.</a>'; ?></p>
        </div>
        <?php
    }

    #------------------------PRODUCT POST---------------------------#

    public static function linksync_productPost() {
        include_once(dirname(__FILE__) . '/classes/Class.linksync.php'); # Handle Module Functions
        include_once(dirname(__FILE__) . '/linksync_add_action_product.php'); #POST Product hook file
    }

    public static function linksync_productPost_QBO() {
        include_once(dirname(__FILE__) . '/classes/Class.linksync.php'); # Handle Module Functions
        include_once(dirname(__FILE__) . '/linksync_add_action_product_QB.php'); #POST Product hook file
    }

    // Function to used to remove space b/w sku 
    public static function linksync_removespaces($vars) {
        //echo "<pre>";print_r($_POST);
        if (isset($_POST['_sku']) && !empty($_POST['_sku'])) {
            $sku = $_POST['_sku'];
            if (strpos($sku, ' ')) {
                $sku_replaced = str_replace(' ', '', $sku);
                $_POST['_sku'] = $sku_replaced;
            }
            $search = array('/', '\\', ':', ';', '!', '@', '#', '$', '%', '^', '*', '(', ')', '+', '=', '|', '{', '}', '[', ']', '"', "'", '<', '>', ',', '?', '~', '`', '&', '.');
            foreach ($search as $special) {
                $sku = $_POST['_sku'];
                if (strpos($sku, $special)) {
                    $sku_replaced = str_replace($special, '-', $sku);
                    $_POST['_sku'] = $sku_replaced;
                }
            }
        }
        if (isset($_POST['product-type']) && $_POST['product-type'] == 'variable') {
            if (isset($_POST['variable_sku']) && !empty($_POST['variable_sku'])) {
                foreach ($_POST['variable_sku'] as $key => $sku) {
                    //  print_r($sku);
                    //Remove the Space in the SKU 
                    if (isset($sku) && !empty($sku)) {
                        if (strpos($sku, ' ')) {
                            $sku_replaced = str_replace(' ', '', $sku);
                            $_POST['variable_sku']["{$key}"] = $sku_replaced;
                        }
                    }
                    $search = array('/', '\\', ':', ';', '!', '@', '#', '$', '%', '^', '*', '(', ')', '+', '=', '|', '{', '}', '[', ']', '"', "'", '<', '>', ',', '?', '~', '`', '&', '.');
                    foreach ($search as $special) {
                        if (isset($_POST['variable_sku']["{$key}"]) && !empty($_POST['variable_sku']["{$key}"])) {
                            if (strpos($_POST['variable_sku']["{$key}"], $special)) {
                                $sku_replaced = str_replace($special, '-', $_POST['variable_sku']["{$key}"]);
                                $_POST['variable_sku']["{$key}"] = $sku_replaced;
                            }
                        }
                    }
                }
            }
        }
    }

    public static function linksync_restOptions() {
        update_option('product_sync_type', 'disabled_sync'); # Two-way ,Vend to WooCommerce,WooCommerce to Vend,Disabled
        update_option('ps_name_title', 'on');
        update_option('ps_description', 'on');
        update_option('ps_desc_copy', '');
        update_option('ps_price', 'on');
        update_option('excluding_tax', 'on');
        update_option('tax_class', '');
        update_option('price_book', 'off');
        update_option('price_book_identifier', '');
        update_option('ps_categories', 'off');
        update_option('ps_quantity', 'on');
        update_option('ps_outlet', 'on');
        update_option('ps_unpublish', 'on');
        update_option('ps_brand', 'on');
        update_option('ps_tags', 'off');
        update_option('cat_radio', 'ps_cat_tags');
        update_option('ps_imp_by_tag', 'off');
        update_option('import_by_tags_list', '');
        update_option('ps_images', 'off');
        update_option('ps_import_image_radio', 'Enable');
        update_option('ps_create_new', 'on');
        update_option('ps_delete', '');
        update_option('prod_update_req', '');
        update_option('prod_update_suc', NULL);
        update_option('ps_outlet_details', '');
        update_option('ps_wc_to_vend_outlet', 'on');
        update_option('wc_to_vend_outlet_detail', '');
        update_option('ps_pending', '');
        update_option('price_field', 'regular_price');
        update_option('ps_attribute', 'on');
        update_option('linksync_woocommerce_tax_option', 'on');
        //Order sync Add options
        update_option('order_sync_type', 'disabled');
        update_option('order_time_req', null);
        update_option('order_time_suc', null);
        update_option('order_status_wc_to_vend', 'wc-processing');
        update_option('wc_to_vend_outlet', '');
        update_option('wc_to_vend_register', '');
        update_option('wc_to_vend_user', '');
        update_option('wc_to_vend_tax', '');
        update_option('wc_to_vend_payment', '');
        update_option('wc_to_vend_export', '');
        // Vend To WC
        update_option('order_vend_to_wc', 'wc-completed');
        update_option('vend_to_wc_tax', '');
        update_option('vend_to_wc_payments', '');
        update_option('vend_to_wc_customer', '');
        update_option('laid_message', null);
        update_option('prod_last_page', '');
        //QuickBook Options
        update_option('product_sync_type_QBO', 'disabled_sync');
        update_option('order_sync_type_QBO', 'disabled');
        update_option('order_status_wc_to_QBO', 'wc-processing');
        update_option('wc_to_QBO_tax', '');
        update_option('wc_to_QBO_payment', '');
        update_option('wc_to_QBO_export', '');
        update_option('order_QBO_to_wc', 'wc-completed');
        update_option('QBO_to_wc_tax', '');
        update_option('QBO_to_wc_payments', '');
        update_option('QBO_to_wc_customer', '');
        update_option('ps_account_expense', '');
        update_option('ps_account_revenue', '');
        update_option('ps_account_asset', '');
        update_option('order_account', '');
        update_option('QBO_class', '');
        update_option('QBO_locations', '');
        update_option('product_import', 'no');
        update_option('order_import', 'no');
        //order id
        update_option('linksync_sent_order_id', '');
        update_option('Vend_orderIDs', '');
        //product details
        update_option('product_detail', '');
        //order details
        update_option('order_detail', '');
        //Woo Version Checker
        update_option('linksync_wooVersion', 'off');
        //user activity
        update_option('linksync_user_activity', time());
        update_option('linksync_user_activity_daily', time());
        //update notic 
        update_option('linksync_update_notic', 'off');
        //Post product 
        update_option('post_product', 0);
        // syncing Status
        update_option('linksync_sycning_status', NULL);
        //display_retail_price_tax_inclusive
        update_option('linksync_tax_inclusive', '');
    }

    public static function checkForConnection($api_key) {
        global $wpdb;
        // Start - Saving API Key and Connecting to Server 
        # On Save button clicking , it should be saved and connected as well. 
        $LAIDKey = trim($api_key);
        $testMode = get_option('linksync_test');
        if (isset($testMode) && $testMode == 'on') {
            $testMode = 'on';
        } else {
            $testMode = 'off';
        }
        update_option('linksync_test', $testMode);
        $apicall = new linksync_class($LAIDKey, $testMode);
        $result = $apicall->testConnection();
        if (isset($result) && !empty($result)) {
            if (isset($result['errorCode']) && !empty($result['userMessage'])) {
                mysql_query("UPDATE `" . $wpdb->prefix . "linksync_laidKey` SET status='Invalid' WHERE api_key='$LAIDKey'");
                update_option('linksync_status', "Inactive");
                update_option('linksync_last_test_time', current_time('mysql'));
                update_option('linksync_connected_url', "");
                update_option('linksync_connectedto', '');
                update_option('linksync_connectionwith', '');
                update_option('linksync_addedfile', '');
                update_option('linksync_frequency', $result['userMessage']);
                linksync_class::add('checkAPI Key', 'Fail', $result['userMessage'], $LAIDKey);
                $class1 = 'updated';
                $class2 = 'error';
                $response['error'] = 'Connection Not Established because of ' . $result['userMessage'];
            } else {
                if (isset($result['app']) && !empty($result['app'])) {
                    $app_name = self::appid_app($result['app']);
                    if (isset($app_name) && !empty($app_name['success'])) {
                        update_option('linksync_connectionwith', $app_name['success']);
                        $app_name_status = 'Active';
                    } else {
                        update_option('linksync_connectionwith', 'Supplied API Key not valid');
                        $checkKey = 'Supplied API Key not valid';
                        $app_name_status = 'Inactive';
                    }
                }
                if (isset($result['connected_app']) && !empty($result['connected_app'])) {
                    $connected_app = self::appid_app($result['connected_app']);
                    if (isset($connected_app) && !empty($connected_app['success'])) {
                        update_option('linksync_connectedto', $connected_app['success']);
                        $status = 'Active';
                    } else {
                        update_option('linksync_connectedto', "The supplied API Key is not valid for use with linksync for WooCommerce.");
                        $checkKey = 'Supplied API Key not valid for use with WooCommerce';
                        $status = 'Inactive';
                    }
                }
                if (isset($status) && isset($app_name_status) && $status == 'Active' && $app_name_status == 'Active') {
                    // if (isset($result['connected_app_version']) && !empty($result['connected_app_version'])) {woocommerce/woocommerce.php

                    $plugin_file = dirname(__FILE__) . '/linksync.php';
                    $plugin_data = get_plugin_data($plugin_file, $markup = true, $translate = true);
                    $linksync_version = $plugin_data['Version'];
                    update_option('linksync_version', $linksync_version);
                    $webhook = $apicall->webhookConnection(content_url() . '/plugins/linksync/update.php?c=' . get_option('webhook_url_code'), $linksync_version, 'no');
                    if (isset($webhook) && !empty($webhook)) {
                        if (isset($webhook['result']) && $webhook['result'] == 'success') {
                            linksync_class::add('WebHookConnection', 'success', 'Connected to a file ' . content_url() . '/plugins/linksync/update.php?c=' . get_option('webhook_url_code'), $LAIDKey);
                            update_option('linksync_addedfile', '<a href="' . content_url() . '/plugins/linksync/update.php?c=' . get_option('webhook_url_code') . '">' . content_url() . '/linksync/update.php?c=' . get_option('webhook_url_code') . '</a>');
                        }
                    }
                    //}
                    if (isset($result['time']) && !empty($result['time'])) {
                        $server_response = strtotime($result['time']);
                        $server_time = time();
                        $time = $server_response - $server_time;
                        linksync_class::add('Time Offset', 'success', 'Server Response:' . $result['time'] . ' Current Server Time:' . date("Y-m-d H:i:s") . ' Time Offset: ' . date("H:i:s", abs($time)), $LAIDKey);
                        update_option('linksync_time_offset', $time);
                    }

                    if (get_option('linksync_connectionwith') == 'Vend' || get_option('linksync_connectedto') == 'Vend') {
                        // Add Default setting into DB  
                        $response_outlets = $apicall->linksync_getOutlets();
                        if (@get_option('ps_outlet') == 'on') { #VEND TO WC
                            if (isset($response_outlets) && !empty($response_outlets)) {
                                if (isset($response_outlets['errorCode']) && !empty($response_outlets['userMessage'])) {
                                    update_option('ps_outlet_details', 'off');
                                    $response_ = $response_outlets['userMessage'];
                                    linksync_class::add('linksync_getOutlets', 'fail', $response_, $LAIDKey);
                                } else {
                                    foreach ($response_outlets['outlets'] as $key => $value) {
                                        $oulets["{$key}"] = $value['id'];
                                    }
                                    $ouletsdb = implode('|', $oulets);
                                    update_option('ps_outlet_details', $ouletsdb);
                                    update_option('ps_outlet', 'on');
                                }
                            } else {
                                $class2 = 'updated';
                                $class1 = 'error';
                                $response_ = 'Error in getting outlets';
                                echo "<br>";
                                echo "<span style='color:red';>" . $response_ . "</span>";
                                echo "<br>";
                            }
                        }
                        if (@get_option('ps_wc_to_vend_outlet') == 'on') {
                            if (isset($response_outlets['errorCode']) && !empty($response_outlets['userMessage'])) {
                                update_option('wc_to_vend_outlet_detail', 'off');
                                $response_ = $response_outlets['userMessage'];
                                linksync_class::add('linksync_getOutlets', 'fail', $response_, $LAIDKey);
                            } else {
                                $two_way_wc_to_vend = $response_outlets['outlets'][0]['name'] . '|' . $response_outlets['outlets'][0]['id'];
                                update_option('wc_to_vend_outlet_detail', $two_way_wc_to_vend);
                                update_option('ps_wc_to_vend_outlet', 'on');
                            }
                        }
                        /*
                         * display_retail_price_tax_inclusive(0 or 1)
                         */
                        $vend_config = $apicall->getVendConfig();
                        if (isset($vend_config) && !empty($vend_config)) {
                            if (!isset($vend_config['errorCode'])) {
                                update_option('linksync_tax_inclusive', $vend_config['display_retail_price_tax_inclusive']);
                            } else {
                                update_option('linksync_tax_inclusive', '');
                                echo "<span style='color:red;font-weight:bold;'>Error in getting VEND Config : $vend_config[userMessage]</span><br>";
                            }
                        }
                    }
                    mysql_query("UPDATE `" . $wpdb->prefix . "linksync_laidKey` SET status='connected' WHERE api_key='$LAIDKey'");
                    update_option('linksync_status', 'Active');
                    update_option('linksync_last_test_time', current_time('mysql'));
                    update_option('linksync_connected_url', get_option('linksync_connected_url'));
                    update_option('linksync_frequency', 'Valid API Key');
                    update_option('laid_message', isset($result['message']) ? $result['message'] : null);
                    linksync_class::add('isConnected', 'success', 'Connected URL is ' . get_option('linksync_connected_url'), $LAIDKey);
                    $class2 = 'updated';
                    $class1 = 'error';
                    update_option('linksync_laid', $LAIDKey);
                    $response['success'] = 'Connection is established Successfully!!';
                } else {
                    mysql_query("UPDATE `" . $wpdb->prefix . "linksync_laidKey` SET status='Invalid' WHERE api_key='$LAIDKey'");
                    update_option('linksync_status', "Inactive");
                    update_option('linksync_last_test_time', current_time('mysql'));
                    update_option('linksync_connected_url', get_option('linksync_connected_url'));
                    update_option('linksync_addedfile', '');
                    update_option('linksync_connectedto', "The supplied API Key is not valid for use with linksync for WooCommerce.");
                    update_option('linksync_connectionwith', 'Supplied API Key not valid');
                    update_option('linksync_frequency', 'Invalid API Key');
                    linksync_class::add('checkAPI Key', 'fail', 'Invalid API Key', '-');
                    $class1 = 'updated';
                    $class2 = 'error';
                    $response['error'] = "The supplied API Key is not valid for use with linksync for WooCommerce.";
                }
            }
        } else {
            mysql_query("UPDATE `" . $wpdb->prefix . "linksync_laidKey` SET status='Invalid' WHERE api_key='$LAIDKey'");
            update_option('linksync_status', "Inactive");
            update_option('linksync_last_test_time', current_time('mysql'));
            update_option('linksync_connected_url', get_option('linksync_connected_url'));
            update_option('linksync_addedfile', '');
            update_option('linksync_connectedto', "The supplied API Key is not valid for use with linksync for WooCommerce.");
            update_option('linksync_connectionwith', 'Supplied API Key not valid');
            update_option('linksync_frequency', 'Invalid API Key');
            linksync_class::add('checkAPI Key', 'fail', 'Invalid API Key', '-');
            $class1 = 'updated';
            $class2 = 'error';
            $response['error'] = "The supplied API Key is not valid for use with linksync for WooCommerce.";
        }
        return $response;
    }

    public static function linksync_removespaces_of_exists_product() {
        $posts_array = get_posts(array(
            'numberposts' => -1,
            'post_type' => 'product',
            'post_status' => 'any'
                ));
        if (isset($posts_array) && !empty($posts_array)) {
            foreach ($posts_array as $product_wc) {
                $post_detail = get_post_meta($product_wc->ID);
                if (isset($post_detail['_sku'][0]) && !empty($post_detail['_sku'][0])) {
                    $sku = $post_detail['_sku'][0];
                    if (strpos($sku, ' ')) {
                        $sku = str_replace(' ', '', $sku);
                    }
                    $search = array('/', '\\', ':', ';', '!', '@', '#', '$', '%', '^', '*', '(', ')', '+', '=', '|', '{', '}', '[', ']', '"', "'", '<', '>', ',', '?', '~', '`', '&', '.');
                    foreach ($search as $special) {
                        if (strpos($sku, $special)) {
                            $sku = str_replace($special, '-', $sku);
                        }
                    }
                    update_post_meta($product_wc->ID, '_sku', $sku);
                }
                #Variants product
                $variants_data = get_posts(array(
                    'post_type' => 'product_variation',
                    'post_parent' => $product_wc->ID
                        ));
                if (isset($variants_data) && !empty($variants_data)) {
                    foreach ($variants_data as $variant_data) {
                        $variants_detail = get_post_meta($variant_data->ID);
                        if (isset($variants_detail['_sku'][0]) && !empty($variants_detail['_sku'][0])) {
                            $sku = $variants_detail['_sku'][0]; //SKU(unique Key)
                            if (strpos($sku, ' ')) {
                                $sku = str_replace(' ', '', $sku);
                            }
                            $search = array('/', '\\', ':', ';', '!', '@', '#', '$', '%', '^', '*', '(', ')', '+', '=', '|', '{', '}', '[', ']', '"', "'", '<', '>', ',', '?', '~', '`', '&', '.');
                            foreach ($search as $special) {
                                if (strpos($sku, $special)) {
                                    $sku = str_replace($special, '-', $sku);
                                }
                                update_post_meta($variant_data->ID, '_sku', $sku);
                            }
                        }
                    }
                }
            }
        }
    }

}

register_activation_hook(__FILE__, array('linksync', 'activate')); # When plugin get activated it will triger class method "activate"
if (get_option('linksync_connectionwith') == 'Vend' || get_option('linksync_connectedto') == 'Vend') {
    add_action('save_post', array('linksync', 'linksync_removespaces'), 1);
    add_action('save_post', array('linksync', 'linksync_productPost'), 2);
} elseif (get_option('linksync_connectionwith') == 'QuickBooks Online' || get_option('linksync_connectedto') == 'QuickBooks Online') {
    add_action('save_post', array('linksync', 'linksync_productPost_QBO'));
}

if (get_option('order_sync_type') == 'wc_to_vend') {
    add_action('woocommerce_process_shop_order_meta', 'linksync_OrderFromBackEnd'); # Order From Back End (Admin Order)  
    add_action('woocommerce_thankyou', 'linksync_OrderFromFrontEnd'); # Order From Front End (User Order)
    add_action('transition_post_status', 'post_unpublished', 10, 3);
}
if (get_option('order_sync_type') == 'disabled') {
    add_action('woocommerce_process_shop_order_meta', 'order_product_post'); # Order From Back End (Admin Order)
    add_action('woocommerce_thankyou', 'order_product_post'); # Order From Front End (User Order)
}
if (get_option('linksync_update_notic') == 'on') {
    require_once ABSPATH . 'wp-admin/includes/plugin.php';
    $linksync_version = get_option('linksync_version');
    $plugin_file = dirname(__FILE__) . '/linksync.php';
    $plugin_data = get_plugin_data($plugin_file, $markup = true, $translate = true);
    $running_version = $plugin_data['Version'];
    if ($linksync_version > $running_version) {
        update_option('linksync_update_notic', 'on');
    } else {
        update_option('linksync_update_notic', 'off');
    }
    add_action('admin_notices', array('linksync', 'update_notification_message'));
}
$linksync = new linksync();
$orignal_time = time();
$save_time = get_option('linksync_user_activity') + 3600;
if (isset($save_time) && !empty($save_time) && isset($orignal_time) && !empty($orignal_time)) {
    if ($orignal_time >= $save_time) {
        $linksync->upgrade_notify_linksync();
        update_option('linksync_user_activity', $orignal_time);
    }
}
$daily = get_option('linksync_user_activity_daily') + 86400;
if (isset($daily) && !empty($daily) && isset($orignal_time) && !empty($orignal_time)) {
    if ($orignal_time >= $daily) {
        $linksync->clearLogsDetails();
        update_option('linksync_user_activity_daily', $orignal_time);
    }
}