<?php

namespace App\Http\Controllers\Admin;

use App\Core\Configuration\Configuration;
use App\Http\Controllers\Controller;
use App\Models\WebserviceKey;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Str;
use Illuminate\View\View;

class WebserviceController extends Controller
{
    public function index(): View
    {
        return view('admin.advanced.webservice', [
            'keys' => WebserviceKey::query()->latest()->paginate(20),
            'enabled' => (string) Configuration::get('ERP_WEBSERVICE', Configuration::get('PS_WEBSERVICE', '0')) === '1',
            'cgiMode' => (string) Configuration::get('ERP_WEBSERVICE_CGI_MODE', Configuration::get('PS_WEBSERVICE_CGI_MODE', '0')) === '1',
        ]);
    }

    public function create(): View
    {
        return view('admin.advanced.webservice-form', [
            'webserviceKey' => new WebserviceKey([
                'key' => Str::upper(Str::random(32)),
                'active' => true,
                'permissions' => [],
            ]),
            'mode' => 'create',
            'resources' => WebserviceKey::resources(),
            'methods' => WebserviceKey::methods(),
        ]);
    }

    public function store(Request $request): RedirectResponse
    {
        $data = $this->validated($request);

        WebserviceKey::query()->create($data);

        return redirect()
            ->route('admin.webservice.index')
            ->with('success', 'Webservice key created.');
    }

    public function edit(WebserviceKey $webserviceKey): View
    {
        return view('admin.advanced.webservice-form', [
            'webserviceKey' => $webserviceKey,
            'mode' => 'edit',
            'resources' => WebserviceKey::resources(),
            'methods' => WebserviceKey::methods(),
        ]);
    }

    public function update(Request $request, WebserviceKey $webserviceKey): RedirectResponse
    {
        $webserviceKey->update($this->validated($request, $webserviceKey));

        return redirect()
            ->route('admin.webservice.index')
            ->with('success', 'Webservice key updated.');
    }

    public function toggle(WebserviceKey $webserviceKey): RedirectResponse
    {
        $webserviceKey->update(['active' => ! $webserviceKey->active]);

        return back()->with('success', 'Webservice key updated.');
    }

    public function destroy(WebserviceKey $webserviceKey): RedirectResponse
    {
        $webserviceKey->delete();

        return back()->with('success', 'Webservice key deleted.');
    }

    public function updateConfiguration(Request $request): RedirectResponse
    {
        $request->validate([
            'ERP_WEBSERVICE' => ['nullable', 'boolean'],
            'ERP_WEBSERVICE_CGI_MODE' => ['nullable', 'boolean'],
        ]);

        Configuration::updateValue('ERP_WEBSERVICE', $request->boolean('ERP_WEBSERVICE') ? '1' : '0');
        Configuration::updateValue('ERP_WEBSERVICE_CGI_MODE', $request->boolean('ERP_WEBSERVICE_CGI_MODE') ? '1' : '0');

        return back()->with('success', 'Webservice configuration saved.');
    }

    /**
     * @return array<string, mixed>
     */
    protected function validated(Request $request, ?WebserviceKey $webserviceKey = null): array
    {
        $data = $request->validate([
            'key' => [
                'required',
                'string',
                'size:32',
                'alpha_num',
                'unique:webservice_keys,key'.($webserviceKey ? ','.$webserviceKey->id : ''),
            ],
            'description' => ['nullable', 'string', 'max:255'],
            'active' => ['nullable', 'boolean'],
            'permissions' => ['nullable', 'array'],
            'permissions.*' => ['array'],
            'permissions.*.*' => ['in:'.implode(',', WebserviceKey::methods())],
        ]);

        $permissions = [];
        foreach (WebserviceKey::resources() as $resource) {
            $methods = array_values(array_unique(array_map('strtoupper', (array) ($data['permissions'][$resource] ?? []))));
            if ($methods !== []) {
                $permissions[$resource] = $methods;
            }
        }

        return [
            'key' => strtoupper($data['key']),
            'description' => $data['description'] ?? null,
            'active' => $request->boolean('active', true),
            'permissions' => $permissions,
        ];
    }
}
