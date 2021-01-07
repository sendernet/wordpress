<?php


    class Sender_Automated_Emails
    {

        public function __construct()
        {
            var_dump('hello word my friendos');
        }


        public function sender_activate()
        {
            $this->createTables();
            $this->setupOptions();
        }

        private function createTables()
        {
            require_once(ABSPATH . 'wp-admin/includes/upgrade.php');

            global $wpdb;

            $wcap_collate = '';

            if ($wpdb->has_cap('collation')) {
                $wcap_collate = $wpdb->get_charset_collate();
            }

            $sender_carts = $wpdb->prefix . 'sender_automated_emails_carts';

            $cartsSql = "CREATE TABLE IF NOT EXISTS $sender_carts (
                             `id` int(11) NOT NULL AUTO_INCREMENT,
                             `user_id` int(11) NOT NULL,
                             `user_type` varchar(15),
                             `session` varchar(50) COLLATE utf8_unicode_ci NOT NULL,
                             `cart_data` text COLLATE utf8_unicode_ci NOT NULL,
                             `cart_recovered` int(11) NOT NULL,
                             `cart_status` int(11) NOT NULL,
                             `created` int(11) NOT NULL,
                             `updated` int(11) NOT NULL,
                             PRIMARY KEY (`id`)
                             ) $wcap_collate";

            $wpdb->query( $cartsSql );

            $sender_users = $wpdb->prefix."sender_automated_emails_users" ;

            $usersSql = "CREATE TABLE IF NOT EXISTS $sender_users (
            `id` int(15) NOT NULL AUTO_INCREMENT,
            `first_name` text,
            `last_name` text,
            `email` text,
            `created` int(11) NOT NULL,
            `updated` int(11) NOT NULL,
            PRIMARY KEY (`id`)
            ) $wcap_collate";

            $wpdb->query( $usersSql );
        }

        private function setupOptions(){

            if( !get_option( 'sender_api_key' ) ) {
                add_option( 'sender_api_key', 'api_key' );
            }

            if( !get_option( 'sender_allow_guest_track' ) ) {
                add_option( 'sender_allow_guest_track', false );
            }

            if( !get_option( 'sender_allow_import' ) ) {
                add_option( 'sender_allow_import', 1 );
            }

            if( !get_option( 'sender_allow_forms' ) ) {
                add_option( 'sender_allow_forms', false );
            }

            if( !get_option( 'sender_customers_list' ) ) {
                add_option( 'sender_customers_list', array('id' => false, 'title' => ' ') );
            }

            if( !get_option( 'sender_registration_list' ) ) {
                add_option( 'sender_registration_list', array('id' => false, 'title' => ' ') );
            }

            if( !get_option( 'sender_registration_track' ) ) {
                add_option( 'sender_registration_track', 1 );
            }

            if( !get_option( 'sender_cart_period' ) ) {
                add_option( 'sender_cart_period', 'today' );
            }

            if( !get_option( 'sender_has_woocommerce' ) ) {
                add_option( 'sender_has_woocommerce', false );
            }

            if( !get_option( 'sender_high_acc' ) ) {
                add_option( 'sender_high_acc', true );
            }

            if( !get_option( 'sender_allow_push' ) ) {
                add_option( 'sender_allow_push', false );
            }

            if( !get_option( 'sender_forms_list' ) ) {
                add_option( 'sender_forms_list', false );
            }

            if( !get_option( 'sender_plugin_active' ) ) {
                add_option( 'sender_plugin_active', false );
            }
        }


    }