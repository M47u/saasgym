<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (Schema::hasColumn('rutinas', 'socio_id') || Schema::hasColumn('rutinas', 'fecha_inicio') || Schema::hasColumn('rutinas', 'fecha_fin')) {
            Schema::table('rutinas', function (Blueprint $table) {
                if (Schema::hasColumn('rutinas', 'socio_id')) {
                    $table->dropConstrainedForeignId('socio_id');
                }
                if (Schema::hasColumn('rutinas', 'fecha_inicio')) {
                    $table->dropColumn('fecha_inicio');
                }
                if (Schema::hasColumn('rutinas', 'fecha_fin')) {
                    $table->dropColumn('fecha_fin');
                }
            });
        }
    }

    public function down(): void
    {
        Schema::table('rutinas', function (Blueprint $table) {
            if (!Schema::hasColumn('rutinas', 'socio_id')) {
                $table->foreignId('socio_id')->nullable()->after('gimnasio_id')->constrained('socios')->cascadeOnDelete();
            }
            if (!Schema::hasColumn('rutinas', 'fecha_inicio')) {
                $table->date('fecha_inicio')->nullable()->after('descripcion');
            }
            if (!Schema::hasColumn('rutinas', 'fecha_fin')) {
                $table->date('fecha_fin')->nullable()->after('fecha_inicio');
            }
        });
    }
};
