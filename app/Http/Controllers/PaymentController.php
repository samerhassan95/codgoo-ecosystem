<?php

namespace App\Http\Controllers;

use App\Models\Invoice;
use App\Services\OPayService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;

class PaymentController extends Controller
{
    protected $opayService;

    public function __construct(OPayService $opayService)
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
    $orderNo = $request->orderNo ?? $request->input('orderNo');

    Log::info("Received callback with orderNo: " . json_encode($request->all()));

    if (!$orderNo) {
        Log::error('Callback received without orderNo');
        return response()->json(['message' => 'Order number not found'], 400);
    }

    $paymentData = $this->opayService->verifyPayment($orderNo);

    if (!$paymentData) {
        Log::error('OPay Verification Failed: No data received for orderNo: ' . $orderNo);
        return response()->json(['message' => 'Payment verification failed'], 400);
    }

    Log::info('Payment Data from OPay: ', (array) $paymentData);

    if (isset($paymentData['status']) && $paymentData['status'] === 'SUCCESS') {
        $invoice = Invoice::where('reference', $orderNo)->first(); 

        if (!$invoice) {
            Log::error("Invoice not found for reference: {$orderNo}");
            return response()->json(['message' => 'Invoice not found'], 404);
        }

        $invoice->update(['status' => 'paid']);
        Log::info("Invoice #{$invoice->id} updated to paid.");

        return response()->json(['message' => 'Payment has been completed successfully!']);
    }

    Log::error("Payment verification failed. Status: " . ($paymentData['status'] ?? 'UNKNOWN'));

    return response()->json(['message' => 'Payment verification failed'], 400);}

public function handleCallback(Request $request)
{
    return $this->opayCallback($request); 
}

    

    public function paymentSuccess()
    {
        return response()->json(['message' => 'Payment has been completed successfully!']);
    }

    public function paymentCancel()
    {
        return response()->json(['message' => 'Payment cancelled!']);
    }

}
