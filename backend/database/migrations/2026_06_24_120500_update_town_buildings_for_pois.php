<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('town_buildings', function (Blueprint $table) {
            $table->dropColumn('build_progress');
            $table->json('contributions')->nullable()->after('level');
            $table->unsignedSmallInteger('map_x')->default(0)->after('contributions');
            $table->unsignedSmallInteger('map_y')->default(0)->after('map_x');
            $table->string('image')->nullable()->after('map_y');
        });
    }

    public function down(): void
    {
        Schema::table('town_buildings', function (Blueprint $table) {
            $table->dropColumn(['contributions', 'map_x', 'map_y', 'image']);
            $table->unsignedInteger('build_progress')->default(0);
        });
    }
};
