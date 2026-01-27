<?php

namespace App\Services;

use App\Models\Shipment;
use Illuminate\Support\Facades\File;

class ShipmentExportService
{
	public function exportTodaysShipments(): array
	{
		$shipmentsDir = base_path('shipments');

		if (!File::exists($shipmentsDir))
		{
			File::makeDirectory($shipmentsDir, 0755, true);
		}

		$shipments = Shipment::with('products')
			->whereDate('shipment_date', now()->toDateString())
			->orderBy('created_at', 'desc')
			->get();

		$timestamp = now()->format('Y-m-d_His');
		$filename = "{$shipmentsDir}/{$timestamp}.json";

		$exportData = [
			'generated_at' => now()->toIso8601String(),
			'shipment_date' => now()->format('Y-m-d'),
			'total_shipments' => $shipments->count(),
			'shipments' => $shipments->map(function ($shipment)
			{
				return [
					'id' => $shipment->id,
					'tracking_number' => $shipment->tracking_number,
					'supplier' => $shipment->supplier,
					'shipment_date' => $shipment->shipment_date->format('Y-m-d'),
					'expected_delivery' => $shipment->expected_delivery->format('Y-m-d'),
					'status' => $shipment->status,
					'notes' => $shipment->notes,
					'inventory' => $shipment->products->map(function ($product)
					{
						return $product->toArray();
					})->values()->all(),
				];
			})->values()->all(),
		];

		File::put($filename, json_encode($exportData, JSON_PRETTY_PRINT));

		return [
			'success' => true,
			'filename' => $filename,
			'count' => $shipments->count(),
		];
	}
}
