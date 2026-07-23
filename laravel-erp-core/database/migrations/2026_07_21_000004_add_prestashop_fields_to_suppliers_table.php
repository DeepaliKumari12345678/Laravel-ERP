<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('suppliers', function (Blueprint $table) {
            $table->string('mobile_phone')->nullable()->after('phone');
            $table->string('address2')->nullable()->after('address');
            $table->string('dni')->nullable()->after('tax_number');
            $table->longText('description')->nullable()->after('name');
            $table->string('meta_title')->nullable()->after('logo_path');
            $table->text('meta_description')->nullable()->after('meta_title');
        });
    }

    public function down(): void
    {
        Schema::table('suppliers', function (Blueprint $table) {
            $table->dropColumn([
                'mobile_phone',
                'address2',
                'dni',
                'description',
                'meta_title',
                'meta_description',
            ]);
        });
    }
};
