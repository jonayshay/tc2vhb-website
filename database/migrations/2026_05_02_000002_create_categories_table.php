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
            $table->foreignId('season_id')->constrained()->cascadeOnDelete();
            $table->string('name');
            $table->string('slug');
            $table->enum('gender', ['M', 'F', 'Mixte']);
            $table->integer('birth_year_min');
            $table->integer('birth_year_max');
            $table->timestamps();

            $table->unique(['slug', 'season_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('categories');
    }
};
