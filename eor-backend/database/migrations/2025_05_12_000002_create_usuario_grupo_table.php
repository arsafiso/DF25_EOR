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
        Schema::create('usuario_grupo', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('usuario_id'); // Relacionamento com usuários
            $table->unsignedBigInteger('grupo_id'); // Relacionamento com grupos_acesso
            $table->timestamps();

            // Chaves estrangeiras
            $table->foreign('usuario_id')->references('id')->on('users')->onDelete('cascade');
            $table->foreign('grupo_id')->references('id')->on('grupos_acesso')->onDelete('cascade');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('usuario_grupo');
    }
};