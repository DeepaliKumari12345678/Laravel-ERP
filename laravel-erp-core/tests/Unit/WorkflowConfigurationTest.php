<?php

namespace Tests\Unit;

use App\Http\Controllers\Admin\OrderController;
use App\Models\Customer;
use App\Models\CustomerGroup;
use App\Models\OrderStatus;
use App\Models\Product;
use Illuminate\Foundation\Testing\RefreshDatabase;
use ReflectionMethod;
use Tests\TestCase;

class WorkflowConfigurationTest extends TestCase
{
    use RefreshDatabase;

    public function test_default_order_statuses_have_functional_workflow_flags(): void
    {
        $paid = OrderStatus::query()->where('code', 'paid')->firstOrFail();
        $cancelled = OrderStatus::query()->where('code', 'cancelled')->firstOrFail();

        $this->assertTrue($paid->is_paid);
        $this->assertTrue($paid->counts_as_validated);
        $this->assertTrue($paid->send_email);
        $this->assertTrue($cancelled->is_cancelled);
        $this->assertFalse($cancelled->counts_as_validated);
    }

    public function test_customer_can_belong_to_discount_group(): void
    {
        $group = CustomerGroup::query()->create([
            'name' => 'Wholesale',
            'discount_percent' => 12.5,
            'active' => true,
        ]);
        $customer = Customer::query()->create([
            'customer_code' => 'CUS-TEST',
            'customer_group_id' => $group->id,
            'type' => 'company',
            'first_name' => 'Wholesale',
            'email' => 'wholesale@example.com',
            'active' => true,
        ]);

        $this->assertSame('Wholesale', $customer->group->name);
        $this->assertSame(12.5, (float) $customer->group->discount_percent);
    }

    public function test_customer_group_discount_is_applied_to_admin_cart_lines(): void
    {
        $group = CustomerGroup::query()->create([
            'name' => 'Trade',
            'discount_percent' => 10,
            'active' => true,
        ]);
        $customer = Customer::query()->create([
            'customer_code' => 'CUS-TRADE',
            'customer_group_id' => $group->id,
            'type' => 'company',
            'first_name' => 'Trade',
            'email' => 'trade@example.com',
            'active' => true,
        ]);
        $product = Product::query()->create([
            'sku' => 'TRADE-PRODUCT',
            'name' => 'Trade product',
            'slug' => 'trade-product',
            'price' => 100,
            'type' => Product::TYPE_PRODUCT,
            'active' => true,
        ]);

        $method = new ReflectionMethod(OrderController::class, 'cartLines');
        $lines = $method->invoke(new OrderController, [
            'customer_id' => $customer->id,
            'lines' => [['product_id' => $product->id, 'qty' => 2]],
        ]);

        $this->assertSame(180.0, $lines[0]['total']);
        $this->assertSame(20.0, $lines[0]['discount_total']);
        $this->assertSame(10.0, $lines[0]['discount_percent']);
    }
}
