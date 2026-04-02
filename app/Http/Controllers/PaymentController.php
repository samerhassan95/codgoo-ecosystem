<?php

namespace App\Http\Controllers;

use App\Models\Invoice;
use App\Services\OpayService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use App\Models\Payment as PaymentModel;
use App\Services\PayPalService;

class PaymentController extends Controller
{
    protected $opayService;

    public function __construct(OpayService $opayService)
    {
        $this->opayService = $opayService;
    }

    public function payInvoice($invoiceId)
    {
        $invoice = Invoice::findOrFail($invoiceId);
        $paymentData = $this->opayService->initiatePayment($invoice);

        if (!$paymentData) {
            return response()->json(['message' => 'Payment creation failed'], 400);
        }

        return response()->json([
            'message' => 'Payment has been initiated successfully',
            'order_no' => $paymentData['order_no'],
            'payment_link' => $paymentData['payment_link']
        ]);
    }

    public function opayCallback(Request $request)
    {
        $callbackData = $request->all();
        Log::info("Received callback: " . json_encode($callbackData));
    
        $orderNo = $callbackData['orderNo'] ?? ($callbackData['data']['orderNo'] ?? null);
    
        if (!$orderNo) {
            Log::error('Callback received without orderNo');
            return response()->json(['message' => 'Order number not found'], 400);
        }
    
        $paymentData = $this->opayService->verifyPayment($orderNo);
    
        if (!$paymentData) {
            Log::error('OPay Verification Failed: No data received for orderNo: ' . $orderNo);
            return response()->json(['message' => 'Payment verification failed'], 400);
        }
    
        // معالجة الحالة الناجحة
        if (isset($paymentData['status']) && $paymentData['status'] === 'SUCCESS') {
            $invoice = Invoice::where('order_no', $orderNo)->first();
            if (!$invoice) {
                Log::error("Invoice not found for order_no: {$orderNo}");
                return response()->json(['message' => 'Invoice not found'], 404);
            }
            $invoice->update(['status' => 'paid']);
            Log::info("Invoice #{$invoice->id} updated to paid.");
            return response()->json(['message' => 'Payment completed successfully!']);
        }
    
        Log::error("Payment failed. Status: " . ($paymentData['status'] ?? 'UNKNOWN'));
        return response()->json(['message' => 'Payment failed'], 400);
    }

    

public function handleCallback(Request $request)
{
    return $this->opayCallback($request);
}



public function paypalSuccess(PaymentModel $payment, PayPalService $paypal)
{
    if ($payment->status !== 'pending') {
        return response()->json(['status' => false]);
    }

    $capture = $paypal->captureOrder(
        $payment->provider_payment_id
    );

    if (($capture['status'] ?? null) !== 'COMPLETED') {
        $payment->update(['status' => 'failed']);
        return response()->json(['status' => false]);
    }

    DB::transaction(function () use ($payment) {
        $payment->update(['status' => 'completed']);

        $bundle = $payment->payable;
        $bundle->update(['status' => 'active']);

        if (!empty($bundle->requested_app_ids)) {
            $bundle->applications()->sync($bundle->requested_app_ids);
        }
    });

    return response()->json(['status' => true]);
}

    
    

public function paypalCancel()
{
    return redirect()->route('payment.failed')->with('error', 'Payment cancelled by user.');
}

}