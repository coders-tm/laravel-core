<?php

namespace Coderstm\Http\Resources\Coupon;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class ProductResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return ['id' => $this->id, 'label' => $this->title];
    }
}
