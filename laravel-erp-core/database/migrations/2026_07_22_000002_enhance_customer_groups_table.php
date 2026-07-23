<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('customer_groups', function (Blueprint $table) {
            $table->boolean('show_prices')->default(true)->after('discount_percent');
            $table->string('price_display_method', 30)->default('tax_excluded')->after('show_prices');
            $table->boolean('is_system')->default(false)->after('active');
            $table->json('meta')->nullable()->after('description');
        });
    }

    public function down(): void
    {
        Schema::table('customer_groups', function (Blueprint $table) {
            $table->dropColumn(['show_prices', 'price_display_method', 'is_system', 'meta']);
        });
    }
};
