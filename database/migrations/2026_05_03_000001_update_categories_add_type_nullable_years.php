<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('categories', function (Blueprint $table) {
            $table->integer('birth_year_min')->nullable()->change();
            $table->integer('birth_year_max')->nullable()->change();
            $table->enum('type', ['youth', 'senior', 'loisirs'])->default('youth')->after('birth_year_max');
        });
    }

    public function down(): void
    {
        Schema::table('categories', function (Blueprint $table) {
            $table->dropColumn('type');
            $table->integer('birth_year_min')->nullable(false)->change();
            $table->integer('birth_year_max')->nullable(false)->change();
        });
    }
};
