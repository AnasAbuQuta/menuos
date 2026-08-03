<?php

namespace App\Models\Concerns;

trait HasLocalizedContent
{
    public function getLocalizedName(string $language): string
    {
        return $this->localizedValue('name', $language) ?? '';
    }

    public function getLocalizedDescription(string $language): ?string
    {
        return $this->localizedValue('description', $language);
    }

    private function localizedValue(string $field, string $language): ?string
    {
        $otherLanguage = $language === 'ar' ? 'en' : 'ar';

        foreach (["{$field}_{$language}", "{$field}_{$otherLanguage}", $field] as $candidate) {
            $value = $this->getAttribute($candidate);
            if (is_string($value) && trim($value) !== '') {
                return $value;
            }
        }

        return null;
    }
}
