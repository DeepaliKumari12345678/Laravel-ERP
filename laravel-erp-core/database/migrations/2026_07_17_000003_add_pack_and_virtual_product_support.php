<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('products', function (Blueprint $table) {
            $table->string('virtual_file_path')->nullable()->after('image_path');
            $table->string('virtual_file_name')->nullable()->after('virtual_file_path');
            $table->unsignedInteger('download_limit')->nullable()->after('virtual_file_name');
            $table->unsignedInteger('download_expiry_days')->nullable()->after('download_limit');
            $table->date('download_expires_at')->nullable()->after('download_expiry_days');
        });

        Schema::create('product_pack_items', function (Blueprint $table) {
            $table->id();
            $table->foreignId('pack_product_id')->constrained('products')->cascadeOnDelete();
            $table->foreignId('item_product_id')->constrained('products')->cascadeOnDelete();
            $table->decimal('quantity', 15, 2)->default(1);
            $table->timestamps();

            $table->unique(['pack_product_id', 'item_product_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('product_pack_items');

        Schema::table('products', function (Blueprint $table) {
            $table->dropColumn([
                'virtual_file_path',
                'virtual_file_name',
                'download_limit',
                'download_expiry_days',
                'download_expires_at',
            ]);
        });
    }
};
