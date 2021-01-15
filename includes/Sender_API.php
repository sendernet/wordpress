<?php

if (!defined('ABSPATH')) {
	exit;
}

class Sender_API
{
	private $senderBaseRequestArguments;
	private $senderBaseUrl = 'https://api.sender.net/v2/';
	protected $senderApiKey;

	public function __construct()
	{
		$this->senderSetApiKey()
			 ->senderSetBaseArguments();
	}

	private function senderSetBaseArguments()
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
		$this->senderApiKey = $apiKey ?? get_option('sender_api_key');
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
		$data = wp_remote_request($this->senderBaseUrl . 'carts/' . $cartHash, $this->senderBaseRequestArguments());
		return $this->senderBuildResponse($data);
	}

	public function senderConvertCart($wpCartId)
	{
		$list = get_option('sender_customers_list');

		$data = [
			'external_id' => $wpCartId,
		];
		if ($list) {
			$data['list_id'] = $list;
		}
		echo '
			<script> sender("convertCart", ' . json_encode($data) . '</script>
		';
	}

	public function senderDeleteCart($wpCartId)
	{
		$response = wp_remote_request($this->senderBaseUrl . 'carts/' . $wpCartId, $this->senderBaseRequestArguments(true));
		return $this->senderBuildResponse($response);
	}

    public function senderTrackCart(array $cartParams)
    {
        $params = array_merge($this->senderBaseRequestArguments(), ['body' => json_encode($cartParams)]);
        $response = wp_remote_post($this->senderBaseUrl . 'carts', $params);
        return $this->senderBuildResponse($response);
    }

//        public function addToGroup($email, $firstname, $lastname, $groupId)
//        {
//            $subscriberParams = [
//                'firstname' => $firstname,
//                'lastname' => $lastname,
//                'email' => $email,
//                'tag_ids' => [$groupId],
//                'update_existing' => true
//            ];
//
//            $params = array_merge($this->senderBaseRequestArguments(), ['body' => json_encode($subscriberParams)]);
//            $response = wp_remote_post($this->senderBaseUrl . 'subscribers/create_update', $params);
//            return $this->senderBuildResponse($response);
//        }

	public function senderTrackRegisteredUsers($userId)
	{
		$user = get_userdata($userId);
		$list = get_option('sender_registration_list');

		if (isset($user->user_email)) {

			$data = [
				'email' => $user->user_email,
				'firstname' => $user->first_name,
				'lastname' => $user->last_name,
			];
			if ($list) {
				$data['list_id'] = $list;
			}
			echo '
				<script> sender("attachEmail", ' . json_encode($data) . '</script>
			';

		}
	}

	private function senderBuildResponse($response)
	{
		return json_decode($response['body']);
	}

}