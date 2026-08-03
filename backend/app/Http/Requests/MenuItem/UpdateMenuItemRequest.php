<?php

namespace App\Http\Requests\MenuItem;

use App\Http\Requests\Concerns\NormalizesBilingualInput;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UpdateMenuItemRequest extends FormRequest
{
    use NormalizesBilingualInput;

    public function authorize(): bool
    {
        abort_if(! $this->user()?->restaurant, 409, 'Create a restaurant before managing menu items.');

        return true;
    }

    protected function prepareForValidation(): void
    {
        $this->normalizeBilingualFields();
        if ($this->has('name')) {
            $this->merge(['name' => trim((string) $this->input('name'))]);
        }
    }

    public function rules(): array
    {
        $restaurantId = $this->user()?->restaurant?->id ?? 0;

        return [
            'category_id' => ['sometimes', 'required', 'integer', Rule::exists('categories', 'id')->where('restaurant_id', $restaurantId)],
            'name' => ['sometimes', 'nullable', 'required_without_all:name_ar,name_en', 'string', 'max:160'],
            'name_ar' => ['sometimes', 'nullable', 'required_without_all:name,name_en', 'string', 'max:160'],
            'name_en' => ['sometimes', 'nullable', 'required_without_all:name,name_ar', 'string', 'max:160'],
            'description' => ['sometimes', 'nullable', 'string', 'max:5000'],
            'description_ar' => ['sometimes', 'nullable', 'string', 'max:5000'],
            'description_en' => ['sometimes', 'nullable', 'string', 'max:5000'],
            'price' => ['sometimes', 'required', 'numeric', 'min:0', 'max:99999999.99', 'decimal:0,2'],
            'image' => ['sometimes', 'nullable', 'file', 'image', 'mimes:jpg,jpeg,png,webp', 'mimetypes:image/jpeg,image/png,image/webp', 'max:2048'],
            'is_available' => ['sometimes', 'boolean'],
            'is_featured' => ['sometimes', 'boolean'],
            'sort_order' => ['sometimes', 'integer', 'min:0'],
            'restaurant_id' => ['prohibited'],
            'image_path' => ['prohibited'],
        ];
    }
}
