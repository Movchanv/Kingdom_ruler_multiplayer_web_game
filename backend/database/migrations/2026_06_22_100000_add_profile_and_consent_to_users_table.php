<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->string('username')->nullable()->unique()->after('id');
            $table->char('country', 2)->nullable()->after('email');
            $table->date('date_of_birth')->nullable()->after('country');
            $table->string('gender')->nullable()->after('date_of_birth');
            $table->timestamp('terms_accepted_at')->nullable()->after('gender');
            $table->string('privacy_policy_version')->nullable()->after('terms_accepted_at');
            $table->dropColumn('name');
        });
    }

    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->string('name')->after('id');
            $table->dropColumn([
                'username',
                'country',
                'date_of_birth',
                'gender',
                'terms_accepted_at',
                'privacy_policy_version',
            ]);
        });
    }
};
