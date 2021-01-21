<?php

if (!defined('ABSPATH')) {
    exit;
}

class Sender_Templates_Loader
{
	public $sender;

	public function __construct($sender)
	{
		$this->sender = $sender;

		add_action('admin_menu', [&$this, 'senderInitSidebar'], 2,2);
	}

	function senderInitSidebar()
	{
		add_action('admin_post_submit-sender-settings', 'senderSubmitForm');
		add_menu_page('Sender Automated Emails Marketing', 'Sender.net', 'manage_options', 'sender-settings', [&$this, 'senderAddSidebar'], plugin_dir_url( $this->sender->senderBaseFile). 'assets/images/settings.png');
	}

	function senderHandleFormPost()
	{
		$changes = [];
		foreach ($_POST as $name => $value) {

			if (strpos($name, 'hidden_checkbox') !== false && !isset($_POST[ str_replace('_hidden_checkbox','', $name)])) {
				$changes[str_replace('_hidden_checkbox','', $name)] = false;
			} else {
				$changes[$name] = $value;
			}
		}

		$this->sender->updateSettings($changes);
	}

	function senderAddSidebar()
	{
		if($_POST) {
			$this->senderHandleFormPost();
		}

		$this->sender->checkApiKey();

		$apiKey = get_option( 'sender_api_key' );
		$wooEnabled = $this->sender->senderIsWooEnabled();

		if ($apiKey) {
			$user = $this->sender->senderApi->senderGetAccount();
			$groups = $this->sender->senderApi->senderGetGroups()->data;
		}

		require_once('settings.php');
	}
}
