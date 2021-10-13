<?php

if (!defined('ABSPATH')) {
	exit;
}

class Sender_Carts
{
	private $sender;

	public function __construct($sender)
	{
		$this->sender = $sender;

		$this->senderAddCartsActions()
            ->senderAddCartsFilters();
	}

	private function senderAddCartsActions()
	{
		add_action('woocommerce_checkout_order_processed', [&$this, 'senderConvertCart'], 10, 1);
		add_action('woocommerce_cart_updated', [&$this, 'senderCartUpdated']);

		return $this;
	}

	private function senderAddCartsFilters()
	{
		add_filter('template_include', [&$this, 'senderRecoverCart'], 99, 1);

		return $this;
	}

	public function senderConvertCart($orderId)
	{
		$session = $this->senderGetWoo()->session->get_session_cookie()[0];

		$cart = (new Sender_Cart())->findBy('session', $session);

		$this->sender->senderApi->senderApiShutdownCallback("senderConvertCart", ['cartId' => $cart->id, 'orderId' => $orderId]);

		$cart->cart_status = '2';
		$cart->save();
	}

	public function senderPrepareCartData($cart)
	{

		$items = $this->senderGetCart();
		$total = $this->senderGetWoo()->cart->total;
		$user = (new Sender_User())->find($cart->user_id);

		$data = [
			"visitor_id"  => $user->visitor_id,
			"external_id" => $cart->id,
			"url"         => wc_get_cart_url() . '&hash=' . $cart->id,
			"currency"    => 'EUR',
			"order_total" => $total,
			"products"    => [],
		];

		foreach ($items as $item => $values) {

			$_product = wc_get_product($values['data']->get_id());
			$regularPrice = (int)get_post_meta($values['product_id'], '_regular_price', true);
			$salePrice = (int)get_post_meta($values['product_id'], '_sale_price', true);

			if ($regularPrice <= 0) {
				$regularPrice = 1;
			}

			$discount = round(100 - ($salePrice / $regularPrice * 100));

			$prod = [
				'sku'           => $values['data']->get_sku(),
				'name'          => (string)$_product->get_title(),
				'price'         => (string)$regularPrice,
				'price_display' => (string)$_product->get_price() . get_woocommerce_currency_symbol(),
				'discount'      => (string)$discount,
				'qty'           => $values['quantity'],
				'image'         => get_the_post_thumbnail_url($values['data']->get_id()),
			];

			$data['products'][] = $prod;
		}

		return $data;
	}

	public function trackUser()
	{
		if (!is_user_logged_in() || !isset($_COOKIE['sender_site_visitor'])) {
			return;
		}

		$wpUser = wp_get_current_user();
		$wpId = $wpUser->ID;

		$visitorId = sanitize_text_field($_COOKIE['sender_site_visitor']);
		$user = (new Sender_User())->findBy('wp_user_id', $wpId);

		if (!$user) {
			$user = (new Sender_User())->findBy('visitor_id', $visitorId);
		}

		if (!$user) {
			$user = new Sender_User();
		}

		$user->visitor_id = $visitorId;
		$user->wp_user_id = $wpId;
		$user->email = $wpUser->user_email;

		if ($user->isDirty()) {
            $this->sender->senderApi->senderApiShutdownCallback("senderTrackRegisteredUsers", $wpId);
        }

		$user->save();
	}

	public function senderCartUpdated()
	{
		$this->trackUser();
		$items = $this->senderGetCart();

		$cartData = serialize($items);

		if (!$this->senderGetWoo()->session->get_session_cookie()) {
			return;
		}

		$session = $this->senderGetWoo()->session->get_session_cookie()[0];

		$cart = (new Sender_Cart())->findBy('session', $session);

		if (empty($items) && $cart) {
			$cart->delete();
			if ($cart->cart_status == "2") {
				return;
			}
            $this->sender->senderApi->senderApiShutdownCallback("senderDeleteCart", $cart->id);

            return;
		}

		if ($cart && !empty($items)) {
			$cart->cart_data = $cartData;
			$cart->save();
			$cartData = $this->senderPrepareCartData($cart);
            $this->sender->senderApi->senderApiShutdownCallback("senderUpdateCart", $cartData);
			return;
		}

		if (!empty($items)) {
			$newCart = new Sender_Cart();
			$newCart->cart_data = $cartData;
			$newCart->user_id = $this->senderGetVisitor()->id;
			$newCart->session = $session;
			$newCart->save();

			$cartData = $this->senderPrepareCartData($newCart);

            $this->sender->senderApi->senderApiShutdownCallback("senderTrackCart", $cartData);
		}

	}

	public function senderGetVisitor()
	{
		$visitor = $_COOKIE['sender_site_visitor'];
		$user = (new Sender_User())->findBy('visitor_id', $visitor);

		if (!$user) {
			$user = new Sender_User();
			$user->visitor_id = $visitor;
			$user->save();
		}
		return $user;
	}

	public function senderGetCart()
	{
		return $this->senderGetWoo()->cart->get_cart();
	}

	public function senderGetWoo()
	{
		global $woocommerce;

		if (function_exists('WC')) {
			return WC();
		}

		return $woocommerce;
	}

	public function senderRecoverCart($template)
	{

		if (!isset($_GET['hash'])) {
			return $template;
		}

		$cartId = sanitize_text_field($_GET['hash']);

		$cart = (new Sender_Cart())->find($cartId);

		if (!$cart || $cart->cart_recovered ) {
			return $template;
		}

		$cart->cart_recovered = '1';
		$cart->save();

		$cartData = unserialize($cart->cart_data);

		if (empty($cartData)) {
			return $template;
		}

		$wooCart = new WC_Cart();

		foreach ($cartData as $product) {
            $wooCart->add_to_cart(
				(int)$product['product_id'],
				(int)$product['quantity'],
				(int)$product['variation_id'],
				$product['variation']
			);
		}

		new WC_Cart_Session($wooCart);

		return $template;
	}

}