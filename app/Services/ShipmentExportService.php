<?php

namespace App\Services;

use App\Models\Shipment;
use Illuminate\Support\Facades\File;
use Carbon\Carbon;

class ShipmentExportService
{
	public function exportTodaysShipments(): array
	{
		return $this->exportShipmentForDate(now());
	}

	public function exportShipmentForDate(Carbon $date): array
	{
		$shipmentsDir = storage_path('logs/shipments');

		if (!File::exists($shipmentsDir))
		{
			File::makeDirectory($shipmentsDir, 0755, true);
		}

		$shipment = Shipment::with('products')
			->whereDate('shipment_date', $date->toDateString())
			->orderBy('created_at', 'desc')
			->first();

		if (!$shipment)
		{
			return [
				'success' => false,
				'message' => 'No shipment found for ' . $date->format('Y-m-d'),
				'count' => 0,
			];
		}

		$timestamp = $date->format('Y-m-d_His');
		$filename = "{$shipmentsDir}/{$timestamp}.json";

		$exportData = [
			'generated_at' => now()->toIso8601String(),
			'shipment_date' => $date->format('Y-m-d'),
			'shipment' => [
				'id' => $shipment->id,
				'tracking_number' => $shipment->tracking_number,
				'supplier' => $shipment->supplier,
				'shipment_date' => $shipment->shipment_date->format('Y-m-d'),
				'expected_delivery' => $shipment->expected_delivery->format('Y-m-d'),
				'status' => $shipment->status,
				'total_packages' => $shipment->total_packages,
				'notes' => $shipment->notes,
				'inventory' => $shipment->products->map(function ($product)
				{
					return $product->toInventoryArray();
				})->values()->all(),
			],
		];

		File::put($filename, json_encode($exportData, JSON_PRETTY_PRINT));

		return [
			'success' => true,
			'filename' => $filename,
			'count' => 1,
		];
	}
}
