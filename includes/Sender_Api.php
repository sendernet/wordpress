<?php

    if (!defined('ABSPATH')) {
        exit;
    }

    class Sender_Api
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

        public function senderBaseRequestArguments()
        {
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

        public function senderTrackCart()
        {

        }

        public function senderConvertCart()
        {

        }

        public function senderDeleteCart()
        {

        }

        private function senderBuildResponse($response)
        {
            return json_decode($response['body']);
        }

    }