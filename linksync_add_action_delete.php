<?php

function linksync_DeleteProduct($post_id) {
    $pro_object = new WC_Product($post_id);
    if ($pro_object->post->post_type == 'product') {
        $testMode = get_option('linksync_test');
        $LAIDKey = get_option('linksync_laid');
        $apicall = new linksync_class($LAIDKey, $testMode);
        if (!defined('ABSPATH'))
            define('ABSPATH', dirname(__FILE__) . '/');
        include_once (ABSPATH . 'wp-includes/post.php');
        $product_sku = get_post_meta($post_id, '_sku', true);
        if (!empty($product_sku)) {
            $apicall->linksync_deleteProduct($product_sku);
        }
    }
}

$pro_sync_type = get_option('product_sync_type');
if ($pro_sync_type == 'two_way' || $pro_sync_type == 'wc_to_vend') {
    if (get_option('ps_delete') == 'on') {
        add_action('before_delete_post', 'linksync_DeleteProduct');
    }
}
?>