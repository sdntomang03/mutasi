<?php

namespace Database\Factories;

use App\Models\Sudin;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Sudin>
 */
class SudinFactory extends Factory
{
    protected $model = Sudin::class;

    public function definition(): array
    {
        $faker = fake('id_ID');
        $city = $faker->randomElement([
            'Jakarta Pusat',
            'Jakarta Utara',
            'Jakarta Barat',
            'Jakarta Selatan',
            'Jakarta Timur',
            'Kepulauan Seribu',
        ]);

        return [
            'name' => $city.($city === 'Jakarta Barat' ? ' '.$faker->numberBetween(1, 2) : ''),
        ];
    }
}
