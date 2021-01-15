<?php

    if (!defined('ABSPATH')) {
        exit;
    }

   /*
   Plugin Name: Sender.net email marketing
   Plugin URI: https://sender.net
   description: Sender email marketing tool
   Version: 2.0
   Author: Sender
   Author URI: https://sender.net
   License: GPL2
   */

    if( !class_exists( 'Sender_Automated_Emails' ) ) {
        require_once( "includes/Sender_Automated_Emails.php" );
    }
    if( !class_exists('Sender_Templates_Loader') ) {
        require_once( "templates/Sender_Templates_Loader.php" );
    }

    $sender = new Sender_Automated_Emails(__FILE__);

    register_activation_hook( __FILE__, [&$sender->repository, 'senderCreateTables']);

new Sender_Templates_Loader($sender);

    add_action( 'wp_head', function () { ?>
        <script>

            jQuery(document).on('wc_cart_emptied',function (ev) {

                var adminUrl = "<?php echo get_admin_url();?>admin-ajax.php";

                var data = {
                    action: 'labas'
                };

                var resp = jQuery.post( adminUrl, data);

                console.log(resp);
            });

        </script>
    <?php },9999 );

?>
