<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('categories', function (Blueprint $table) {
            $table->id();
            $table->string('name', 100);
            $table->string('code', 20)->unique();
            $table->text('description')->nullable();
            $table->timestamps();
        });
        Schema::create('books', function (Blueprint $table) {
            $table->id();
            $table->foreignId('category_id')->constrained()->restrictOnDelete();
            $table->string('title', 200);
            $table->string('author', 150);
            $table->string('code', 20)->unique();
            $table->unsignedSmallInteger('publication_year');
            $table->string('publisher', 150)->nullable();
            $table->enum('status', ['disponivel', 'emprestado', 'indisponivel'])->default('disponivel');
            $table->text('synopsis')->nullable();
            $table->timestamps();
            $table->index(['status', 'title']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('books');
        Schema::dropIfExists('categories');
    }
};
