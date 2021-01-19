<?php

class Sender_Automated_Emails
{
	private $availableSettings = [
		'sender_api_key'            => false,
		'sender_allow_guest_track'  => false,
		'sender_allow_import'       => true,
		'sender_allow_forms'        => false,
		'sender_customers_list'     => 0,
		'sender_registration_list'  => 0,
		'sender_registration_track' => true,
		'sender_cart_period'        => 'today',
		'sender_has_woocommerce'    => false,
		'sender_high_acc'           => true,
		'sender_allow_push'         => false,
	];

	private $senderBaseFile;
	public $senderApi;
	public $repository;

	public function __construct($senderBaseFile)
	{

		$this->senderBaseFile = $senderBaseFile;

        if( !class_exists('Sender_API') ) {
            require_once("Sender_API.php" );
        }

        $this->senderApi = new Sender_API();

        if( !class_exists('Sender_Repository') ) {
            require_once("Sender_Repository.php" );
        }

        $this->repository = new Sender_Repository();

        if( !class_exists('Sender_Forms_Widget') ) {
            require_once("Sender_Forms_Widget.php" );
        }

		if (!class_exists('Sender_User')) {
			require_once 'Model/Sender_User.php';
		}

		$this->senderActivate()
			 ->senderAddActions()
			 ->senderAddFilters()
			 ->senderSetupWooCommerce();

	}

	private function senderAddActions()
	{
        add_action('wp_head', [&$this, 'insertFormsScript']);
        add_action('admin_init', [&$this, 'senderCheckWooCommerce']);
        add_action( 'widgets_init', [&$this,'senderRegisterFormsWidget']);
        add_action('user_register', [&$this->senderApi, 'senderTrackRegisteredUsers'], 10, 1);
        add_action('wp_login', [&$this->senderApi, 'senderTrackRegisteredUsers']);

		return $this;
	}

	private function senderAddFilters()
	{
		add_filter('plugin_action_links_' . plugin_basename($this->senderBaseFile), [&$this, 'senderAddPluginLinks']);
		return $this;
	}

	public function senderActivate()
	{
		$this->senderSetupOptions();
		$this->senderCheckWooCommerce();
		return $this;
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
		return is_plugin_active('woocommerce/woocommerce.php');
	}

	public function senderSetupWooCommerce()
	{
		if (!$this->senderIsWooEnabled()) {
			return $this;
		}

        if( !class_exists('Sender_Carts') ) {
            require_once("Sender_Carts.php" );
        }
        if( !class_exists('Sender_WooCommerce') ) {
            require_once("Sender_WooCommerce.php" );
        }
		new Sender_Carts($this);
         new Sender_WooCommerce($this);
	}

	public function insertFormsScript()
	{

		echo "
			<script>
			  (function (s, e, n, d, er) {
				s['Sender'] = er;
				s[er] = s[er] || function () {
				  (s[er].q = s[er].q || []).push(arguments)
				}, s[er].l = 1 * new Date();
				var a = e.createElement(n),
					m = e.getElementsByTagName(n)[0];
				a.async = 1;
				a.src = d;
				m.parentNode.insertBefore(a, m)
			  })(window, document, 'script', '/wp-content/plugins/sender/assets/sdk.js', 'sender');
			  sender('birkanosis');
			</script>
			";
	}

    public function senderRegisterFormsWidget() {
        register_widget( new Sender_Forms_Widget($this));
    }

    public function senderAddPluginLinks($links)
    {

        $additionalLinks = [
            '<a href="' . admin_url('admin.php?page=sender-settings') . '">Settings</a>',
        ];

        return array_merge($links, $additionalLinks);
    }

}
