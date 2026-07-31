<?php

namespace App\Http\Requests\Restaurant;

use Illuminate\Foundation\Http\FormRequest;

class StoreRestaurantRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'name' => ['required', 'string', 'max:255'],
            'description' => ['nullable', 'string', 'max:5000'],
            'logo' => ['nullable', 'string', 'max:2048'],
            'cover_image' => ['nullable', 'string', 'max:2048'],
            'whatsapp' => ['nullable', 'string', 'max:30'],
            'phone' => ['nullable', 'string', 'max:30'],
            'address' => ['nullable', 'string', 'max:2000'],
            'opening_hours' => ['nullable', 'array'],
            'currency' => ['sometimes', 'string', 'size:3', 'uppercase'],
            'primary_color' => ['nullable', 'regex:/^#[0-9A-Fa-f]{6}$/'],
            'is_active' => ['sometimes', 'boolean'],
            'owner_id' => ['prohibited'],
            'slug' => ['prohibited'],
        ];
    }
}
