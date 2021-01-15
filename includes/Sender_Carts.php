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
            $this->senderAddCartsActions();
        }

        private function senderAddCartsActions()
        {
            add_action('woocommerce_single_product_summary', [&$this, 'senderAddProductImportScript'], 10, 2);
            add_action( 'woocommerce_checkout_order_processed', [&$this,  'senderConvertCart'], 10 , 1 );
            add_action( 'woocommerce_after_checkout_billing_form',  [&$this, 'senderCatchGuestEmailAfterCheckout'], 10, 2 );
            add_action('woocommerce_cart_updated', [&$this, 'senderCartUpdated']);
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

        public function senderCartUpdated()
        {
            if(is_user_logged_in()){
                return $this->senderUpdateLoggedInUserCart();
            }

            return $this->senderUpdateVisitorCart();
        }

        public function senderUpdateLoggedInUserCart()
        {
            $woo = $this->senderGetWoo();

            $session = $woo->session->get_session_cookie()[0];

            $existingCart = $this->sender->repository->senderGetCartBySession($session);

            if($existingCart){

            }

        }

        public function senderUpdateVisitorCart()
        {

        }


        public function senderGetWoo()
        {
            global $woocommerce;

            if(function_exists('WC')){
                return WC();
            }

            return $woocommerce;
        }

    }