<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Services\OpayService;

class PaymentController extends Controller
{
    protected $opayService;

    public function __construct(OpayService $opayService)
    {
        $this->opayService = $opayService;
    }

    public function initiatePayment(Request $request)
    {
        $orderId = uniqid('OPAY_');
        $amount = $request->amount;
        $returnUrl = route('payment.success');
        $callbackUrl = route('payment.callback');

        $response = $this->opayService->initiatePayment($amount, $orderId, $returnUrl, $callbackUrl);

        if ($response['code'] == '00000') {
            return redirect($response['data']['cashierUrl']);
        }

        return response()->json(['error' => 'Payment initiation failed'], 400);
    }

    public function callback(Request $request)
    {
        $orderId = $request->input('reference');
        $response = $this->opayService->verifyPayment($orderId);

        if ($response['code'] == '00000' && $response['data']['status'] == 'SUCCESS') {
            return response()->json(['message' => 'Payment successful']);
        }

        return response()->json(['error' => 'Payment verification failed'], 400);
    }
}
