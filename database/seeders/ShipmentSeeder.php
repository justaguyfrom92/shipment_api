<?php

namespace Database\Seeders;

use App\Models\Shipment;
use App\Models\Product;
use Illuminate\Database\Seeder;

class ShipmentSeeder extends Seeder
{
	public function run(): void
	{
		// Create random shipments for today using factories
		Shipment::factory()
			->count(1)
			->withProducts()
			->create();
	}
}
