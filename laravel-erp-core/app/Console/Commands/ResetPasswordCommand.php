<?php

namespace App\Console\Commands;

use App\Models\User;
use Illuminate\Console\Command;

class ResetPasswordCommand extends Command
{
    protected $signature = 'erp:reset-password
        {email : Employee email}
        {--password= : New password (min 8 chars). If omitted, you will be prompted.}';

    protected $description = 'Emergency reset of an employee/superadmin password (when email reset is not available)';

    public function handle(): int
    {
        $email = $this->argument('email');
        $user = User::query()->where('email', $email)->where('user_type', 'employee')->first();

        if (! $user) {
            $this->error("No employee found for [{$email}].");

            return self::FAILURE;
        }

        $password = $this->option('password') ?: $this->secret('New password');

        if (! is_string($password) || strlen($password) < 8) {
            $this->error('Password must be at least 8 characters.');

            return self::FAILURE;
        }

        // Use plain value — User model hashed cast will hash it once
        $user->password = $password;
        $user->save();

        $this->info("Password updated for {$user->email}. They can log in now.");

        return self::SUCCESS;
    }
}
