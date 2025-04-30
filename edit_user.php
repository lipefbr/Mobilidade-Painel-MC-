<?php

require __DIR__.'/vendor/autoload.php';

$app = require_once __DIR__.'/bootstrap/app.php';

$app->make('Illuminate\Contracts\Console\Kernel')->bootstrap();

use App\Models\User;
use App\Base\Constants\Auth\Role as RoleSlug;

try {
    // Buscar o usuário pelo ID
    $user = User::find(9);
    
    if (!$user) {
        echo "Usuário com ID 9 não encontrado.\n";
        exit;
    }
    
    echo "Dados atuais do usuário:\n";
    echo "ID: " . $user->id . "\n";
    echo "Nome: " . $user->name . "\n";
    echo "E-mail: " . $user->email . "\n";
    echo "Celular: " . $user->mobile . "\n";
    echo "Data de criação: " . $user->created_at . "\n\n";
    
    // Atualizar os dados do usuário
    $user->name = "Leonardo Atualizado";
    $user->email = "dadka@ggg.co"; // Mantendo o mesmo email
    
    // Salvar as alterações
    $user->save();
    
    echo "Usuário atualizado com sucesso!\n\n";
    
    echo "Novos dados do usuário:\n";
    echo "ID: " . $user->id . "\n";
    echo "Nome: " . $user->name . "\n";
    echo "E-mail: " . $user->email . "\n";
    echo "Celular: " . $user->mobile . "\n";
    echo "Data de atualização: " . $user->updated_at . "\n";
    
} catch (\Exception $e) {
    echo "Erro ao editar usuário: " . $e->getMessage() . "\n";
    echo "Arquivo: " . $e->getFile() . "\n";
    echo "Linha: " . $e->getLine() . "\n";
}
