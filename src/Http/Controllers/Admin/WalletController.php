<?php

namespace Coderstm\Http\Controllers\Admin;

use Coderstm\Coderstm;
use Coderstm\Http\Controllers\Controller;
use Coderstm\Http\Resources\WalletTransactionResource;
use Illuminate\Http\Request;
use Illuminate\Validation\ValidationException;

class WalletController extends Controller
{
    public function balance(Request $request, $user)
    {
        $userModel = Coderstm::$userModel;
        $user = $userModel::findOrFail($user);
        $wallet = $user->getOrCreateWallet();

        return response()->json(['balance' => (float) $wallet->balance, 'formatted_balance' => format_amount($wallet->balance, config('app.currency', 'USD')), 'currency' => strtoupper(config('app.currency', 'USD'))]);
    }

    public function transactions(Request $request, $user)
    {
        $userModel = Coderstm::$userModel;
        $user = $userModel::findOrFail($user);
        $transactions = $user->walletTransactions()->with('transactionable')->orderBy('created_at', 'desc')->paginate($request->get('per_page', 15));

        return WalletTransactionResource::collection($transactions);
    }

    public function credit(Request $request, $user)
    {
        $validated = $request->validate(['amount' => 'required|numeric|min:0.01', 'description' => 'nullable|string|max:255']);
        $userModel = Coderstm::$userModel;
        $user = $userModel::findOrFail($user);
        $wallet = $user->getOrCreateWallet();
        $transaction = $wallet->credit($validated['amount'], $validated['description'] ?? 'Manual credit by admin');

        return response()->json(['message' => 'Wallet credited successfully', 'transaction' => new WalletTransactionResource($transaction), 'balance' => (float) $wallet->fresh()->balance]);
    }

    public function debit(Request $request, $user)
    {
        $validated = $request->validate(['amount' => 'required|numeric|min:0.01', 'description' => 'nullable|string|max:255']);
        $userModel = Coderstm::$userModel;
        $user = $userModel::findOrFail($user);
        $wallet = $user->getOrCreateWallet();
        if ($wallet->balance < $validated['amount']) {
            throw ValidationException::withMessages(['amount' => ['Insufficient wallet balance.']]);
        }
        $transaction = $wallet->debit($validated['amount'], $validated['description'] ?? 'Manual debit by admin');

        return response()->json(['message' => 'Wallet debited successfully', 'transaction' => new WalletTransactionResource($transaction), 'balance' => (float) $wallet->fresh()->balance]);
    }
}
