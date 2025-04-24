<?php

namespace App\Http\Controllers\Web;

use App\Http\Controllers\Web\BaseController;
use Illuminate\Http\Request;
use App\Models\User;
use GuzzleHttp\Client;
use Illuminate\Support\Facades\Log;

class CredpayController extends BaseController
{
    protected $client;

    public function __construct()
    {
        $this->client = new Client();
    }

    /**
     * Exibe o formulário de checkout para o pagamento.
     *
     * @param Request $request
     * @return \Illuminate\View\View
     */
    public function credpay(Request $request)
    {
        // Busca o usuário pelo ID
        $user = User::find(request()->input('user_id'));

        // Extrai parâmetros da requisição
        $amount = $request->input('amount');
        $name = $user->name ?? 'bala';
        $email = $user->email ?? 'balathemask@gmail.com';
        $mobile = $user->mobile ?? '9790200663';
        $currency = $user->countryDetail->currency_code ?? "INR";
        $payment_for = $request->input('payment_for');
        $request_id = $request->input('request_id') ?? "test";

        // Redirecionamento para o formulário de checkout
        return view('credpay.checkout', compact('amount', 'currency', 'payment_for', 'request_id', 'user'));
    }

    /**
     * Processa o pagamento com os dados do cartão.
     *
     * @param Request $request
     * @return \Illuminate\Http\RedirectResponse
     */
    public function processPayment(Request $request)
    {
        // Validação dos dados do formulário
        $request->validate([
            'card_number' => 'required|string',
            'card_expiry' => 'required|string',
            'card_cvc' => 'required|string',
            'card_holder' => 'required|string',
        ]);

        // Captura os dados do cartão
        $cardNumber = $request->input('card_number');
        $cardExpiry = $request->input('card_expiry');
        $cardCvc = $request->input('card_cvc');
        $cardHolder = $request->input('card_holder');

        try {
            // Autenticação na CredPay
            $token = $this->getCredpayToken();

            // Processa o pagamento com a CredPay
            $paymentResult = $this->processPaymentWithCredPay($token, $cardNumber, $cardExpiry, $cardCvc, $cardHolder);

            // Redireciona para a página de sucesso
            return redirect()->route('payment.success')->with('success', 'Pagamento processado com sucesso!');
        } catch (\Exception $e) {
            // Log do erro e redirecionamento para a página de erro
            Log::error('Erro ao processar pagamento:', ['error' => $e->getMessage()]);
            return redirect()->route('payment.error')->with('error', 'Erro ao processar o pagamento: ' . $e->getMessage());
        }
    }

    /**
     * Obtém o token de autenticação da CredPay.
     *
     * @return string
     * @throws \Exception
     */
    private function getCredpayToken()
    {
        $url = "https://api-homolog.credpay.com.br/AuthCredPay";

        try {
            $response = $this->client->post($url, [
                'form_params' => [
                    'User' => 'vaptvupthomolog', // Credencial de homologação
                    'Key' => '1234567890',       // Senha de homologação
                    'grant_type' => 'password'
                ]
            ]);

            // Verifica se a requisição foi bem-sucedida
            if ($response->getStatusCode() !== 200) {
                throw new \Exception('Erro na autenticação: Status ' . $response->getStatusCode());
            }

            $data = json_decode($response->getBody(), true);

            // Verifica se o token foi retornado
            if (isset($data['access_token'])) {
                return $data['access_token'];
            } else {
                throw new \Exception('Token de acesso não encontrado na resposta da API.');
            }
        } catch (\Exception $e) {
            Log::error('Erro ao obter token da CredPay:', ['error' => $e->getMessage()]);
            throw new \Exception('Erro ao obter token da CredPay: ' . $e->getMessage());
        }
    }

    /**
     * Processa o pagamento com a CredPay.
     *
     * @param string $token
     * @param string $cardNumber
     * @param string $cardExpiry
     * @param string $cardCvc
     * @param string $cardHolder
     * @return mixed
     * @throws \Exception
     */
    private function processPaymentWithCredPay($token, $cardNumber, $cardExpiry, $cardCvc, $cardHolder)
    {
        $url = "https://api-homolog.credpay.com.br/ProcessarPagamento";

        try {
            $response = $this->client->post($url, [
                'headers' => [
                    'Authorization' => 'Bearer ' . $token,
                    'Content-Type' => 'application/json'
                ],
                'json' => [
                    'card_number' => $cardNumber,
                    'card_expiry' => $cardExpiry,
                    'card_cvc' => $cardCvc,
                    'card_holder' => $cardHolder,
                    // Outros dados necessários para o pagamento
                ]
            ]);

            // Verifica se a requisição foi bem-sucedida
            if ($response->getStatusCode() !== 200) {
                throw new \Exception('Erro ao processar pagamento: Status ' . $response->getStatusCode());
            }

            $data = json_decode($response->getBody(), true);

            // Verifica se o pagamento foi aprovado
            if (isset($data['status']) && $data['status'] === 'approved') {
                return $data;
            } else {
                throw new \Exception('Pagamento não aprovado: ' . json_encode($data));
            }
        } catch (\Exception $e) {
            Log::error('Erro ao processar pagamento com a CredPay:', ['error' => $e->getMessage()]);
            throw new \Exception('Erro ao processar pagamento com a CredPay: ' . $e->getMessage());
        }
    }
}