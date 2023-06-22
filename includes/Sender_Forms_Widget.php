<?php

if (!defined('ABSPATH')) {
    exit;
}

class Sender_Forms_Widget extends WP_Widget
{
    public function __construct()
    {
        /* Widget settings. */
        $widget_ops = [
            'classname' => 'sae_sender_form',
            'description' => __('Add Sender.net form to your website.', 'framework')
        ];

        /* Widget control settings. */
        $control_ops = [
            'id_base' => 'sender_automated_emails_widget'
        ];

        /* Create the widget. */
        parent::__construct('sender_automated_emails_widget', __('Sender.net Form', 'framework'), $widget_ops, $control_ops);
    }

    public function update($newInstance, $oldInstance)
    {
        $instance = [];

        $instance['form'] = (!empty($newInstance['form'])) ? strip_tags($newInstance['form']) : '';

        do_action('sender_sdk_insert');
        return $instance;
    }


    public function widget($args, $instance)
    {
        if (!isset($instance['form'])) {
            return;
        }

        echo $args['before_widget'];
        $code = $instance['form'];
        echo "<div class='sender-form-field' data-sender-form-id='$code'></div>";
        echo $args['after_widget'];

        if ( defined( 'REST_REQUEST' ) && REST_REQUEST ) {
            echo '<img class="sender-form-preview">';
        }
    }

    public function form($instance)
    {
        $senderApi = new Sender_API();
        $forms = $senderApi->senderGetForms();
        if (isset($forms->data)) {
            $formsData = [];
            foreach ($forms->data as $form) {
                $formsData[] = [
                    'embed_hash' => $form->settings->embed_hash,
                    'title' => $form->title,
                    'thumbnail_url' => $form->thumbnail_url
                ];
            }
            update_option('sender_forms_data', $formsData);
        }

        require(dirname(dirname(__FILE__)) . '/templates/widget_options.php');
    }
}