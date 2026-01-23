<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Services\TransferService;

class TransferController extends Controller
{
    protected TransferService $transferService;

    public function __construct(TransferService $transferService)
    {
        $this->transferService = $transferService;
    }

    public function transfer(Request $request)
    {
        $request->validate([
            'to_account' => 'required|exists:accounts,account_number',
            'amount' => 'required|numeric|min:1',
            'pin'    => 'required|digits:4',
        ]);

        $user = $request->user();

        $result = $this->transferService->transfer(
            $user,
            $request->to_account,
            $request->amount,
            $request->pin
        );

        return response()->json($result, $result['status']);
    }
}
