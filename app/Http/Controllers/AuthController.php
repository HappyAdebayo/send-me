<?php

namespace App\Http\Controllers;

use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Validator;
use App\Models\Account;
use App\Helpers\AccountHelper;
use App\Models\Kyc;

class AuthController extends Controller
{
    public function register(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'name' => 'required|string|max:255',
            'email' => 'required|email|unique:users',
            'password' => 'required|min:6',
            'image' => 'required|image|mimes:jpg,jpeg,png|max:2048'
        ]);

        if ($validator->fails()) {
            return response()->json($validator->errors(), 422);
        }

        $imagePath = null;

        if ($request->hasFile('image')) {
            $imagePath = $request->file('image')->store('users', 'public');
        }

        $user = User::create([
            'name' => $request->name,
            'email' => $request->email,
            'password' => Hash::make($request->password),
            'image' => $imagePath
        ]);

        Account::create([
            'user_id' => $user->id,
            'account_number' => AccountHelper::generateAccountNumber(),
            'balance' => 10000
        ]);


        $token = $user->createToken('auth-token')->plainTextToken;

        return response()->json([
            'message' => 'User registered successfully',
            'user' => $user,
            'token' => $token
        ], 201);
    }

    public function login(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'email' => 'required|email',
            'password' => 'required'
        ]);

        if ($validator->fails()) {
            return response()->json($validator->errors(), 422);
        }

        $user = User::where('email', $request->email)->first();

        if (!$user || !Hash::check($request->password, $user->password)) {
            return response()->json([
                'message' => 'Invalid credentials'
            ], 401);
        }

        $token = $user->createToken('auth-token')->plainTextToken;

        return response()->json([
            'message' => 'Login successful',
            'user' => $user,
            'token' => $token
        ]);
    }

    public function submitKyc(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'first_name'   => 'required|string|max:255',
            'last_name'    => 'required|string|max:255',
            'date_of_birth'=> 'required|date',
            'id_type'      => 'required|string|max:50',
            'id_number'    => 'required|string|max:100',
            'id_document'  => 'required|file|mimes:jpg,png,pdf|max:2048', 
        ]);

        if ($validator->fails()) {
            return response()->json([
                'message' => 'Validation failed',
                'errors'  => $validator->errors()
            ], 422);
        }

        $path = $request->file('id_document')->store('kyc');

        $kyc = Kyc::updateOrCreate(
            ['user_id' => $request->user()->id],
            [
                'first_name'   => $request->first_name,
                'last_name'    => $request->last_name,
                'date_of_birth'=> $request->date_of_birth,
                'id_type'      => $request->id_type,
                'id_number'    => $request->id_number,
                'id_document'  => $path,
                'status'       => 'approved',  
                'verified_at'  => now()
            ]
        );

        return response()->json([
            'message' => 'KYC submitted and approved successfully',
            'kyc'     => [
                'first_name'    => $kyc->first_name,
                'last_name'     => $kyc->last_name,
                'date_of_birth' => $kyc->date_of_birth,
                'id_type'       => $kyc->id_type,
                'id_number'     => $kyc->id_number,
                'id_document'   => $kyc->id_document,
                'status'        => $kyc->status,
                'verified_at'   => $kyc->verified_at,
            ]
        ], 201);
    }

    public function setPin(Request $request) { 
        $request->validate([
            'pin' => 'required|digits:4'
        ]); 

        $user = $request->user(); 
        $user->transaction_pin = bcrypt($request->pin); 
        $user->save(); 
        
        return response()->json([ 
            'message' => 'Transaction PIN set successfully' 
        ], 200); 
    }
}
