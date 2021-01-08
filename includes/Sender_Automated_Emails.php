<?php


    class Sender_Automated_Emails
    {

        private $senderBaseFile;

        public function __construct($senderBaseFile)
        {
            $this->senderBaseFile = $senderBaseFile;
            $this->senderActivate()
                ->senderAddActions()
                ->senderAddFilters()
                ->senderSetupWooCommerce();
        }

        private function senderAddActions()
        {
            add_action('admin_init', [&$this,'senderCheckWooCommerce']);
            return $this;
        }

        private function senderAddFilters()
        {
            add_filter( 'plugin_action_links_' . plugin_basename($this->senderBaseFile), [&$this, 'senderAddPluginLinks'] );
            return $this;
        }

        public function senderActivate()
        {
            $this->senderCreateTables();
            $this->senderSetupOptions();
            $this->senderCheckWooCommerce();
            return $this;
        }

        private function senderCreateTables()
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

        private function senderSetupOptions()
        {

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
                add_option( 'sender_customers_list', ['id' => false, 'title' => ' '] );
            }

            if( !get_option( 'sender_registration_list' ) ) {
                add_option( 'sender_registration_list', ['id' => false, 'title' => ' '] );
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

        public function senderCheckWooCommerce()
        {
            update_option('sender_has_woocommerce', $this->senderIsWooEnabled());
        }

        private function senderIsWooEnabled()
        {
            include_once( ABSPATH . 'wp-admin/includes/plugin.php' );
            return is_plugin_active('woocommerce/woocommerce.php') && class_exists('WooCommerce');
        }

        public function senderAddPluginLinks($links) {

            $additionalLinks = [
                '<a href="' . admin_url( 'sender-settings.php' ) . '">Settings</a>',
            ];

            return array_merge( $links, $additionalLinks );
        }

        public function senderSetupWooCommerce()
        {
            if(!$this->senderIsWooEnabled()){
                return $this;
            }

            add_action( 'init',  [&$this, 'senderCaptureEmail'], 10, 2 );
            add_action( 'woocommerce_single_product_summary',  [&$this, 'senderAddProductImportScript'], 10, 2 );
        }

        public function senderCaptureEmail()
        {
            //todo capture customer
            if (!is_user_logged_in()) {
                add_action( 'wp_ajax_nopriv_save_data',  [&$this, 'senderSaveCapturedCostumer'], 10, 2 );
            }
        }

        public function senderSaveCapturedCostumer()
        {

        }

        public function senderAddProductImportScript()
        {
            if(get_option('sender_allow_import')) {

                global $product;

                $id = $product->get_id();
                $pName = $product->get_name();
                $pImage = get_the_post_thumbnail_url($id);
                $pDescription = str_replace("\"", '\\"', $product->get_description());
                $pPrice = $product->get_regular_price();
                $pCurrency = get_option('woocommerce_currency');
                $pQty = $product->get_stock_quantity();
                $pRating = $product->get_average_rating();
                $pSalePrice = $pPrice;
                $pDiscount = 0;

                if($product->is_on_sale() && !empty($product->get_sale_price())){
                    $pSalePrice = $product->get_sale_price();
                    $pDiscount = round((string) 100 - ($pSalePrice / $pPrice * 100));
                }

                echo '<script type="application/sender+json">
                        {
                          "name": "' . $pName . '",
                          "image": "' . $pImage . '",
                          "description": "' . $pDescription . '",
                          "price": "' . (float) $pPrice .'",
                          "discount": "-' .$pDiscount . '%",
                          "special_price": "' . (float) $pSalePrice.'",
                          "currency": "' . $pCurrency . '",
                          "quantity": "' . $pQty . '",
                          "rating": "' . $pRating . '"
                        }
                    </script>';
            }
        }


    }