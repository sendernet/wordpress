<?php

    if ( ! defined( 'ABSPATH' ) ) {
        exit;
    }

    // Add function to widgets_init that'll load our widget.
    if(true) {
        add_action( 'widgets_init', 'Sender_Automated_Emails_Widget' );
    }

// Register widget.
    function Sender_Automated_Emails_Widget() {
        register_widget( 'Sender_Forms_Widget' );
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