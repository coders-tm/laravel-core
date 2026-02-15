<?php

namespace Coderstm\Http\Controllers\User;

use Coderstm\Http\Controllers\Controller;
use Coderstm\Http\Resources\WalletTransactionResource;
use Illuminate\Http\Request;

class WalletController extends Controller
{
    public function balance(Request $request)
    {
        $user = $request->user();
        $wallet = $user->getOrCreateWallet();

        return response()->json(['balance' => (float) $wallet->balance, 'formatted_balance' => format_amount($wallet->balance, config('app.currency', 'USD')), 'currency' => strtoupper(config('app.currency', 'USD'))]);
    }

    public function transactions(Request $request)
    {
        $user = $request->user();
        $transactions = $user->walletTransactions()->with('transactionable')->orderBy('created_at', 'desc')->paginate($request->get('per_page', 15));

        return WalletTransactionResource::collection($transactions);
    }
}
