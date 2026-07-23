<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('attribute_groups', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->string('public_name');
            $table->string('type', 20)->default('select'); // select|radio|color
            $table->unsignedInteger('position')->default(0);
            $table->timestamps();
        });

        Schema::create('attribute_values', function (Blueprint $table) {
            $table->id();
            $table->foreignId('attribute_group_id')->constrained('attribute_groups')->cascadeOnDelete();
            $table->string('name');
            $table->string('color', 20)->nullable();
            $table->unsignedInteger('position')->default(0);
            $table->timestamps();
        });

        Schema::create('features', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->unsignedInteger('position')->default(0);
            $table->timestamps();
        });

        Schema::create('feature_values', function (Blueprint $table) {
            $table->id();
            $table->foreignId('feature_id')->constrained('features')->cascadeOnDelete();
            $table->string('value');
            $table->unsignedInteger('position')->default(0);
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('feature_values');
        Schema::dropIfExists('features');
        Schema::dropIfExists('attribute_values');
        Schema::dropIfExists('attribute_groups');
    }
};
