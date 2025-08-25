<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
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
        DB::table('estruturas')->update([
            'classificacao_federal' => null,
            'classificacao_estadual' => null,
        ]);

        Schema::table('estruturas', function (Blueprint $table) {
            $table->unsignedInteger('classificacao_federal')->nullable()->change();
            $table->unsignedInteger('classificacao_estadual')->nullable()->change();
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::table('estruturas', function (Blueprint $table) {
            $table->string('classificacao_federal')->nullable()->change();
            $table->string('classificacao_estadual')->nullable()->change();
        });
    }
};
