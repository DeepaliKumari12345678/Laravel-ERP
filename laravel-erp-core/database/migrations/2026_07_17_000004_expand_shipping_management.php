<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('shipping_carriers', function (Blueprint $table) {
            $table->string('logo_path')->nullable()->after('delay');
            $table->string('tracking_url')->nullable()->after('logo_path');
            $table->unsignedTinyInteger('speed_grade')->default(0)->after('tracking_url');
            $table->string('billing_basis', 20)->default('price')->after('currency');
            $table->boolean('free_shipping')->default(false)->after('billing_basis');
            $table->boolean('apply_handling_cost')->default(true)->after('free_shipping');
            $table->decimal('tax_rate', 5, 2)->default(0)->after('apply_handling_cost');
            $table->string('out_of_range_behavior', 20)->default('disable')->after('tax_rate');
            $table->json('country_codes')->nullable()->after('out_of_range_behavior');
            $table->decimal('max_width', 10, 2)->default(0)->after('country_codes');
            $table->decimal('max_height', 10, 2)->default(0)->after('max_width');
            $table->decimal('max_depth', 10, 2)->default(0)->after('max_height');
            $table->decimal('max_weight', 10, 2)->default(0)->after('max_depth');
        });

        Schema::create('shipping_rate_ranges', function (Blueprint $table) {
            $table->id();
            $table->foreignId('shipping_carrier_id')->constrained()->cascadeOnDelete();
            $table->decimal('from_value', 15, 2)->default(0);
            $table->decimal('to_value', 15, 2);
            $table->decimal('price', 15, 2)->default(0);
            $table->timestamps();

            $table->index(['shipping_carrier_id', 'from_value', 'to_value'], 'shipping_rate_lookup');
        });

        Schema::table('products', function (Blueprint $table) {
            $table->decimal('weight', 10, 3)->default(0)->after('cost');
            $table->decimal('width', 10, 2)->default(0)->after('weight');
            $table->decimal('height', 10, 2)->default(0)->after('width');
            $table->decimal('depth', 10, 2)->default(0)->after('height');
        });

        Schema::table('orders', function (Blueprint $table) {
            $table->foreignId('shipping_carrier_id')->nullable()->after('payment_method')
                ->constrained('shipping_carriers')->nullOnDelete();
            $table->string('shipping_carrier_name')->nullable()->after('shipping_carrier_id');
            $table->decimal('shipping_cost', 15, 2)->default(0)->after('shipping_carrier_name');
            $table->decimal('shipping_tax', 15, 2)->default(0)->after('shipping_cost');
            $table->decimal('shipping_weight', 15, 3)->default(0)->after('shipping_tax');
        });
    }

    public function down(): void
    {
        Schema::table('orders', function (Blueprint $table) {
            $table->dropConstrainedForeignId('shipping_carrier_id');
            $table->dropColumn(['shipping_carrier_name', 'shipping_cost', 'shipping_tax', 'shipping_weight']);
        });

        Schema::table('products', function (Blueprint $table) {
            $table->dropColumn(['weight', 'width', 'height', 'depth']);
        });

        Schema::dropIfExists('shipping_rate_ranges');

        Schema::table('shipping_carriers', function (Blueprint $table) {
            $table->dropColumn([
                'logo_path',
                'tracking_url',
                'speed_grade',
                'billing_basis',
                'free_shipping',
                'apply_handling_cost',
                'tax_rate',
                'out_of_range_behavior',
                'country_codes',
                'max_width',
                'max_height',
                'max_depth',
                'max_weight',
            ]);
        });
    }
};
