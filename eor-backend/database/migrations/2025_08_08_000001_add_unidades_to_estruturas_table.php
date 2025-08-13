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
        Schema::table('estruturas', function (Blueprint $table) {
            $table->enum('unidade_elevacao_crista', ['mm','cm','dm','m','dam','hm','km'])->default('m')->after('elevacao_crista');
            $table->enum('unidade_elevacao_base', ['mm','cm','dm','m','dam','hm','km'])->default('m')->after('elevacao_base');
            $table->enum('unidade_altura_maxima_federal', ['mm','cm','dm','m','dam','hm','km'])->default('m')->after('altura_maxima_federal');
            $table->enum('unidade_altura_maxima_estadual', ['mm','cm','dm','m','dam','hm','km'])->default('m')->after('altura_maxima_estadual');
            $table->enum('unidade_area_reservatorio_crista', ['mm²','cm²','dm²','m²','dam²','hm²','km²'])->default('Km²')->after('area_reservatorio_crista');
            $table->enum('unidade_area_reservatorio_soleira', ['mm²','cm²','dm²','m²','dam²','hm²','km²'])->default('Km²')->after('area_reservatorio_soleira');
            $table->enum('unidade_altura_maxima_entre_bermas', ['mm','cm','dm','m','dam','hm','km'])->default('m')->after('altura_maxima_entre_bermas');
            $table->enum('unidade_largura_bermas', ['mm','cm','dm','m','dam','hm','km'])->default('m')->after('largura_bermas');
            $table->enum('unidade_area_bacia_contribuicao', ['mm²','cm²','dm²','m²','dam²','hm²','km²'])->default('km²')->after('area_bacia_contribuicao');
            $table->enum('unidade_area_espelho_dagua', ['mm²','cm²','dm²','m²','dam²','hm²','km²'])->default('Km²')->after('area_espelho_dagua');
            $table->enum('unidade_na_maximo_operacional', ['mm','cm','dm','m','dam','hm','km'])->default('m')->after('na_maximo_operacional');
            $table->enum('unidade_na_maximo_maximorum', ['mm','cm','dm','m','dam','hm','km'])->default('m')->after('na_maximo_maximorum');
            $table->enum('unidade_borda_livre', ['mm','cm','dm','m','dam','hm','km'])->default('m')->after('borda_livre');
            $table->enum('unidade_volume_transito_cheias', ['mm³','cm³','dm³','m³','dam³','hm³','km³'])->default('m³')->after('volume_transito_cheias');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('estruturas', function (Blueprint $table) {
            $table->dropColumn([
                'unidade_elevacao_crista',
                'unidade_elevacao_base',
                'unidade_altura_maxima_federal',
                'unidade_altura_maxima_estadual',
                'unidade_area_reservatorio_crista',
                'unidade_area_reservatorio_soleira',
                'unidade_altura_maxima_entre_bermas',
                'unidade_largura_bermas',
                'unidade_area_bacia_contribuicao',
                'unidade_area_espelho_dagua',
                'unidade_na_maximo_operacional',
                'unidade_na_maximo_maximorum',
                'unidade_borda_livre',
                'unidade_volume_transito_cheias',
            ]);
        });
    }
};
