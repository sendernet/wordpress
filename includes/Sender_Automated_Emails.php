<?php

if (!defined('ABSPATH')) {
    exit;
}

class Sender_Automated_Emails
{
	private $availableSettings = [
		'sender_api_key'            => false,
        'sender_resource_key'       => false,
        'sender_allow_tracking'     => false,
		'sender_customers_list'     => 0,
		'sender_registration_list'  => 0,
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

       if($this->senderIsWooEnabled()){
           if (!class_exists('Sender_User')) {
               require_once 'Model/Sender_User.php';
           }
           if (!class_exists('Sender_Cart')) {
               require_once 'Model/Sender_Cart.php';
           }

           if( !class_exists('Sender_Repository') ) {
               require_once("Sender_Repository.php" );
           }

           $this->repository = new Sender_Repository();

           register_activation_hook( $senderBaseFile, [&$this->repository, 'senderCreateTables']);
       }

		$this->senderSetupOptions()
			 ->senderAddActions()
			 ->senderAddFilters()
			 ->senderSetupWooCommerce();
	}

	private function senderAddActions()
	{
        add_action('wp_head', [&$this, 'insertSdkScript']);
        add_action( 'widgets_init', [&$this,'senderRegisterFormsWidget']);

        if(get_option('sender_allow_tracking') && $this->senderIsWooEnabled()){
            add_action('user_register', [&$this->senderApi, 'senderTrackRegisterUserCallback'], 10, 1);
            add_action('wp_login', [&$this->senderApi, 'senderTrackRegisterUserCallback']);
        }

		return $this;
	}

	private function senderAddFilters()
	{
		add_filter('plugin_action_links_' . plugin_basename($this->senderBaseFile), [&$this, 'senderAddPluginLinks']);
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
		return $this;
	}

	public function senderIsWooEnabled()
	{
		include_once(ABSPATH . 'wp-admin/includes/plugin.php');
		return is_plugin_active('woocommerce/woocommerce.php');
	}

    public function senderSetupWooCommerce()
    {
        if (!$this->senderIsWooEnabled()) {
            return $this;
        }

        if (get_option('sender_allow_tracking')) {

            if (!class_exists('Sender_Carts')) {
                require_once("Sender_Carts.php");
            }

            new Sender_Carts($this);
        }

        if (!class_exists('Sender_WooCommerce')) {
            require_once("Sender_WooCommerce.php");
        }

        new Sender_WooCommerce($this);

        return $this;
    }

	public function insertSdkScript()
	{
	    $key = $this->senderApi->senderGetResourceKey();

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
			  })(window, document, 'script', 'https://cdn.sender.net/accounts_resources/universal.js', 'sender');
			  sender('{$key}');
			</script>
			";

		if (get_option('sender_allow_tracking') && $this->senderIsWooEnabled()) {
			echo "
			<script>
			  sender('trackVisitors')
			</script>
			";
		}
	}

    public function senderRegisterFormsWidget() {

        if( !class_exists('Sender_Forms_Widget') ) {
            require_once("Sender_Forms_Widget.php" );
        }

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
