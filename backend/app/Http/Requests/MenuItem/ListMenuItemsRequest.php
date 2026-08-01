<?php

namespace App\Http\Requests\MenuItem;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class ListMenuItemsRequest extends FormRequest
{
    public function authorize(): bool
    {
        abort_if(! $this->user()?->restaurant, 409, 'Create a restaurant before managing menu items.');

        return true;
    }

    protected function prepareForValidation(): void
    {
        if ($this->has('search')) {
            $this->merge(['search' => trim((string) $this->input('search'))]);
        }
    }

    public function rules(): array
    {
        $restaurantId = $this->user()?->restaurant?->id ?? 0;

        return [
            'category_id' => ['sometimes', 'integer', Rule::exists('categories', 'id')->where('restaurant_id', $restaurantId)],
            'search' => ['sometimes', 'string', 'max:160'],
            'is_available' => ['sometimes', 'boolean'],
            'is_featured' => ['sometimes', 'boolean'],
        ];
    }
}
