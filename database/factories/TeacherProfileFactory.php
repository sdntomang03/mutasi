<?php

namespace Database\Factories;

use App\Models\Sudin;
use App\Models\TeacherProfile;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;
use RuntimeException;
use Spatie\Permission\Models\Role;

/**
 * @extends Factory<TeacherProfile>
 */
class TeacherProfileFactory extends Factory
{
    protected $model = TeacherProfile::class;

    public function definition(): array
    {
        $sudins = Sudin::query()->with('districts')->get()
            ->filter(fn (Sudin $sudin) => $sudin->districts->isNotEmpty())
            ->values();
        if ($sudins->isEmpty()) {
            throw new RuntimeException('Seed at least one Sudin with assigned districts before creating a teacher profile.');
        }

        $sudin = $sudins->random();
        $district = $sudin->districts->random();
        $destinationSudins = $sudins->reject(fn (Sudin $candidate) => $candidate->is($sudin))->values();
        if ($destinationSudins->isEmpty()) {
            throw new RuntimeException('Seed at least two Sudins with assigned districts before creating a teacher profile.');
        }
        $destinationSudin = $destinationSudins->random();
        $faker = fake('id_ID');

        return [
            'user_id' => User::factory(),
            'name' => $faker->name(),
            'phone' => fake()->unique()->numerify('+628##########'),
            'employment_type' => fake()->randomElement(['PNS', 'PPPK', 'KKI']),
            'position' => 'guru_kelas',
            'level' => 'SD',
            'destination_position' => 'guru_kelas',
            'school_name' => 'SDN '.$faker->city().' '.fake()->numberBetween(1, 5),
            'school_address' => $faker->address(),
            'sudin_id' => $sudin->id,
            'destination_sudin_id' => $destinationSudin->id,
            'province_code' => '31',
            'regency_code' => $district->regency_code,
            'regency_name' => $district->regency_name,
            'district_code' => $district->code,
            'district_name' => $district->name,
            'village_code' => $district->code.'.1001',
            'village_name' => 'Kelurahan '.$faker->firstName(),
        ];
    }

    public function configure(): static
    {
        return $this->afterCreating(function (TeacherProfile $profile): void {
            if (! $profile->user->hasRole('guru')) {
                $profile->user->assignRole(Role::findOrCreate('guru', 'web'));
            }
            if ($profile->destinationLevels()->doesntExist()) {
                $profile->syncDestinationLevels(['SD']);
            }
            if (fake()->boolean(50)) {
                $profile->destinationDistricts()->sync(
                    $profile->destinationSudin->districts->random(fake()->numberBetween(1, $profile->destinationSudin->districts->count()))->pluck('code'),
                );
            }
        });
    }
}
