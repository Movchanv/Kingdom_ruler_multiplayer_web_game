<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('games', function (Blueprint $table): void {
            $table->timestamp('last_world_event_at')->nullable()->after('results');
        });
    }

    public function down(): void
    {
        Schema::table('games', function (Blueprint $table): void {
            $table->dropColumn('last_world_event_at');
        });
    }
};
