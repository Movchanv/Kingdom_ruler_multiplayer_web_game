<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('building_levels', function (Blueprint $table) {
            $table->id();
            $table->foreignId('building_id')->constrained()->cascadeOnDelete();
            $table->unsignedTinyInteger('level');
            $table->json('bonus')->nullable();
            $table->json('cost')->nullable();
            $table->unsignedInteger('build_points')->default(0);
            $table->timestamps();

            $table->unique(['building_id', 'level']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('building_levels');
    }
};
