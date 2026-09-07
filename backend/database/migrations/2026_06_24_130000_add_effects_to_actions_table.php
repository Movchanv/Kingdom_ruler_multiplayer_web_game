<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('actions', function (Blueprint $table): void {
            $table->json('effects')->nullable()->after('base_xp');
        });
    }

    public function down(): void
    {
        Schema::table('actions', function (Blueprint $table): void {
            $table->dropColumn('effects');
        });
    }
};
