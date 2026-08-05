<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('categories', function (Blueprint $table): void {
            $table->index(['restaurant_id', 'is_active', 'sort_order', 'id'], 'categories_public_menu_idx');
        });
        Schema::table('menu_items', function (Blueprint $table): void {
            $table->index(['restaurant_id', 'is_available', 'category_id', 'sort_order', 'id'], 'menu_items_public_menu_idx');
        });
    }

    public function down(): void
    {
        Schema::table('menu_items', fn (Blueprint $table) => $table->dropIndex('menu_items_public_menu_idx'));
        Schema::table('categories', fn (Blueprint $table) => $table->dropIndex('categories_public_menu_idx'));
    }
};
