<?php

namespace App\Http\Resources\Public;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;
use Illuminate\Support\Facades\Storage;

class PublicMenuItemResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'name' => $this->name,
            'description' => $this->description,
            'price' => $this->price,
            'image_url' => $this->image ? Storage::disk('public')->url($this->image) : null,
            'is_featured' => $this->is_featured,
            'sort_order' => $this->sort_order,
            'category_id' => $this->category_id,
        ];
    }
}
