<?php

namespace App\Http\Controllers\Api\V1\Payment\PhizPay;

use Illuminate\Http\Request;
use App\Http\Controllers\Api\V1\BaseController;
use Illuminate\Support\Facades\Log;
use App\Models\Payment\UserWallet;
use App\Models\Payment\DriverWallet;
use App\Models\Payment\OwnerWallet;
use App\Models\Payment\UserWalletHistory;
use App\Models\Payment\DriverWalletHistory;
use App\Models\Payment\OwnerWalletHistory;

class PhizPayController extends BaseController
{
    /**
     * Handle PhizPay webhook notifications
     */
    public function webhook(Request $request)
    {
        $data = $request->all();
        Log::info('PhizPay Webhook Received: ' . json_encode($data));

        if ($data['event_type'] === 'TRANSACTION.SUCCESS') {
            $ciphertext = $data['resource']['ciphertext'];
            // TODO: Implementar descriptografia do ciphertext (AEAD_AES_256_GCM)
            // Exemplo: $decryptedData = $this->decryptCiphertext($ciphertext);

            $transactionId = $data['resource']['out_trade_no'];
            $userId = $this->extractUserIdFromTransaction($transactionId);

            $user = \App\Models\User::find($userId);
            if (!$user) {
                Log::error('PhizPay Webhook: User not found for transaction ' . $transactionId);
                return response()->json(['code' => 'ERROR', 'message' => 'User not found']);
            }

            $amount = $data['resource']['amount']['total'] ?? 0;
            $walletModel = $user->hasRole('user') ? new UserWallet() :
                          ($user->hasRole('driver') ? new DriverWallet() :
                          new OwnerWallet());
            $historyModel = $user->hasRole('user') ? new UserWalletHistory() :
                            ($user->hasRole('driver') ? new DriverWalletHistory() :
                            new OwnerWalletHistory());

            $wallet = $walletModel::firstOrCreate(['user_id' => $user->id], [
                'amount_added' => 0,
                'amount_balance' => 0,
                'amount_spent' => 0,
                'currency_code' => 'BRL',
            ]);

            $wallet->amount_added += $amount;
            $wallet->amount_balance += $amount;
            $wallet->save();

            $historyModel::create([
                'user_id' => $user->id,
                'amount' => $amount,
                'transaction_id' => $transactionId,
                'is_credit' => true,
                'remarks' => 'Added via PhizPay Webhook',
            ]);

            return response()->json(['code' => 'SUCCESS', 'message' => 'Sucesso']);
        }

        return response()->json(['code' => 'ERROR', 'message' => 'Evento não suportado']);
    }

    /**
     * Extract user_id from transaction_id
     */
    protected function extractUserIdFromTransaction($transactionId)
    {
        // Extrai o user_id do formato WALLET_..._{user_id}
        $parts = explode('_', $transactionId);
        return end($parts); // Última parte é o user_id
    }

    /**
     * Add money to wallet (specific endpoint for PhizPay)
     */
    public function addMoneyToWallet(Request $request)
    {
        $phizpayTask = new PhizPayTask();
        $wallet = $phizpayTask->addMoneyToWallet($request);
        return $this->respondSuccess($wallet, 'money_added_successfully');
    }
}