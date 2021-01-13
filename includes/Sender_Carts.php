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

        public function senderCaptureEmail()
        {
            //todo capture customer on init
            if (!is_user_logged_in()) {
                add_action('wp_ajax_nopriv_sender_track_guest', [&$this, 'senderSaveCapturedCustomer'], 10, 2);
            }
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

        public function senderConvertCart($order)
        {
            global $wpdb;
            //todo convert cart everywhere
            var_dump('nupirko kazka');
        }

        public function senderCatchGuestEmailAfterCheckout()
        {
            if (! is_user_logged_in()) {
                ?>
                <script type="text/javascript">
                    jQuery( 'input#billing_email' ).on( 'blur', function() {

                        var data = {
                            first_name	: jQuery('#billing_first_name').val(),
                            last_name	: jQuery('#billing_last_name').val(),
                            email		: jQuery('#billing_email').val(),
                            action: 'sender_track_guest'
                        };

                        var adminUrl = "<?php echo get_admin_url();?>admin-ajax.php";

                        jQuery.post( adminUrl, data);
                    });
                </script>
                <?php
            }
        }

        public function senderSaveCapturedCustomer()
        {
            global $woocommerce;

            $now = current_time( 'timestamp' );

            if(function_exists('WC')){
                $woo = WC();
            } else {
                $woo = $woocommerce;
            }

            if(session_id() === '') {
                session_start();
            }

            if ( isset($_POST['first_name']) && !empty($_POST['first_name']) ){
                $_SESSION['first_name'] = sanitize_text_field($_POST['first_name']);
            }

            if ( isset($_POST['last_name']) && !empty($_POST['last_name']) ) {
                $_SESSION['last_name'] = sanitize_text_field($_POST['last_name']);
            }

            if ( isset($_POST['email']) && !empty($_POST['email']) ) {
                $_SESSION['email'] = sanitize_email($_POST['email']);
            }

            $firstName = $_SESSION['first_name'] ? sanitize_text_field($_SESSION['first_name']) : '';
            $lastName = $_SESSION['last_name'] ? sanitize_text_field($_SESSION['last_name']) : '';
            $email = $_SESSION['email'] ? sanitize_email($_SESSION['email']) : '';

            $guest = $this->senderGetCustomerByEmail($email);
            $sessionCookie = $woo->session->get_session_cookie()[0];
            $guestCart = $this->senderGetCartBySession($sessionCookie);

            if($guest){
                $userId = $guest[0]->id;
                $this->senderUpdateUserModified($userId, $now);
                if($guestCart){
                    $this->senderUpdateCartModified($userId, $guestCart[0]->id, $now);
                }
            } elseif ($guestCart)
            {
                $userId = $guestCart[0]->user_id;

                $userId == 0
                    ? $userId = $this->senderCreateGuestUser($firstName, $lastName, $email, $now)
                    : $this->senderUpdateUserEmail($userId, $email, $now);

                $this->senderUpdateCartModified($userId, $guestCart[0]->id, $now);
            } else {
                $userId = $this->senderCreateNewGuestUser($firstName, $lastName,$email,$now);
            }

            $senderCart = $this->senderGetCartByUser($userId);
            $_SESSION['user_id'] = $userId;

            $this->sender->senderApi->addToGroup($email, $firstName, $lastName, get_option('sender_customers_list'));

            $cartInfo = [ 'cart' => $woo->session->cart];

            if(count($senderCart) > 0){
                $this->senderUpdateCartData($userId, json_encode($cartInfo));
                $user = $this->senderGetUserById($userId);

                if(isset($user[0]->email)){
                    $wpUserObject = get_userdata($userId);
                    if(isset($wpUserObject->user_email)){
                        $this->sender->senderApi->addToGroup($wpUserObject->user_email,
                            $wpUserObject->first_name ? $wpUserObject->first_name : '',
                            $wpUserObject->last_name ? $wpUserObject->last_name : '',
                            get_option('sender_registration_list'));
                        $this->senderPrepareCartData($senderCart[0]->id, $user[0]->email);
                    }
                }
                $_SESSION['sender_automated_emails_cart_id'] = $senderCart[0]->id;
            } else {

                if($sessionCookie != ''){

                    $cartBySession = $this->senderGetCartBySession($sessionCookie);

                    if(!count($cartBySession)){

                        if(!empty($cartInfo['cart'])){
                            $_SESSION['sender_automated_emails_cart_id'] = $this->senderSaveCart(0, 'GUEST', json_encode($cartInfo), $sessionCookie);
                        }

                    } else {

                        if(!empty($cartInfo['cart'])){
                            $this->senderUpdateCartByIdAndSession($cartBySession[0]->id, json_encode($cartInfo), $sessionCookie, $now);
                            $guestCart = $this->senderGetCartBySession($sessionCookie);
                            $email = $this->senderGetUserById($guestCart[0]->user_id)[0]->email;
                            $this->senderPrepareCartData($cartBySession[0]->id, $email);
                            $_SESSION['sender_automated_emails_cart_id'] = $cartBySession[0]->id;
                        }

                    }
                }
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

        public function senderCreateGuestUser($firstName, $lastName, $email, $timestamp)
        {
            global $wpdb;

            $sqlQuery = "INSERT INTO `".$wpdb->prefix."sender_automated_emails_users`
                             ( first_name, last_name, email, created, updated )
                             VALUES ( %s, %s, %s, %d, %d )";

            $wpdb->query($wpdb->prepare($sqlQuery, $firstName, $lastName, $email, $timestamp, $timestamp));

            return $wpdb->insert_id;
        }

        public function senderUpdateUserEmail($userId, $email, $timestamp)
        {
            global $wpdb;
            $sqlQuery = "UPDATE `".$wpdb->prefix."sender_automated_emails_users`
                                SET email =   %s,
                                    updated = %d
                                WHERE id = %d ";

            $wpdb->query( $wpdb->prepare($sqlQuery, $email, $timestamp, $userId));
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

        public function senderGetUserById($userId)
        {
            global $wpdb;
            $sqlQuery = "SELECT * FROM `".$wpdb->prefix."sender_automated_emails_users` WHERE id = %d AND id != '0'";

            return $wpdb->get_results( $wpdb->prepare( $sqlQuery, $userId ) );
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

            if(count($data['products']) >= 1) {
                $this->sender->senderApi->senderTrackCart($data);
            } else {
                $this->sender->senderApi->senderDeleteCart($cartId);
            }
        }

        public function senderSaveCart($userId, $userType, $cartData, $session)
        {
            global $wpdb;

            $currentTime = current_time('timestamp');

            $sqlQuery = "INSERT INTO `".$wpdb->prefix."sender_automated_emails_carts`
                         ( user_id, user_type, cart_data, session, created, updated )
                         VALUES ( %d, %s, %s, %s, %d, %d )";

            $wpdb->query($wpdb->prepare($sqlQuery, $userId, $userType, $cartData, $session, $currentTime, $currentTime));

            return $wpdb->insert_id;
        }

    }