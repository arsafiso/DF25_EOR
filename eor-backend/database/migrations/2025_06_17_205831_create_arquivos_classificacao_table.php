<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('arquivos_classificacao', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('estrutura_id');
            $table->string('nome_arquivo');
            $table->string('tipo')->nullable();
            $table->unsignedBigInteger('tamanho')->nullable();
            $table->timestamps();

            $table->foreign('estrutura_id')->references('id')->on('estruturas')->onDelete('cascade');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('arquivos_classificacao');
    }
};