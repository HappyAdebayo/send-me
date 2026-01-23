<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use App\Models\Transaction;
use App\Helpers\TransactionHelper;
use Illuminate\Support\Facades\DB;

class WithdrawalController extends Controller
{
     public function withdraw(Request $request)
    {
        $user = $request->user();

        if (!$user->kyc || $user->kyc->status !== 'approved') {
            return response()->json([
                'message' => 'KYC not verified. You cannot withdraw funds.'
            ], 403);
        }

        $request->validate([
            'amount' => 'required|numeric|min:1',
            'pin'    => 'required|digits:4'
        ]);

        if (!$user->transaction_pin || !Hash::check($request->pin, $user->transaction_pin)) {
            return response()->json([
                'message' => 'Invalid transaction PIN'
            ], 403);
        }

        $account = $user->account;
        $amount = $request->amount;

        if ($account->balance < $amount) {
            return response()->json([
                'message' => 'Insufficient balance'
            ], 400);
        }

        $reference = TransactionHelper::generateReference();

        DB::transaction(function () use ($account, $amount, &$transaction, $reference) {
            $account->decrement('balance', $amount);
            $account->refresh();

            $transaction = Transaction::create([
                'reference' => $reference,
                'sender_account_id' => $account->id,
                'receiver_account_id' => null,
                'amount' => $amount,
                'type' => 'debit',
                'status' => 'success'
            ]);
        });

        return response()->json([
            'message' => 'Withdrawal successful',
            'transaction' => [
                'reference' => $transaction->reference,
                'amount' => $transaction->amount,
                'balance_after' => $account->balance,
                'created_at' => $transaction->created_at
            ]
        ], 201);
    }
}
