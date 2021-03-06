<?php

require(dirname(__FILE__) . '../../../../wp-load.php'); # WordPress Configuration File   
@mysql_connect(DB_HOST, DB_USER, DB_PASSWORD);
@mysql_select_db(DB_NAME);
include_once(dirname(__FILE__) . '/classes/Class.linksync.php'); # Class file having API Call functions 
global $wp;
// Initializing 
$wp->init();
$wp->parse_request();
$wp->query_posts();
$wp->register_globals();
if ($_POST['communication_key'] != get_option('webhook_url_code')) {
    die('Access is Denied');
}    #Product Image
global $wpdb;
if (isset($_POST['product_id']) && !empty($_POST['product_id'])) {
    $message = array();
    $product_details = get_option('product_image_ids');
    $product_ID_check = $product_details[$_POST['product_id'] - 1];
    $product_detail = explode('|', $product_ID_check);
    if (isset($product_detail[1]) && !empty($product_detail[1])) {
        if ($product_detail[1] == 'update_id') {
            #Product Image   
            $product_ID = $product_detail[0];
            if (get_option('ps_images') == 'on') {
                $image_response = 'on';
                $product_thumbnail = get_post_meta($product_ID, 'Vend_thumbnail_image', TRUE);
                if (isset($product_thumbnail) && !empty($product_thumbnail)) {
                    if (get_option('ps_import_image_radio') == 'Ongoing') {
                        $thumbnail_id = addImage_thumbnail($product_thumbnail, $product_ID);
                        $image_thumbnail = mysql_num_rows(mysql_query("SELECT * FROM  `" . $wpdb->prefix . "postmeta` WHERE  meta_key='_thumbnail_id' AND `post_id` ='" . $product_ID . "'"));
                        if ($image_thumbnail != 0) {
                            update_post_meta($product_ID, '_thumbnail_id', $thumbnail_id);
                        } else {
                            add_post_meta($product_ID, '_thumbnail_id', $thumbnail_id);
                        }
                        delete_post_meta($product_ID, 'Vend_thumbnail_image', $product_thumbnail);
                        unset($thumbnail_id);
                        $thumbnail_response = 'success';
                    }
                    /*
                     * Enable (Once)-> This option will sync images from Vend to WooCommerce products on creation of a new product,
                     *  or if an existing product in WooCommerce does not have an image.
                     */ elseif (get_option('ps_import_image_radio') == 'Enable') {
                        $attach_id = addImage_thumbnail($product_thumbnail, $product_ID);
                        add_post_meta($product_ID, '_thumbnail_id', $attach_id);
                        delete_post_meta($product_ID, 'Vend_thumbnail_image', $product_thumbnail);
                        unset($attach_id);
                        $thumbnail_response = 'success';
                    }
                } else {
                    $thumbnail_response = 'success';
                }
                $woo_filename_gallery = array();
                $image_gallery = get_post_meta($product_ID, '_product_image_gallery', TRUE);
                if (isset($image_gallery) && !empty($image_gallery)) {
                    $images_postId = explode(',', $image_gallery);
                    if (isset($images_postId) && !empty($images_postId)) {
                        foreach ($images_postId as $value) {
                            $wp_attached_file = get_post_meta($value, '_wp_attached_file', true); // returns an array  
                            if (isset($wp_attached_file) && !empty($wp_attached_file)) {
                                $woo_filename_gallery[$value] = basename($wp_attached_file);
                            }
                        }
                    }
                }
 
                $vend_gallery_image = get_post_meta($product_ID, 'Vend_product_image_gallery', TRUE);
                if (isset($vend_gallery_image) && !empty($vend_gallery_image)) {
                    if (strpos($vend_gallery_image, ','))
                        $gallery_data = explode(',', $vend_gallery_image);
                    else
                        $gallery_data[] = $vend_gallery_image;
                    foreach ($gallery_data as $image) {
                        if (get_option('ps_import_image_radio') == 'Ongoing') {
                            if (isset($woo_filename_gallery) && !empty($woo_filename_gallery)) {
                                if (!in_array(basename($image), $woo_filename_gallery)) {
                                    $attach_ids = linksync_insert_image($image, $product_ID);
                                    mysql_query("UPDATE `" . $wpdb->prefix . "postmeta` SET meta_value=CONCAT(meta_value,',$attach_ids') WHERE post_id='" . $product_ID . "' AND meta_key='_product_image_gallery'");
                                }
                            } else {
                                $attach_ids = linksync_insert_image($image, $product_ID);
                                $imageDb = mysql_num_rows(mysql_query("SELECT * FROM  `" . $wpdb->prefix . "postmeta` WHERE  meta_key='_product_image_gallery' AND `post_id` ='" . $product_ID . "'"));
                                if ($imageDb != 0)
                                    mysql_query("UPDATE `" . $wpdb->prefix . "postmeta` SET meta_value=CONCAT(meta_value,',$attach_ids') WHERE post_id='" . $product_ID . "' AND meta_key='_product_image_gallery'");
                                else
                                    add_post_meta($product_ID, '_product_image_gallery', $attach_ids);
                            }
                        } elseif (get_option('ps_import_image_radio') == 'Enable') {
                            if ($_POST['check_status'] == 'send') {
                                if (isset($woo_filename_gallery) && !empty($woo_filename_gallery)) {
                                    
                                } else {
                                    $attach_ids = linksync_insert_image($image, $product_ID);
                                    $imageDb = mysql_num_rows(mysql_query("SELECT * FROM  `" . $wpdb->prefix . "postmeta` WHERE  meta_key='_product_image_gallery' AND `post_id` ='" . $product_ID . "'"));
                                    if ($imageDb != 0)
                                        mysql_query("UPDATE `" . $wpdb->prefix . "postmeta` SET meta_value=CONCAT(meta_value,',$attach_ids') WHERE post_id='" . $product_ID . "' AND meta_key='_product_image_gallery'");
                                    else
                                        add_post_meta($product_ID, '_product_image_gallery', $attach_ids);
                                }
                            } else {
                                if (isset($woo_filename_gallery) && !empty($woo_filename_gallery)) {
                                    if (!in_array(basename($image), $woo_filename_gallery)) {
                                        $attach_ids = linksync_insert_image($image, $product_ID);
                                        mysql_query("UPDATE `" . $wpdb->prefix . "postmeta` SET meta_value=CONCAT(meta_value,',$attach_ids') WHERE post_id='" . $product_ID . "' AND meta_key='_product_image_gallery'");
                                    }
                                } else {
                                    $attach_ids = linksync_insert_image($image, $product_ID);
                                    $imageDb = mysql_num_rows(mysql_query("SELECT * FROM  `" . $wpdb->prefix . "postmeta` WHERE  meta_key='_product_image_gallery' AND `post_id` ='" . $product_ID . "'"));
                                    if ($imageDb != 0)
                                        mysql_query("UPDATE `" . $wpdb->prefix . "postmeta` SET meta_value=CONCAT(meta_value,',$attach_ids') WHERE post_id='" . $product_ID . "' AND meta_key='_product_image_gallery'");
                                    else
                                        add_post_meta($product_ID, '_product_image_gallery', $attach_ids);
                                }
                            }
                        }
                    }
                    unset($gallery_data);
                    delete_post_meta($product_ID, 'Vend_product_image_gallery');
                    $gallery_response = 'success';
                } else {
                    $gallery_response = 'success';
                }
            } else {
                $image_response = 'off';
            }
        } else if ($product_detail[1] == 'new_id') {
            if (get_option('ps_images') == 'on') {
                $product_ID = $product_detail[0];
                $woo_filename_gallery = array();
                $image_gallery = get_post_meta($product_ID, '_product_image_gallery', TRUE);
                if (isset($image_gallery) && !empty($image_gallery)) {
                    $images_postId = explode(',', $image_gallery);
                    if (isset($images_postId) && !empty($images_postId)) {
                        foreach ($images_postId as $value) {
                            $wp_attached_file = get_post_meta($value, '_wp_attached_file', true); // returns an array  
                            if (isset($wp_attached_file) && !empty($wp_attached_file)) {
                                $woo_filename_gallery[$value] = basename($wp_attached_file);
                            }
                        }
                    }
                } 
                if (get_option('ps_import_image_radio') == 'Enable' || get_option('ps_import_image_radio') == 'Ongoing') {
                    $product_thumbnail_image = get_post_meta($product_ID, 'Vend_thumbnail_image', TRUE);
                    if (isset($product_thumbnail_image) && !empty($product_thumbnail_image)) {
                        $attach_thumb_id = addImage_thumbnail($product_thumbnail_image, $product_ID);
                        add_post_meta($product_ID, '_thumbnail_id', $attach_thumb_id);
                        delete_post_meta($product_ID, 'Vend_thumbnail_image', $product_thumbnail_image);
                        unset($attach_thumb_id);
                        $thumbnail_response = 'success';
                    } else {
                        $thumbnail_response = 'success';
                    }
                    $vend_gallery_image = get_post_meta($product_ID, 'Vend_product_image_gallery', TRUE);
                    if (isset($vend_gallery_image) && !empty($vend_gallery_image)) {
                        if (strpos($vend_gallery_image, ','))
                            $gallery_data = explode(',', $vend_gallery_image);
                        else
                            $gallery_data[] = $vend_gallery_image;
                        foreach ($gallery_data as $image) {
                            if (isset($woo_filename_gallery) && !empty($woo_filename_gallery)) {
                                if (!in_array(basename($image), $woo_filename_gallery)) {
                                    $attach_ids = linksync_insert_image($image, $product_ID);
                                    mysql_query("UPDATE `" . $wpdb->prefix . "postmeta` SET meta_value=CONCAT(meta_value,',$attach_ids') WHERE post_id='" . $product_ID . "' AND meta_key='_product_image_gallery'");
                                }
                            } else {
                                $attach_ids = linksync_insert_image($image, $product_ID);
                                $imageDb = mysql_num_rows(mysql_query("SELECT * FROM  `" . $wpdb->prefix . "postmeta` WHERE  meta_key='_product_image_gallery' AND `post_id` ='" . $product_ID . "'"));
                                if ($imageDb != 0)
                                    mysql_query("UPDATE `" . $wpdb->prefix . "postmeta` SET meta_value=CONCAT(meta_value,',$attach_ids') WHERE post_id='" . $product_ID . "' AND meta_key='_product_image_gallery'");
                                else
                                    add_post_meta($product_ID, '_product_image_gallery', $attach_ids);
                            }
                        }
                        unset($gallery_data);
                        delete_post_meta($product_ID, 'Vend_product_image_gallery');
                        $gallery_response = 'success';
                    } else {
                        $gallery_response = 'success';
                    }

                    unset($vend_gallery_image);

                    $image_response = 'on';
                }
            } else {
                $image_response = 'off';
            }
        }
        $message['response']['image'] = isset($image_response) ? $image_response : 'off';
        $message['response']['thumbnail'] = isset($thumbnail_response) ? $thumbnail_response : 'error';
        $message['response']['gallery'] = isset($gallery_response) ? $gallery_response : 'error';
        $message['response']['update'] = isset($update_again) ? $update_again : 'error';
        echo json_encode($message);
        exit;
    }
} elseif (isset($_POST['get_total']) && $_POST['get_total'] == '1') {
    $product_details = get_option('product_image_ids');
    if (isset($product_details) && !empty($product_details)) {
        $total_post = count($product_details);
        $total_post_id = explode('|', $total_post);
        $first_post = current($product_details);
        $first_post_id = explode('|', $first_post);
        $last_post = end($product_details);
        $last_post_id = explode('|', $last_post);
        $product_wc['total_post_id'] = $total_post_id[0];
        $product_wc['first_post_id'] = $first_post_id[0];
        $product_wc['last_post_id'] = $last_post_id[0];
        update_option('image_process', 'complete');
        echo json_encode($product_wc);
        exit;
    }
} else {
    $product_details = get_option('product_image_ids');
    if (isset($product_details) && !empty($product_details)) {
        $total_post = count($product_details);
        $total_post_id = explode('|', $total_post);
        $first_post = current($product_details);
        $first_post_id = explode('|', $first_post);
        $last_post = end($product_details);
        $last_post_id = explode('|', $last_post);
        $product_wc['total_post_id'] = $total_post_id[0];
        $product_wc['first_post_id'] = $first_post_id[0];
        $product_wc['last_post_id'] = $last_post_id[0];
        $product_wc['total_product'] = get_option('product_detail');
        echo json_encode($product_wc);
        exit;
    }
}
exit;
?>