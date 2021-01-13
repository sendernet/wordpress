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

			$groups = $this->sender->senderApi->senderGetGroups()->data;

			$usedForms = $this->senderGetAllForms();

			$shownForms = [];

			foreach ($forms = $this->sender->senderApi->senderGetForms()->data as $form) {
				if (isset($form->settings->embed_hash) && in_array($form->settings->embed_hash, $usedForms)) {
					$shownForms[] = $form;
				}
			}

		}
		$periods = [
			'today' => 'Today',
			'tomorrow' => 'Tomorrow',
			'month' => 'Month'
		];

		require_once('settings.php');
	}


	function senderGetAllForms() {
		global $wp_registered_sidebars;

		$output = array();
		foreach ( $wp_registered_sidebars as $sidebar ) {
			if ( empty( $sidebar['name'] ) ) {
				continue;
			}
			$sidebar_name = $sidebar['name'];
			$output = array_merge($this->senderGetWidgetDataFor( $sidebar_name ), $output);
		}
		return $output;
	}

	function senderGetWidgetDataFor( $sidebar_name ) {
		global $wp_registered_sidebars, $wp_registered_widgets;

		// Holds the final data to return
		$output = array();

		// Loop over all of the registered sidebars looking for the one with the same name as $sidebar_name
		$sidebar_id = false;
		foreach ( $wp_registered_sidebars as $sidebar ) {
			if ( $sidebar['name'] == $sidebar_name ) {
				// We now have the Sidebar ID, we can stop our loop and continue.
				$sidebar_id = $sidebar['id'];
				break;
			}
		}

		if ( ! $sidebar_id ) {
			// There is no sidebar registered with the name provided.
			return $output;
		}

		// A nested array in the format $sidebar_id => array( 'widget_id-1', 'widget_id-2' ... );
		$sidebars_widgets = wp_get_sidebars_widgets();
		$widget_ids = $sidebars_widgets[ $sidebar_id ];

		if ( ! $widget_ids ) {
			// Without proper widget_ids we can't continue.
			return array();
		}

		// Loop over each widget_id so we can fetch the data out of the wp_options table.
		foreach ( $widget_ids as $id ) {
			if (strpos($id, 'sender_automated_emails_widget') === false) {
				continue;
			}
			// The name of the option in the database is the name of the widget class.
			$option_name = $wp_registered_widgets[ $id ]['callback'][0]->option_name;

			// Widget data is stored as an associative array. To get the right data we need to get the right key which is stored in $wp_registered_widgets
			$key = $wp_registered_widgets[ $id ]['params'][0]['number'];

			$widget_data = get_option( $option_name );

			// Add the widget data on to the end of the output array.
			$output[] =  $widget_data[ $key ]['form'];
		}

		return $output;
	}
}
