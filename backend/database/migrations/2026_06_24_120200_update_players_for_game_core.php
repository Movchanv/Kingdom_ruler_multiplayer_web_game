<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('players', function (Blueprint $table) {
            $table->dropForeign(['title_id']);
            $table->dropColumn(['xp', 'title_id', 'display_name']);
            $table->foreignId('current_town_id')->nullable()->after('country_id')->constrained('towns')->nullOnDelete();
        });
    }

    public function down(): void
    {
        Schema::table('players', function (Blueprint $table) {
            $table->dropForeign(['current_town_id']);
            $table->dropColumn('current_town_id');
            $table->foreignId('title_id')->nullable()->constrained()->nullOnDelete();
            $table->string('display_name');
            $table->unsignedInteger('xp')->default(0);
        });
    }
};
