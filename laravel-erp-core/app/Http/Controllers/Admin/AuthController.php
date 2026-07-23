<?php

namespace App\Http\Controllers\Admin;

use App\Core\Configuration\Configuration;
use App\Core\Mail\ErpMail;
use App\Core\Mail\MailSettings;
use App\Http\Controllers\Controller;
use App\Models\Employee;
use App\Models\Permission;
use App\Models\Role;
use App\Models\User;
use Illuminate\Auth\Events\PasswordReset;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Password;
use Illuminate\Support\Str;
use Illuminate\Validation\Rules\Password as PasswordRule;
use Illuminate\View\View;

class AuthController extends Controller
{
    public function showLogin(Request $request): View|RedirectResponse
    {
        if (Auth::check() && Auth::user()->isEmployee()) {
            return redirect()->to($this->homeRouteFor(Auth::user()));
        }

        return view('admin.auth.login', [
            'canSignup' => $this->canSignup(),
            'rememberedEmail' => $request->cookie('erp_login_email', ''),
        ]);
    }

    public function showSignup(): View|RedirectResponse
    {
        if (! $this->canSignup()) {
            return redirect()->route('admin.login')
                ->with('error', 'Signup is closed. Ask a Super Admin to create your account under Employees.');
        }

        if (Auth::check() && Auth::user()->isEmployee()) {
            return redirect()->to($this->homeRouteFor(Auth::user()));
        }

        return view('admin.auth.signup');
    }

    public function signup(Request $request): RedirectResponse
    {
        if (! $this->canSignup()) {
            return redirect()->route('admin.login')
                ->with('error', 'Signup is closed. Ask a Super Admin to create your account under Employees.');
        }

        $data = $request->validate([
            'company_name' => ['required', 'string', 'max:150'],
            'name' => ['required', 'string', 'max:120'],
            'email' => ['required', 'email', 'max:150', 'unique:users,email'],
            'password' => ['required', 'confirmed', PasswordRule::min(8)],
        ]);

        DB::transaction(function () use ($data) {
            $this->ensureBaseRolesAndPermissions();

            $role = Role::query()->where('name', 'super_admin')->firstOrFail();

            $user = User::query()->create([
                'name' => $data['name'],
                'email' => $data['email'],
                'password' => $data['password'],
                'user_type' => 'employee',
                'active' => true,
            ]);

            Employee::query()->create([
                'user_id' => $user->id,
                'role_id' => $role->id,
                'employee_code' => 'EMP-00001',
                'first_name' => $this->firstName($data['name']),
                'last_name' => $this->lastName($data['name']),
                'active' => true,
            ]);

            Configuration::updateValue('PS_SHOP_NAME', $data['company_name']);
            Configuration::updateValue('PS_SHOP_EMAIL', $data['email']);
            Configuration::updateValue('PS_CURRENCY_DEFAULT', config('erp.currency', 'INR'));
            Configuration::updateValue('PS_LANG_DEFAULT', config('erp.locale', 'en'));
            Configuration::updateValue('PS_TIMEZONE', Configuration::get('PS_TIMEZONE', 'Asia/Kolkata'));
        });

        ErpMail::send($data['email'], 'Your Super Admin account is ready', 'emails.super-admin-created', [
            'name' => $data['name'],
            'email' => $data['email'],
            'shopName' => $data['company_name'],
            'loginUrl' => route('admin.login'),
        ]);

        return redirect()
            ->route('admin.login')
            ->with('success', 'Account created. Please log in with your email and password.')
            ->withInput(['email' => $data['email']]);
    }

    public function login(Request $request): RedirectResponse
    {
        $credentials = $request->validate([
            'email' => ['required', 'email'],
            'password' => ['required'],
        ]);

        if (! Auth::attempt($credentials, $request->boolean('remember'))) {
            return back()->withErrors(['email' => 'Invalid email or password.'])->onlyInput('email');
        }

        $request->session()->regenerate();
        $user = Auth::user();

        if (! $user->active || ! $user->isEmployee() || ! $user->employee?->active) {
            Auth::logout();

            return back()->withErrors(['email' => 'Back office access denied.'])->onlyInput('email');
        }

        if ($user->employee) {
            $user->employee->update(['last_login_at' => now()]);
        }

        $response = redirect()->intended($this->homeRouteFor($user));

        // Remember the email for next login (1 year); never store the password.
        if ($request->boolean('remember')) {
            $response->withCookie(cookie('erp_login_email', $credentials['email'], 60 * 24 * 365));
        } else {
            $response->withCookie(cookie()->forget('erp_login_email'));
        }

        return $response;
    }

    protected function homeRouteFor(User $user): string
    {
        foreach (config('erp.menu', []) as $section) {
            foreach ($section['items'] as $item) {
                if (! empty($item['children'])) {
                    foreach ($item['children'] as $child) {
                        if ($user->hasPermission($child['permission'])) {
                            return route($child['route']);
                        }
                    }

                    continue;
                }

                if (! empty($item['route']) && $user->hasPermission($item['permission'])) {
                    return route($item['route']);
                }
            }
        }

        return route('admin.profile');
    }

    public function logout(Request $request): RedirectResponse
    {
        Auth::logout();
        $request->session()->invalidate();
        $request->session()->regenerateToken();

        return redirect()->route('admin.login');
    }

    public function showForgotPassword(): View
    {
        return view('admin.auth.forgot-password');
    }

    public function sendResetLink(Request $request): RedirectResponse
    {
        $request->validate(['email' => ['required', 'email']]);

        $user = User::query()
            ->where('email', $request->email)
            ->where('user_type', 'employee')
            ->where('active', true)
            ->first();

        if (! $user) {
            return back()->withErrors(['email' => 'No active employee account found for that email.']);
        }

        MailSettings::apply();

        $token = Password::broker()->createToken($user);
        $resetUrl = route('admin.password.reset', [
            'token' => $token,
            'email' => $user->email,
        ]);

        $sent = ErpMail::send($user->email, 'Reset your password', 'emails.password-reset', [
            'name' => $user->name,
            'resetUrl' => $resetUrl,
        ]);

        // Without working mail, show the link on screen so recovery still works
        $showLink = ! $sent;

        return back()
            ->with('success', $showLink
                ? 'Use the reset link below to set a new password.'
                : 'Password reset link sent to your email.')
            ->with('reset_url', $showLink ? $resetUrl : null);
    }

    public function showResetPassword(Request $request, string $token): View
    {
        return view('admin.auth.reset-password', [
            'token' => $token,
            'email' => $request->query('email', ''),
        ]);
    }

    public function resetPassword(Request $request): RedirectResponse
    {
        $request->validate([
            'token' => ['required'],
            'email' => ['required', 'email'],
            'password' => ['required', 'confirmed', PasswordRule::min(8)],
        ]);

        $status = Password::broker()->reset(
            $request->only('email', 'password', 'password_confirmation', 'token'),
            function (User $user, string $password) {
                $user->forceFill([
                    'password' => $password,
                    'remember_token' => Str::random(60),
                ])->save();

                event(new PasswordReset($user));

                ErpMail::send($user->email, 'Your password was changed', 'emails.password-changed', [
                    'name' => $user->name,
                ]);
            }
        );

        if ($status !== Password::PASSWORD_RESET) {
            return back()->withErrors(['email' => __($status)])->withInput($request->only('email'));
        }

        return redirect()
            ->route('admin.login')
            ->with('success', 'Password updated. Please log in with your new password.')
            ->withInput(['email' => $request->email]);
    }

    public function showProfile(): View
    {
        return view('admin.auth.profile', [
            'user' => auth()->user()->load('employee'),
        ]);
    }

    public function updateProfile(Request $request): RedirectResponse
    {
        /** @var User $user */
        $user = auth()->user();

        $data = $request->validate([
            'name' => ['required', 'string', 'max:120'],
            'email' => ['required', 'email', 'max:150', 'unique:users,email,'.$user->id],
            'current_password' => ['required'],
            'password' => ['nullable', 'confirmed', PasswordRule::min(8)],
        ]);

        if (! Hash::check($data['current_password'], $user->password)) {
            return back()->withErrors(['current_password' => 'Current password is incorrect.']);
        }

        $user->name = $data['name'];
        $user->email = $data['email'];

        if (! empty($data['password'])) {
            $user->password = $data['password'];
        }

        $user->save();

        if ($user->employee) {
            $user->employee->update([
                'first_name' => $this->firstName($data['name']),
                'last_name' => $this->lastName($data['name']),
            ]);
        }

        Configuration::updateValue('PS_SHOP_EMAIL', $data['email']);

        return back()->with('success', 'Your credentials were updated.');
    }

    protected function canSignup(): bool
    {
        return ! Employee::query()->exists();
    }

    protected function firstName(string $fullName): string
    {
        $parts = preg_split('/\s+/', trim($fullName)) ?: [$fullName];

        return $parts[0] ?: $fullName;
    }

    protected function lastName(string $fullName): ?string
    {
        $parts = preg_split('/\s+/', trim($fullName)) ?: [];

        if (count($parts) < 2) {
            return null;
        }

        return implode(' ', array_slice($parts, 1));
    }

    protected function ensureBaseRolesAndPermissions(): void
    {
        foreach (config('erp.tab_permissions', []) as $name => $label) {
            Permission::query()->updateOrCreate(
                ['name' => $name],
                ['guard_name' => 'web', 'group' => 'tab']
            );
        }

        $superAdmin = Role::query()->updateOrCreate(
            ['name' => 'super_admin'],
            [
                'display_name' => 'Super Admin',
                'description' => 'Full access to all tabs',
                'is_system' => true,
            ]
        );

        $manager = Role::query()->updateOrCreate(
            ['name' => 'manager'],
            [
                'display_name' => 'Manager',
                'description' => 'Day-to-day operations',
                'is_system' => true,
            ]
        );

        $superAdmin->permissions()->sync(
            Permission::query()->where('group', 'tab')->pluck('id')
        );

        $manager->permissions()->sync(
            Permission::query()->whereIn('name', [
                'tab.dashboard',
                'tab.orders',
                'tab.customers',
                'tab.catalog',
                'tab.reports',
                'tab.shipping',
                'tab.payment',
            ])->pluck('id')
        );
    }
}
