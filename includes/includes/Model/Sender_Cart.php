<?php
if (!defined('ABSPATH')) {
	exit;
}

if (!class_exists('Sender_Model')) {
	require_once 'Sender_Model.php';
}

class Sender_Cart extends Sender_Model
{
	protected $tableName = 'sender_automated_emails_carts';

	protected $id;

	protected $user_id;

	protected $user_type;

	protected $session;

	protected $cart_data;

	protected $cart_recovered;

	protected $cart_status;
}