<?php

namespace App\Http\Requests\Category;

use App\Http\Requests\Concerns\NormalizesBilingualInput;
use Illuminate\Foundation\Http\FormRequest;

class UpdateCategoryRequest extends FormRequest
{
    use NormalizesBilingualInput;

    public function authorize(): bool
    {
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
        return [
            'name' => ['sometimes', 'nullable', 'required_without_all:name_ar,name_en', 'string', 'max:120'],
            'name_ar' => ['sometimes', 'nullable', 'required_without_all:name,name_en', 'string', 'max:120'],
            'name_en' => ['sometimes', 'nullable', 'required_without_all:name,name_ar', 'string', 'max:120'],
            'sort_order' => ['sometimes', 'integer', 'min:0'],
            'is_active' => ['sometimes', 'boolean'],
            'restaurant_id' => ['prohibited'],
        ];
    }
}
