<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::dropIfExists('arquivos_classificacao');
        Schema::create('arquivos_estrutura', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('estrutura_id');
            $table->string('nome_arquivo');
            $table->string('tipo')->nullable(); // tipo MIME
            $table->string('categoria'); // classificacao, dam_break, paebm, etc
            $table->bigInteger('tamanho')->nullable();
            $table->boolean('manter_historico')->default(false); // se deve manter histórico de uploads
            $table->unsignedBigInteger('uploaded_by')->nullable();
            $table->timestamp('uploaded_at')->nullable();
            $table->timestamps();

            $table->foreign('estrutura_id')->references('id')->on('estruturas')->onDelete('cascade');
            $table->foreign('uploaded_by')->references('id')->on('users')->onDelete('set null');
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('arquivos_estrutura');
        // Opcional: recriar arquivos_classificacao se necessário
    }
};
