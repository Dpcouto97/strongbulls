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
        Schema::create('plan_client', function (Blueprint $table) {
            $table->id();
            //onDelete() Garante que se um plano de treino ou cliente for eliminado todas as associacoes desta tabela tambem sao eliminadas.
            $table->foreignId('plan_id')->constrained()->onDelete('cascade');
            $table->foreignId('client_id')->constrained()->onDelete('cascade');
            $table->timestamps();

            // Assegura que nao existe 2 entradas iguais com a mesma associacao.
            $table->unique(['plan_id', 'client_id']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('plan_client');
    }
};

