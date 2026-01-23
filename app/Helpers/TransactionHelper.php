<?php

namespace App\Helpers;

use App\Models\Transaction;
use Illuminate\Support\Str;

class TransactionHelper
{
    public static function generateReference(): string
    {
        do {
            $reference = 'TXN-' . strtoupper(Str::random(12));
        } while (Transaction::where('reference', $reference)->exists());

        return $reference;
    }
}
