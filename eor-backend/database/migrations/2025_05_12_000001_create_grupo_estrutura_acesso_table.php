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
        Schema::create('grupo_estrutura_acesso', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('grupo_id'); // Relacionamento com grupos_acesso
            $table->unsignedBigInteger('estrutura_id'); // Relacionamento com estruturas
            $table->enum('nivel_acesso', ['sem_nivel', 'leitura', 'leitura_escrita'])->default('sem_nivel'); // Nível de acesso
            $table->timestamps();

            // Chaves estrangeiras
            $table->foreign('grupo_id')->references('id')->on('grupos_acesso')->onDelete('cascade');
            $table->foreign('estrutura_id')->references('id')->on('estruturas')->onDelete('cascade');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('grupo_estrutura_acesso');
    }
};