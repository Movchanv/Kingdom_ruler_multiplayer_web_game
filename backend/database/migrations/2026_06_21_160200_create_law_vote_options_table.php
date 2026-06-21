<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('law_vote_options', function (Blueprint $table) {
            $table->id();
            $table->foreignId('law_vote_id')->constrained()->cascadeOnDelete();
            $table->foreignId('law_id')->constrained();
            $table->timestamps();

            $table->unique(['law_vote_id', 'law_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('law_vote_options');
    }
};
