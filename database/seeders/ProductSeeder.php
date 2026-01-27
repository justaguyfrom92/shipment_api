<?php

namespace Database\Seeders;

use App\Models\Product;
use Illuminate\Database\Seeder;

class ProductSeeder extends Seeder
{
	public function run(): void
	{
		// Create 30 random products using factory
		Product::factory()->count(30)->create();
	}
}
