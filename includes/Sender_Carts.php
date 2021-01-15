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
//            add_action('woocommerce_cart_updated', [&$this, 'senderCartUpdated']);
            add_action( 'wp_ajax_labas', [&$this, 'senderCartUpdated']);
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
			global $woocommerce;
			$items = $woocommerce->cart->get_cart();

			$cartData =  serialize($items);
			$session = $this->senderGetWoo()->session->get_session_cookie()[0];

			$cart = $this->sender->repository->senderGetCartBySession($session);

			if (empty($items) && $cart) {
				$this->sender->repository->senderDeleteCartBySession($session);
				echo json_encode(['method' => 'deleteCart', 'argument' => ['external_id' => $cart->id]]);
                return;
			}
			if ($cart) {
				$this->sender->repository->senderUpdateCartBySession($cartData, $session);
			} else {
				$this->sender->repository->senderCreateCart($cartData, $this->senderGetVisitor()->id,$session);
			}


        }

        public function senderAddSdkFunction()
		{
			echo "<script> sender('$this->method', '$this->params')</script>";
		}

		public function senderAddSdk($method, $param)
		{
			$this->method = $method;
			$this->params = $param;

			return [
                $method,
                $param
            ];
//			add_action('wp_head', [&$this, 'senderAddSdkFunction'], 15);
		}

		public function senderGetVisitor()
		{
			$visitor = $_COOKIE['sender_site_visitor'] ?? 'nigger';
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