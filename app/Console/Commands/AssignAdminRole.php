<?php

namespace App\Console\Commands;

use App\Models\User;
use Illuminate\Console\Command;
use Spatie\Permission\Models\Role;

class AssignAdminRole extends Command
{
    protected $signature = 'app:assign-admin {email : Email address of the registered user}';

    protected $description = 'Grant the admin role to an existing account';

    public function handle(): int
    {
        $user = User::query()->where('email', $this->argument('email'))->first();

        if (! $user) {
            $this->error('No registered account exists for this email address.');

            return self::FAILURE;
        }

        Role::findOrCreate('admin', 'web');
        $user->syncRoles(['admin']);

        $this->info("Admin role assigned to {$user->email}.");

        return self::SUCCESS;
    }
}
