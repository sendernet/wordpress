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

		$apiKey = get_option( 'sender_api_key' ) === 'api_key' ? false : get_option( 'sender_api_key' );

		if ($apiKey) {
		    $this->sender->senderApi->senderSetApiKey($apiKey);
			$user = $this->sender->senderApi->senderGetAccount();
			$forms = $this->sender->senderApi->senderGetForms()->data;
			$groups = $this->sender->senderApi->senderGetGroups();
		}
		require_once('settings.php');
	}

	function senderSubmitForm()
	{
		var_dump($_POST);

	}
}
