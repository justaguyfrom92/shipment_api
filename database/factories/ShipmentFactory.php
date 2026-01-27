<?php

namespace Database\Factories;

use App\Models\Shipment;
use Illuminate\Database\Eloquent\Factories\Factory;

class ShipmentFactory extends Factory
{
	protected $model = Shipment::class;

	public function definition(): array
	{
		$deliveryDate = $this->faker->dateTimeBetween('now', '+90 days');

		return [
			'tracking_number' => strtoupper($this->faker->bothify('???-########')),
			'supplier' => 'Supplier',
			'shipment_date' => now()->toDateString(),
			'expected_delivery' => $deliveryDate->format('Y-m-d'),
			'status' => 'pending',
			'notes' => $this->faker->optional(0.6)->sentence(rand(8, 20)),
			'total_packages' => 0,
		];
	}

	public function withProducts(int $count = null): static
	{
		return $this->afterCreating(function (Shipment $shipment) use ($count)
		{
			$productCount = $count ?? rand(5, 15);
			$products = \App\Models\Product::inRandomOrder()->limit($productCount)->get();

			if ($products->isEmpty())
			{
				$shipment->delete();
				return;
			}

			$totalPackages = 0;
			$allFulfilled = true;

			foreach ($products as $product)
			{
				$requestedAmount = rand(1, 50);
				$receivedAmount = rand(1, $requestedAmount);

				$totalPackages += $requestedAmount;

				if ($receivedAmount < $requestedAmount)
				{
					$allFulfilled = false;
				}

				$shipment->products()->attach($product->id, [
					'requested_amount' => $requestedAmount,
					'received_amount' => $receivedAmount,
				]);
			}

			$shipment->update([
				'total_packages' => $totalPackages,
				'status' => $allFulfilled ? 'fulfilled' : 'pending',
			]);
		});
	}
}
