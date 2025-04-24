<?php
use Carbon\Carbon;

// Dados recebidos do controlador
$amount = $amount; // Valor do pagamento
$currency = $currency; // Moeda
$payment_for = $payment_for; // Finalidade do pagamento (wallet, corrida, etc.)
$request_id = $request_id; // ID da requisição
$user = $user; // Dados do usuário
$preOperation = $preOperation; // Dados da pré-operação retornados pela CredPay

// Link de pagamento gerado pela CredPay
$payment_link = $preOperation['link'];
?>

<!DOCTYPE html>
<html>
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>CredPay - Checkout</title>
    <style>
        body {
            text-align: center;
            padding: 40px 0;
        }
        .card {
            background: white;
            padding: 60px;
            border-radius: 4px;
            box-shadow: 0 2px 3px #C8D0D8;
            display: inline-block;
            margin: 0 auto;
        }
        .center {
            display: flex;
            flex-direction: column;
            align-items: center;
            justify-content: center;
            position: absolute;
            top: 50%;
            left: 50%;
            transform: translate(-50%, -50%);
        }
        .button {
            margin-top: 20px;
        }
        .button a {
            background-color: #007bff;
            color: white;
            padding: 10px 20px;
            text-decoration: none;
            border-radius: 5px;
            font-size: 16px;
        }
        .button a:hover {
            background-color: #0056b3;
        }
    </style>
</head>
<body>
    <div class="center">
        <!-- Logo da CredPay (substitua pelo caminho correto da imagem) -->
        <img src="{{ asset('assets/img/credpay.png') }}" class="img-fluid" width="500px">

        <!-- Exibição do valor do pagamento -->
        <div class="amount-display text-center">
            <h1>{{ $currency }} {{ $amount }}</h1>
        </div>

        <!-- Botão de redirecionamento para o pagamento -->
        <div class="button">
            <a href="{{ $payment_link }}" target="_blank">Pagar com CredPay</a>
        </div>
    </div>
</body>
</html>