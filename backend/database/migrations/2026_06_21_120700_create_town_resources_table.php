<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('town_resources', function (Blueprint $table) {
            $table->id();
            $table->foreignId('town_id')->constrained()->cascadeOnDelete();
            $table->foreignId('resource_id')->constrained();
            $table->unsignedBigInteger('amount')->default(0);
            $table->unsignedBigInteger('capacity')->nullable();
            $table->timestamps();

            $table->unique(['town_id', 'resource_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('town_resources');
    }
};
