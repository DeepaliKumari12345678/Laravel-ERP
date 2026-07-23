<?php

namespace App\Core\Mail;

use App\Models\EmailLog;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\View;
use Throwable;

class ErpMail
{
    /**
     * Send an ERP email using current mail settings and optionally log it.
     *
     * @param  array<string, mixed>  $data
     */
    public static function send(?string $to, string $subject, string $view, array $data = [], bool $forceLog = false): bool
    {
        $to = trim((string) $to);

        if ($to === '' || ! filter_var($to, FILTER_VALIDATE_EMAIL)) {
            return false;
        }

        MailSettings::apply();

        $subject = MailSettings::subject($subject);
        $mailer = (string) (\App\Core\Configuration\Configuration::get('PS_MAIL_MAILER', 'log') ?: 'log');
        $html = View::make($view, array_merge($data, [
            'shopName' => \App\Core\Configuration\Configuration::get('PS_SHOP_NAME', config('erp.name')),
            'subject' => $subject,
        ]))->render();

        try {
            Mail::html($html, function ($message) use ($to, $subject) {
                $message->to($to)->subject($subject);
            });

            // "log" mailer never reaches a real inbox — record it honestly.
            static::log($to, $subject, $mailer, $mailer === 'log' ? 'logged' : 'sent', null, $forceLog);

            return true;
        } catch (Throwable $e) {
            static::log($to, $subject, $mailer, 'failed', $e->getMessage(), true);

            report($e);

            return false;
        }
    }

    public static function log(
        string $recipient,
        string $subject,
        string $mailer,
        string $status,
        ?string $error = null,
        bool $force = false
    ): void {
        if ($status === 'sent' && ! $force && ! MailSettings::shouldLog()) {
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
            //
        }
    }
}
