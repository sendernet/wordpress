<?php

    if ( ! defined( 'ABSPATH' ) ) {
        exit;
    }

    class Sender_Forms_Widget extends  WP_Widget
    {

        public function __construct()
        {
            /* Widget settings. */
            $widget_ops = ['classname' => 'sae_sender_form', 'description' => __('Add Sender.net form to your website.', 'framework')];

            /* Widget control settings. */
            $control_ops = ['id_base' => 'sender_automated_emails_widget'];

            /* Create the widget. */
            parent::__construct('sender_automated_emails_widget', __('Sender.net Form', 'framework'), $widget_ops, $control_ops);
        }

    }