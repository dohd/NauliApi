<?php

namespace App\Http\Controllers;

use Illuminate\Support\Facades\Http;

trait DarajaApi
{
    public $api_headers = [
        'accept' => 'application/json',
        'content_type' => 'application/json',
        'authorization' => 'Bearer eyJ0eXAiOiJKV1QiLCJhbGciOiJSUzI1NiJ9.eyJhdWQiOiIxIiwianRpIjoiYzk2YzNmMzRiZjM5OGZmYjExYzEwZTdmMjBlZTg4YTQ1MGJkNjVjYjFkZDQ0Zjc0ZDVmNjI5NTg5YjY2MWNhOGI4MWE0NzEzMjgwODdjYzYiLCJpYXQiOjE2ODk0MTk4MjcuMDAwNzIxLCJuYmYiOjE2ODk0MTk4MjcuMDAwNzI1LCJleHAiOjE3MjEwNDIyMjYuOTg3NjM0LCJzdWIiOiIxIiwic2NvcGVzIjpbXX0.OSTP0T0f4Gb9E0oUObS13Rjf1PwDsrlWMV6gQEVYYUbgjDQAhED9Oe4D7xpH_pLPAQPwg6iH_G_R7KRH9GjQWDQYpY-w5pcw8g8IwxVyFs_uyo2N4vB5STsieQwMiriqyMRRkIbtZ63H66UNa2XJ65D0nYIxmGcAYDSqwt3aYuxhN3L28bbrpdBstYrBfAndSd9jzaagLX0K2evziUXN3nLYPjedjZFtXwWFdt1Pq98ZAkvGPg7g2tmjwD4OrgTblpOIhOabAbS189F8gvQuSrKNlHvg-EyFNsRJp735tWnoR1c6uiSGgLw0fyaM7cBWzyIpT7QVrM7RZ_-4ces01aGGwnkVvLXUA6ZG3MLMUTVs6MKelKxcnQyDwd90pyzPfk_dS9LsOLJO6167EaDB6GTCVfijaMCajn-jwoX76ewM7rAfWlxs5v3JFhqbx4EsQPn3Xa-WHGTvtuYZobP58VRCm6pQB9iQj2igcqIJ6QAoPVgLxaJkGRpX2N97xTKEnyCJHVU-FSP70IFfa6VNBqGuS8_Xulmy9NTQ8JT9bQBOheE07W36vYoMlQb3wRDnpiPO-eGaH9De5UFX7M2bprR88shRi6s6NkTdJEmPFI0DWpzXTiK_2W--kWvYkHsBjyuGFL0FVwpnNsznDOKodyxjnofBtg3SRCZIwcIGEqs',
    ];
    public $api_routes = [
        'b2c_payment' => 'http://127.0.0.1:5001/api/withdrawals/store',
    ];

    // trigger B2C transaction (business payment)
    public function businessPayment($amount) {
        return Http::timeout(1)
        ->withHeaders($this->api_headers)
        ->post($this->api_routes['b2c_payment'], [
            'amount' => $amount,
        ])['body'];
    }
}