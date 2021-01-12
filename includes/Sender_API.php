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
                    'Content-Type' => 'application/json',
                    'Accept' => 'application/json',
                    'Authorization' => 'Bearer ' . $this->senderGetApiKey()
                ]
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
            if($delete){
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
            $data = wp_remote_request($this->senderBaseUrl . 'forms', $this->senderBaseRequestArguments());
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

        public function senderTrackCart(array $cartParams)
        {
            $params = array_merge($this->senderBaseRequestArguments(), ['params' => $cartParams]);
            $response = wp_remote_post($this->senderBaseUrl . 'carts', $params);
            return $this->senderBuildResponse($response);
        }

        public function senderConvertCart($wpCartId)
        {
            $response = wp_remote_post($this->senderBaseUrl . 'carts/' . $wpCartId . '/convert', $this->senderBaseRequestArguments());
            return $this->senderBuildResponse($response);
        }

        public function senderDeleteCart($wpCartId)
        {
            $response = wp_remote_request($this->senderBaseUrl . 'carts/' . $wpCartId, $this->senderBaseRequestArguments(true));
            return $this->senderBuildResponse($response);
        }

        private function senderBuildResponse($response)
        {
            return json_decode($response['body']);
        }

    }