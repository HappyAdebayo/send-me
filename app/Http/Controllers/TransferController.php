<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use App\Models\Account;
use App\Models\Transaction;
use Illuminate\Support\Str;

use Illuminate\Support\Facades\DB;
use App\Helpers\TransactionHelper;

class TransferController extends Controller
{
    public function transfer(Request $request)
    {
        $user = $request->user();

        if (!$user->kyc || $user->kyc->status !== 'approved') {
            return response()->json([
                'message' => 'KYC not verified. You cannot send money.'
            ], 403);
        }

        $request->validate([
            'to_account' => 'required|exists:accounts,account_number',
            'amount' => 'required|numeric|min:1',
            'pin'    => 'required|digits:4'
        ]);

        if (!$user->transaction_pin || !Hash::check($request->pin, $user->transaction_pin)) {
            return response()->json([
                'message' => 'Invalid transaction PIN'
            ], 403);
        }

        $senderAccount = $user->account;

        if ($senderAccount->account_number === $request->to_account) {
            return response()->json([
                'message' => 'You cannot transfer money to your own account'
            ], 400);
        }

        $receiverAccount = Account::where('account_number', $request->to_account)->first();
        $amount = $request->amount;

        if ($senderAccount->balance < $amount) {
            return response()->json([
                'message' => 'Insufficient balance'
            ], 400);
        }

        $reference = TransactionHelper::generateReference(); // store once

        DB::transaction(function () use ($senderAccount, $receiverAccount, $amount, $reference, &$transaction) {
            $senderAccount->decrement('balance', $amount);
            $receiverAccount->increment('balance', $amount);

            $senderAccount->refresh();
            $receiverAccount->refresh();

            $transaction = Transaction::create([
                'reference' => $reference,
                'sender_account_id' => $senderAccount->id,
                'receiver_account_id' => $receiverAccount->id,
                'amount' => $amount,
                'type' => 'transfer',
                'status' => 'success'
            ]);
        });

        return response()->json([
            'message' => 'Transfer successful',
            'data' => [
                'reference' => $transaction->reference,
                'from_account' => $senderAccount->account_number,
                'to_account' => $receiverAccount->account_number,
                'amount' => $amount,
                'sender_balance' => $senderAccount->balance,
                'receiver_balance' => $receiverAccount->balance,
                'transferred_at' => $transaction->created_at
            ]
        ], 201);
    }

}
