<?php

namespace App\Base\Payment\PhizPayTasks;

use App\Models\Payment\CardInfo;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;
use App\Base\Constants\Setting\Settings;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\File;

class PhizPayTask
{
    protected $baseUrl;
    protected $mchid;
    protected $serialNo;
    protected $privateKey;
    protected $publicKey;

    public function __construct()
    {
        $this->baseUrl = env('PHIZPAY_API_URL', 'https://api.phizpay.com');
        $this->mchid = env('PHIZPAY_MCHID');
        $this->serialNo = env('PHIZPAY_SERIAL_NO');

        // Carregar chaves a partir dos arquivos
        $privateKeyPath = env('PHIZPAY_PRIVATE_KEY');
        $publicKeyPath = env('PHIZPAY_PUBLIC_KEY');

        if (!File::exists($privateKeyPath) || !File::exists($publicKeyPath)) {
            throw new \Exception('PhizPay key files not found');
        }

        $this->privateKey = File::get($privateKeyPath);
        $this->publicKey = File::get($publicKeyPath);
    }

    /**
     * Generate PhizPay signature for request
     */
    protected function generateSignature($method, $url, $timestamp, $nonceStr, $body)
    {
        $message = "$method\n$url\n$timestamp\n$nonceStr\n$body\n";
        openssl_sign($message, $signature, $this->privateKey, 'SHA256');
        return base64_encode($signature);
    }

    /**
     * Verify PhizPay signature from response
     */
    protected function verifySignature($method, $url, $timestamp, $nonceStr, $body, $signature)
    {
        $message = "$method\n$url\n$timestamp\n$nonceStr\n$body\n";
        return openssl_verify($message, base64_decode($signature), $this->publicKey, 'SHA256') === 1;
    }

    /**
     * Add a card to PhizPay
     */
    public function addCard(Request $request)
    {
        $user = auth()->user();
        $nonceStr = Str::random(32);
        $timestamp = time();
        $cardData = $request->validate([
            'card_no' => 'required|string',
            'cvv' => 'required|string',
            'expiration_year' => 'required|string',
            'expiration_month' => 'required|string',
            'holder_name' => 'required|string',
            'documento' => 'required|string',
        ]);

        $body = [
            'appid' => env('PHIZPAY_APPID'),
            'mchid' => $this->mchid,
            'description' => 'Card registration',
            'out_trade_no' => 'CARD_' . Str::random(10),
            'amount' => [
                'total' => 0.01, // Valor simbólico para validação
                'currency' => 'BRL',
            ],
            'name' => $cardData['holder_name'],
            'documento' => $cardData['documento'],
            'email' => $user->email ?? 'user@example.com',
            'celular' => '+55' . ($user->mobile ?? '00000000000'),
            'telefone' => $user->mobile ?? '00000000000',
            'card_no' => $cardData['card_no'],
            'type_card' => '1', // 1 = Crédito
            'bandeira' => $this->detectCardBrand($cardData['card_no']),
            'cvv' => $cardData['cvv'],
            'bank_code' => '0000', // Ajustar conforme necessário
            'bank_branch' => '0000',
            'bank_branch_digit' => '0',
            'expiration_year' => $cardData['expiration_year'],
            'expiration_month' => $cardData['expiration_month'],
            'holder_name' => $cardData['holder_name'],
            'holder_name_documento' => $cardData['documento'],
            'spbill_create_ip' => $request->ip(),
        ];

        $url = '/phiz-microwd-merchapi/pay/transactions/bank';
        $bodyJson = json_encode($body);
        $signature = $this->generateSignature('POST', $url, $timestamp, $nonceStr, $bodyJson);
        $authorization = "Phizpay2-SHA256-RSA2048 mchid=\"{$this->mchid}\",nonce_str=\"{$nonceStr}\",signature=\"{$signature}\",timestamp=\"{$timestamp}\",serial_no=\"{$this->serialNo}\"";

        $response = Http::withHeaders([
            'Authorization' => $authorization,
            'Content-Type' => 'application/json',
        ])->post($this->baseUrl . $url, $body);

        if ($response->successful()) {
            $responseData = $response->json();
            $card = CardInfo::create([
                'id' => Str::uuid(),
                'customer_id' => 'customer_' . $user->id,
                'merchant_id' => $this->mchid,
                'card_token' => $responseData['transaction_id'],
                'last_number' => substr($cardData['card_no'], -4),
                'card_type' => $body['bandeira'],
                'valid_through' => $cardData['expiration_month'] . '/' . $cardData['expiration_year'],
                'user_id' => $user->id,
                'user_role' => $user->hasRole('user') ? 'user' : ($user->hasRole('driver') ? 'driver' : 'owner'),
                'is_default' => false,
            ]);
            return $card;
        }

        Log::error('PhizPay addCard error: ' . $response->body());
        throw new \Exception('Failed to add card to PhizPay');
    }

    /**
     * List cards for the user
     */
    public function listCards()
    {
        $user = auth()->user();
        return CardInfo::where('user_id', $user->id)->where('merchant_id', $this->mchid)->get();
    }

    /**
     * Make a card the default
     */
    public function makeDefaultCard(Request $request)
    {
        $user = auth()->user();
        $cardId = $request->validate(['card_id' => 'required|uuid']);
        CardInfo::where('user_id', $user->id)->update(['is_default' => false]);
        $card = CardInfo::where('id', $cardId['card_id'])->where('user_id', $user->id)->firstOrFail();
        $card->update(['is_default' => true]);
    }

    /**
     * Delete a card
     */
    public function deleteCard(CardInfo $card)
    {
        $card->delete();
    }

    /**
     * Add money to wallet
     */
    public function addMoneyToWallet(Request $request)
    {
        $user = auth()->user();
        $validated = $request->validate([
            'amount' => 'required|numeric|min:0.01',
            'card_id' => 'required|uuid',
        ]);

        $card = CardInfo::where('id', $validated['card_id'])->where('user_id', $user->id)->firstOrFail();
        $nonceStr = Str::random(32);
        $timestamp = time();

        $body = [
            'appid' => env('PHIZPAY_APPID'),
            'mchid' => $this->mchid,
            'description' => 'Add money to wallet',
            'out_trade_no' => 'WALLET_' . Str::random(10) . '_' . $user->id,
            'amount' => [
                'total' => $validated['amount'],
                'currency' => 'BRL',
            ],
            'card_no' => '**** **** **** ' . $card->last_number, // Referência mascarada
            'type_card' => '1', // Crédito
            'bandeira' => $card->card_type,
            'spbill_create_ip' => $request->ip(),
            'callback_url' => env('APP_URL') . '/api/v1/payment/phizpay/webhook',
        ];

        $url = '/phiz-microwd-merchapi/pay/transactions/bank';
        $bodyJson = json_encode($body);
        $signature = $this->generateSignature('POST', $url, $timestamp, $nonceStr, $bodyJson);
        $authorization = "Phizpay2-SHA256-RSA2048 mchid=\"{$this->mchid}\",nonce_str=\"{$nonceStr}\",signature=\"{$signature}\",timestamp=\"{$timestamp}\",serial_no=\"{$this->serialNo}\"";

        $response = Http::withHeaders([
            'Authorization' => $authorization,
            'Content-Type' => 'application/json',
        ])->post($this->baseUrl . $url, $body);

        if ($response->successful()) {
            $responseData = $response->json();
            $walletModel = $user->hasRole('user') ? new \App\Models\Payment\UserWallet() :
                          ($user->hasRole('driver') ? new \App\Models\Payment\DriverWallet() :
                          new \App\Models\Payment\OwnerWallet());
            $wallet = $walletModel::firstOrCreate(['user_id' => $user->id], [
                'amount_added' => 0,
                'amount_balance' => 0,
                'amount_spent' => 0,
                'currency_code' => 'BRL',
            ]);

            $wallet->amount_added += $validated['amount'];
            $wallet->amount_balance += $validated['amount'];
            $wallet->save();

            $historyModel = $user->hasRole('user') ? new \App\Models\Payment\UserWalletHistory() :
                            ($user->hasRole('driver') ? new \App\Models\Payment\DriverWalletHistory() :
                            new \App\Models\Payment\OwnerWalletHistory());
            $historyModel::create([
                'user_id' => $user->id,
                'amount' => $validated['amount'],
                'transaction_id' => $responseData['transaction_id'],
                'is_credit' => true,
                'remarks' => 'Added via PhizPay',
            ]);

            return $wallet;
        }

        Log::error('PhizPay addMoneyToWallet error: ' . $response->body());
        throw new \Exception('Failed to add money to wallet via PhizPay');
    }

    /**
     * Detect card brand based on card number
     */
    protected function detectCardBrand($cardNumber)
    {
        $cardNumber = preg_replace('/\D/', '', $cardNumber);
        if (preg_match('/^4[0-9]{12}(?:[0-9]{3})?$/', $cardNumber)) {
            return 'Visa';
        } elseif (preg_match('/^5[1-5][0-9]{14}$/', $cardNumber)) {
            return 'Mastercard';
        } elseif (preg_match('/^3[47][0-9]{13}$/', $cardNumber)) {
            return 'American Express';
        }
        return 'Unknown';
    }
}