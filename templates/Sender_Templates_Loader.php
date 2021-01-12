<?php

class Sender_Templates_Loader
{
	public $sender;
	public $senderApi;

	public function __construct($sender)
	{
		$this->sender = $sender;

        if( !class_exists('Sender_Api') ) {
            require_once( dirname(dirname(__FILE__)) . "/includes/Sender_Api.php" );
        }

        $this->senderApi = new Sender_Api();

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
		    $this->senderApi->senderSetApiKey($apiKey);
			$response = $this->senderApi->senderGetAccount()['body'];
			$user = json_decode($response);
			$forms = $this->senderApi->senderGetForms()['body'];
		}
		require_once('settings.php');
	}

	function senderSubmitForm()
	{
		var_dump($_POST);

	}
}
