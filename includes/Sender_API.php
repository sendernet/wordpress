<?php

if (!defined('ABSPATH')) {
    exit;
}

class Sender_API
{
    private $senderBaseUrl = 'https://api.sender.net/v2/';
    private $senderStatsBaseUrl = 'https://stats.sender.net/';

    public function senderGetBaseArguments()
    {
        return [
            'headers' => [
                'Content-Type' => 'application/json',
                'Accept' => 'application/json',
                'Authorization' => 'Bearer ' . get_option('sender_api_key'),
            ],
        ];
    }

    public function senderBaseRequestArguments($delete = false)
    {
        if ($delete) {
            return array_merge($this->senderGetBaseArguments(), ['method' => 'DELETE']);
        }

        return $this->senderGetBaseArguments();
    }

    public function senderGetAccount()
    {
        $data = wp_remote_request($this->senderBaseUrl . 'users', $this->senderBaseRequestArguments());
        return $this->senderBuildResponse($data);
    }

    public function senderGetForms()
    {
        $data = wp_remote_request($this->senderBaseUrl . 'forms?type=embed&is_active=1&limit=100', $this->senderBaseRequestArguments());
        return $this->senderBuildResponse($data);
    }

    public function senderGetGroups()
    {
        $data = wp_remote_request($this->senderBaseUrl . 'tags?limit=100', $this->senderBaseRequestArguments());
        return $this->senderBuildResponse($data);
    }

    public function senderGetCart($cartHash)
    {
        $data = wp_remote_request($this->senderStatsBaseUrl . 'carts/' . $cartHash, $this->senderBaseRequestArguments());
        return $this->senderBuildStatsResponse($data);
    }

    public function senderDeleteCart($wpCartId)
    {
        $data = ['resource_key' => $this->senderGetResourceKey()];
        $params = array_merge($this->senderBaseRequestArguments(true), ['body' => json_encode($data)]);
        $response = wp_remote_request($this->senderStatsBaseUrl . 'carts/' . $wpCartId, $params);

        return $this->senderBuildStatsResponse($response);
    }

    public function senderUpdateCart(array $cartParams)
    {
        $cartParams['resource_key'] = $this->senderGetResourceKey();
        $params = array_merge($this->senderBaseRequestArguments(), ['body' => json_encode($cartParams), 'method' => 'PATCH']);

        $response = wp_remote_request($this->senderStatsBaseUrl . 'carts/' . $cartParams['external_id'], $params);

        return $this->senderBuildStatsResponse($response);
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
                $data['list_id'] = $list;
            }

            if ($newsletter = get_user_meta($userId, 'sender_newsletter', true)) {
                if($newsletter == true){
                    $data['newsletter'] = true;
                }else{
                    $data['newsletter'] = false;
                }
            }

            $params = array_merge($this->senderBaseRequestArguments(), ['body' => json_encode($data)]);
            $response = wp_remote_post($this->senderStatsBaseUrl . 'attach_visitor', $params);

            return $this->senderBuildStatsResponse($response);
        }
    }

    public function senderTrackNotRegisteredUsers($userData)
    {
        if (isset($userData['email']) && isset($userData['visitor_id'])) {
            $params = array_merge($this->senderBaseRequestArguments(), ['body' => json_encode($userData)]);
            $response = wp_remote_post($this->senderStatsBaseUrl . 'attach_visitor', $params);

            return $this->senderBuildStatsResponse($response);
        }
    }

    public function senderTrackRegisterUserCallback($userId)
    {
        $this->senderApiShutdownCallback('senderTrackRegisteredUsers', $userId);
    }

    public function senderApiShutdownCallback($callback, $params)
    {
        register_shutdown_function([$this, $callback], $params);
    }

    public function senderGetResourceKey()
    {
        $key = get_option('sender_resource_key');

        if (!$key) {
            $user = $this->senderGetAccount();
            $key = $user->account->resource_key;
            update_option('sender_resource_key', $key);
        }

        return $key;
    }

    private function senderBuildResponse($response)
    {
        $responseCode = wp_remote_retrieve_response_code($response);
        if (is_wp_error($response) || $responseCode != 200) {
            if ($responseCode == 429) {
                return json_decode(json_encode(['xRate' => true]));
            }

            //Handle 401 unathorized response
            if ($responseCode === 401) {
                update_option('sender_api_key', false);
                update_option('sender_account_disconnected', true);
            }

            return false;
        }

        return json_decode($response['body']);
    }

    private function senderBuildStatsResponse($response)
    {
        return json_decode($response['body']);
    }

    public function senderGetStore()
    {
        $response = wp_remote_request($this->senderBaseUrl . 'stores/' . get_option('sender_store_register'), $this->senderBaseRequestArguments());

        return $this->senderBuildResponse($response);
    }

    public function senderAddStore()
    {
        $storeParams = [
            'domain' => get_site_url(),
            'name' => get_bloginfo('name'),
            'type' => 'wordpress'
        ];

        $params = array_merge($this->senderBaseRequestArguments(), ['body' => json_encode($storeParams)]);

        $response = wp_remote_post($this->senderBaseUrl . 'stores', $params);

        return $this->senderBuildResponse($response);
    }

    public function senderDeleteStore()
    {
        $removingStoreParams = [
            'headers' => [
                'Content-Type' => 'application/json',
                'Accept' => 'application/json',
                'Authorization' => 'Bearer ' . get_option('sender_api_key'),
            ],
            'method' => 'DELETE'
        ];

        $response = wp_remote_request($this->senderBaseUrl . 'stores/' . get_option('sender_store_register'), $removingStoreParams);

        if (is_wp_error($response) || wp_remote_retrieve_response_code($response) != 200) {
            return false;
        }

        return $this->senderBuildResponse($response);
    }

    public function senderExportData($exportData)
    {
        $params = array_merge($this->senderBaseRequestArguments(), ['body' => json_encode($exportData), 'data_format' => 'body']);

        $response = wp_remote_post($this->senderBaseUrl . 'stores/' . get_option('sender_store_register') . '/import_shop_data', $params);

        return $this->senderBuildResponse($response);
    }

    public function generateVisitorId()
    {
        $data = ['resource_key' => $this->senderGetResourceKey()];
        $params = array_merge($this->senderBaseRequestArguments(), ['body' => json_encode($data), 'data_format' => 'body']);

        $response = wp_remote_post($this->senderStatsBaseUrl . 'get_visitor_id/', $params);

        if (is_wp_error($response) || wp_remote_retrieve_response_code($response) != 200) {
            return false;
        }

        return $this->senderBuildStatsResponse($response);
    }

    public function senderTrackCart(array $cartParams)
    {
        $params = array_merge($this->senderBaseRequestArguments(), ['body' => json_encode($cartParams)]);

        $response = wp_remote_post($this->senderStatsBaseUrl . 'carts', $params);

        return $this->senderBuildResponse($response);
    }


}