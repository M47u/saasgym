<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('planes', function (Blueprint $table) {
            $table->dropColumn('precio');
            $table->decimal('precio_efectivo', 10, 2)->nullable()->after('descripcion');
            $table->decimal('precio_digital',  10, 2)->nullable()->after('precio_efectivo');
        });
    }

    public function down(): void
    {
        Schema::table('planes', function (Blueprint $table) {
            $table->dropColumn(['precio_efectivo', 'precio_digital']);
            $table->decimal('precio', 10, 2)->nullable()->after('descripcion');
        });
    }
};
