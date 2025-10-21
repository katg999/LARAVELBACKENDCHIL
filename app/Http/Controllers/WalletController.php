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
        $currentEntity = session('current_entity');

        if (!$currentEntity) {
            return redirect()->back()->with('error', 'No active entity found. Please navigate from a valid dashboard.');
        }

        $userType = $currentEntity['type'];
        $userId = $currentEntity['id'];

        $wallet = $this->getWalletModel($userType, $userId);

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
            'amount' => 'required|numeric|min:0.01',
            'description' => 'nullable|string'
        ]);

        $currentEntity = session('current_entity');

        if (!$currentEntity) {
            return response()->json(['error' => 'No active entity found'], 400);
        }

        $userType = $currentEntity['type'];
        $userId = $currentEntity['id'];
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
            'amount' => 'required|numeric|min:0.01',
            'description' => 'nullable|string'
        ]);

        $currentEntity = session('current_entity');

        if (!$currentEntity) {
            return response()->json(['error' => 'No active entity found'], 400);
        }

        $userType = $currentEntity['type'];
        $userId = $currentEntity['id'];
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
        $currentEntity = session('current_entity');

        if (!$currentEntity) {
            return response()->json(['error' => 'No active entity found'], 400);
        }

        $wallet = $this->getWalletModel($currentEntity['type'], $currentEntity['id']);
        
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
