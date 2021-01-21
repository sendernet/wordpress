<?php

if (!defined('ABSPATH')) {
	exit;
}

class Sender_API
{
	private $senderBaseRequestArguments;
	private $senderBaseUrl = 'https://api.sender.net/v2/';
	private $senderStatsBaseUrl = 'https://stats.sender.net/';
	protected $senderApiKey;

	public function __construct()
	{
		$this->senderSetApiKey()
			 ->senderSetBaseArguments();
	}

	public function senderSetBaseArguments()
	{
		$this->senderBaseRequestArguments = [
			'headers' => [
				'Content-Type'  => 'application/json',
				'Accept'        => 'application/json',
				'Authorization' => 'Bearer ' . $this->senderGetApiKey(),
			],
		];
	}

	public function senderSetApiKey($apiKey = null)
	{
		$this->senderApiKey = $apiKey ? $apiKey : get_option('sender_api_key');
		return $this;
	}

	public function senderGetApiKey()
	{
		return $this->senderApiKey;
	}

	public function senderBaseRequestArguments($delete = false)
	{
		if ($delete) {
			return array_merge($this->senderBaseRequestArguments, ['method' => 'DELETE']);
		}

		return $this->senderBaseRequestArguments;
	}

	public function senderGetAccount()
	{
		$data = wp_remote_request($this->senderBaseUrl . 'users', $this->senderBaseRequestArguments());
		return $this->senderBuildResponse($data);
	}

	public function senderGetForms()
	{
		$data = wp_remote_request($this->senderBaseUrl . 'forms?type=embed&status=PUBLISHED', $this->senderBaseRequestArguments());
		return $this->senderBuildResponse($data);
	}

	public function senderGetGroups()
	{
		$data = wp_remote_request($this->senderBaseUrl . 'tags', $this->senderBaseRequestArguments());
		return $this->senderBuildResponse($data);
	}

	public function senderGetCart($cartHash)
	{
		$data = wp_remote_request($this->senderStatsBaseUrl . 'carts/' . $cartHash, $this->senderBaseRequestArguments());
		return $this->senderBuildResponse($data);
	}

	public function senderConvertCart($params)
	{
		$list = get_option('sender_customers_list');

		$email = wc_get_order($params['orderId'])->get_billing_email();

		$data = [
			'external_id' => $params['cartId'],
			'email' => $email,
            'resource_key' => $this->senderGetResourceKey()
		];

		$url = $this->senderStatsBaseUrl . 'carts/' . $params['cartId'] . '/convert';

		if ($list) {
			$data['list_id'] = $list;
		}

		$params = array_merge($this->senderBaseRequestArguments(), ['body' => json_encode($data)]);

		$response = wp_remote_post($url, $params);
		return $this->senderBuildResponse($response);
	}

	public function senderDeleteCart($wpCartId)
	{
		$data = ['resource_key' => $this->senderGetResourceKey()];
		$params = array_merge($this->senderBaseRequestArguments(true), ['body' => json_encode($data)]);

		$response = wp_remote_request($this->senderStatsBaseUrl . 'carts/' . $wpCartId, $params);
		return $this->senderBuildResponse($response);
	}

    public function senderTrackCart(array $cartParams)
    {
        $params = array_merge($this->senderBaseRequestArguments(), ['body' => json_encode($cartParams)]);

        $response = wp_remote_post($this->senderStatsBaseUrl . 'carts', $params);

        return $this->senderBuildResponse($response);
    }

    public function senderUpdateCart(array $cartParams)
    {
        $cartParams['resource_key'] = $this->senderGetResourceKey();
        $params = array_merge($this->senderBaseRequestArguments(), ['body' => json_encode($cartParams), 'method' => 'PATCH']);

		$response = wp_remote_request($this->senderStatsBaseUrl . 'carts/' . $cartParams['external_id'], $params);

        return $this->senderBuildResponse($response);
    }


	public function senderTrackRegisteredUsers($userId)
	{
		$user = get_userdata($userId);
		$list = get_option('sender_registration_list');

		if (isset($user->user_email)) {

			$data = [
				'email' => $user->user_email,
				'firstname' => $user->first_name,
				'lastname' => $user->last_name,
				'visitor_id' => $_COOKIE['sender_site_visitor']
			];

			if ($list) {
				$data['list_id'] = (int) $list;
			}

			$params = array_merge($this->senderBaseRequestArguments(), ['body' => json_encode($data)]);
			$response = wp_remote_post($this->senderStatsBaseUrl . 'attach_visitor', $params);

			return $this->senderBuildResponse($response);

		}
	}

	public function senderTrackRegisterUserCallback($userId)
    {
        $this->senderApiShutdownCallback('senderTrackRegisteredUsers', $userId);
    }

	private function senderBuildResponse($response)
	{
		return json_decode($response['body']);
	}

	public function senderApiShutdownCallback($callback, $params)
    {
        register_shutdown_function([$this, $callback], $params);
    }

    public function senderGetResourceKey()
    {
        $key = get_option('sender_resource_key');

        if(!$key){
            $user = $this->senderGetAccount();
            $key = $user->account->resource_key;
            update_option('sender_resource_key', $key);
        }

        return $key;
    }
}