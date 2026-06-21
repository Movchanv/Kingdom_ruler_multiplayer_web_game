<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('town_buildings', function (Blueprint $table) {
            $table->id();
            $table->foreignId('town_id')->constrained()->cascadeOnDelete();
            $table->foreignId('building_id')->constrained();
            $table->unsignedTinyInteger('level')->default(0);
            $table->unsignedInteger('build_progress')->default(0);
            $table->timestamps();

            $table->unique(['town_id', 'building_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('town_buildings');
    }
};
