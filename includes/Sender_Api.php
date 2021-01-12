<?php

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
            return wp_remote_request($this->senderBaseUrl . 'users', $this->senderBaseRequestArguments());
        }

        public function senderGetForms()
        {
            return wp_remote_request($this->senderBaseUrl . 'forms', $this->senderBaseRequestArguments());
        }

    }