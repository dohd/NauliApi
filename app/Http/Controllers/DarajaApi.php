<?php

namespace App\Http\Controllers;

use Illuminate\Support\Facades\Http;

trait DarajaApi
{
    public $base_url = 'https://3d96-2c0f-fe38-2240-dda6-dd5d-df04-64d1-abfc.ngrok-free.app';
    public $api_headers = [
        'accept' => 'application/json',
        'content_type' => 'application/json',
    ];
    public $api_endpoints = [
        'access_token' => 'https://sandbox.safaricom.co.ke/oauth/v1/generate?grant_type=client_credentials',
        'b2c_payment' => 'https://sandbox.safaricom.co.ke/mpesa/b2c/v1/paymentrequest',
    ];

    function getAccessToken() {
        $this->api_headers['authorization'] = 'Basic ' . config('daraja.access_token_auth');
        return Http::timeout(5)
        ->withHeaders($this->api_headers)
        ->get($this->api_endpoints['access_token'])
        ->throw()
        ->json();
    }

    public function businessPayment($amount=1, $phone=254708374149) 
    {
        $params = [
            "InitiatorName" => config('daraja.initiator_name'),
            "SecurityCredential" => config('daraja.security_credential'),
            "CommandID" => "BusinessPayment",
            "Amount" => $amount,
            "PartyA" => config('daraja.b2c_shortcode'),
            "PartyB" => $phone,
            "Remarks" => "Cashout Payment",
            "QueueTimeOutURL" => $this->base_url .  "/api/cashouts/store_timeout",
            "ResultURL" => $this->base_url .  "/api/cashouts/store",
            "Occassion" => "Cashout"
        ];
        $response = $this->getAccessToken();
        $this->api_headers['authorization'] = 'Bearer ' . $response['access_token'];
        return Http::timeout(5)
        ->withHeaders($this->api_headers)
        ->post($this->api_endpoints['b2c_payment'], $params)
        ->throw()
        ->json();
    }
}