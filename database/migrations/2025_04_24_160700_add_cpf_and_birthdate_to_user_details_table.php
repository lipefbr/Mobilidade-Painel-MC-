<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class AddCpfAndBirthdateToUserDetailsTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        // Primeiro adiciona as colunas sem a restrição de unicidade
        Schema::table('user_details', function (Blueprint $table) {
            $table->string('cpf')->nullable()->after('email')->comment('CPF do passageiro'); // Adiciona CPF após email, permitindo nulo inicialmente
            $table->date('data_nascimento')->nullable()->after('cpf')->comment('Data de nascimento do passageiro'); // Adiciona data de nascimento após CPF
        });
        
        // Depois adiciona a restrição de unicidade
        Schema::table('user_details', function (Blueprint $table) {
            $table->unique('cpf'); // Adiciona a restrição de unicidade para CPF
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::table('user_details', function (Blueprint $table) {
            $table->dropColumn(['cpf', 'data_nascimento']); // Remove as colunas ao reverter
        });
    }
}
