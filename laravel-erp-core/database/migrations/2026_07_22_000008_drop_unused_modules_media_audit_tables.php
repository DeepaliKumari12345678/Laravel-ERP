<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::dropIfExists('modules');
        Schema::dropIfExists('media');
        Schema::dropIfExists('audit_logs');

        if (Schema::hasTable('permissions')) {
            $ids = DB::table('permissions')->where('name', 'tab.modules')->pluck('id');
            if ($ids->isNotEmpty()) {
                if (Schema::hasTable('permission_role')) {
                    DB::table('permission_role')->whereIn('permission_id', $ids)->delete();
                }
                DB::table('permissions')->whereIn('id', $ids)->delete();
            }
        }
    }

    public function down(): void
    {
        // Intentionally empty — unused leftover tables are not restored.
    }
};
