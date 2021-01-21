<?php

    if (!defined('WP_UNINSTALL_PLUGIN')) exit;
    if (!defined('ABSPATH')) {
        exit;
    }

    $availableSettings = [
        'sender_api_key',
        'sender_resource_key',
        'sender_allow_tracking',
        'sender_customers_list',
        'sender_registration_list',
        'sender_account_message'
    ];

    global $wpdb;

    $tables = [
      "sender_automated_emails_carts",
      "sender_automated_emails_users"
    ];

    foreach ($tables as $table){
        $name = $wpdb->prefix . $table;
        $wpdb->query( "DROP TABLE IF EXISTS {$name}");
    }

    foreach ($availableSettings as $setting)
    {
        delete_option($setting);
    }

?>