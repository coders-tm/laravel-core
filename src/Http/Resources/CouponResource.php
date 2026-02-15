<?php

namespace Coderstm\Http\Resources;

use Coderstm\Http\Resources\Coupon\PlanResource;
use Coderstm\Http\Resources\Coupon\ProductResource;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class CouponResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return ['id' => $this->id, 'type' => $this->type, 'name' => $this->name, 'promotion_code' => $this->promotion_code, 'duration' => $this->duration, 'duration_in_months' => $this->duration_in_months, 'discount_type' => $this->discount_type, 'max_redemptions' => $this->max_redemptions, 'value' => $this->value, 'active' => $this->active, 'auto_apply' => $this->auto_apply, 'expires_at' => $this->expires_at, 'created_at' => $this->created_at, 'updated_at' => $this->updated_at, 'has_max_redemptions' => $this->whenAppended('has_max_redemptions'), 'specific_plans' => $this->whenAppended('specific_plans'), 'specific_products' => $this->whenAppended('specific_products'), 'has_expires_at' => $this->whenAppended('has_expires_at'), 'plans' => PlanResource::collection($this->whenLoaded('plans')), 'products' => ProductResource::collection($this->whenLoaded('products')), 'logs' => $this->whenLoaded('logs')];
    }
}
