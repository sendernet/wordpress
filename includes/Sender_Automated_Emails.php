<?php

class Sender_Automated_Emails
{
	private $availableSettings = [
		'sender_api_key'            => false,
		'sender_allow_guest_track'  => false,
		'sender_allow_import'       => true,
		'sender_allow_forms'        => false,
		'sender_customers_list'     => ['id' => false, 'title' => ' '],
		'sender_registration_list'  => ['id' => false, 'title' => ' '],
		'sender_registration_track' => 1,
		'sender_cart_period'        => 'today',
		'sender_has_woocommerce'    => false,
		'sender_high_acc'           => true,
		'sender_allow_push'         => false,
		'sender_forms_list'         => false,
		'sender_plugin_active'      => false,
	];

	private $senderBaseFile;
	public $senderApi;

	public function __construct($senderBaseFile)
	{
		$this->senderBaseFile = $senderBaseFile;

        if( !class_exists('Sender_API') ) {
            require_once("Sender_API.php" );
        }

        $this->senderApi = new Sender_API();

        if( !class_exists('Sender_Forms_Widget') ) {
            require_once("Sender_Forms_Widget.php" );
        }

        new Sender_Forms_Widget($this);

		$this->senderActivate()
			 ->senderAddActions()
			 ->senderAddFilters()
			 ->senderSetupWooCommerce();
	}

	private function senderAddActions()
	{
		add_action('admin_init', [&$this, 'senderCheckWooCommerce']);
		return $this;
	}

	private function senderAddFilters()
	{
		add_filter('plugin_action_links_' . plugin_basename($this->senderBaseFile), [&$this, 'senderAddPluginLinks']);
		return $this;
	}

	public function senderActivate()
	{
		$this->senderCreateTables();
		$this->senderSetupOptions();
		$this->senderCheckWooCommerce();
		$this->senderEnableForms();
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

		$wpdb->query($cartsSql);

		$sender_users = $wpdb->prefix . "sender_automated_emails_users";

		$usersSql = "CREATE TABLE IF NOT EXISTS $sender_users (
            `id` int(15) NOT NULL AUTO_INCREMENT,
            `first_name` text,
            `last_name` text,
            `email` text,
            `created` int(11) NOT NULL,
            `updated` int(11) NOT NULL,
            PRIMARY KEY (`id`)
            ) $wcap_collate";

		$wpdb->query($usersSql);
	}

	public function updateSettings($updates)
	{
		foreach ($this->availableSettings as $name => $defaultValue) {
			if (isset($updates[$name])) {
				update_option($name, $updates[$name]);
			}
		}
	}

	private function senderSetupOptions()
	{
		foreach ($this->availableSettings as $name => $defaultValue) {
			if (!get_option($name)) {
				add_option($name, $defaultValue);
			}
		}
	}

	public function senderCheckWooCommerce()
	{
		update_option('sender_has_woocommerce', $this->senderIsWooEnabled());
	}

	private function senderIsWooEnabled()
	{
		include_once(ABSPATH . 'wp-admin/includes/plugin.php');
		return is_plugin_active('woocommerce/woocommerce.php') && class_exists('WooCommerce');
	}

	public function senderAddPluginLinks($links)
	{

		$additionalLinks = [
			'<a href="' . admin_url('sender-settings.php') . '">Settings</a>',
		];

		return array_merge($links, $additionalLinks);
	}

	public function senderSetupWooCommerce()
	{
		if (!$this->senderIsWooEnabled()) {
			return $this;
		}

		add_action('init', [&$this, 'senderCaptureEmail'], 10, 2);
		add_action('woocommerce_single_product_summary', [&$this, 'senderAddProductImportScript'], 10, 2);
	}

	public function senderCaptureEmail()
	{
		//todo capture customer
		if (!is_user_logged_in()) {
			add_action('wp_ajax_nopriv_save_data', [&$this, 'senderSaveCapturedCostumer'], 10, 2);
		}
	}

	public function senderSaveCapturedCostumer()
	{

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

	private function senderEnableForms()
	{
		add_action('wp_head', [&$this, 'insertFormsScript']);
	}

	public function insertFormsScript()
	{
		//Need enabled popups setting and account key for fetching json
//		echo "
//			<script>
//			  (function (s, e, n, d, er) {
//				s['Sender'] = er;
//				s[er] = s[er] || function () {
//				  (s[er].q = s[er].q || []).push(arguments)
//				}, s[er].l = 1 * new Date();
//				var a = e.createElement(n),
//					m = e.getElementsByTagName(n)[0];
//				a.async = 1;
//				a.src = d;
//				m.parentNode.insertBefore(a, m)
//			  })(window, document, 'script', 'https://cdn.sender.net/accounts_resources/universal.js', 'sender');
//			  sender('birkanosis')
//			</script>
//			";
	}

}
