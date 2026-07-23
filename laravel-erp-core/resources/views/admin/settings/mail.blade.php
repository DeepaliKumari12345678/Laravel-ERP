@extends('admin.layouts.app')

@section('title', 'E-mail')

@section('content')
@php
    $mailer = old('PS_MAIL_MAILER', $mail['PS_MAIL_MAILER']);
    $enc = old('PS_MAIL_ENCRYPTION', $mail['PS_MAIL_ENCRYPTION'] ?: 'none');
    $format = old('PS_MAIL_FORMAT', $mail['PS_MAIL_FORMAT']);
    $prefixOn = (string) old('PS_MAIL_SUBJECT_PREFIX', $mail['PS_MAIL_SUBJECT_PREFIX']) === '1';
    $logOn = (string) old('PS_MAIL_LOG', $mail['PS_MAIL_LOG']) === '1';
@endphp

<style>
    .mail-form-row {
        display:grid; grid-template-columns: 240px 1fr; gap:1rem; align-items:start;
        padding:0.9rem 0; border-bottom:1px solid #f0f2f4;
    }
    .mail-form-row:last-of-type { border-bottom:0; }
    .mail-label { font-weight:600; color:var(--ps-ink); padding-top:0.35rem; }
    .mail-label .req { color:var(--danger); }
    .mail-hint { color:var(--ps-muted); font-size:0.78rem; margin-top:0.35rem; }
    .mail-radios { display:grid; gap:0.55rem; }
    .mail-radios label { display:flex; align-items:flex-start; gap:0.5rem; color:var(--ps-ink); font-size:0.9rem; }
    .mail-radios input { width:auto; margin-top:0.2rem; }
    .mail-switch-row { display:flex; align-items:center; gap:0.75rem; }
    .mail-switch {
        position:relative; width:44px; height:24px; border-radius:12px; border:0; cursor:pointer;
        background:#bbcdd2; transition:background .15s; flex-shrink:0;
    }
    .mail-switch.on { background:#70b580; }
    .mail-switch::after {
        content:''; position:absolute; top:3px; left:3px; width:18px; height:18px;
        border-radius:50%; background:#fff; transition:left .15s;
    }
    .mail-switch.on::after { left:23px; }
    .mail-info {
        background:#eef7fb; border:1px solid #cfe8f1; color:#1e6f84;
        padding:0.75rem 0.85rem; border-radius:4px; font-size:0.85rem; margin:0.75rem 0;
    }
    .mail-actions { display:flex; justify-content:flex-end; margin-top:1rem; padding-top:1rem; border-top:1px solid var(--ps-line); }
    .mail-smtp { display:none; margin-top:0.5rem; padding:0.85rem; border:1px solid var(--ps-line); border-radius:4px; background:#fafbfc; }
    .mail-smtp.show { display:block; }
    .mail-empty { text-align:center; color:var(--ps-muted); padding:1.5rem 1rem; }
    @media (max-width:800px) { .mail-form-row { grid-template-columns:1fr; } }
</style>

<div class="ps-breadcrumb">Advanced Parameters &gt; E-mail</div>

<div style="display:flex;justify-content:space-between;align-items:center;gap:1rem;flex-wrap:wrap;margin-bottom:1rem;">
    <h1 class="page-title" style="margin:0;">E-mail</h1>
</div>

{{-- Email logs --}}
<div class="card" style="margin-bottom:1rem;">
    <div class="card-head">
        <h3 style="margin:0;">Email ({{ $logs->total() }})</h3>
    </div>
    @if($logs->total() === 0)
        <div class="mail-empty">No records found</div>
    @else
        <div style="overflow-x:auto;">
            <table>
                <thead>
                <tr>
                    <th>ID</th>
                    <th>Recipient</th>
                    <th>Subject</th>
                    <th>Mailer</th>
                    <th>Status</th>
                    <th>Date</th>
                </tr>
                </thead>
                <tbody>
                @foreach($logs as $log)
                    <tr>
                        <td>{{ $log->id }}</td>
                        <td>{{ $log->recipient }}</td>
                        <td>{{ $log->subject ?: '—' }}</td>
                        <td>{{ $log->mailer ?: '—' }}</td>
                        <td>
                            <span class="badge {{ $log->status === 'sent' ? 'badge-on' : 'badge-off' }}"
                                  @if($log->status === 'failed') style="background:#fde8e6;color:var(--danger);"
                                  @elseif($log->status === 'logged') style="background:#fff3d6;color:#8a6100;" @endif
                                  title="{{ $log->status === 'logged' ? 'Written to log file only — not delivered to a real inbox' : $log->error }}">
                                {{ $log->status === 'logged' ? 'Log only' : ucfirst($log->status) }}
                            </span>
                        </td>
                        <td>{{ $log->created_at?->format('Y-m-d H:i') }}</td>
                    </tr>
                @endforeach
                </tbody>
            </table>
        </div>
        <div style="margin-top:0.75rem;">{{ $logs->links() }}</div>
    @endif
</div>

{{-- Configuration --}}
<div class="card" style="margin-bottom:1rem;">
    <div class="card-head">
        <h3 style="margin:0;display:flex;align-items:center;gap:0.4rem;">
            <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.7"><circle cx="12" cy="12" r="3"/><path d="M12 2v2M12 20v2M4.9 4.9l1.4 1.4M17.7 17.7l1.4 1.4M2 12h2M20 12h2M4.9 19.1l1.4-1.4M17.7 6.3l1.4-1.4"/></svg>
            E-mail configuration
        </h3>
    </div>

    <form method="post" action="{{ route('admin.mail.update') }}" id="mail-config-form">
        @csrf

        <div class="mail-form-row">
            <div class="mail-label">From email <span class="req">*</span></div>
            <div>
                <input type="email" name="PS_MAIL_FROM_ADDRESS" value="{{ old('PS_MAIL_FROM_ADDRESS', $mail['PS_MAIL_FROM_ADDRESS']) }}" required>
            </div>
        </div>

        <div class="mail-form-row">
            <div class="mail-label">From name <span class="req">*</span></div>
            <div>
                <input name="PS_MAIL_FROM_NAME" value="{{ old('PS_MAIL_FROM_NAME', $mail['PS_MAIL_FROM_NAME']) }}" required>
            </div>
        </div>

        <div class="mail-form-row">
            <div class="mail-label">Email sending method <span class="req">*</span></div>
            <div class="mail-radios">
                <label>
                    <input type="radio" name="PS_MAIL_MAILER" value="sendmail" @checked($mailer === 'sendmail') data-mailer>
                    Use PHP / sendmail (recommended when available on the server)
                </label>
                <label>
                    <input type="radio" name="PS_MAIL_MAILER" value="smtp" @checked($mailer === 'smtp') data-mailer>
                    Set my own SMTP parameters (for advanced users)
                </label>
                <label>
                    <input type="radio" name="PS_MAIL_MAILER" value="never" @checked($mailer === 'never') data-mailer>
                    Never send emails (log only — useful for testing)
                </label>

                <div id="smtp-box" class="mail-smtp {{ $mailer === 'smtp' ? 'show' : '' }}">
                    <div class="form-row">
                        <label>SMTP host
                            <input name="PS_MAIL_HOST" value="{{ old('PS_MAIL_HOST', $mail['PS_MAIL_HOST']) }}" placeholder="smtp.hostinger.com">
                        </label>
                        <label>Port
                            <input type="number" name="PS_MAIL_PORT" value="{{ old('PS_MAIL_PORT', $mail['PS_MAIL_PORT']) }}" placeholder="465">
                        </label>
                    </div>
                    <div class="form-row">
                        <label>Username
                            <input name="PS_MAIL_USERNAME" value="{{ old('PS_MAIL_USERNAME', $mail['PS_MAIL_USERNAME']) }}" autocomplete="off">
                        </label>
                        <label>Password {{ $mail['has_password'] ? '(leave blank to keep)' : '' }}
                            <input type="password" name="PS_MAIL_PASSWORD" value="" autocomplete="new-password" placeholder="{{ $mail['has_password'] ? '••••••••' : '' }}">
                        </label>
                    </div>
                    <label>Encryption
                        <select name="PS_MAIL_ENCRYPTION">
                            <option value="tls" @selected($enc === 'tls')>TLS / STARTTLS (port 587)</option>
                            <option value="ssl" @selected($enc === 'ssl')>SSL / SMTPS (port 465)</option>
                            <option value="none" @selected($enc === 'none')>None</option>
                        </select>
                    </label>
                    <div class="mail-hint">Hostinger: use SSL + 465, and From email should match your mailbox domain.</div>
                </div>
            </div>
        </div>

        <div class="mail-form-row">
            <div class="mail-label">Subject prefix <span class="req">*</span></div>
            <div>
                <div class="mail-switch-row">
                    <button type="button" class="mail-switch {{ $prefixOn ? 'on' : '' }}" data-switch="PS_MAIL_SUBJECT_PREFIX"></button>
                    <span class="switch-text">{{ $prefixOn ? 'Yes' : 'No' }}</span>
                    <input type="hidden" name="PS_MAIL_SUBJECT_PREFIX" value="{{ $prefixOn ? '1' : '0' }}">
                </div>
                <div class="mail-hint">Enable the shop name as a prefix in the email subject.</div>
            </div>
        </div>

        <div class="mail-form-row">
            <div class="mail-label">Email format <span class="req">*</span></div>
            <div class="mail-radios">
                <label><input type="radio" name="PS_MAIL_FORMAT" value="html" @checked($format === 'html')> Send email in HTML format</label>
                <label><input type="radio" name="PS_MAIL_FORMAT" value="text" @checked($format === 'text')> Send email in text format</label>
                <label><input type="radio" name="PS_MAIL_FORMAT" value="both" @checked($format === 'both')> Both</label>
            </div>
        </div>

        <div class="mail-form-row">
            <div class="mail-label">Log emails <span class="req">*</span></div>
            <div>
                <div class="mail-switch-row">
                    <button type="button" class="mail-switch {{ $logOn ? 'on' : '' }}" data-switch="PS_MAIL_LOG"></button>
                    <span class="switch-text">{{ $logOn ? 'Yes' : 'No' }}</span>
                    <input type="hidden" name="PS_MAIL_LOG" value="{{ $logOn ? '1' : '0' }}">
                </div>
                <div class="mail-hint">Keep a history of emails sent from this ERP (shown above).</div>
            </div>
        </div>

        <div class="mail-info">
            Tip: use a real mailbox (SMTP) for forgot-password and customer emails. Prefer From address that matches your SMTP username domain to avoid spam filters.
            <br><br>
            <strong>Automatic emails:</strong> customer created · order created / status change · employee / Super Admin created · password reset · password changed · test mail.
        </div>

        <div class="mail-actions">
            <button class="btn btn-primary" type="submit">Save</button>
        </div>
    </form>
</div>

{{-- Test --}}
<div class="card">
    <div class="card-head">
        <h3 style="margin:0;display:flex;align-items:center;gap:0.4rem;">
            <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.7"><circle cx="12" cy="12" r="3"/><path d="M12 2v2M12 20v2M4.9 4.9l1.4 1.4M17.7 17.7l1.4 1.4M2 12h2M20 12h2M4.9 19.1l1.4-1.4M17.7 6.3l1.4-1.4"/></svg>
            Test your email configuration
        </h3>
    </div>

    <form method="post" action="{{ route('admin.mail.test') }}">
        @csrf
        <div class="mail-form-row" style="border:0;padding-top:0;">
            <div class="mail-label">Send a test email to <span class="req">*</span></div>
            <div>
                <input type="email" name="test_email" value="{{ old('test_email', auth()->user()->email) }}" required>
                <div class="mail-hint">Save settings first, then send a test.</div>
            </div>
        </div>
        <div class="mail-actions">
            <button class="btn btn-primary" type="submit">Send a test email</button>
        </div>
    </form>
</div>

@push('scripts')
<script>
document.querySelectorAll('[data-mailer]').forEach(radio => {
    radio.addEventListener('change', () => {
        document.getElementById('smtp-box').classList.toggle('show', radio.value === 'smtp' && radio.checked);
    });
});
document.querySelectorAll('[data-switch]').forEach(btn => {
    btn.addEventListener('click', () => {
        const name = btn.dataset.switch;
        const input = btn.parentElement.querySelector(`input[name="${name}"]`);
        const text = btn.parentElement.querySelector('.switch-text');
        const on = input.value !== '1';
        input.value = on ? '1' : '0';
        btn.classList.toggle('on', on);
        text.textContent = on ? 'Yes' : 'No';
    });
});
</script>
@endpush
@endsection
