<?php

namespace App\Services;

use App\Models\User;
use App\Models\Account;
use App\Models\Kyc;
use Illuminate\Support\Facades\Hash;
use App\Helpers\AccountHelper;

class AuthService
{
    public function register(array $data, $imageFile = null)
    {
        $imagePath = null;

        if ($imageFile) {
            $imagePath = $imageFile->store('users', 'public');
        }

        $user = User::create([
            'name' => $data['name'],
            'email' => $data['email'],
            'password' => Hash::make($data['password']),
            'image' => $imagePath
        ]);

        Account::create([
            'user_id' => $user->id,
            'account_number' => AccountHelper::generateAccountNumber(),
            'balance' => 10000,
        ]);

        $token = $user->createToken('auth-token')->plainTextToken;

        return [
            'success' => true,
            'user' => $user,
            'token' => $token,
            'message' => 'User registered successfully',
            'status' => 201,
        ];
    }

    public function login(array $data)
    {
        $user = User::where('email', $data['email'])->first();

        if (!$user || !Hash::check($data['password'], $user->password)) {
            return [
                'success' => false,
                'message' => 'Invalid credentials',
                'status' => 401,
            ];
        }

        $token = $user->createToken('auth-token')->plainTextToken;

        return [
            'success' => true,
            'user' => $user,
            'token' => $token,
            'message' => 'Login successful',
            'status' => 200,
        ];
    }

    public function submitKyc($user, array $data, $documentFile)
    {
        $path = $documentFile->store('kyc');

        $kyc = Kyc::updateOrCreate(
            ['user_id' => $user->id],
            [
                'first_name'   => $data['first_name'],
                'last_name'    => $data['last_name'],
                'date_of_birth'=> $data['date_of_birth'],
                'id_type'      => $data['id_type'],
                'id_number'    => $data['id_number'],
                'id_document'  => $path,
                'status'       => 'approved',  
                'verified_at'  => now()
            ]
        );

        return [
            'success' => true,
            'kyc' => $kyc,
            'message' => 'KYC submitted and approved successfully',
            'status' => 201,
        ];
    }

    public function setPin($user, string $pin)
    {
        $user->transaction_pin = bcrypt($pin);
        $user->save();

        return [
            'success' => true,
            'message' => 'Transaction PIN set successfully',
            'status' => 200,
        ];
    }
}
