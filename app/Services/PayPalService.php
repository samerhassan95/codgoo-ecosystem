<?php

namespace App\Services;

use Srmklive\PayPal\Services\PayPal as PayPalClient;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Http;



class PayPalService
    {
    protected string $baseUrl;
    protected string $token;

    public function __construct()
    {
        $this->baseUrl = config('paypal.mode') === 'sandbox'
            ? 'https://api-m.sandbox.paypal.com'
            : 'https://api-m.paypal.com';

        $this->token = $this->getAccessToken();
    }

    protected function getAccessToken(): string
    {
        $response = Http::asForm()
            ->withBasicAuth(
                config('paypal.client_id'),
                config('paypal.client_secret')
            )
            ->post($this->baseUrl . '/v1/oauth2/token', [
                'grant_type' => 'client_credentials',
            ]);

        return $response->json('access_token');
    }

public function createOrder(float $amount, string $currency, string $returnUrl, string $cancelUrl)
{
    $order = Http::withToken($this->token)
        ->withHeaders([
            'Content-Type' => 'application/json',
        ])
        ->post($this->baseUrl . '/v2/checkout/orders', [
            'intent' => 'CAPTURE',
            'purchase_units' => [
                [
                    'amount' => [
                        'currency_code' => $currency,
                        'value' => number_format($amount, 2, '.', ''), // e.g., 500.00
                    ],
                ]
            ],
            'application_context' => [
                'return_url' => $returnUrl,
                'cancel_url' => $cancelUrl,
            ],
        ])->json();

    Log::info('PayPal Order Response', $order);

    return $order;
}




public function captureOrder(string $orderId)
{
    $response = Http::withToken($this->token)
        ->withHeaders([
            'Content-Type' => 'application/json',
            'Accept' => 'application/json',
        ])
        // Manually setting the body to an empty JSON object string
        ->withBody('{}', 'application/json') 
        ->post($this->baseUrl . "/v2/checkout/orders/{$orderId}/capture");

    $data = $response->json();

    \Log::info('PayPal Capture API Attempt', [
        'order_id' => $orderId,
        'response' => $data
    ]);

    return $data;
}
}
