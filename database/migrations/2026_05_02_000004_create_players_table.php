<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('players', function (Blueprint $table) {
            $table->id();
            $table->foreignId('category_id')->nullable()->constrained()->nullOnDelete();
            $table->string('last_name');
            $table->string('first_name');
            $table->date('birth_date');
            $table->string('gender')->nullable();
            $table->string('license_number')->nullable();
            $table->string('photo')->nullable();
            $table->boolean('has_image_rights')->default(false);
            $table->timestamps();

            $table->unique(['last_name', 'first_name', 'birth_date']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('players');
    }
};
