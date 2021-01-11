<?php

class Sender_Templates_Loader
{

	public function __construct()
	{
		add_action('admin_menu', 'test_plugin_setup_menu');

		function test_plugin_setup_menu()
		{

			add_menu_page('Sender Automated Emails Marketing', 'Sender.net', 'manage_options', 'sender-settings', 'test_init');
		}

		function test_init()
		{
			$apiKey = false;
			require_once('settings.php');
		}
	}

}
