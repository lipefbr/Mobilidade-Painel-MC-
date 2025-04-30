<?php

// Script para testar a atualização de CPF e data de nascimento de um motorista

require_once __DIR__ . '/vendor/autoload.php';

// Carregar o ambiente Laravel
$app = require_once __DIR__ . '/bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

use App\Models\Admin\Driver;
use Illuminate\Support\Facades\DB;

// Selecionar um motorista para atualizar (ID 1 neste exemplo)
$driver_id = 1;
$driver = Driver::find($driver_id);

if (!$driver) {
    echo "Motorista com ID $driver_id não encontrado.\n";
    exit;
}

echo "=== Dados do motorista antes da atualização ===\n";
echo "ID: " . $driver->id . "\n";
echo "Nome: " . $driver->name . "\n";
echo "Email: " . $driver->email . "\n";
echo "CPF: " . ($driver->cpf ?: 'NÃO DEFINIDO') . "\n";
echo "Data de Nascimento: " . ($driver->data_nascimento ?: 'NÃO DEFINIDA') . "\n";

// Atualizar CPF e data de nascimento
$driver->cpf = '123.456.789-00';
$driver->data_nascimento = '1990-01-01';
$driver->save();

// Recarregar o motorista do banco de dados
$driver = Driver::find($driver_id);

echo "\n=== Dados do motorista após a atualização ===\n";
echo "ID: " . $driver->id . "\n";
echo "Nome: " . $driver->name . "\n";
echo "Email: " . $driver->email . "\n";
echo "CPF: " . ($driver->cpf ?: 'NÃO DEFINIDO') . "\n";
echo "Data de Nascimento: " . ($driver->data_nascimento ?: 'NÃO DEFINIDA') . "\n";

echo "\nAtualização concluída.\n";
