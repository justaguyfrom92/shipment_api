<?php

namespace Database\Factories;

use App\Models\Shipment;
use Illuminate\Database\Eloquent\Factories\Factory;

class ShipmentFactory extends Factory
{
	protected $model = Shipment::class;

	public function definition(): array
	{
		$shipmentDate = now()->toDateString();
		$expectedDelivery = now()->addDays($this->faker->numberBetween(1, 30))->toDateString();

		return [
			'tracking_number' => strtoupper($this->faker->bothify('???-########')),
			'supplier' => $this->faker->company(),
			'shipment_date' => $shipmentDate,
			'expected_delivery' => $expectedDelivery,
			'status' => $this->faker->randomElement(['pending', 'in_transit', 'delivered', 'delayed']),
			'notes' => $this->faker->optional(0.6)->sentence(rand(8, 20)),
		];
	}

	public function withProducts(int $count = null): static
	{
		return $this->afterCreating(function (Shipment $shipment) use ($count)
		{
			$productCount = $count ?? rand(1, 8);
			$products = \App\Models\Product::inRandomOrder()->limit($productCount)->get();

			foreach ($products as $product)
			{
				$requestedAmount = rand(1, 1000);

				$receivedAmount = match($shipment->status)
				{
					'delivered' => $requestedAmount,
					'in_transit' => rand(0, (int)($requestedAmount * 0.5)),
					'delayed' => rand(0, (int)($requestedAmount * 0.3)),
					default => 0,
				};

				$shipment->products()->attach($product->id, [
					'requested_amount' => $requestedAmount,
					'received_amount' => $receivedAmount,
				]);
			}
		});
	}
}
