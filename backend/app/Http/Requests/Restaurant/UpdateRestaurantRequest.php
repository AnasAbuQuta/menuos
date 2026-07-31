<?php

namespace App\Http\Requests\Restaurant;

use Illuminate\Foundation\Http\FormRequest;

class UpdateRestaurantRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'name' => ['sometimes', 'required', 'string', 'max:255'],
            'description' => ['sometimes', 'nullable', 'string', 'max:5000'],
            'logo' => ['sometimes', 'nullable', 'string', 'max:2048'],
            'cover_image' => ['sometimes', 'nullable', 'string', 'max:2048'],
            'whatsapp' => ['sometimes', 'nullable', 'string', 'max:30'],
            'phone' => ['sometimes', 'nullable', 'string', 'max:30'],
            'address' => ['sometimes', 'nullable', 'string', 'max:2000'],
            'opening_hours' => ['sometimes', 'nullable', 'array'],
            'currency' => ['sometimes', 'string', 'size:3', 'uppercase'],
            'primary_color' => ['sometimes', 'nullable', 'regex:/^#[0-9A-Fa-f]{6}$/'],
            'is_active' => ['sometimes', 'boolean'],
            'owner_id' => ['prohibited'],
            'slug' => ['prohibited'],
        ];
    }
}
