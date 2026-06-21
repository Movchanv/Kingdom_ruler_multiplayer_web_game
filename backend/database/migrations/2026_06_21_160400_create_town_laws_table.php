<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('town_laws', function (Blueprint $table) {
            $table->id();
            $table->foreignId('town_id')->constrained()->cascadeOnDelete();
            $table->foreignId('law_id')->constrained();
            $table->foreignId('law_vote_id')->nullable()->constrained()->nullOnDelete();
            $table->timestamp('applied_at')->nullable();
            $table->timestamp('expires_at')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('town_laws');
    }
};
