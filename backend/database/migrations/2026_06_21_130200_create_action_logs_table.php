<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('action_logs', function (Blueprint $table) {
            $table->id();
            $table->foreignId('game_id')->constrained()->cascadeOnDelete();
            $table->foreignId('player_id')->constrained()->cascadeOnDelete();
            $table->foreignId('action_id')->constrained();
            $table->foreignId('event_id')->nullable()->constrained()->nullOnDelete();
            $table->json('payload')->nullable();
            $table->json('result')->nullable();
            $table->unsignedInteger('xp_gained')->default(0);
            $table->timestamps();

            $table->index(['player_id', 'created_at']);
            $table->index(['game_id', 'created_at']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('action_logs');
    }
};
