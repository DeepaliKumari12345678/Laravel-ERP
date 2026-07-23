<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('invoices', function (Blueprint $table) {
            $table->id();
            $table->string('number')->unique();
            $table->foreignId('order_id')->constrained()->cascadeOnDelete();
            $table->foreignId('customer_id')->nullable()->constrained()->nullOnDelete();
            $table->decimal('total', 15, 2)->default(0);
            $table->string('currency', 3)->default('INR');
            $table->string('status')->default('issued')->index(); // issued|paid|cancelled
            $table->timestamp('issued_at')->nullable();
            $table->text('notes')->nullable();
            $table->timestamps();
        });

        Schema::create('credit_slips', function (Blueprint $table) {
            $table->id();
            $table->string('number')->unique();
            $table->foreignId('order_id')->constrained()->cascadeOnDelete();
            $table->foreignId('customer_id')->nullable()->constrained()->nullOnDelete();
            $table->decimal('amount', 15, 2)->default(0);
            $table->string('currency', 3)->default('INR');
            $table->string('reason')->nullable();
            $table->string('status')->default('issued')->index();
            $table->timestamp('issued_at')->nullable();
            $table->timestamps();
        });

        Schema::create('delivery_slips', function (Blueprint $table) {
            $table->id();
            $table->string('number')->unique();
            $table->foreignId('order_id')->constrained()->cascadeOnDelete();
            $table->foreignId('customer_id')->nullable()->constrained()->nullOnDelete();
            $table->string('carrier')->nullable();
            $table->string('tracking_number')->nullable();
            $table->string('status')->default('prepared')->index(); // prepared|shipped|delivered
            $table->timestamp('shipped_at')->nullable();
            $table->text('notes')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('delivery_slips');
        Schema::dropIfExists('credit_slips');
        Schema::dropIfExists('invoices');
    }
};
