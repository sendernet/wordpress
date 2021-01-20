<?php

    if (!defined('WP_UNINSTALL_PLUGIN')) exit;
    if (!defined('ABSPATH')) {
        exit;
    }

    $availableSettings = [
        'sender_api_key',
        'sender_allow_guest_track',
        'sender_allow_import',
        'sender_allow_forms',
        'sender_customers_list',
        'sender_registration_list',
        'sender_registration_track',
        'sender_cart_period',
        'sender_has_woocommerce',
        'sender_high_acc',
        'sender_allow_push',
    ];

    global $wpdb;

    $tables = [
      "sender_automated_emails_carts",
      "sender_automated_emails_users"
    ];

    foreach ($tables as $table){
        $name = $wpdb->prefix . $table;
        $wpdb->query( "DROP TABLE {$name}");
    }

    foreach ($availableSettings as $setting)
    {
        delete_option($setting);
    }

?>