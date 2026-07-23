<?php

namespace App\Http\Controllers\Admin;

use App\Core\Configuration\Configuration;
use App\Core\Mail\MailSettings;
use App\Http\Controllers\Controller;
use App\Models\EmailLog;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Mail;
use Illuminate\View\View;
use Throwable;

class MailController extends Controller
{
    public function index(): View
    {
        return view('admin.settings.mail', [
            'mail' => MailSettings::formValues(),
            'logs' => EmailLog::query()->latest()->paginate(15),
        ]);
    }

    public function update(Request $request): RedirectResponse
    {
        $data = $request->validate([
            'PS_MAIL_MAILER' => ['required', 'in:sendmail,smtp,never'],
            'PS_MAIL_HOST' => ['nullable', 'required_if:PS_MAIL_MAILER,smtp', 'string', 'max:150'],
            'PS_MAIL_PORT' => ['nullable', 'required_if:PS_MAIL_MAILER,smtp', 'integer', 'min:1', 'max:65535'],
            'PS_MAIL_USERNAME' => ['nullable', 'string', 'max:150'],
            'PS_MAIL_PASSWORD' => ['nullable', 'string', 'max:255'],
            'PS_MAIL_ENCRYPTION' => ['nullable', 'in:tls,ssl,none'],
            'PS_MAIL_FROM_ADDRESS' => ['required', 'email', 'max:150'],
            'PS_MAIL_FROM_NAME' => ['required', 'string', 'max:150'],
            'PS_MAIL_SUBJECT_PREFIX' => ['nullable', 'boolean'],
            'PS_MAIL_FORMAT' => ['required', 'in:html,text,both'],
            'PS_MAIL_LOG' => ['nullable', 'boolean'],
        ]);

        Configuration::updateValue('PS_MAIL_MAILER', $data['PS_MAIL_MAILER'] === 'never' ? 'log' : $data['PS_MAIL_MAILER']);
        Configuration::updateValue('PS_MAIL_HOST', $data['PS_MAIL_HOST'] ?? '');
        Configuration::updateValue('PS_MAIL_PORT', (string) ($data['PS_MAIL_PORT'] ?? '587'));
        Configuration::updateValue('PS_MAIL_USERNAME', $data['PS_MAIL_USERNAME'] ?? '');
        Configuration::updateValue(
            'PS_MAIL_ENCRYPTION',
            ($data['PS_MAIL_ENCRYPTION'] ?? 'tls') === 'none' ? '' : ($data['PS_MAIL_ENCRYPTION'] ?? 'tls')
        );
        Configuration::updateValue('PS_MAIL_FROM_ADDRESS', $data['PS_MAIL_FROM_ADDRESS']);
        Configuration::updateValue('PS_MAIL_FROM_NAME', $data['PS_MAIL_FROM_NAME']);
        Configuration::updateValue('PS_MAIL_SUBJECT_PREFIX', $request->boolean('PS_MAIL_SUBJECT_PREFIX') ? '1' : '0');
        Configuration::updateValue('PS_MAIL_FORMAT', $data['PS_MAIL_FORMAT']);
        Configuration::updateValue('PS_MAIL_LOG', $request->boolean('PS_MAIL_LOG') ? '1' : '0');

        if (! empty($data['PS_MAIL_PASSWORD'])) {
            MailSettings::storePassword($data['PS_MAIL_PASSWORD']);
        }

        MailSettings::apply();

        return back()->with('success', 'E-mail settings saved.');
    }

    public function test(Request $request): RedirectResponse
    {
        $data = $request->validate([
            'test_email' => ['required', 'email'],
        ]);

        MailSettings::apply();

        $subject = MailSettings::subject('ERP SMTP test');
        $mailer = Configuration::get('PS_MAIL_MAILER', 'log');

        try {
            Mail::raw(
                'This is a test email from '.config('erp.name').'. Your e-mail configuration is working.',
                function ($message) use ($data, $subject) {
                    $message->to($data['test_email'])->subject($subject);
                }
            );

            $this->logEmail($data['test_email'], $subject, $mailer, $mailer === 'log' ? 'logged' : 'sent');

            if ($mailer === 'log') {
                return back()->with('success', 'Mail method is "Never send emails": the test was written to the log only, no real email was delivered.');
            }

            return back()->with('success', 'Test email sent to '.$data['test_email'].'.');
        } catch (Throwable $e) {
            $this->logEmail($data['test_email'], $subject, $mailer, 'failed', $e->getMessage());

            return back()->with('error', 'Mail failed: '.$e->getMessage());
        }
    }

    protected function logEmail(string $recipient, string $subject, string $mailer, string $status, ?string $error = null): void
    {
        // Always keep test/failed mail history when logging is enabled; failed always logged.
        if ($status === 'sent' && ! MailSettings::shouldLog()) {
            return;
        }

        try {
            EmailLog::query()->create([
                'recipient' => $recipient,
                'subject' => $subject,
                'mailer' => $mailer,
                'status' => $status,
                'error' => $error,
                'employee_id' => auth()->user()?->employee?->id,
            ]);
        } catch (Throwable) {
            // Don't break the mail flow if logging fails
        }
    }
}
