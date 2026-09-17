<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('students', function (Blueprint $table) {
            $table->id();
            $table->foreignId('course_id')->constrained()->restrictOnDelete();
            $table->string('name', 150);
            $table->string('email')->unique();
            $table->string('registration', 20)->unique();
            $table->date('birth_date');
            $table->string('phone', 25)->nullable();
            $table->enum('status', ['ativo', 'trancado', 'concluido'])->default('ativo');
            $table->timestamps();
            $table->index(['status', 'name']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('students');
    }
};
