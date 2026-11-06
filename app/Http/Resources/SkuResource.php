<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class SkuResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'sku' => $this->sku,
            'name' => $this->localized('name'),
            'stock_quantity' => $this->stock_quantity,
            'price' => $this->price,
            'currency' => $this->currency,
            'quantity_unit' => $this->quantity_unit,
        ];
    }
}
