<?php

namespace Database\Factories;

use App\Enums\CustomerStorageType;
use App\Models\Customer;
use App\Models\Metal;
use App\Models\Withdrawal;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Withdrawal>
 */
class WithdrawalFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'reference_number' => 'WDR-'.str_pad((string) $this->faker->unique()->numberBetween(1, 9999), 4, '0', STR_PAD_LEFT),
            'sequence_number' => $this->faker->unique()->numberBetween(1, 9999),
            'customer_id' => Customer::factory(),
            'metal_id' => Metal::factory(),
            'storage_type' => $this->faker->randomElement(CustomerStorageType::cases())->value,
            'quantity_kg' => $this->faker->randomFloat(4, 0.1, 100),
            'price_per_kg_snapshot' => $this->faker->randomFloat(2, 100, 100000),
            'value_snapshot' => $this->faker->randomFloat(2, 100, 1000000),
            'withdrawn_at' => $this->faker->dateTimeBetween('-2 years', 'now'),
        ];
    }
}
