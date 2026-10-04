<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Spatie\Permission\Models\Role;

class DatabaseSeeder extends Seeder
{
    public function run(): void
    {
        Role::findOrCreate('guru', 'web');
        Role::findOrCreate('admin', 'web');

        if (app()->environment(['local', 'testing'])) {
            $this->call([
                AdminUserSeeder::class,
                DkiDistrictSeeder::class,
                DkiSudinSeeder::class,
                // FakeTeachersSeeder::class,
                // TargetedExchangeTeachersSeeder::class,
            ]);
        }
    }
}
