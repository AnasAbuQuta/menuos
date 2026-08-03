<?php

namespace App\Http\Requests\Restaurant;

use App\Http\Requests\Concerns\NormalizesBilingualInput;
use App\Support\RestaurantTheme;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StoreRestaurantRequest extends FormRequest
{
    use NormalizesBilingualInput;

    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'name' => ['nullable', 'required_without_all:name_ar,name_en', 'string', 'max:255'],
            'name_ar' => ['nullable', 'required_without_all:name,name_en', 'string', 'max:255'],
            'name_en' => ['nullable', 'required_without_all:name,name_ar', 'string', 'max:255'],
            'description' => ['nullable', 'string', 'max:5000'],
            'description_ar' => ['nullable', 'string', 'max:5000'],
            'description_en' => ['nullable', 'string', 'max:5000'],
            'default_language' => ['sometimes', 'string', 'in:ar,en'],
            'logo' => ['prohibited'],
            'cover_image' => ['prohibited'],
            'whatsapp' => ['nullable', 'string', 'max:30'],
            'phone' => ['nullable', 'string', 'max:30'],
            'address' => ['nullable', 'string', 'max:2000'],
            'opening_hours' => ['nullable', 'array'],
            'currency' => ['sometimes', 'string', Rule::in(['ILS', 'USD', 'JOD'])],
            'primary_color' => ['nullable', 'regex:/^#[0-9A-Fa-f]{6}$/'],
            'theme_key' => ['sometimes', 'string', Rule::in(RestaurantTheme::KEYS)],
            'is_active' => ['sometimes', 'boolean'],
            'owner_id' => ['prohibited'],
            'slug' => ['prohibited'],
        ];
    }

    protected function prepareForValidation(): void
    {
        $this->normalizeBilingualFields();
    }
}
