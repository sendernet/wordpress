<?php

class Sender_Repository
{
	public function __construct()
	{

	}

	public function senderCreateTables()
	{
		require_once(ABSPATH . 'wp-admin/includes/upgrade.php');

		global $wpdb;

		$wcap_collate = '';

		if ($wpdb->has_cap('collation')) {
			$wcap_collate = $wpdb->get_charset_collate();
		}

		$sender_carts = $wpdb->prefix . 'sender_automated_emails_carts';

		$cartsSql = "CREATE TABLE IF NOT EXISTS $sender_carts (
                             `id` int(11) NOT NULL AUTO_INCREMENT,
                             `user_id` int(11) NOT NULL,
                             `user_type` varchar(15),
                             `session` varchar(50) COLLATE utf8_unicode_ci NOT NULL,
                             `cart_data` text COLLATE utf8_unicode_ci NOT NULL,
                             `cart_recovered` int(11) NOT NULL,
                             `cart_status` int(11) NOT NULL,
                             `created` int(11) NOT NULL,
                             `updated` int(11) NOT NULL,
                             PRIMARY KEY (`id`)
                             ) $wcap_collate";

		$wpdb->query($cartsSql);

		$sender_users = $wpdb->prefix . "sender_automated_emails_users";

		$usersSql = "CREATE TABLE IF NOT EXISTS $sender_users (
            `id` int(15) NOT NULL AUTO_INCREMENT,
            `first_name` text,
            `last_name` text,
            `email` text,
            `created` int(11) NOT NULL,
            `updated` int(11) NOT NULL,
            `visitor_id` varchar(32),
            PRIMARY KEY (`id`)
            ) $wcap_collate";

		$wpdb->query($usersSql);
	}

    public function senderGetCustomerByEmail($email)
    {
        global $wpdb;

        $sqlQuery = "SELECT * FROM `".$wpdb->prefix."sender_automated_emails_users` WHERE email = %s AND id != '0'";

        $result = $wpdb->get_results( $wpdb->prepare( $sqlQuery, $email ) );

        return $result;
    }

    public function senderGetCartBySession($sessionKey)
    {
        if(!$sessionKey) {
            return [];
        }

        global $wpdb;

        $query   = "SELECT * FROM `".$wpdb->prefix."sender_automated_emails_carts`
                        WHERE session = %s
                        AND cart_recovered = %d
                        AND cart_status = '0' ";

        $result = $wpdb->get_results($wpdb->prepare( $query, $sessionKey, 0));

        return !count($result) ? false : $result[0];
    }

    public function senderUpdateUserModified($userId, $timestamp)
    {
        global $wpdb;
        $sqlQuery = "UPDATE `".$wpdb->prefix."sender_automated_emails_users`
                            SET updated = %d
                            WHERE id = %d ";

        $wpdb->query( $wpdb->prepare($sqlQuery, $timestamp, $userId));
    }

    public function senderUpdateCartUser($cartId, $userId, $timestamp)
    {
        global $wpdb;

        $sqlQuery = "UPDATE `".$wpdb->prefix."sender_automated_emails_carts`
                            SET user_id = %d,
                                updated = %d
                            WHERE id = %d ";

        $wpdb->query( $wpdb->prepare($sqlQuery, $userId, $timestamp, $cartId));
    }

    public function senderCreateNewGuestUser($visitorId, $timestamp)
    {
        global $wpdb;
        $sqlQuery = "INSERT INTO `".$wpdb->prefix."sender_automated_emails_users`
                             ( visitor_id, created, updated )
                             VALUES (%s, %d, %d )";

        $wpdb->query($wpdb->prepare($sqlQuery, $visitorId, $timestamp, $timestamp));

        return $wpdb->insert_id;
    }

    public function senderGetCartByUser($userId) {

        global $wpdb;

        $query   = "SELECT * FROM `".$wpdb->prefix."sender_automated_emails_carts`
                        WHERE user_id = %d
                        AND cart_recovered = %d
                        AND cart_status = '0'";

        return $wpdb->get_results($wpdb->prepare( $query, $userId, 0) );
    }

    public function senderGetUserById($userId){

        global $wpdb;

        $query = "SELECT * FROM `".$wpdb->prefix."sender_automated_emails_users` WHERE id = %d";

        $result = $wpdb->get_results($wpdb->prepare( $query, $userId) );

        return empty($result) ? false : $result[0] ;
    }

    public function senderGetCartByVisitor($visitorId) {

        global $wpdb;

        $query   = "SELECT * FROM `".$wpdb->prefix."sender_automated_emails_carts`
                        WHERE visitor_id = %d
                        AND cart_recovered = %d
                        AND cart_status = '0'";

        return $wpdb->get_results($wpdb->prepare( $query, $visitorId, 0) );
    }

    public function senderUpdateUserCartData($userId, $cartData)
    {
        global $wpdb;

        $sqlQuery = "UPDATE `".$wpdb->prefix."sender_automated_emails_carts`
                             SET cart_data = %s,
                                 updated = %d
                             WHERE user_id = %d 
                             AND cart_recovered = %d
                             AND cart_status = '0' ";

        $wpdb->query( $wpdb->prepare($sqlQuery, $cartData, current_time('timestamp'), $userId, 0));
    }

    public function senderVisitorUserCartData($visitorId, $cartData)
    {
        global $wpdb;

        $sqlQuery = "UPDATE `".$wpdb->prefix."sender_automated_emails_carts`
                             SET cart_data = %s,
                                 updated = %d
                             WHERE visitor_id = %d 
                             AND cart_recovered = %d
                             AND cart_status = '0' ";

        $wpdb->query( $wpdb->prepare($sqlQuery, $cartData, current_time('timestamp'), $visitorId, 0));
    }

    public function senderUpdateCartBySession($cartData, $session, $timestamp = null)
    {
    	if (!$timestamp) {
    		$timestamp = current_time('timestamp');
		}
        global $wpdb;

        $sqlQuery = "UPDATE `".$wpdb->prefix."sender_automated_emails_carts`
                                        SET cart_data = %s,
                                            updated = %d
                                        WHERE session = %s AND
                                              cart_recovered = %d";

        $wpdb->query( $wpdb->prepare($sqlQuery, $cartData, $timestamp, $session, 0));
    }

    public function senderCreateCart($cartData, $userId, $session)
    {
        global $wpdb;
        $currentTime = current_time('timestamp');

        $sqlQuery = "INSERT INTO `".$wpdb->prefix."sender_automated_emails_carts`
                         ( user_id, cart_data, session, created, updated )
                         VALUES ( %d, %s, %s, %d, %d )";

        $wpdb->query($wpdb->prepare($sqlQuery, $userId, $cartData, $session, $currentTime, $currentTime));

        return $wpdb->insert_id;
    }

    public function senderGetUserByVisitorId($visitorId)
    {
        global $wpdb;

        $sqlQuery = "SELECT * FROM `".$wpdb->prefix."sender_automated_emails_users` WHERE visitor_id = %s AND id != '0'";

        $result = $wpdb->get_results( $wpdb->prepare( $sqlQuery, $visitorId ) );

        if(count($result)){
            return $result[0];
        }

        $sqlQuery = "INSERT INTO `".$wpdb->prefix."sender_automated_emails_users`
                     ( visitor_id, created, updated )
                     VALUES (%s, %d, %d )";

        $currentTime = current_time('timestamp');

        $wpdb->query($wpdb->prepare($sqlQuery, $visitorId, $currentTime, $currentTime));

        return $this->senderGetUserByVisitorId($visitorId);
    }

    public function senderDeleteCartBySession($session)
    {
        global $wpdb;
        $query = "DELETE FROM `".$wpdb->prefix."sender_automated_emails_carts` WHERE session = %d";
        $wpdb->query($wpdb->prepare($query, $session));
    }

}