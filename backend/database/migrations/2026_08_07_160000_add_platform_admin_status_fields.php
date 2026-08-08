<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('users', function (Blueprint $table): void {
            $table->boolean('is_super_admin')->default(false)->index();
            $table->string('account_status', 20)->default('active')->index();
        });

        Schema::table('restaurants', function (Blueprint $table): void {
            $table->string('platform_status', 20)->default('active')->index();
        });
    }

    public function down(): void
    {
        Schema::table('restaurants', fn (Blueprint $table) => $table->dropColumn('platform_status'));
        Schema::table('users', fn (Blueprint $table) => $table->dropColumn(['is_super_admin', 'account_status']));
    }
};
