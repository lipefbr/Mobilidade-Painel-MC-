<?php

require __DIR__.'/vendor/autoload.php';

$app = require_once __DIR__.'/bootstrap/app.php';

$app->make('Illuminate\Contracts\Console\Kernel')->bootstrap();

use App\Models\User;
use App\Base\Constants\Auth\Role as RoleSlug;

try {
    // Verificar a conexão com o banco de dados
    $dbname = \Illuminate\Support\Facades\DB::connection()->getDatabaseName();
    echo "Conexão bem-sucedida com o banco de dados: " . $dbname . "\n\n";
    
    // Listar todos os usuários
    $users = User::all();
    
    echo "Total de usuários no banco de dados: " . $users->count() . "\n\n";
    
    echo "Lista de usuários:\n";
    echo "----------------\n";
    
    foreach ($users as $user) {
        echo "ID: " . $user->id . "\n";
        echo "Nome: " . $user->name . "\n";
        echo "E-mail: " . $user->email . "\n";
        echo "Celular: " . $user->mobile . "\n";
        echo "Data de criação: " . $user->created_at . "\n";
        echo "----------------\n";
    }
    
    // Listar apenas usuários com a função USER
    $userRoleUsers = User::whereHas('roles', function($query) {
        $query->where('slug', RoleSlug::USER);
    })->get();
    
    echo "\nTotal de usuários com a função USER: " . $userRoleUsers->count() . "\n\n";
    
    echo "Lista de usuários com a função USER:\n";
    echo "----------------\n";
    
    foreach ($userRoleUsers as $user) {
        echo "ID: " . $user->id . "\n";
        echo "Nome: " . $user->name . "\n";
        echo "E-mail: " . $user->email . "\n";
        echo "Celular: " . $user->mobile . "\n";
        echo "Data de criação: " . $user->created_at . "\n";
        echo "----------------\n";
    }
    
} catch (\Exception $e) {
    echo "Erro: " . $e->getMessage() . "\n";
    echo "Arquivo: " . $e->getFile() . "\n";
    echo "Linha: " . $e->getLine() . "\n";
}
