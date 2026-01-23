<?php

namespace App\Helpers;

use Illuminate\Support\Facades\Hash;

class UserHelper
{
    public static function validateKycAndPin($user, string $pin): array
    {
        if (!$user->kyc || $user->kyc->status !== 'approved') {
            return [
                'success' => false,
                'message' => 'KYC not verified. Action cannot be performed.',
                'status' => 403,
            ];
        }

        if (!$user->transaction_pin || !Hash::check($pin, $user->transaction_pin)) {
            return [
                'success' => false,
                'message' => 'Invalid transaction PIN',
                'status' => 403,
            ];
        }

        return ['success' => true];
    }
}
