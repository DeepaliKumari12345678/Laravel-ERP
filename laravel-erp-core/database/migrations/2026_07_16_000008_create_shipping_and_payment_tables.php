<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('shipping_carriers', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->string('delay')->nullable();
            $table->decimal('price', 15, 2)->default(0);
            $table->string('currency', 3)->default('INR');
            $table->boolean('active')->default(true);
            $table->unsignedInteger('position')->default(0);
            $table->text('notes')->nullable();
            $table->timestamps();
        });

        Schema::create('payment_methods', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->string('code')->unique();
            $table->text('description')->nullable();
            $table->boolean('active')->default(true);
            $table->unsignedInteger('position')->default(0);
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('payment_methods');
        Schema::dropIfExists('shipping_carriers');
    }
};
