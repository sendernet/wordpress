<?php

    if ( ! defined( 'ABSPATH' ) ) {
        exit;
    }

    class Sender_Carts
    {
        private $sender;

        public function __construct($sender)
        {
            $this->sender = $sender;
        }


        public function senderAddProductImportScript()
        {
            if (get_option('sender_allow_import')) {

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

                if ($product->is_on_sale() && !empty($product->get_sale_price())) {
                    $pSalePrice = $product->get_sale_price();
                    $pDiscount = round((string)100 - ($pSalePrice / $pPrice * 100));
                }

                echo '<script type="application/sender+json">
                        {
                          "name": "' . $pName . '",
                          "image": "' . $pImage . '",
                          "description": "' . $pDescription . '",
                          "price": "' . (float)$pPrice . '",
                          "discount": "-' . $pDiscount . '%",
                          "special_price": "' . (float)$pSalePrice . '",
                          "currency": "' . $pCurrency . '",
                          "quantity": "' . $pQty . '",
                          "rating": "' . $pRating . '"
                        }
                    </script>';
            }
        }


        public function senderGetCustomerByEmail($email)
        {
            global $wpdb;

            $sqlQuery = "SELECT * FROM `".$wpdb->prefix."sender_automated_emails_users` WHERE email = %s AND id != '0'";

            $result = $wpdb->get_results( $wpdb->prepare( $sqlQuery, $email ) );

            return $result;
        }

        public function senderGetCartBySession($cookie)
        {
            if(!$cookie) {
                return [];
            }

            global $wpdb;

            $query   = "SELECT * FROM `".$wpdb->prefix."sender_automated_emails_carts`
                        WHERE session = %s
                        AND user_type = 'GUEST'
                        AND cart_recovered = %d
                        AND cart_status = '0' ";

            return $wpdb->get_results($wpdb->prepare( $query, $cookie, 0));
        }

        public function senderUpdateUserModified($userId, $timestamp)
        {
            global $wpdb;
            $sqlQuery = "UPDATE `".$wpdb->prefix."sender_automated_emails_users`
                            SET updated = %d
                            WHERE id = %d ";

            $wpdb->query( $wpdb->prepare($sqlQuery, $timestamp, $userId));
        }

        public function senderUpdateCartModified($userId, $cartId, $timestamp)
        {
            global $wpdb;

            $sqlQuery = "UPDATE `".$wpdb->prefix."sender_automated_emails_carts`
                            SET user_id = %d,
                                updated = %d
                            WHERE id = %d ";

            $wpdb->query( $wpdb->prepare($sqlQuery, $userId, $timestamp, $cartId));
        }


        public function senderCreateNewGuestUser($firstName, $lastName, $email, $now)
        {
            global $wpdb;
            $sqlQuery = "INSERT INTO `".$wpdb->prefix."sender_automated_emails_users`
                             ( first_name, last_name, email, created, updated )
                             VALUES ( %s, %s, %s, %d, %d )";

            $wpdb->query($wpdb->prepare($sqlQuery, $firstName, $lastName, $email, $now, $now));

            return $wpdb->insert_id;
        }

        public function senderGetCartByUser($userId, $userType = 'GUEST') {

            global $wpdb;

            $query   = "SELECT * FROM `".$wpdb->prefix."sender_automated_emails_carts`
                        WHERE user_id = %d
                        AND user_type = %s
                        AND cart_recovered = %d
                        AND cart_status = '0'";

            return $wpdb->get_results($wpdb->prepare( $query, $userId, $userType, 0) );
        }

        public function senderUpdateCartData($userId, $cartData)
        {
            global $wpdb;

            $sqlQuery = "UPDATE `".$wpdb->prefix."sender_automated_emails_carts`
                             SET cart_data = %s,
                                 updated = %d
                             WHERE user_id = %d 
                             AND cart_recovered = %d
                             AND cart_status = '0' ";

            $wpdb->query( $wpdb->prepare($sqlQuery, $cartData, current_time('timestamp'), $userId, 0));
        }

        public function senderUpdateCartByIdAndSession($cartId, $cartInfo, $sessionCookie, $timestamp)
        {
            global $wpdb;

            $sqlQuery = "UPDATE `".$wpdb->prefix."sender_automated_emails_carts`
                                        SET cart_data = %s,
                                            updated = %d
                                        WHERE id = %d AND
                                              session = %s AND
                                              user_type = 'GUEST' AND
                                              cart_recovered = %d";

            $wpdb->query( $wpdb->prepare($sqlQuery, $cartInfo, $timestamp, $cartId, $sessionCookie, 0));
        }



        public function senderPrepareCartData($cartId, $email = '')
        {
            global $woocommerce;

            $items = $woocommerce->cart->get_cart();
            $total = $woocommerce->cart->total;

            $data = array(
                "email" => $email,
                "external_id" => $cartId,
                "url" => 'null',
                "currency" => 'EUR',
                "grand_total" =>  $total,
                "products" => array()
            );

            foreach($items as $item => $values) {

                $_product     = wc_get_product( $values['data']->get_id() );
                $regularPrice = (int) get_post_meta($values['product_id'] , '_regular_price', true);
                $salePrice    = (int) get_post_meta($values['product_id'] , '_sale_price', true);

                if ($regularPrice <= 0) $regularPrice = 1;

                $discount = round(100 - ($salePrice / $regularPrice * 100));

                $prod = [
                    'sku' => $values['data']->get_sku(),
                    'name' => $_product->get_title(),
                    'price' => $regularPrice,
                    'price_display' => (string) $_product->get_price().get_woocommerce_currency_symbol(),
                    'discount' => (string) $discount,
                    'qty' =>  $values['quantity'],
                    'image' => get_the_post_thumbnail_url($values['data']->get_id())
                ];

                $data['products'][] = $prod;
            }

            $data['grand_total'] = $woocommerce->cart->total;

            return $data;
        }

    }