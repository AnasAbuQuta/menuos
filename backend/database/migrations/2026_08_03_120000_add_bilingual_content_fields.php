<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('restaurants', function (Blueprint $table): void {
            $table->string('name_ar')->nullable()->after('name');
            $table->string('name_en')->nullable()->after('name_ar');
            $table->text('description_ar')->nullable()->after('description');
            $table->text('description_en')->nullable()->after('description_ar');
            $table->string('default_language', 2)->default('ar')->after('description_en');
        });
        Schema::table('categories', function (Blueprint $table): void {
            $table->string('name_ar', 120)->nullable()->after('name');
            $table->string('name_en', 120)->nullable()->after('name_ar');
        });
        Schema::table('menu_items', function (Blueprint $table): void {
            $table->string('name_ar', 160)->nullable()->after('name');
            $table->string('name_en', 160)->nullable()->after('name_ar');
            $table->text('description_ar')->nullable()->after('description');
            $table->text('description_en')->nullable()->after('description_ar');
        });

        DB::table('restaurants')->whereNull('name_ar')->update(['name_ar' => DB::raw('name')]);
        DB::table('restaurants')->whereNull('description_ar')->update(['description_ar' => DB::raw('description')]);
        DB::table('categories')->whereNull('name_ar')->update(['name_ar' => DB::raw('name')]);
        DB::table('menu_items')->whereNull('name_ar')->update(['name_ar' => DB::raw('name')]);
        DB::table('menu_items')->whereNull('description_ar')->update(['description_ar' => DB::raw('description')]);
    }

    public function down(): void
    {
        Schema::table('menu_items', fn (Blueprint $table) => $table->dropColumn(['name_ar', 'name_en', 'description_ar', 'description_en']));
        Schema::table('categories', fn (Blueprint $table) => $table->dropColumn(['name_ar', 'name_en']));
        Schema::table('restaurants', fn (Blueprint $table) => $table->dropColumn(['name_ar', 'name_en', 'description_ar', 'description_en', 'default_language']));
    }
};
