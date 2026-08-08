<?php

namespace App\Console\Commands;

use App\Models\User;
use Illuminate\Console\Command;

class MakeSuperAdmin extends Command
{
    protected $signature = 'menuos:make-super-admin {email} {--force}';

    protected $description = 'Grant platform Super Admin access to an existing user';

    public function handle(): int
    {
        $user = User::query()->where('email', $this->argument('email'))->first();
        if (! $user) {
            $this->error('No user exists with that email address.');

            return self::FAILURE;
        }
        if ($user->isSuperAdmin()) {
            $this->info('The user is already a Super Admin.');

            return self::SUCCESS;
        }
        if (! $this->option('force') && ! $this->confirm("Grant Super Admin access to {$user->email}?")) {
            return self::FAILURE;
        }

        $user->forceFill(['is_super_admin' => true, 'account_status' => 'active'])->save();
        $this->info("Super Admin access granted to {$user->email}.");

        return self::SUCCESS;
    }
}
