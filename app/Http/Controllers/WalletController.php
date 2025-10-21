<?php

namespace App\Http\Controllers;

use App\Models\Doctor;
use App\Models\School;
use App\Models\HealthFacility;
use App\Models\WalletTransaction;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class WalletController extends Controller
{
    /**
     * Show wallet dashboard
     */
    public function index(Request $request)
    {
        $userType = $request->get('type'); // 'doctor', 'school', 'health_facility'
        $userId = $request->get('id');

        $wallet = null;
        $transactions = collect();

        switch ($userType) {
            case 'doctor':
                $wallet = Doctor::findOrFail($userId);
                break;
            case 'school':
                $wallet = School::findOrFail($userId);
                break;
            case 'health_facility':
                $wallet = HealthFacility::findOrFail($userId);
                break;
            default:
                abort(400, 'Invalid user type');
        }

        // Get transaction history
        $transactions = WalletTransaction::forUser($userType, $userId)
            ->latest()
            ->paginate(20);

        return view('wallet.index', compact('wallet', 'userType', 'transactions'));
    }

    /**
     * Deposit money to wallet
     */
    public function deposit(Request $request)
    {
        $request->validate([
            'user_type' => 'required|in:doctor,school,health_facility',
            'user_id' => 'required|integer',
            'amount' => 'required|numeric|min:0.01',
            'description' => 'nullable|string'
        ]);

        $userType = $request->user_type;
        $userId = $request->user_id;
        $amount = $request->amount;

        DB::beginTransaction();
        try {
            $wallet = $this->getWalletModel($userType, $userId);
            
            $wallet->increment('wallet_balance', $amount);
            $newBalance = $wallet->fresh()->wallet_balance;
            
            // Log transaction
            $this->logTransaction($userType, $userId, 'deposit', $amount, $request->description, $newBalance);
            
            DB::commit();
            
            return response()->json([
                'success' => true,
                'message' => 'Deposit successful',
                'new_balance' => $newBalance
            ]);
        } catch (\Exception $e) {
            DB::rollBack();
            Log::error('Wallet deposit failed: ' . $e->getMessage());
            return response()->json(['error' => 'Deposit failed'], 500);
        }
    }

    /**
     * Withdraw money from wallet
     */
    public function withdraw(Request $request)
    {
        $request->validate([
            'user_type' => 'required|in:doctor,school,health_facility',
            'user_id' => 'required|integer',
            'amount' => 'required|numeric|min:0.01',
            'description' => 'nullable|string'
        ]);

        $userType = $request->user_type;
        $userId = $request->user_id;
        $amount = $request->amount;

        DB::beginTransaction();
        try {
            $wallet = $this->getWalletModel($userType, $userId);
            
            if ($wallet->wallet_balance < $amount) {
                return response()->json(['error' => 'Insufficient balance'], 400);
            }
            
            $wallet->decrement('wallet_balance', $amount);
            $newBalance = $wallet->fresh()->wallet_balance;
            
            // Log transaction
            $this->logTransaction($userType, $userId, 'withdraw', $amount, $request->description, $newBalance);
            
            DB::commit();
            
            return response()->json([
                'success' => true,
                'message' => 'Withdrawal successful',
                'new_balance' => $newBalance
            ]);
        } catch (\Exception $e) {
            DB::rollBack();
            Log::error('Wallet withdrawal failed: ' . $e->getMessage());
            return response()->json(['error' => 'Withdrawal failed'], 500);
        }
    }

    /**
     * Get wallet balance
     */
    public function balance(Request $request)
    {
        $request->validate([
            'user_type' => 'required|in:doctor,school,health_facility',
            'user_id' => 'required|integer'
        ]);

        $wallet = $this->getWalletModel($request->user_type, $request->user_id);
        
        return response()->json([
            'balance' => $wallet->wallet_balance
        ]);
    }

    /**
     * Get wallet model instance
     */
    private function getWalletModel($userType, $userId)
    {
        switch ($userType) {
            case 'doctor':
                return Doctor::findOrFail($userId);
            case 'school':
                return School::findOrFail($userId);
            case 'health_facility':
                return HealthFacility::findOrFail($userId);
            default:
                throw new \InvalidArgumentException('Invalid user type');
        }
    }

    /**
     * Log wallet transaction
     */
    private function logTransaction($userType, $userId, $type, $amount, $description = null, $balanceAfter = null)
    {
        WalletTransaction::create([
            'user_type' => $userType,
            'user_id' => $userId,
            'type' => $type,
            'amount' => $amount,
            'balance_after' => $balanceAfter,
            'description' => $description,
        ]);

        // Also log to system log
        Log::info("Wallet transaction: {$type} of {$amount} for {$userType} ID {$userId}", [
            'description' => $description,
            'balance_after' => $balanceAfter
        ]);
    }
}
