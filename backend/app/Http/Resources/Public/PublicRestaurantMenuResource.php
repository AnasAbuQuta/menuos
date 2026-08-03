<?php

namespace App\Http\Resources\Public;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;
use Illuminate\Support\Facades\Storage;

class PublicRestaurantMenuResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'language' => $this->active_language,
            'available_languages' => ['ar', 'en'],
            'name' => $this->getLocalizedName($this->active_language),
            'slug' => $this->slug,
            'description' => $this->getLocalizedDescription($this->active_language),
            'logo_url' => $this->logo ? Storage::disk('public')->url($this->logo) : null,
            'cover_image_url' => $this->cover_image ? Storage::disk('public')->url($this->cover_image) : null,
            'whatsapp' => $this->whatsapp,
            'phone' => $this->phone,
            'address' => $this->address,
            'opening_hours' => $this->opening_hours,
            'currency' => $this->currency,
            'primary_color' => $this->primary_color,
            'theme_key' => $this->theme_key,
            'is_active' => $this->is_active,
            'is_open_now' => $this->is_open_now,
            'categories' => PublicCategoryResource::collection($this->whenLoaded('categories')),
        ];
    }
}
