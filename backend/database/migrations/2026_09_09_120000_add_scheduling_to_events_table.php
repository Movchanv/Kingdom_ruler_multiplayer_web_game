<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('events', function (Blueprint $table): void {
            $table->json('requirement')->nullable()->after('effects');
            $table->json('success_effects')->nullable()->after('requirement');
            $table->json('failure_effects')->nullable()->after('success_effects');
            $table->unsignedInteger('delay_min_minutes')->nullable()->after('failure_effects');
            $table->unsignedInteger('delay_max_minutes')->nullable()->after('delay_min_minutes');
            $table->string('icon', 8)->nullable()->after('description');
        });
    }

    public function down(): void
    {
        Schema::table('events', function (Blueprint $table): void {
            $table->dropColumn([
                'requirement',
                'success_effects',
                'failure_effects',
                'delay_min_minutes',
                'delay_max_minutes',
                'icon',
            ]);
        });
    }
};
