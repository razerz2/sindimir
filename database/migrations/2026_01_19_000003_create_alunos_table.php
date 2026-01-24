<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('alunos', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->nullable()->constrained('users')->nullOnDelete();
            $table->string('cpf', 14)->unique();
            $table->date('data_nascimento')->nullable();
            $table->string('sexo')->nullable();
            $table->string('nome_completo');
            $table->string('nome_social')->nullable();
            $table->string('naturalidade')->nullable();
            $table->string('nacionalidade')->nullable();
            $table->char('uf_naturalidade', 2)->nullable();
            $table->string('nome_pai')->nullable();
            $table->string('nome_mae')->nullable();
            $table->string('endereco')->nullable();
            $table->string('bairro')->nullable();
            $table->char('uf_residencia', 2)->nullable();
            $table->string('municipio')->nullable();
            $table->string('cep', 10)->nullable();
            $table->string('email')->nullable();
            $table->string('celular', 20)->nullable();
            $table->string('telefone', 20)->nullable();
            $table->string('estado_civil')->nullable();
            $table->string('raca_cor')->nullable();
            $table->string('possui_deficiencia')->nullable();
            $table->string('escolaridade')->nullable();
            $table->string('renda_familiar')->nullable();
            $table->boolean('estuda')->nullable();
            $table->boolean('trabalha')->nullable();
            $table->string('situacao_participante')->nullable();
            $table->string('tipo_entidade_origem')->nullable();
            $table->string('numero_cadastro_unico')->nullable();
            $table->boolean('recebe_bolsa_familia')->nullable();
            $table->boolean('responsavel_menor')->nullable();
            $table->unsignedSmallInteger('idade_menor_mais_novo')->nullable();
            $table->boolean('tem_com_quem_deixar_menores')->nullable();
            $table->timestamps();
            $table->softDeletes();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('alunos');
    }
};
