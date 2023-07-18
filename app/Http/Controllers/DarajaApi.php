<?php

namespace App\Http\Controllers;

use Illuminate\Support\Facades\Http;

trait DarajaApi
{
    public $base_url = 'https://d943-197-248-216-91.ngrok-free.app';
    public $api_headers = [
        'accept' => 'application/json',
        'content_type' => 'application/json',
    ];
    public $api_endpoints = [
        'access_token' => 'https://sandbox.safaricom.co.ke/oauth/v1/generate?grant_type=client_credentials',
        'business_payment' => 'https://sandbox.safaricom.co.ke/mpesa/b2c/v1/paymentrequest',
    ];

    function getAccessToken() {
        $this->api_headers['authorization'] = 'Basic ' . config('daraja.access_token_auth');
        return Http::timeout(3)
        ->withHeaders($this->api_headers)
        ->get($this->api_endpoints['access_token'])
        ->throw()
        ->json();
    }

    // trigger B2C transaction (business payment)
    public function businessPayment($amount=0, $phone=254708374149) 
    {
        $response = $this->getAccessToken();
        $this->api_headers['authorization'] = 'Bearer ' . $response['access_token'];

        $params = [
            "InitiatorName" => "testapi",
            "SecurityCredential" => config('daraja.security_credential'),
            "CommandID" => "BusinessPayment",
            "Amount" => $amount,
            "PartyA" => config('daraja.b2c_shortcode'),
            "PartyB" => $phone,
            "Remarks" => "Test remarks",
            "QueueTimeOutURL" => $this->base_url .  "/api/cashouts/store_timeout",
            "ResultURL" => $this->base_url .  "/api/cashouts/store",
            "Occassion" => ""
        ];
        
        return Http::timeout(3)
        ->withHeaders($this->api_headers)
        ->post($this->api_endpoints['business_payment'], $params)
        ->throw()
        ->json();
    }
}