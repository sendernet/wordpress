<?php

    if ( ! defined( 'ABSPATH' ) ) {
        exit;
    }

    class Sender_Carts
    {
        private $sender;
        private $method;
        private $params;


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


        public function senderPrepareCartData($cart)
        {
            global $woocommerce;

            $items = $woocommerce->cart->get_cart();
            $total = $woocommerce->cart->total;
			$user =  $this->sender->repository->senderGetUserById($cart->user_id);

            $data = array(
                "visitor_id" => $user->visitor_id,
                "external_id" => $cart->id,
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
                    'name' =>(string)  $_product->get_title(),
                    'price' => (string) $regularPrice,
                    'price_display' => (string) $_product->get_price().get_woocommerce_currency_symbol(),
                    'discount' => (string) $discount,
                    'qty' => $values['quantity'],
                    'image' =>  get_the_post_thumbnail_url($values['data']->get_id())
                ];

                $data['products'][] = $prod;
            }

            $data['grand_total'] = $woocommerce->cart->total;

            return $data;
        }

        public function senderCartUpdated()
        {
			$items = $this->senderGetCart();

			$cartData =  serialize($items);
			if ( !$this->senderGetWoo()->session->get_session_cookie() ) {
				return;
			}

			$session = $this->senderGetWoo()->session->get_session_cookie()[0];

			$cart = $this->sender->repository->senderGetCartBySession($session);

			if (empty($items) && $cart) {
				$this->sender->repository->senderDeleteCartBySession($session);
				var_dump($this->sender->senderApi->senderDeleteCart($cart->id));
				register_shutdown_function([&$this->sender->senderApi, "senderDeleteCart"], $cart->id);
				return;
			}

			if ($cart && !empty($items)) {
				$this->sender->repository->senderUpdateCartBySession($cartData, $session);
				$cartData = $this->senderPrepareCartData($cart);
				register_shutdown_function([&$this->sender->senderApi, "senderUpdateCart"], ...[$cartData, $session]);
				return;

			} else if(!empty($items)){
				$this->sender->repository->senderCreateCart($cartData, $this->senderGetVisitor()->id,$session);
				$cart = $this->sender->repository->senderGetCartBySession($session);
				$cartData = $this->senderPrepareCartData($cart);
				register_shutdown_function([&$this->sender->senderApi, "senderTrackCart"], $cartData);
			}


        }

		public function senderGetVisitor()
		{
			$visitor = $_COOKIE['sender_site_visitor'];
			return $this->sender->repository->senderGetUserByVisitorId($visitor);
		}

        public function senderGetCart()
		{
			return $this->senderGetWoo()->cart->get_cart();
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