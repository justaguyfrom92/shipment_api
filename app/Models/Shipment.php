<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;

class Shipment extends Model
{
	use HasFactory;

	protected $fillable = [
		'tracking_number',
		'supplier',
		'shipment_date',
		'expected_delivery',
		'status',
		'total_packages',
		'notes'
	];

	protected $casts = [
		'shipment_date' => 'date',
		'expected_delivery' => 'date',
	];

	public function products(): BelongsToMany
	{
		return $this->belongsToMany(Product::class, 'shipment_product')
			->withPivot('requested_amount', 'received_amount')
			->withTimestamps();
	}

	public function inventory()
	{
		return $this->products->map(function ($product)
		{
			$product->toArray();
		});
	}

	public function updateFulfillmentStatus(): void
	{
		$allFulfilled = true;

		foreach ($this->products as $product)
		{
			if ($product->pivot->received_amount < $product->pivot->requested_amount)
			{
				$allFulfilled = false;
				break;
			}
		}

		$this->update(['status' => $allFulfilled ? 'fulfilled' : 'pending']);
	}
}

