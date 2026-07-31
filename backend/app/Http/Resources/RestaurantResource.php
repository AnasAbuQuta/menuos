<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class RestaurantResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'name' => $this->name,
            'slug' => $this->slug,
            'description' => $this->description,
            'logo' => $this->logo,
            'cover_image' => $this->cover_image,
            'whatsapp' => $this->whatsapp,
            'phone' => $this->phone,
            'address' => $this->address,
            'opening_hours' => $this->opening_hours,
            'currency' => $this->currency,
            'primary_color' => $this->primary_color,
            'is_active' => $this->is_active,
            'created_at' => $this->created_at?->toISOString(),
            'updated_at' => $this->updated_at?->toISOString(),
        ];
    }
}
