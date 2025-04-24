<!-- resources/views/errors/payment.blade.php -->
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Erro no Pagamento</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
</head>
<body>
    <div class="container mt-5">
        <h1>Erro no Pagamento</h1>
        @if (session('error'))
            <p>{{ session('error') }}</p>
        @endif
        <a href="/" class="btn btn-primary">Voltar para a página inicial</a>
    </div>
</body>
</html>