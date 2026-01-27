<?php

namespace Database\Factories;

use App\Models\Product;
use Illuminate\Database\Eloquent\Factories\Factory;

class ProductFactory extends Factory
{
	protected $model = Product::class;

	public function definition(): array
	{
		return [
			'name' => $this->faker->words(rand(2, 4), true),
			'sku' => strtoupper($this->faker->bothify('???-####')),
			'unit_price' => $this->faker->randomFloat(2, 5, 5000),
			'description' => $this->faker->optional(0.7)->sentence(rand(5, 15)),
		];
	}
}

