<?php

namespace App\Services;

use Illuminate\Support\Facades\Http;

class OpayService
{
    protected $baseUrl;
    protected $publicKey;
    protected $secretKey;
    protected $merchantId;

    public function __construct()
    {
        $this->baseUrl = env('OPAY_BASE_URL');
        $this->publicKey = env('OPAY_PUBLIC_KEY');
        $this->secretKey = env('OPAY_SECRET_KEY');
        $this->merchantId = env('OPAY_MERCHANT_ID');
    }

    public function initiatePayment($amount, $orderId, $returnUrl, $callbackUrl)
    {
        $endpoint = "/api/v1/international/payment/create";
        $url = $this->baseUrl . $endpoint;

        $data = [
            "amount" => $amount,
            "currency" => "NGN",
            "reference" => $orderId,
            "merchantId" => $this->merchantId,
            "product" => "Test Product",
            "callbackUrl" => $callbackUrl,
            "returnUrl" => $returnUrl,
            "paymentMethods" => ["CARD", "ACCOUNT", "USSD"],
            "expireAt" => 30,
        ];

        $response = Http::withHeaders([
            'Authorization' => "Bearer " . $this->secretKey,
            'Content-Type' => 'application/json',
        ])->post($url, $data);

        return $response->json();
    }

    public function verifyPayment($orderId)
    {
        $endpoint = "/api/v1/international/payment/status";
        $url = $this->baseUrl . $endpoint;

        $data = [
            "reference" => $orderId,
            "merchantId" => $this->merchantId,
        ];

        $response = Http::withHeaders([
            'Authorization' => "Bearer " . $this->secretKey,
            'Content-Type' => 'application/json',
        ])->post($url, $data);

        return $response->json();
    }
}
