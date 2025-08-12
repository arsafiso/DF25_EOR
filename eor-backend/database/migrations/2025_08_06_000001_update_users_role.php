<?php
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up()
    {
        Schema::table('users', function (Blueprint $table) {
            // Remove o campo is_admin, se existir
            if (Schema::hasColumn('users', 'is_admin')) {
                $table->dropColumn('is_admin');
            }
            // Adiciona o campo role
            $table->enum('role', ['normal', 'admin', 'superadmin'])->default('normal')->after('email');        });
    }

    public function down()
    {
        Schema::table('users', function (Blueprint $table) {
            // Remove o campo role
            $table->dropColumn('role');
            // Adiciona o campo is_admin novamente
            $table->boolean('is_admin')->default(false)->after('email');
        });
    }
};