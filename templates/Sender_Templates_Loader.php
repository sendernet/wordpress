<?php

class Sender_Templates_Loader
{
	public $sender;

	public function __construct($sender)
	{
		$this->sender = $sender;
		add_action('admin_menu', [&$this, 'senderInitSidebar']);
	}

	function senderInitSidebar()
	{

		add_action('admin_post_submit-sender-settings', 'senderSubmitForm');
		add_menu_page('Sender Automated Emails Marketing', 'Sender.net', 'manage_options', 'sender-settings', [&$this, 'senderAddSidebar']);

	}

	function senderAddSidebar()
	{

		if($_POST) {
			var_dump($_POST);
			$this->sender->updateSettings($_POST);
		}

		$apiKey = get_option( 'sender_api_key' ) === 'api_key' ? false : get_option( 'sender_api_key' );

		if ($apiKey) {
			$response = $this->sender->senderGetAccount()['body'];
			$user = json_decode($response);
		}
		require_once('settings.php');
	}

	function senderSubmitForm()
	{
		var_dump($_POST);

	}
}
