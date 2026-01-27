<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;

class Product extends Model
{
	use HasFactory;

	protected $fillable = [
		'name',
		'sku',
		'unit_price',
		'description'
	];

	protected $casts = [
		'unit_price' => 'decimal:2'
	];

	public function shipments(): BelongsToMany
	{
		return $this->belongsToMany(Shipment::class, 'shipment_product')
			->withPivot('requested_amount', 'received_amount')
			->withTimestamps();
	}

	public function toArray(): array
	{
		return [
			'product_id' => $this->id,
			'product_name' => $this->name,
			'sku' => $this->sku,
			'unit_price' => $this->unit_price,
			'requested_amount' => $this->pivot->requested_amount,
			'received_amount' => $this->pivot->received_amount,
		];
	}
}

