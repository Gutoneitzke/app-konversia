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
        Schema::create('user_departments', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained();
            $table->foreignId('department_id')->constrained();
            $table->boolean('is_primary')->default(false);
            $table->boolean('active')->default(true);
            $table->timestamps();

            $table->unique(['user_id', 'department_id']);

            // Cada usuário pode ter apenas um departamento primário
            $table->unique(['user_id', 'is_primary'], 'unique_primary_department');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('user_departments');
    }
};
