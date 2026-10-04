<?php

namespace Database\Seeders;

use App\Models\Sudin;
use App\Models\TeacherProfile;
use App\Models\User;
use Faker\Generator;
use Illuminate\Database\Seeder;
use Spatie\Permission\Models\Role;

class FakeTeachersSeeder extends Seeder
{
    public function run(): void
    {
        $faker = fake('id_ID');
        $sudins = Sudin::query()
            ->with('districts')
            ->whereHas('districts')
            ->orderBy('id')
            ->get();

        if ($sudins->count() < 2) {
            throw new \RuntimeException('Configure at least two Sudins with assigned districts before seeding demo teachers.');
        }

        $pairs = [];
        for ($index = 0; $index < $sudins->count(); $index += 2) {
            $pairs[] = [
                $sudins[$index],
                $sudins->get($index + 1) ?? $sudins->first(),
            ];
        }

        foreach ($pairs as $pairIndex => [$firstSudin, $secondSudin]) {
            foreach ([[$firstSudin, $secondSudin], [$secondSudin, $firstSudin]] as [$originSudin, $destinationSudin]) {
                for ($teacherIndex = 1; $teacherIndex <= 2; $teacherIndex++) {
                    $number = $pairIndex * 4 + (($originSudin->id === $firstSudin->id) ? $teacherIndex : $teacherIndex + 2);
                    $this->seedTeacher(
                        $number,
                        $faker,
                        $originSudin,
                        $destinationSudin,
                    );
                }
            }
        }
    }

    private function seedTeacher(int $number, Generator $faker, Sudin $originSudin, Sudin $destinationSudin): void
    {
        $email = sprintf('guru.demo.%02d@example.test', $number);
        $existingUser = User::query()
            ->where('email', $email)
            ->first();
        if ($existingUser?->teacherProfile?->destination_sudin_id) {
            $existingUser->syncRoles([Role::findOrCreate('guru', 'web')]);

            return;
        }

        $generatedUser = User::factory()->make([
            'name' => $faker->name(),
            'email' => $email,
            'password' => 'password',
            'email_verified_at' => now(),
        ]);
        $user = User::query()->updateOrCreate(
            ['email' => $email],
            $generatedUser->only(['name', 'password', 'email_verified_at']),
        );
        $user->syncRoles([Role::findOrCreate('guru', 'web')]);

        $originDistrict = $originSudin->districts->random();
        $generatedProfile = TeacherProfile::factory()
            ->for($user)
            ->state([
                'name' => $user->name,
                'phone' => sprintf('+62812%08d', $number),
                'employment_type' => fake()->randomElement(['PNS', 'PPPK', 'KKI']),
                'school_name' => 'SDN '.$originDistrict->name.' '.fake()->numberBetween(1, 5),
                'school_address' => $faker->address(),
                'sudin_id' => $originSudin->id,
                'destination_sudin_id' => $destinationSudin->id,
                'province_code' => '31',
                'regency_code' => $originDistrict->regency_code,
                'regency_name' => $originDistrict->regency_name,
                'district_code' => $originDistrict->code,
                'district_name' => $originDistrict->name,
                'village_code' => $originDistrict->code.'.1001',
                'village_name' => 'Kelurahan '.$faker->firstName(),
            ])
            ->make();
        $profileAttributes = $generatedProfile->getAttributes();
        unset($profileAttributes['id'], $profileAttributes['user_id'], $profileAttributes['created_at'], $profileAttributes['updated_at']);
        $profile = TeacherProfile::query()->updateOrCreate(
            ['user_id' => $user->id],
            $profileAttributes,
        );

        $destinationDistrictCodes = $destinationSudin->districts->pluck('code');
        $profile->destinationDistricts()->sync($destinationDistrictCodes);
        $profile->destinations()->delete();
    }
}
