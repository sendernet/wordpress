<?php

    if (!defined('ABSPATH')) {
        exit;
    }

   /*
   Plugin Name: Sender.net email marketing
   Plugin URI: https://sender.net
   description: Sender email marketing tool
   Version: 2.0.4
   Author: Sender
   Author URI: https://sender.net
   License: GPL2
   */

    if( !class_exists( 'Sender_Automated_Emails' ) ) {
        require_once( "includes/Sender_Automated_Emails.php" );
    }

    new Sender_Automated_Emails(__FILE__);