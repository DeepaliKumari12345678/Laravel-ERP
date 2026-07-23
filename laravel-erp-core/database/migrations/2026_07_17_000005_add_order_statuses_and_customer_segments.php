<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('order_statuses', function (Blueprint $table) {
            $table->id();
            $table->string('code', 50)->unique();
            $table->string('name', 100);
            $table->string('color', 7)->default('#607D8B');
            $table->boolean('send_email')->default(false);
            $table->boolean('is_paid')->default(false);
            $table->boolean('is_shipped')->default(false);
            $table->boolean('is_delivered')->default(false);
            $table->boolean('is_cancelled')->default(false);
            $table->boolean('counts_as_validated')->default(false);
            $table->boolean('active')->default(true);
            $table->unsignedInteger('position')->default(0);
            $table->timestamps();
        });

        DB::table('order_statuses')->insert([
            $this->status('pending', 'Pending', '#6C5CE7', 1, sendEmail: true),
            $this->status('processing', 'Processing', '#00A8CC', 2, sendEmail: true),
            $this->status('paid', 'Payment accepted', '#00A65A', 3, sendEmail: true, paid: true, validated: true),
            $this->status('shipped', 'Shipped', '#20B2AA', 4, sendEmail: true, paid: true, shipped: true, validated: true),
            $this->status('completed', 'Delivered', '#2E8B57', 5, paid: true, shipped: true, delivered: true, validated: true),
            $this->status('cancelled', 'Cancelled', '#D9534F', 6, sendEmail: true, cancelled: true),
        ]);

        Schema::create('customer_groups', function (Blueprint $table) {
            $table->id();
            $table->string('name', 100)->unique();
            $table->decimal('discount_percent', 5, 2)->default(0);
            $table->text('description')->nullable();
            $table->boolean('active')->default(true);
            $table->unsignedInteger('position')->default(0);
            $table->timestamps();
        });

        $defaultGroupId = DB::table('customer_groups')->insertGetId([
            'name' => 'Retail',
            'discount_percent' => 0,
            'description' => 'Default customer group',
            'active' => true,
            'position' => 1,
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        Schema::table('customers', function (Blueprint $table) {
            $table->foreignId('customer_group_id')->nullable()->after('customer_code')
                ->constrained('customer_groups')->nullOnDelete();
        });

        DB::table('customers')->whereNull('customer_group_id')->update(['customer_group_id' => $defaultGroupId]);

        Schema::create('customer_titles', function (Blueprint $table) {
            $table->id();
            $table->string('name', 50)->unique();
            $table->string('gender', 20)->default('neutral');
            $table->boolean('active')->default(true);
            $table->unsignedInteger('position')->default(0);
            $table->timestamps();
        });

        DB::table('customer_titles')->insert([
            ['name' => 'Mr', 'gender' => 'male', 'active' => true, 'position' => 1, 'created_at' => now(), 'updated_at' => now()],
            ['name' => 'Mrs', 'gender' => 'female', 'active' => true, 'position' => 2, 'created_at' => now(), 'updated_at' => now()],
            ['name' => 'Ms', 'gender' => 'female', 'active' => true, 'position' => 3, 'created_at' => now(), 'updated_at' => now()],
            ['name' => 'Other', 'gender' => 'neutral', 'active' => true, 'position' => 4, 'created_at' => now(), 'updated_at' => now()],
        ]);
    }

    public function down(): void
    {
        Schema::dropIfExists('customer_titles');

        Schema::table('customers', function (Blueprint $table) {
            $table->dropConstrainedForeignId('customer_group_id');
        });

        Schema::dropIfExists('customer_groups');
        Schema::dropIfExists('order_statuses');
    }

    /**
     * @return array<string, mixed>
     */
    protected function status(
        string $code,
        string $name,
        string $color,
        int $position,
        bool $sendEmail = false,
        bool $paid = false,
        bool $shipped = false,
        bool $delivered = false,
        bool $cancelled = false,
        bool $validated = false
    ): array {
        return [
            'code' => $code,
            'name' => $name,
            'color' => $color,
            'send_email' => $sendEmail,
            'is_paid' => $paid,
            'is_shipped' => $shipped,
            'is_delivered' => $delivered,
            'is_cancelled' => $cancelled,
            'counts_as_validated' => $validated,
            'active' => true,
            'position' => $position,
            'created_at' => now(),
            'updated_at' => now(),
        ];
    }
};
