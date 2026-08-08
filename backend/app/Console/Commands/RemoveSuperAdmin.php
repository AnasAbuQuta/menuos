<?php

namespace App\Console\Commands;

use App\Models\User;
use Illuminate\Console\Command;

class RemoveSuperAdmin extends Command
{
    protected $signature = 'menuos:remove-super-admin {email} {--force}';

    protected $description = 'Remove platform Super Admin access from a user';

    public function handle(): int
    {
        $user = User::query()->where('email', $this->argument('email'))->first();
        if (! $user) {
            $this->error('No user exists with that email address.');

            return self::FAILURE;
        }
        if (! $user->isSuperAdmin()) {
            $this->info('The user is not a Super Admin.');

            return self::SUCCESS;
        }
        if (User::query()->where('is_super_admin', true)->count() <= 1) {
            $this->error('The final Super Admin cannot be removed.');

            return self::FAILURE;
        }
        if (! $this->option('force') && ! $this->confirm("Remove Super Admin access from {$user->email}?")) {
            return self::FAILURE;
        }

        $user->forceFill(['is_super_admin' => false])->save();
        $this->info("Super Admin access removed from {$user->email}.");

        return self::SUCCESS;
    }
}
