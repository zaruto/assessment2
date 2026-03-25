<?php

namespace Database\Factories;

use App\Models\Bar;
use App\Models\Deposit;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Bar>
 */
class BarFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'deposit_id' => Deposit::factory(),
            'serial_number' => strtoupper($this->faker->bothify('??-####-#####')),
        ];
    }
}
