<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('feature_product', function (Blueprint $table) {
            $table->id();
            $table->foreignId('product_id')->constrained()->cascadeOnDelete();
            $table->foreignId('feature_id')->constrained()->cascadeOnDelete();
            $table->foreignId('feature_value_id')->constrained()->cascadeOnDelete();
            $table->timestamps();

            $table->unique(['product_id', 'feature_id']);
        });

        Schema::create('product_combinations', function (Blueprint $table) {
            $table->id();
            $table->foreignId('product_id')->constrained()->cascadeOnDelete();
            $table->string('reference', 64)->nullable();
            $table->decimal('quantity', 12, 2)->default(0);
            $table->decimal('price_impact', 12, 2)->default(0);
            $table->boolean('active')->default(true);
            $table->unsignedInteger('position')->default(0);
            $table->timestamps();
        });

        Schema::create('product_combination_values', function (Blueprint $table) {
            $table->id();
            $table->foreignId('product_combination_id')->constrained('product_combinations')->cascadeOnDelete();
            $table->foreignId('attribute_value_id')->constrained('attribute_values')->cascadeOnDelete();
            $table->timestamps();

            $table->unique(['product_combination_id', 'attribute_value_id'], 'pcv_combo_value_unique');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('product_combination_values');
        Schema::dropIfExists('product_combinations');
        Schema::dropIfExists('feature_product');
    }
};
