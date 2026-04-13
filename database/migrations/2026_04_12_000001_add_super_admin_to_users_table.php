<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // Soft deletes for gimnasios (safe delete instead of cascade destroy)
        Schema::table('gimnasios', function (Blueprint $table) {
            $table->softDeletes();
        });

        // Make gimnasio_id nullable (super_admin has no gimnasio)
        // Change rol from strict enum to string so super_admin value is accepted.
        // Application-layer validation (FormRequests) enforces allowed values per role type.
        Schema::table('users', function (Blueprint $table) {
            $table->unsignedBigInteger('gimnasio_id')->nullable()->change();
            $table->string('rol', 20)->default('entrenador')->change();
        });
    }

    public function down(): void
    {
        Schema::table('gimnasios', function (Blueprint $table) {
            $table->dropSoftDeletes();
        });

        Schema::table('users', function (Blueprint $table) {
            $table->unsignedBigInteger('gimnasio_id')->nullable(false)->change();
            $table->enum('rol', ['admin', 'entrenador'])->default('entrenador')->change();
        });
    }
};
