<?php

namespace App\Console\Commands;

use App\Models\Shipment;
use App\Models\Product;
use App\Services\ShipmentExportService;
use Illuminate\Console\Command;
use Carbon\Carbon;
use Illuminate\Support\Str;

class CreateShipmentCommand extends Command
{
	protected $signature = 'shipment:create {--start-date=} {--end-date=}';
	protected $description = 'Create shipments for specific date or date range';

	public function handle(ShipmentExportService $exportService): int
	{
		$startDate = $this->option('start-date');
		$endDate = $this->option('end-date');

		if (!$startDate)
		{
			echo('You must provide --start-date');
			echo('Examples:');
			echo('  php artisan shipment:create --start-date=2026-01-15');
			echo('  php artisan shipment:create --start-date=2026-01-01 --end-date=2026-01-31');
			return self::FAILURE;
		}

		$endDate = $endDate ?? $startDate;

		try
		{
			$start = Carbon::parse($startDate);
			$end = Carbon::parse($endDate);

			if ($start->greaterThan($end))
			{
				echo('Start date must be before or equal to end date');
				return self::FAILURE;
			}

			$productCount = Product::count();
			if ($productCount === 0)
			{
				echo('No products exist. Please seed products first.');
				return self::FAILURE;
			}

			if ($start->equalTo($end))
			{
				echo("Creating shipment for {$start->format('Y-m-d')}...");
			}
			else
			{
				echo("Creating shipments from {$start->format('Y-m-d')} to {$end->format('Y-m-d')}...");
			}

			$created = 0;
			$skipped = 0;
			$current = $start->copy();

			while ($current->lessThanOrEqualTo($end))
			{
				$existingShipment = Shipment::whereDate('shipment_date', $current->toDateString())->first();

				if ($existingShipment)
				{
					echo("  Skipped {$current->format('Y-m-d')} - already exists");
					$skipped++;
					$current->addDay();
					continue;
				}

				$shipment = $this->createShipmentForDate($current);
				echo("  ✓ Created shipment for {$current->format('Y-m-d')} - {$shipment->tracking_number}");

				// Export shipment log
				$exportResult = $exportService->exportShipmentForDate($current);
				if ($exportResult['success'])
				{
					echo("    ✓ Exported log: {$exportResult['filename']}");
				}

				$created++;
				$current->addDay();
			}

			echo("\nSummary:");
			echo("  Created: {$created}");
			if ($skipped > 0)
			{
				echo("  Skipped: {$skipped}");
			}

			return self::SUCCESS;
		}
		catch (\Exception $e)
		{
			echo('Error creating shipments: ' . $e->getMessage());
			return self::FAILURE;
		}
	}

	private function createShipmentForDate(Carbon $shipmentDate): Shipment
	{
		$shipment = Shipment::create([
			'tracking_number' => strtoupper(Str::random(3) . '-' . rand(10000000, 99999999)),
			'supplier' => 'Supplier',
			'shipment_date' => $shipmentDate->toDateString(),
			'expected_delivery' => $shipmentDate->toDateString(),
			'status' => 'pending',
			'notes' => fake()->optional(0.6)->sentence(rand(8, 20)),
			'total_packages' => 0,
		]);

		$productCount = rand(5, 15);
		$products = Product::inRandomOrder()->limit($productCount)->get();

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

		return $shipment;
	}
}
