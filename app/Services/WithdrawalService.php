<?php

namespace App\Services;

use App\Models\Transaction;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use App\Helpers\TransactionHelper;
use App\Helpers\UserHelper;
use Exception;

class WithdrawalService
{
    public function withdraw($user, float $amount, string $pin)
    {
        $validation = UserHelper::validateKycAndPin($user, $pin);
        if (!$validation['success']) {
            return $validation;
        }

        $account = $user->account;

        if ($account->balance < $amount) {
            return [
                'success' => false,
                'message' => 'Insufficient balance',
                'status' => 400,
            ];
        }

        $reference = TransactionHelper::generateReference();

        $transaction = null;
        DB::transaction(function () use ($account, $amount, $reference, &$transaction) {
            $account->decrement('balance', $amount);
            $account->refresh();

            $transaction = Transaction::create([
                'reference' => $reference,
                'sender_account_id' => $account->id,
                'receiver_account_id' => null,
                'amount' => $amount,
                'type' => 'debit',
                'status' => 'success',
            ]);
        });

        return [
            'success' => true,
            'message' => 'Withdrawal successful',
            'transaction' => [
                'reference' => $transaction->reference,
                'amount' => $transaction->amount,
                'balance_after' => $account->balance,
                'created_at' => $transaction->created_at,
            ],
            'status' => 201,
        ];
    }
}
