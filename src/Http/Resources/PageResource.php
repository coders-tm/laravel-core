<?php

namespace Coderstm\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class PageResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return ['id' => $this->id, 'title' => $this->title, 'slug' => $this->slug, 'parent' => $this->parent, 'meta_title' => $this->meta_title, 'meta_keywords' => $this->meta_keywords, 'meta_description' => $this->meta_description, 'is_active' => $this->is_active, 'template' => $this->template, 'options' => $this->options, 'created_at' => $this->created_at, 'updated_at' => $this->updated_at];
    }
}
