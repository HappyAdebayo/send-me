<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Services\WithdrawalService;
use Illuminate\Validation\ValidationException;

class WithdrawalController extends Controller
{
    protected WithdrawalService $withdrawalService;

    public function __construct(WithdrawalService $withdrawalService)
    {
        $this->withdrawalService = $withdrawalService;
    }

   public function withdraw(Request $request)
    {
        $request->validate([
            'amount' => 'required|numeric|min:1',
            'pin'    => 'required|digits:4',
        ]);

        $user = $request->user();
        $result = $this->withdrawalService->withdraw($user, $request->amount, $request->pin);

        return response()->json(
            $result,
            $result['status']
        );
    }

}
