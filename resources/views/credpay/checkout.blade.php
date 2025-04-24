<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Finalizar Compra</title>
    <!-- Bootstrap CSS -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <!-- Estilos personalizados -->
    <style>
        body {
            background-color: #f8f9fa;
            font-family: 'Arial', sans-serif;
        }
        .checkout-container {
            max-width: 100%;
            margin: 0;
            padding: 20px;
            background: #fff;
            min-height: 100vh;
        }
        .checkout-header {
            text-align: center;
            margin-bottom: 20px;
            padding: 20px 0;
            background: #007bff;
            color: #fff;
            border-radius: 0 0 20px 20px;
        }
        .checkout-header h1 {
            font-size: 24px;
            font-weight: bold;
            margin: 0;
        }
        .checkout-header p {
            font-size: 16px;
            margin: 10px 0 0;
        }
        .checkout-details {
            margin-bottom: 20px;
            padding: 20px;
            background: #fff;
            border-radius: 10px;
            box-shadow: 0 2px 4px rgba(0, 0, 0, 0.1);
        }
        .checkout-details h5 {
            font-size: 18px;
            font-weight: bold;
            color: #333;
            margin-bottom: 15px;
        }
        .checkout-details p {
            font-size: 16px;
            color: #666;
            margin: 0;
        }
        .form-label {
            font-weight: bold;
            color: #555;
            font-size: 14px;
        }
        .form-control {
            border-radius: 8px;
            border: 1px solid #ddd;
            padding: 12px;
            font-size: 16px;
            margin-bottom: 15px;
        }
        .form-control:focus {
            border-color: #007bff;
            box-shadow: 0 0 5px rgba(0, 123, 255, 0.5);
        }
        .btn-primary {
            width: 100%;
            padding: 15px;
            font-size: 16px;
            font-weight: bold;
            background-color: #007bff;
            border: none;
            border-radius: 8px;
            margin-top: 10px;
        }
        .btn-primary:hover {
            background-color: #0056b3;
        }
        @media (max-width: 576px) {
            .checkout-header {
                padding: 15px 0;
            }
            .checkout-header h1 {
                font-size: 20px;
            }
            .checkout-header p {
                font-size: 14px;
            }
            .checkout-details {
                padding: 15px;
            }
            .checkout-details h5 {
                font-size: 16px;
            }
            .checkout-details p {
                font-size: 14px;
            }
            .form-control {
                padding: 10px;
                font-size: 14px;
            }
            .btn-primary {
                padding: 12px;
                font-size: 14px;
            }
        }
    </style>
</head>
<body>
    <div class="checkout-container">
        <!-- Cabeçalho -->
        <div class="checkout-header">
            <h1>Finalizar Compra</h1>
            <p>Olá, Preencha os dados do seu cartão.</p>
        </div>

        <!-- Detalhes da Compra -->
        <div class="checkout-details">
            <h5>Detalhes da Compra</h5>
            <p><strong>Valor:</strong> {{ $currency }} {{ $amount }}</p>
            <p><strong>Descrição:</strong> {{ $payment_for }}</p>
        </div>

        <!-- Formulário de Pagamento -->
        <div class="checkout-details">
            <h5>Dados do Cartão</h5>
            <form action="{{ route('credpay.process') }}" method="POST" id="payment-form">
                @csrf
                <!-- Número do Cartão -->
                <div class="mb-3">
                    <label for="card-number" class="form-label">Número do Cartão</label>
                    <input type="text" class="form-control" id="card-number" name="card_number" placeholder="1234 5678 9012 3456" required>
                </div>

                <!-- Validade e CVC -->
                <div class="row">
                    <div class="col-md-6 mb-3">
                        <label for="card-expiry" class="form-label">Validade (MM/AA)</label>
                        <input type="text" class="form-control" id="card-expiry" name="card_expiry" placeholder="MM/AA" required>
                    </div>
                    <div class="col-md-6 mb-3">
                        <label for="card-cvc" class="form-label">Código de Segurança (CVC)</label>
                        <input type="text" class="form-control" id="card-cvc" name="card_cvc" placeholder="CVC" required>
                    </div>
                </div>

                <!-- Nome do Titular -->
                <div class="mb-3">
                    <label for="card-holder" class="form-label">Nome do Titular</label>
                    <input type="text" class="form-control" id="card-holder" name="card_holder" placeholder="Nome como no cartão" required>
                </div>

                <!-- Botão de Pagamento -->
                <button type="submit" class="btn btn-primary">Pagar Agora</button>
            </form>
        </div>
    </div>

    <!-- Bootstrap JS (opcional, apenas se precisar de funcionalidades JS do Bootstrap) -->
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>