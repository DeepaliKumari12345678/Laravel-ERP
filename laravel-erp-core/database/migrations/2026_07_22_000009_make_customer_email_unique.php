<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // Resolve duplicate emails before adding unique index.
        DB::table('customers')->where('email', '')->update(['email' => null]);

        $duplicates = DB::table('customers')
            ->select('email')
            ->whereNotNull('email')
            ->groupBy('email')
            ->havingRaw('COUNT(*) > 1')
            ->pluck('email');

        foreach ($duplicates as $email) {
            $ids = DB::table('customers')
                ->where('email', $email)
                ->orderBy('id')
                ->pluck('id');

            foreach ($ids->slice(1) as $id) {
                DB::table('customers')
                    ->where('id', $id)
                    ->update(['email' => $email.'.dup.'.$id]);
            }
        }

        Schema::table('customers', function (Blueprint $table) {
            $table->unique('email');
        });
    }

    public function down(): void
    {
        Schema::table('customers', function (Blueprint $table) {
            $table->dropUnique(['email']);
        });
    }
};
