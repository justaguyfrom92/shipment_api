<?php

namespace App\Services;

use App\Models\Shipment;
use Illuminate\Support\Facades\Storage;

class ShipmentExportService
{
	public function exportTodaysShipments(): array
	{
		$shipments = Shipment::with('products')
			->whereDate('shipment_date', now()->toDateString())
			->orderBy('created_at', 'desc')
			->get();

		$timestamp = now()->format('Y-m-d_His');
		$filepath = "logs/shipment/{$timestamp}.json";

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

		Storage::put($filepath, json_encode($exportData, JSON_PRETTY_PRINT));

		return [
			'success' => true,
			'filename' => storage_path("app/{$filepath}"),
			'count' => $shipments->count(),
		];
	}
}
