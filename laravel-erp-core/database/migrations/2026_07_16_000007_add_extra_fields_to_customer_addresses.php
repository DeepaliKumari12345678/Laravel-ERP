<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('customer_addresses', function (Blueprint $table) {
            $table->string('dni')->nullable()->after('company');
            $table->string('vat_number')->nullable()->after('dni');
            $table->string('phone_mobile')->nullable()->after('phone');
            $table->text('other')->nullable()->after('phone_mobile');
        });
    }

    public function down(): void
    {
        Schema::table('customer_addresses', function (Blueprint $table) {
            $table->dropColumn(['dni', 'vat_number', 'phone_mobile', 'other']);
        });
    }
};
