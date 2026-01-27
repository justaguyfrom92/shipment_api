<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Shipment;
use App\Models\Product;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;

class ShipmentController extends Controller
{
	public function index(Request $request): JsonResponse
	{
		$query = Shipment::with('products');

		if ($request->has('status'))
		{
			$query->where('status', $request->status);
		}

		if ($request->has('from_date'))
		{
			$query->where('shipment_date', '>=', $request->from_date);
		}

		if ($request->has('to_date'))
		{
			$query->where('shipment_date', '<=', $request->to_date);
		}

		if ($request->has('supplier'))
		{
			$query->where('supplier', 'like', '%' . $request->supplier . '%');
		}

		$shipments = $query->orderBy('shipment_date', 'desc')
			->paginate($request->get('per_page', 15));

		$data = $shipments->getCollection()->map(function ($shipment)
		{
			return [
				'id' => $shipment->id,
				'tracking_number' => $shipment->tracking_number,
				'supplier' => $shipment->supplier,
				'shipment_date' => $shipment->shipment_date->format('Y-m-d'),
				'expected_delivery' => $shipment->expected_delivery->format('Y-m-d'),
				'status' => $shipment->status,
				'total_packages' => $shipment->total_packages,
				'notes' => $shipment->notes,
				'inventory' => $shipment->inventory(),
				'created_at' => $shipment->created_at->toIso8601String(),
			];
		});

		return response()->json([
			'success' => true,
			'data' => $data,
			'pagination' => [
				'current_page' => $shipments->currentPage(),
				'per_page' => $shipments->perPage(),
				'total' => $shipments->total(),
			]
		]);
	}

	public function show(string $id): JsonResponse
	{
		$shipment = Shipment::with('products')->find($id);

		if (!$shipment)
		{
			return response()->json([
				'success' => false,
				'message' => 'Shipment not found'
			], 404);
		}

		return response()->json([
			'success' => true,
			'data' => [
				'id' => $shipment->id,
				'tracking_number' => $shipment->tracking_number,
				'supplier' => $shipment->supplier,
				'shipment_date' => $shipment->shipment_date->format('Y-m-d'),
				'expected_delivery' => $shipment->expected_delivery->format('Y-m-d'),
				'status' => $shipment->status,
				'total_packages' => $shipment->total_packages,
				'notes' => $shipment->notes,
				'inventory' => $shipment->inventory(),
				'created_at' => $shipment->created_at->toIso8601String(),
			]
		]);
	}

	public function store(Request $request): JsonResponse
	{
		$validated = $request->validate([
			'tracking_number' => 'required|string|unique:shipments',
			'supplier' => 'required|string',
			'shipment_date' => 'required|date',
			'expected_delivery' => 'required|date',
			'status' => 'sometimes|in:pending,fulfilled',
			'notes' => 'nullable|string',
			'products' => 'required|array|min:1',
			'products.*.product_id' => 'required|exists:products,id',
			'products.*.requested_amount' => 'required|integer|min:1',
			'products.*.received_amount' => 'sometimes|integer|min:0',
		]);

		$totalPackages = 0;
		foreach ($validated['products'] as $productData)
		{
			$totalPackages += $productData['requested_amount'];
		}

		$shipment = Shipment::create([
			'tracking_number' => $validated['tracking_number'],
			'supplier' => $validated['supplier'],
			'shipment_date' => $validated['shipment_date'],
			'expected_delivery' => $validated['expected_delivery'],
			'status' => $validated['status'] ?? 'pending',
			'total_packages' => $totalPackages,
			'notes' => $validated['notes'] ?? null,
		]);

		foreach ($validated['products'] as $productData)
		{
			$shipment->products()->attach($productData['product_id'], [
				'requested_amount' => $productData['requested_amount'],
				'received_amount' => $productData['received_amount'] ?? 0,
			]);
		}

		$shipment->load('products');
		$shipment->updateFulfillmentStatus();

		return response()->json([
			'success' => true,
			'message' => 'Shipment created successfully',
			'data' => [
				'id' => $shipment->id,
				'tracking_number' => $shipment->tracking_number,
				'supplier' => $shipment->supplier,
				'shipment_date' => $shipment->shipment_date->format('Y-m-d'),
				'expected_delivery' => $shipment->expected_delivery->format('Y-m-d'),
				'status' => $shipment->status,
				'total_packages' => $shipment->total_packages,
				'notes' => $shipment->notes,
				'inventory' => $shipment->inventory(),
			]
		], 201);
	}

	public function update(Request $request, string $id): JsonResponse
	{
		$shipment = Shipment::find($id);

		if (!$shipment)
		{
			return response()->json([
				'success' => false,
				'message' => 'Shipment not found'
			], 404);
		}

		$validated = $request->validate([
			'tracking_number' => 'sometimes|string|unique:shipments,tracking_number,' . $id,
			'supplier' => 'sometimes|string',
			'shipment_date' => 'sometimes|date',
			'expected_delivery' => 'sometimes|date',
			'status' => 'sometimes|in:pending,fulfilled',
			'notes' => 'nullable|string',
			'products' => 'sometimes|array',
			'products.*.product_id' => 'required|exists:products,id',
			'products.*.requested_amount' => 'required|integer|min:1',
			'products.*.received_amount' => 'sometimes|integer|min:0',
		]);

		$updateData = array_filter([
			'tracking_number' => $validated['tracking_number'] ?? null,
			'supplier' => $validated['supplier'] ?? null,
			'shipment_date' => $validated['shipment_date'] ?? null,
			'expected_delivery' => $validated['expected_delivery'] ?? null,
			'status' => $validated['status'] ?? null,
			'notes' => $validated['notes'] ?? null,
		]);

		if (isset($validated['products']))
		{
			$totalPackages = 0;
			foreach ($validated['products'] as $productData)
			{
				$totalPackages += $productData['requested_amount'];
			}
			$updateData['total_packages'] = $totalPackages;

			$shipment->products()->detach();
			foreach ($validated['products'] as $productData)
			{
				$shipment->products()->attach($productData['product_id'], [
					'requested_amount' => $productData['requested_amount'],
					'received_amount' => $productData['received_amount'] ?? 0,
				]);
			}
		}

		$shipment->update($updateData);
		$shipment->load('products');
		$shipment->updateFulfillmentStatus();

		return response()->json([
			'success' => true,
			'message' => 'Shipment updated successfully',
			'data' => [
				'id' => $shipment->id,
				'tracking_number' => $shipment->tracking_number,
				'supplier' => $shipment->supplier,
				'shipment_date' => $shipment->shipment_date->format('Y-m-d'),
				'expected_delivery' => $shipment->expected_delivery->format('Y-m-d'),
				'status' => $shipment->status,
				'total_packages' => $shipment->total_packages,
				'notes' => $shipment->notes,
				'inventory' => $shipment->inventory(),
			]
		]);
	}

	public function todaysShipments(): JsonResponse
	{
		$shipment = Shipment::with('products')
			->whereDate('shipment_date', now()->toDateString())
			->orderBy('created_at', 'desc')
			->first();

		if (!$shipment)
		{
			return response()->json([
				'success' => false,
				'message' => 'No shipment found for today',
			], 404);
		}

		$data = [
			'id' => $shipment->id,
			'tracking_number' => $shipment->tracking_number,
			'supplier' => $shipment->supplier,
			'shipment_date' => $shipment->shipment_date->format('Y-m-d'),
			'expected_delivery' => $shipment->expected_delivery->format('Y-m-d'),
			'status' => $shipment->status,
			'total_packages' => $shipment->total_packages,
			'notes' => $shipment->notes,
			'inventory' => $shipment->inventory(),
			'created_at' => $shipment->created_at->toIso8601String(),
		];

		return response()->json([
			'success' => true,
			'data' => $data
		]);
	}
}
