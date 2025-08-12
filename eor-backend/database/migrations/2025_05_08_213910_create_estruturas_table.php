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
        Schema::create('estruturas', function (Blueprint $table) {
            $table->id();
            $table->string('finalidade')->nullable();
            $table->text('projetistas')->nullable();
            $table->string('status')->nullable();
            $table->string('classificacao_federal')->nullable();
            $table->string('classificacao_estadual')->nullable();
            $table->string('classificacao_cda')->nullable();
            $table->float('elevacao_crista')->nullable();
            $table->string('altura_maxima_federal')->nullable();
            $table->string('altura_maxima_estadual')->nullable();
            $table->string('largura_coroamento')->nullable();
            $table->float('area_reservatorio_crista')->nullable();
            $table->float('area_reservatorio_soleira')->nullable();
            $table->string('elevacao_base')->nullable();
            $table->float('altura_maxima_entre_bermas')->nullable();
            $table->string('largura_bermas')->nullable();
            $table->text('tipo_secao')->nullable();
            $table->text('drenagem_interna')->nullable();
            $table->text('instrumentacao')->nullable();
            $table->text('fundacao')->nullable();
            $table->text('analises_estabilidade')->nullable();
            $table->float('area_bacia_contribuicao')->nullable();
            $table->float('area_espelho_dagua')->nullable();
            $table->float('na_maximo_operacional')->nullable();
            $table->float('na_maximo_maximorum')->nullable();
            $table->float('borda_livre')->nullable();
            $table->float('volume_transito_cheias')->nullable();
            $table->text('sistema_extravasor')->nullable();
            $table->unsignedBigInteger('created_by')->nullable(); // ID do usuário externo que criou o registro
            $table->unsignedBigInteger('updated_by')->nullable(); // ID do usuário externo que atualizou o registro
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('estruturas');
    }
};
