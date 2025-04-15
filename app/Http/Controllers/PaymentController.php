<?php
// app/Http/Controllers/PaymentController.php

namespace App\Http\Controllers;

use App\Services\MomoService;
use Illuminate\Http\Request;

class PaymentController extends Controller
{
    protected $momoService;

    public function __construct(MomoService $momoService)
    {
        $this->momoService = $momoService;
    }

    // Initialize API User (one-time setup)
    public function initApiUser()
    {
        $result = $this->momoService->createApiUser();
        return response()->json([
            'success' => $result,
            'message' => $result ? 'API user created successfully' : 'Failed to create API user'
        ]);
    }

    // Get API User details
    public function getApiUser()
    {
        $user = $this->momoService->getApiUser();
        return response()->json($user);
    }

    // Request payment
    public function requestPayment(Request $request)
    {
        $validated = $request->validate([
            'amount' => 'required|numeric',
            'phone_number' => 'required|string',
            'external_id' => 'required|string'
        ]);

        $result = $this->momoService->requestToPay(
            $validated['amount'],
            $validated['phone_number'],
            $validated['external_id'],
            'Payment for doctor appointment',
            'School health service'
        );

        return response()->json($result);
    }

    // Check payment status
    public function paymentStatus($referenceId)
    {
        $status = $this->momoService->getPaymentStatus($referenceId);
        return response()->json($status);
    }

    // Get account balance
    public function accountBalance()
    {
        $balance = $this->momoService->getAccountBalance();
        return response()->json($balance);
    }

    // Handle MoMo callback
    public function handleCallback(Request $request)
    {
        Log::info('MoMo Callback Received:', $request->all());
        
        // Process the callback - update your database, etc.
        // $referenceId = $request->input('referenceId');
        // $status = $request->input('status');
        
        return response()->json(['success' => true]);
    }
}