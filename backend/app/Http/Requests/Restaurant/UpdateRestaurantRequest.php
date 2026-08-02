<?php

namespace App\Http\Requests\Restaurant;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UpdateRestaurantRequest extends FormRequest
{
    private const DAYS = ['saturday', 'sunday', 'monday', 'tuesday', 'wednesday', 'thursday', 'friday'];

    public function authorize(): bool
    {
        return true;
    }

    protected function prepareForValidation(): void
    {
        $normalized = [];
        if ($this->has('name')) {
            $normalized['name'] = trim((string) $this->input('name'));
        }
        if ($this->has('currency')) {
            $normalized['currency'] = strtoupper(trim((string) $this->input('currency')));
        }
        if ($this->has('primary_color') && $this->input('primary_color') !== null) {
            $normalized['primary_color'] = strtoupper(trim((string) $this->input('primary_color')));
        }
        foreach (['phone', 'whatsapp'] as $field) {
            if ($this->has($field) && $this->input($field) !== null) {
                $normalized[$field] = str_replace([' ', '-', '(', ')'], '', trim((string) $this->input($field)));
            }
        }
        $this->merge($normalized);
    }

    public function rules(): array
    {
        $rules = [
            'name' => ['sometimes', 'required', 'string', 'max:255'],
            'description' => ['sometimes', 'nullable', 'string', 'max:5000'],
            'whatsapp' => ['sometimes', 'nullable', 'string', 'max:21', 'regex:/^\+?[0-9]{6,20}$/'],
            'phone' => ['sometimes', 'nullable', 'string', 'max:21', 'regex:/^\+?[0-9]{6,20}$/'],
            'address' => ['sometimes', 'nullable', 'string', 'max:2000'],
            'opening_hours' => ['sometimes', 'required', 'array:'.implode(',', self::DAYS)],
            'currency' => ['sometimes', 'string', Rule::in(['ILS', 'USD', 'JOD'])],
            'primary_color' => ['sometimes', 'nullable', 'regex:/^#[0-9A-F]{6}$/'],
            'is_active' => ['sometimes', 'boolean'],
            'owner_id' => ['prohibited'],
            'slug' => ['prohibited'],
            'logo' => ['prohibited'],
            'cover_image' => ['prohibited'],
        ];

        foreach (self::DAYS as $day) {
            $rules["opening_hours.{$day}"] = ['required_with:opening_hours', 'array:is_open,open,close'];
            $rules["opening_hours.{$day}.is_open"] = ['required_with:opening_hours', 'boolean'];
            $rules["opening_hours.{$day}.open"] = [
                Rule::requiredIf(fn () => (bool) $this->input("opening_hours.{$day}.is_open")),
                'nullable', 'date_format:H:i',
            ];
            $rules["opening_hours.{$day}.close"] = [
                Rule::requiredIf(fn () => (bool) $this->input("opening_hours.{$day}.is_open")),
                'nullable', 'date_format:H:i',
            ];
        }

        return $rules;
    }
}
