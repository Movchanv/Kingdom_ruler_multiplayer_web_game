<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('town_events', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('game_id')->constrained()->cascadeOnDelete();
            $table->foreignId('town_id')->constrained()->cascadeOnDelete();
            $table->foreignId('event_id')->constrained();
            $table->string('status')->default('pending');

            $table->timestamp('resolves_at');
            $table->timestamp('resolved_at')->nullable();

            $table->json('requirement')->nullable();
            $table->json('success_effects')->nullable();
            $table->json('failure_effects')->nullable();
            $table->decimal('intensity', 4, 2)->default(1);

            $table->json('outcome')->nullable();

            $table->foreignId('triggered_by')->nullable()->constrained('users')->nullOnDelete();

            $table->timestamps();

            $table->index(['town_id', 'status']);
            $table->index(['status', 'resolves_at']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('town_events');
    }
};
