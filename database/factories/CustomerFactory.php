<?php

namespace Database\Factories;

use App\Enums\CustomerAccountType;
use App\Enums\CustomerStorageType;
use App\Models\Customer;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Carbon;

class CustomerFactory extends Factory
{
    protected $model = Customer::class;

    public function definition(): array
    {
        return [
            'name' => $this->faker->name(),
            'email' => $this->faker->unique()->safeEmail(),
            'account_type' => $this->faker->randomElement(CustomerAccountType::cases())->value,
            'storage_type' => $this->faker->randomElement(CustomerStorageType::cases())->value,
            'portfolio_value' => $this->faker->randomFloat(2, 0, 500000),
            'joined_at' => $this->faker->dateTimeBetween('-5 years'),
            'created_at' => Carbon::now(),
            'updated_at' => Carbon::now(),
        ];
    }
}
