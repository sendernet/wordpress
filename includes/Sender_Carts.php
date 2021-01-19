<?php

if (!defined('ABSPATH')) {
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
		$this->senderAddCartsFilters();
	}

	private function senderAddCartsActions()
	{
		add_action('woocommerce_single_product_summary', [&$this, 'senderAddProductImportScript'], 10, 2);
		add_action('woocommerce_checkout_order_processed', [&$this, 'senderConvertCart'], 10, 1);
		add_action('woocommerce_cart_updated', [&$this, 'senderCartUpdated']);
	}

	private function senderAddCartsFilters()
	{
		add_filter('template_include', [&$this, 'senderRecoverCart'], 99, 1);
	}

	public function senderConvertCart($orderId)
	{
		$session = $this->senderGetWoo()->session->get_session_cookie()[0];

		$cart = $this->sender->repository->senderGetCartBySession($session);

		register_shutdown_function([&$this->sender->senderApi, "senderConvertCart"], ['cartId' => $cart->id, 'orderId' => $orderId]);

		$this->sender->repository->senderConvertCartBySession($session);
	}

	public function senderPrepareCartData($cart)
	{
		global $woocommerce;

		$items = $woocommerce->cart->get_cart();
		$total = $woocommerce->cart->total;
		$user = $this->sender->repository->senderGetUserById($cart->user_id);

		$data = [
			"visitor_id"  => $user->visitor_id,
			"external_id" => $cart->id,
			"url"         => wc_get_cart_url() . '&hash=' . $cart->id,
			"currency"    => 'EUR',
			"grand_total" => $total,
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

		$data['grand_total'] = $woocommerce->cart->total;

		return $data;
	}

	public function trackUser()
	{
		if (!is_user_logged_in()) {
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
			register_shutdown_function([&$this->sender->senderApi, "senderTrackRegisteredUsers"], $wpId);
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

		$cart = $this->sender->repository->senderGetCartBySession($session);

		if (empty($items) && $cart) {
			$this->sender->repository->senderDeleteCartBySession($session);
			if ($cart->cart_status == "2") {
				return;
			}
			register_shutdown_function([&$this->sender->senderApi, "senderDeleteCart"], $cart->id);
			return;
		}

		if ($cart && !empty($items)) {
			$this->sender->repository->senderUpdateCartBySession($cartData, $session);
			$cartData = $this->senderPrepareCartData($cart);
			register_shutdown_function([&$this->sender->senderApi, "senderUpdateCart"], ...[$cartData, $session]);
			return;

		} else {
			if (!empty($items)) {
				$this->sender->repository->senderCreateCart($cartData, $this->senderGetVisitor()->id, $session);
				$cart = $this->sender->repository->senderGetCartBySession($session);
				$cartData = $this->senderPrepareCartData($cart);
				register_shutdown_function([&$this->sender->senderApi, "senderTrackCart"], $cartData);
			}
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

		$cart = $this->sender->repository->senderGetCartById($cartId);

		if (!$cart) {
			return $template;
		}

		$cartData = unserialize($cart->cart_data);

		if (empty($cartData)) {
			return $template;
		}

		$Cart = new WC_Cart();

		foreach ($cartData as $product) {
			$Cart->add_to_cart(
				(int)$product['product_id'],
				(int)$product['quantity'],
				(int)$product['variation_id'],
				$product['variation']
			);
		}
		new WC_Cart_Session($Cart);

		return $template;
	}

}