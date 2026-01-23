<?php

namespace App\Services;

use App\Models\Account;
use App\Models\Transaction;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use App\Helpers\TransactionHelper;
use App\Helpers\UserHelper;

class TransferService
{
    public function transfer($user, string $toAccountNumber, float $amount, string $pin)
    {
        $validation = UserHelper::validateKycAndPin($user, $pin);
        if (!$validation['success']) {
            return $validation;
        }

        $senderAccount = $user->account;

        if ($senderAccount->account_number === $toAccountNumber) {
            return [
                'success' => false,
                'message' => 'You cannot transfer money to your own account',
                'status' => 400,
            ];
        }

        $receiverAccount = Account::where('account_number', $toAccountNumber)->first();

        if (!$receiverAccount) {
            return [
                'success' => false,
                'message' => 'Receiver account not found',
                'status' => 404,
            ];
        }

        if ($senderAccount->balance < $amount) {
            return [
                'success' => false,
                'message' => 'Insufficient balance',
                'status' => 400,
            ];
        }

        $reference = TransactionHelper::generateReference();
        $transaction = null;

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
                'status' => 'success',
            ]);
        });

        return [
            'success' => true,
            'message' => 'Transfer successful',
            'transaction' => [
                'reference' => $transaction->reference,
                'from_account' => $senderAccount->account_number,
                'to_account' => $receiverAccount->account_number,
                'amount' => $amount,
                'sender_balance' => $senderAccount->balance,
                'receiver_balance' => $receiverAccount->balance,
                'transferred_at' => $transaction->created_at,
            ],
            'status' => 201,
        ];
    }
}
