<?php

namespace App\Services;

use GuzzleHttp\Client;
use Illuminate\Support\Facades\Log;

class OPayService
{
    protected $client;
    protected $merchantId;
    protected $publicKey;
    protected $privateKey;
    protected $baseUrl;

    public function __construct()
    {
        $this->client = new Client();
        $this->merchantId = config('services.opay.merchant_id');
        $this->publicKey = config('services.opay.public_key'); 
        $this->privateKey = config('services.opay.secret_key');
        $this->baseUrl = config('services.opay.base_url');
    }

    public function initiatePayment($invoice)
    {
        $reference = 'INV-' . time();
        $invoice->update(['reference' => $reference]); 

        $payload = [
            "country" => "EG",
            "reference" => $reference,
            "amount" => [
                "total" => (int) ($invoice->amount * 100),
                "currency" => "EGP"
            ],
            "returnUrl" => route('payment.success'),
            "callbackUrl" => route('opay.callback'),
            "cancelUrl" => route('payment.cancel'),
            "expireAt" => 300,
            "userInfo" => [
                "userEmail" => "test@email.com",
                "userId" => "userid001",
                "userMobile" => "+201088889999",
                "userName" => "David"
            ],
            "productList" => [
                [
                    "productId" => "productId",
                    "name" => "Invoice Payment",
                    "description" => "Payment for invoice #{$invoice->id}",
                    "price" => (int) $invoice->amount,
                    "quantity" => 1,
                    "imageUrl" => "https://your-image-url.com"
                ]
            ],
            "payMethod" => "BankCard"
        ];

        try {
            $response = $this->client->post("{$this->baseUrl}/international/cashier/create", [
                'json' => $payload,
                'headers' => [
                    'MerchantId' => $this->merchantId,
                    'Authorization' => 'Bearer ' . $this->publicKey,
                    'Content-Type' => 'application/json',
                ],
            ]);

            $data = json_decode($response->getBody(), true);

            if (isset($data['data']['cashierUrl'])) {
                return [
                    'order_no' => $data['data']['orderNo'],
                    'payment_link' => $data['data']['cashierUrl']
                ];
            }

            return null;
        } catch (\Exception $e) {
            Log::error('OPay Payment Error: ' . $e->getMessage());
            return null;
        }
    }

    public function verifyPayment($orderNo)
    {
        try {
            Log::info("Verifying payment for orderNo: {$orderNo}");

            $response = $this->client->post("{$this->baseUrl}/international/cashier/query", [
                'json' => ['orderNo' => $orderNo],
                'headers' => [
                    'MerchantId' => $this->merchantId,
                    'Authorization' => 'Bearer ' . $this->publicKey,
                    'Content-Type' => 'application/json',
                ],
            ]);

            $body = $response->getBody()->getContents();
            Log::info("OPay Raw Response: " . $body);

            $data = json_decode($body, true);

            if (!$data || !isset($data['data'])) {
                Log::error("Invalid response from OPay API.");
                return null;
            }

            Log::info('OPay Verification Response:', $data['data']);

            return $data['data'];
        } catch (\Exception $e) {
            Log::error('OPay Verification Error: ' . $e->getMessage());
            return null;
        }
    }

}
