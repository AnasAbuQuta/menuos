<?php

namespace App\Http\Resources\Admin;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class AdminUserResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'name' => $this->name,
            'email' => $this->email,
            'account_status' => $this->account_status,
            'is_super_admin' => $this->isSuperAdmin(),
            'restaurant' => $this->whenLoaded('restaurant', fn () => $this->restaurant ? [
                'id' => $this->restaurant->id,
                'name' => $this->restaurant->name,
                'name_ar' => $this->restaurant->name_ar,
                'name_en' => $this->restaurant->name_en,
                'slug' => $this->restaurant->slug,
                'is_active' => $this->restaurant->is_active,
                'platform_status' => $this->restaurant->platform_status,
            ] : null),
            'created_at' => $this->created_at?->toISOString(),
            'updated_at' => $this->updated_at?->toISOString(),
        ];
    }
}
