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

        add_action('admin_menu', [&$this, 'senderInitSidebar'], 2, 2);
    }

    function senderInitSidebar()
    {
        add_action('admin_post_submit-sender-settings', 'senderSubmitForm');
        add_menu_page('Sender Automated Emails Marketing', 'Sender.net', 'manage_options', 'sender-settings', [&$this, 'senderAddSidebar'], plugin_dir_url($this->sender->senderBaseFile) . 'assets/images/settings.png');
    }

    function senderHandleFormPost()
    {
        $changes = [];
        foreach ($_POST as $name => $value) {

            if (strpos($name, 'hidden_checkbox') !== false && !isset($_POST[str_replace('_hidden_checkbox', '', $name)])) {
                $changes[str_replace('_hidden_checkbox', '', $name)] = false;
            } else {
                $changes[$name] = $value;
            }
        }

        $this->sender->updateSettings($changes);
    }

    function senderAddSidebar()
    {
        if ($_POST) {
            $this->senderHandleFormPost();
        }

        $this->sender->checkApiKey();

        $apiKey = get_option('sender_api_key');
        $wooEnabled = $this->sender->senderIsWooEnabled();

        if ($apiKey && !get_option('sender_account_disconnected')) {
            $groups = $this->sender->senderApi->senderGetGroups();
            if (isset($groups->data)) {
                $groupsDataSenderOption = [];
                foreach ($groups->data as $group) {
                    $groupsDataSenderOption[$group->id] = $group->title;
                }
                update_option('sender_groups_data', $groupsDataSenderOption);
            }

            if (!get_option('sender_store_register')) {
                $this->sender->senderHandleAddStore();
            }
        }

        $isCronJobRunning = $this->sender_is_cron_job_running();

        require_once('settings.php');
    }

    public function sender_is_cron_job_running()
    {
        $nextTimestamp = wp_next_scheduled('sender_export_shop_data_cron');

        if ($nextTimestamp) {
            // Calculate the remaining time until the next scheduled event
            $remainingTime = $nextTimestamp - time();

            // If the remaining time is greater than 0, there is a scheduled cron job
            if ($remainingTime > 0) {
                return true;
            }
        }

        // Check if there are any running cron events and sender cron is running
        $runningEvents = _get_cron_array();
        if(!empty($runningEvents)) {
            foreach ($runningEvents as $timestamp => $event) {
                if (isset($event['sender_export_shop_data_cron'])) {
                    var_dump($event);
                    return true;
                }
            }
        }

        return false;
    }
}
