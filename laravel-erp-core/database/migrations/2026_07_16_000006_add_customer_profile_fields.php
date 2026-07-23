<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('customers', function (Blueprint $table) {
            $table->string('social_title')->nullable()->after('type');
            $table->date('birthday')->nullable()->after('phone');
            $table->text('note')->nullable()->after('country');
            $table->boolean('newsletter')->default(false)->after('active');
            $table->boolean('partner_offers')->default(false)->after('newsletter');
        });
    }

    public function down(): void
    {
        Schema::table('customers', function (Blueprint $table) {
            $table->dropColumn(['social_title', 'birthday', 'note', 'newsletter', 'partner_offers']);
        });
    }
};
