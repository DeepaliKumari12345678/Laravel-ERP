<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Core\Configuration\Configuration;
use App\Models\Order;
use App\Models\OrderStatus;
use App\Models\OrderStatusHistory;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;
use Illuminate\View\View;

class OrderStatusController extends Controller
{
    public function index(Request $request): View
    {
        $query = OrderStatus::query()->withCount('orders')->orderBy('position')->orderBy('name');

        if ($request->filled('name')) {
            $query->where('name', 'like', '%'.$request->string('name').'%');
        }
        if ($request->filled('active')) {
            $query->where('active', $request->boolean('active'));
        }

        return view('admin.settings.order-statuses', [
            'statuses' => $query->paginate(30)->withQueryString(),
        ]);
    }

    public function create(): View
    {
        return $this->form(new OrderStatus([
            'color' => '#607D8B',
            'active' => true,
            'position' => (int) OrderStatus::query()->max('position') + 1,
        ]), 'create');
    }

    public function store(Request $request): RedirectResponse
    {
        $data = $this->validated($request);
        $data['code'] = $data['code'] ?: Str::slug($data['name'], '_');
        if (OrderStatus::query()->where('code', $data['code'])->exists()) {
            throw ValidationException::withMessages(['code' => 'This status code is already in use.']);
        }

        OrderStatus::query()->create($data);

        return redirect()->route('admin.order-statuses.index')->with('success', 'Order status created.');
    }

    public function edit(OrderStatus $orderStatus): View
    {
        return $this->form($orderStatus->loadCount('orders'), 'edit');
    }

    public function update(Request $request, OrderStatus $orderStatus): RedirectResponse
    {
        $oldCode = $orderStatus->code;
        $data = $this->validated($request, $orderStatus);
        $data['code'] = $data['code'] ?: Str::slug($data['name'], '_');
        if (OrderStatus::query()->where('code', $data['code'])->where('id', '!=', $orderStatus->id)->exists()) {
            throw ValidationException::withMessages(['code' => 'This status code is already in use.']);
        }

        DB::transaction(function () use ($orderStatus, $oldCode, $data) {
            if ($oldCode !== $data['code']) {
                Order::query()->where('status', $oldCode)->update(['status' => $data['code']]);
                OrderStatusHistory::query()->where('status', $oldCode)->update(['status' => $data['code']]);
                if ((string) Configuration::get('PS_ORDER_DEFAULT_STATUS', 'pending') === $oldCode) {
                    Configuration::updateValue('PS_ORDER_DEFAULT_STATUS', $data['code']);
                }
            }
            $orderStatus->update($data);
        });

        return redirect()->route('admin.order-statuses.index')->with('success', 'Order status updated.');
    }

    public function destroy(OrderStatus $orderStatus): RedirectResponse
    {
        if ($orderStatus->orders()->exists()) {
            return back()->with('error', 'This status is used by orders and cannot be deleted. Disable it instead.');
        }

        $orderStatus->delete();

        return back()->with('success', 'Order status deleted.');
    }

    protected function form(OrderStatus $orderStatus, string $mode): View
    {
        return view('admin.settings.order-status-form', [
            'status' => $orderStatus,
            'mode' => $mode,
        ]);
    }

    /**
     * @return array<string, mixed>
     */
    protected function validated(Request $request, ?OrderStatus $orderStatus = null): array
    {
        $codeRule = Rule::unique('order_statuses', 'code');
        if ($orderStatus) {
            $codeRule->ignore($orderStatus);
        }

        $data = $request->validate([
            'name' => ['required', 'string', 'max:100'],
            'code' => ['nullable', 'string', 'max:50', 'regex:/^[a-z0-9_\\-]+$/', $codeRule],
            'color' => ['required', 'regex:/^#[0-9A-Fa-f]{6}$/'],
            'position' => ['nullable', 'integer', 'min:0'],
            'send_email' => ['nullable', 'boolean'],
            'is_paid' => ['nullable', 'boolean'],
            'is_shipped' => ['nullable', 'boolean'],
            'is_delivered' => ['nullable', 'boolean'],
            'is_cancelled' => ['nullable', 'boolean'],
            'counts_as_validated' => ['nullable', 'boolean'],
            'active' => ['nullable', 'boolean'],
        ]);

        foreach (['send_email', 'is_paid', 'is_shipped', 'is_delivered', 'is_cancelled', 'counts_as_validated', 'active'] as $field) {
            $data[$field] = $request->boolean($field);
        }
        $data['position'] = $data['position'] ?? 0;

        return $data;
    }
}
