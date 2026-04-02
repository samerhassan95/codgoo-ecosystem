<?php

namespace App\Http\Controllers\Client;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\ClientPaymentMethod;
use Illuminate\Support\Facades\DB;

class PaymentMethodController extends Controller
{
    public function index()
    {
        $client = auth('client')->user();
        $methods = ClientPaymentMethod::where('client_id', $client->id)
            ->orderBy('default', 'desc')
            ->orderBy('created_at', 'desc')
            ->get();
        return response()->json(['status' => true, 'data' => $methods], 200);
    }

    public function store(Request $request)
    {
        $request->validate([
            'card_number' => 'required|string|min:13|max:19',
            'expiry_date' => 'required|string|regex:/^\d{2}\/\d{2}$/', // Format: MM/YY
            'security_code' => 'required|string|min:3|max:4',
            'remember_card' => 'sometimes|boolean',
            'default' => 'sometimes|boolean'
        ]);

        $client = auth('client')->user();

        // Validate expiry date
        [$month, $year] = explode('/', $request->expiry_date);
        $expiryYear = 2000 + intval($year);
        $expiryMonth = intval($month);
        
        if ($expiryMonth < 1 || $expiryMonth > 12) {
            return response()->json(['status' => false, 'message' => 'Invalid expiry month.'], 422);
        }

        DB::transaction(function() use ($request, $client, $expiryMonth, $expiryYear, &$method) {
            // If this is the first payment method or set as default, make it default
            $isFirstMethod = ClientPaymentMethod::where('client_id', $client->id)->count() === 0;
            $shouldBeDefault = ($request->filled('default') && $request->default) || $isFirstMethod;

            if ($shouldBeDefault) {
                ClientPaymentMethod::where('client_id', $client->id)->update(['default' => false]);
            }

            // Get last 4 digits
            $cardNumber = preg_replace('/\s+/', '', $request->card_number);
            $lastFour = substr($cardNumber, -4);
            
            // Detect card brand
            $cardBrand = $this->detectCardBrand($cardNumber);

            $method = ClientPaymentMethod::create([
                'client_id' => $client->id,
                'card_number' => $this->maskCardNumber($cardNumber), // Store masked version
                'card_last_four' => $lastFour,
                'card_brand' => $cardBrand,
                'expiry_month' => $expiryMonth,
                'expiry_year' => $expiryYear,
                'expiry_date' => $request->expiry_date,
                'remember_card' => $request->remember_card ?? false,
                'default' => $shouldBeDefault,
            ]);

            // Note: In production, you should integrate with a payment gateway
            // (Stripe, PayPal, etc.) and store the provider_token instead
        });

        return response()->json([
            'status' => true, 
            'message' => 'Payment method added successfully.', 
            'data' => $method
        ], 201);
    }

    public function update(Request $request, $id)
    {
        $request->validate([
            'expiry_date' => 'sometimes|string|regex:/^\d{2}\/\d{2}$/',
            'default' => 'sometimes|boolean'
        ]);

        $client = auth('client')->user();
        $method = ClientPaymentMethod::where('id', $id)
            ->where('client_id', $client->id)
            ->firstOrFail();

        DB::transaction(function() use ($request, $client, $method) {
            if ($request->filled('default') && $request->default) {
                ClientPaymentMethod::where('client_id', $client->id)
                    ->where('id', '!=', $method->id)
                    ->update(['default' => false]);
            }

            $updateData = [];
            
            if ($request->filled('expiry_date')) {
                [$month, $year] = explode('/', $request->expiry_date);
                $updateData['expiry_date'] = $request->expiry_date;
                $updateData['expiry_month'] = intval($month);
                $updateData['expiry_year'] = 2000 + intval($year);
            }
            
            if ($request->filled('default')) {
                $updateData['default'] = $request->default;
            }

            $method->update($updateData);
        });

        return response()->json([
            'status' => true, 
            'message' => 'Payment method updated successfully.', 
            'data' => $method
        ], 200);
    }

    public function setDefault($id)
    {
        $client = auth('client')->user();
        $method = ClientPaymentMethod::where('id', $id)
            ->where('client_id', $client->id)
            ->firstOrFail();

        DB::transaction(function() use ($client, $method) {
            ClientPaymentMethod::where('client_id', $client->id)->update(['default' => false]);
            $method->update(['default' => true]);
        });

        return response()->json([
            'status' => true, 
            'message' => 'Default payment method updated.', 
            'data' => $method
        ], 200);
    }

    public function destroy($id)
    {
        $client = auth('client')->user();
        $method = ClientPaymentMethod::where('id', $id)
            ->where('client_id', $client->id)
            ->firstOrFail();

        $totalMethods = ClientPaymentMethod::where('client_id', $client->id)->count();
        
        DB::transaction(function() use ($method, $client, $totalMethods) {
            $wasDefault = $method->default;
            $method->delete();

            // If deleted method was default and there are other methods, set another as default
            if ($wasDefault && $totalMethods > 1) {
                $newDefault = ClientPaymentMethod::where('client_id', $client->id)
                    ->orderBy('created_at', 'desc')
                    ->first();
                if ($newDefault) {
                    $newDefault->update(['default' => true]);
                }
            }
        });

        return response()->json([
            'status' => true, 
            'message' => 'Payment method removed successfully.'
        ], 200);
    }

    /**
     * Mask card number for security
     */
    private function maskCardNumber($cardNumber)
    {
        $lastFour = substr($cardNumber, -4);
        return str_repeat('*', strlen($cardNumber) - 4) . $lastFour;
    }

    /**
     * Detect card brand from card number
     */
    private function detectCardBrand($cardNumber)
    {
        $cardNumber = preg_replace('/\s+/', '', $cardNumber);
        
        if (preg_match('/^4/', $cardNumber)) {
            return 'Visa';
        } elseif (preg_match('/^5[1-5]/', $cardNumber)) {
            return 'Mastercard';
        } elseif (preg_match('/^3[47]/', $cardNumber)) {
            return 'American Express';
        } elseif (preg_match('/^6(?:011|5)/', $cardNumber)) {
            return 'Discover';
        }
        
        return 'Unknown';
    }
}