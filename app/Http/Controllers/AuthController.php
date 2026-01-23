<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;
use App\Services\AuthService;

class AuthController extends Controller
{
    protected AuthService $authService;

    public function __construct(AuthService $authService)
    {
        $this->authService = $authService;
    }

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

        $result = $this->authService->register($request->only('name','email','password'), $request->file('image'));

        return response()->json($result, $result['status']);
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

        $result = $this->authService->login($request->only('email','password'));

        return response()->json($result, $result['status']);
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

        $result = $this->authService->submitKyc($request->user(), $request->only(
            'first_name','last_name','date_of_birth','id_type','id_number'
        ), $request->file('id_document'));

        return response()->json($result, $result['status']);
    }

    public function setPin(Request $request)
    {
        $request->validate([
            'pin' => 'required|digits:4'
        ]);

        $result = $this->authService->setPin($request->user(), $request->pin);

        return response()->json($result, $result['status']);
    }
}
