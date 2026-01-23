<?php

namespace App\Helpers;

use App\Models\Account;

class AccountHelper
{
    public static function generateAccountNumber(): string
    {
        do {
            $accountNumber = 'ACC' . random_int(1000000000, 9999999999);
        } while (Account::where('account_number', $accountNumber)->exists());

        return $accountNumber;
    }
}
