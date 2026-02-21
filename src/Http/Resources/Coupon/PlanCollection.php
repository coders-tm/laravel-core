<?php

namespace Coderstm\Http\Resources\Coupon;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\ResourceCollection;

class PlanCollection extends ResourceCollection
{
    public $collects = PlanResource::class;

    public function toArray(Request $request): array
    {
        return parent::toArray($request);
    }
}
