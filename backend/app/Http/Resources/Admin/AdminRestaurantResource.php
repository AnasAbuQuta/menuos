<?php

namespace App\Http\Resources\Admin;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;
use Illuminate\Support\Facades\Storage;

class AdminRestaurantResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'name' => $this->name,
            'name_ar' => $this->name_ar,
            'name_en' => $this->name_en,
            'slug' => $this->slug,
            'owner' => $this->whenLoaded('owner', fn () => ['id' => $this->owner->id, 'name' => $this->owner->name, 'email' => $this->owner->email]),
            'is_active' => $this->is_active,
            'platform_status' => $this->platform_status,
            'default_language' => $this->default_language,
            'currency' => $this->currency,
            'theme_key' => $this->theme_key,
            'logo_url' => $this->logo ? Storage::disk('public')->url($this->logo) : null,
            'cover_image_url' => $this->cover_image ? Storage::disk('public')->url($this->cover_image) : null,
            'categories_count' => (int) ($this->categories_count ?? 0),
            'menu_items_count' => (int) ($this->menu_items_count ?? 0),
            'public_menu_views' => (int) ($this->public_menu_views ?? 0),
            'qr_visits' => (int) ($this->qr_visits ?? 0),
            'whatsapp_clicks' => (int) ($this->whatsapp_clicks ?? 0),
            'phone_clicks' => (int) ($this->phone_clicks ?? 0),
            'created_at' => $this->created_at?->toISOString(),
            'updated_at' => $this->updated_at?->toISOString(),
        ];
    }
}
