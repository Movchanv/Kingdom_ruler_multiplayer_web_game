<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('events', function (Blueprint $table) {
            $table->string('difficulty')->nullable()->after('type');
            $table->foreignId('country_id')->nullable()->after('difficulty')->constrained()->nullOnDelete();
            $table->foreignId('town_id')->nullable()->after('country_id')->constrained()->nullOnDelete();
            $table->string('image')->nullable()->after('description');
        });
    }

    public function down(): void
    {
        Schema::table('events', function (Blueprint $table) {
            $table->dropForeign(['country_id']);
            $table->dropForeign(['town_id']);
            $table->dropColumn(['difficulty', 'country_id', 'town_id', 'image']);
        });
    }
};
