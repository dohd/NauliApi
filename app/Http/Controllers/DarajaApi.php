<?php

namespace App\Http\Controllers;

use Illuminate\Support\Facades\Http;

trait DarajaApi
{
    public $api_endpoints = [
        'access_token' => 'https://sandbox.safaricom.co.ke/oauth/v1/generate?grant_type=client_credentials',
        'b2c_payment' => 'https://sandbox.safaricom.co.ke/mpesa/b2c/v1/paymentrequest',
        'transaction_status' => 'https://sandbox.safaricom.co.ke/mpesa/transactionstatus/v1/query'
    ];
    
    public $api_headers = [
        'accept' => 'application/json',
        'content_type' => 'application/json',
    ];

    /**
     * Authorization Token
     */
    function getAccessToken() {
        $this->api_headers['authorization'] = 'Basic ' . config('daraja.authorization');
        // $this->api_headers['authorization'] = 'Basic ' . base64_encode(config('daraja.consumer_key') . ':' . config('daraja.consumer_secret'));
        return Http::timeout(5)
        ->withHeaders($this->api_headers)
        ->get($this->api_endpoints['access_token'])
        ->throw()
        ->json();
    }

    /**
     * B2C Business To Customer
     */
    public function businessPayment($amount=1, $phone=254) 
    {
        $params = [
            'InitiatorName' => config('daraja.initiator_name'),
            'SecurityCredential' => config('daraja.security_credential'),
            'CommandID' => 'BusinessPayment',
            'Amount' => $amount,
            'PartyA' => config('daraja.b2c_shortcode'),
            'PartyB' => $phone,
            'Remarks' => 'Cashout Payment',
            'QueueTimeOutURL' => '',
            'ResultURL' => env('API_BASE_URL') .  '/api/cashouts/store',
            'Occassion' => 'Cashout'
        ];
        $response = $this->getAccessToken();
        $this->api_headers['authorization'] = 'Bearer ' . $response['access_token'];
        return Http::timeout(5)
        ->withHeaders($this->api_headers)
        ->post($this->api_endpoints['b2c_payment'], $params)
        ->throw()
        ->json();
    }

    /**
     * Transaction Status
     */
    public function transactionStatus($data) 
    {
        $params = [
            'Initiator' => config('daraja.initiator_name'),
            'SecurityCredential' => config('daraja.security_credential'),
            'CommandID' => 'TransactionStatusQuery',
            'TransactionID' => $data['transaction_id'],
            'PartyA' => $data['party'],
            'IdentifierType' => @$data['identifier_type'],
            'ResultURL' => env('API_BASE_URL') .  '/api/transaction_status/result',
            'QueueTimeOutURL' => '',
            'Remarks' => @$data['remark'],
            'Occassion' => @$data['occassion'],
        ];
        $response = $this->getAccessToken();
        $this->api_headers['authorization'] = 'Bearer ' . $response['access_token'];
        return Http::timeout(5)
        ->withHeaders($this->api_headers)
        ->post($this->api_endpoints['transaction_status'], $params)
        ->throw()
        ->json();
    }
}