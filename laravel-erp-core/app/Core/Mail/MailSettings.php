<?php

namespace App\Core\Mail;

use App\Core\Configuration\Configuration;
use Illuminate\Support\Facades\Config;
use Illuminate\Support\Facades\Crypt;
use Illuminate\Support\Facades\Schema;
use Throwable;

class MailSettings
{
    public const KEYS = [
        'PS_MAIL_MAILER',
        'PS_MAIL_HOST',
        'PS_MAIL_PORT',
        'PS_MAIL_USERNAME',
        'PS_MAIL_PASSWORD',
        'PS_MAIL_ENCRYPTION',
        'PS_MAIL_FROM_ADDRESS',
        'PS_MAIL_FROM_NAME',
        'PS_MAIL_SUBJECT_PREFIX',
        'PS_MAIL_FORMAT',
        'PS_MAIL_LOG',
    ];

    /**
     * Apply saved SMTP settings to Laravel mail config (used for every outgoing mail).
     */
    public static function apply(): void
    {
        if (! Schema::hasTable('configurations')) {
            return;
        }

        $mailer = Configuration::get('PS_MAIL_MAILER');

        if (! $mailer) {
            return;
        }

        // Map ERP options → Laravel mailers
        $laravelMailer = match ($mailer) {
            'sendmail' => 'sendmail',
            'smtp' => 'smtp',
            'never', 'log' => 'log',
            default => $mailer,
        };

        Config::set('mail.default', $laravelMailer);

        if ($mailer === 'smtp') {
            $encryption = strtolower((string) Configuration::get('PS_MAIL_ENCRYPTION', 'tls'));
            $port = (int) Configuration::get('PS_MAIL_PORT', 587);

            [$scheme, $transportEncryption] = match ($encryption) {
                'ssl' => ['smtps', null],
                'tls' => ['smtp', 'tls'],
                default => ['smtp', null],
            };

            if ($encryption === 'ssl' && $port === 0) {
                $port = 465;
            }
            if ($encryption === 'tls' && $port === 0) {
                $port = 587;
            }

            Config::set('mail.mailers.smtp.transport', 'smtp');
            Config::set('mail.mailers.smtp.host', Configuration::get('PS_MAIL_HOST', '127.0.0.1'));
            Config::set('mail.mailers.smtp.port', $port);
            Config::set('mail.mailers.smtp.username', Configuration::get('PS_MAIL_USERNAME'));
            Config::set('mail.mailers.smtp.password', static::password());
            Config::set('mail.mailers.smtp.scheme', $scheme);
            Config::set('mail.mailers.smtp.encryption', $transportEncryption);
        }

        $fromAddress = Configuration::get('PS_MAIL_FROM_ADDRESS')
            ?: Configuration::get('PS_SHOP_EMAIL')
            ?: config('mail.from.address');

        $fromName = Configuration::get('PS_MAIL_FROM_NAME')
            ?: Configuration::get('PS_SHOP_NAME')
            ?: config('mail.from.name');

        Config::set('mail.from.address', $fromAddress);
        Config::set('mail.from.name', $fromName);

        try {
            app('mail.manager')->purge('smtp');
            app('mail.manager')->purge('sendmail');
            app('mail.manager')->purge();
        } catch (Throwable) {
            //
        }
    }

    public static function password(): ?string
    {
        $stored = Configuration::get('PS_MAIL_PASSWORD');

        if ($stored === null || $stored === '') {
            return null;
        }

        try {
            return Crypt::decryptString((string) $stored);
        } catch (Throwable) {
            return (string) $stored;
        }
    }

    public static function storePassword(?string $plain): void
    {
        if ($plain === null || $plain === '') {
            return;
        }

        Configuration::updateValue('PS_MAIL_PASSWORD', Crypt::encryptString($plain));
    }

    public static function shouldLog(): bool
    {
        return static::flag('PS_MAIL_LOG', true);
    }

    public static function subjectPrefixEnabled(): bool
    {
        return static::flag('PS_MAIL_SUBJECT_PREFIX', true);
    }

    /**
     * Config booleans may be stored/decoded as "1", 1, true, "true".
     */
    public static function flag(string $key, bool $default = false): bool
    {
        $value = Configuration::get($key, $default ? '1' : '0');

        if (is_bool($value)) {
            return $value;
        }

        return in_array((string) $value, ['1', 'true', 'yes', 'on'], true);
    }

    public static function subject(string $subject): string
    {
        if (! static::subjectPrefixEnabled()) {
            return $subject;
        }

        $shop = Configuration::get('PS_SHOP_NAME', config('erp.name'));

        if ($shop === '' || str_starts_with($subject, '['.$shop.']')) {
            return $subject;
        }

        return '['.$shop.'] '.$subject;
    }

    /**
     * @return array<string, mixed>
     */
    public static function formValues(): array
    {
        $mailer = Configuration::get('PS_MAIL_MAILER', config('mail.default', 'log'));
        if ($mailer === 'log') {
            $mailer = 'never';
        }

        return [
            'PS_MAIL_MAILER' => $mailer,
            'PS_MAIL_HOST' => Configuration::get('PS_MAIL_HOST', config('mail.mailers.smtp.host')),
            'PS_MAIL_PORT' => Configuration::get('PS_MAIL_PORT', config('mail.mailers.smtp.port', 587)),
            'PS_MAIL_USERNAME' => Configuration::get('PS_MAIL_USERNAME', ''),
            'PS_MAIL_ENCRYPTION' => Configuration::get('PS_MAIL_ENCRYPTION', 'tls') ?: 'none',
            'PS_MAIL_FROM_ADDRESS' => Configuration::get('PS_MAIL_FROM_ADDRESS', Configuration::get('PS_SHOP_EMAIL', config('mail.from.address'))),
            'PS_MAIL_FROM_NAME' => Configuration::get('PS_MAIL_FROM_NAME', Configuration::get('PS_SHOP_NAME', config('erp.name'))),
            'PS_MAIL_SUBJECT_PREFIX' => Configuration::get('PS_MAIL_SUBJECT_PREFIX', '1'),
            'PS_MAIL_FORMAT' => Configuration::get('PS_MAIL_FORMAT', 'both'),
            'PS_MAIL_LOG' => Configuration::get('PS_MAIL_LOG', '1'),
            'has_password' => filled(Configuration::get('PS_MAIL_PASSWORD')),
        ];
    }

    public static function isSmtpReady(): bool
    {
        return Configuration::get('PS_MAIL_MAILER') === 'smtp'
            && filled(Configuration::get('PS_MAIL_HOST'))
            && filled(Configuration::get('PS_MAIL_FROM_ADDRESS'));
    }
}
