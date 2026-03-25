<?php

namespace Database\Factories;

use App\Models\Metal;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Carbon;

class MetalFactory extends Factory
{
    protected $model = Metal::class;

    public function definition(): array
    {
        $metalCatalog = [
            ['name' => 'Gold', 'code' => 'AU'],
            ['name' => 'Silver', 'code' => 'AG'],
            ['name' => 'Platinum', 'code' => 'PT'],
        ];

        $selectedMetal = $this->faker->randomElement($metalCatalog);

        return [
            'name' => $selectedMetal['name'],
            'code' => $selectedMetal['code'],
            'price' => $this->faker->randomFloat(2, 100, 100000),
            'created_at' => Carbon::now(),
            'updated_at' => Carbon::now(),
        ];
    }
}
