<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('towns', function (Blueprint $table) {
            $table->dropForeign(['player_id']);
            $table->dropColumn('player_id');
            $table->unsignedSmallInteger('map_x')->default(0)->after('name');
            $table->unsignedSmallInteger('map_y')->default(0)->after('map_x');
            $table->timestamp('destroyed_at')->nullable()->after('loyalty');
        });
    }

    public function down(): void
    {
        Schema::table('towns', function (Blueprint $table) {
            $table->dropColumn(['map_x', 'map_y', 'destroyed_at']);
            $table->foreignId('player_id')->nullable()->constrained()->nullOnDelete();
        });
    }
};
