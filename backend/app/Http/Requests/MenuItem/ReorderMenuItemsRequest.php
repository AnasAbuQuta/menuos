<?php

namespace App\Http\Requests\MenuItem;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class ReorderMenuItemsRequest extends FormRequest
{
    public function authorize(): bool
    {
        abort_if(! $this->user()?->restaurant, 409, 'Create a restaurant before managing menu items.');

        return true;
    }

    public function rules(): array
    {
        $restaurantId = $this->user()?->restaurant?->id ?? 0;

        return [
            'category_id' => ['required', 'integer', Rule::exists('categories', 'id')->where('restaurant_id', $restaurantId)],
            'menu_item_ids' => ['required', 'array', 'min:1'],
            'menu_item_ids.*' => ['required', 'integer', 'distinct'],
        ];
    }
}
