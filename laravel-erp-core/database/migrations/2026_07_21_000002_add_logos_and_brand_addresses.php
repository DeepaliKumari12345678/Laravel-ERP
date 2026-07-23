<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('brands', function (Blueprint $table) {
            $table->string('logo_path')->nullable()->after('description');
        });

        Schema::table('suppliers', function (Blueprint $table) {
            $table->string('logo_path')->nullable()->after('notes');
        });

        Schema::create('brand_addresses', function (Blueprint $table) {
            $table->id();
            $table->foreignId('brand_id')->constrained()->cascadeOnDelete();
            $table->string('first_name')->nullable();
            $table->string('last_name')->nullable();
            $table->string('address1')->nullable();
            $table->string('address2')->nullable();
            $table->string('postcode')->nullable();
            $table->string('city')->nullable();
            $table->string('state')->nullable();
            $table->string('country')->nullable();
            $table->string('phone')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('brand_addresses');

        Schema::table('suppliers', function (Blueprint $table) {
            $table->dropColumn('logo_path');
        });

        Schema::table('brands', function (Blueprint $table) {
            $table->dropColumn('logo_path');
        });
    }
};
