<?php

namespace App\Http\Requests\Concerns;

trait NormalizesBilingualInput
{
    protected function normalizeBilingualFields(): void
    {
        $normalized = [];
        foreach (['name', 'name_ar', 'name_en', 'description', 'description_ar', 'description_en'] as $field) {
            if ($this->has($field) && is_string($this->input($field))) {
                $normalized[$field] = trim($this->input($field));
            }
        }
        $this->merge($normalized);
    }
}
