<?php

namespace Database\Seeders;

use App\Models\Sudin;
use App\Models\TeacherProfile;
use App\Models\User;
use Illuminate\Database\Seeder;
use RuntimeException;
use Spatie\Permission\Models\Role;

class TargetedExchangeTeachersSeeder extends Seeder
{
    public function run(): void
    {
        $eastJakartaWest = $this->sudin('Suku Dinas Pendidikan Wilayah II Kota Administrasi Jakarta Timur');
        $westJakartaWest = $this->sudin('Suku Dinas Pendidikan Wilayah II Kota Administrasi Jakarta Barat');

        $this->seedTeacher(
            email: 'guru.demo.jaktim2@example.test',
            name: 'Guru Demo Jakarta Timur Wilayah II',
            phone: '+6281299999001',
            originSudin: $eastJakartaWest,
            destinationSudin: $westJakartaWest,
        );
        $this->seedTeacher(
            email: 'guru.demo.jakbar2@example.test',
            name: 'Guru Demo Jakarta Barat Wilayah II',
            phone: '+6281299999002',
            originSudin: $westJakartaWest,
            destinationSudin: $eastJakartaWest,
        );
    }

    private function sudin(string $name): Sudin
    {
        $sudin = Sudin::query()->with('districts')->where('name', $name)->first();
        if (! $sudin || $sudin->districts->isEmpty()) {
            throw new RuntimeException("Configure Sudin [{$name}] with assigned districts before seeding targeted exchange teachers.");
        }

        return $sudin;
    }

    private function seedTeacher(
        string $email,
        string $name,
        string $phone,
        Sudin $originSudin,
        Sudin $destinationSudin,
    ): void {
        $user = User::query()->updateOrCreate(
            ['email' => $email],
            ['name' => $name, 'password' => 'password', 'email_verified_at' => now()],
        );
        $user->syncRoles([Role::findOrCreate('guru', 'web')]);

        $originDistrict = $originSudin->districts->random();
        $profile = TeacherProfile::query()->updateOrCreate(
            ['user_id' => $user->id],
            [
                'name' => $name,
                'phone' => $phone,
                'employment_type' => fake()->randomElement(['PNS', 'PPPK', 'KKI']),
                'position' => 'guru_kelas',
                'level' => 'SD',
                'destination_position' => 'guru_kelas',
                'school_name' => 'SDN '.$originDistrict->name.' 1',
                'school_address' => fake('id_ID')->address(),
                'sudin_id' => $originSudin->id,
                'destination_sudin_id' => $destinationSudin->id,
                'province_code' => '31',
                'regency_code' => $originDistrict->regency_code,
                'regency_name' => $originDistrict->regency_name,
                'district_code' => $originDistrict->code,
                'district_name' => $originDistrict->name,
                'village_code' => $originDistrict->code.'.1001',
                'village_name' => 'Kelurahan Contoh',
            ],
        );
        $profile->destinationDistricts()->sync([]);
        $profile->syncDestinationLevels(['SD']);
        $profile->destinations()->delete();
    }
}
