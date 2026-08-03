<?php

namespace App\Support;

class BilingualContent
{
    public static function synchronize(array $data, string $defaultLanguage = 'ar'): array
    {
        foreach (['name', 'description'] as $field) {
            foreach (['ar', 'en'] as $language) {
                $key = "{$field}_{$language}";
                if (array_key_exists($key, $data) && is_string($data[$key])) {
                    $data[$key] = trim($data[$key]) ?: null;
                }
            }
        }

        if (isset($data['name']) && ! isset($data['name_ar']) && ! isset($data['name_en'])) {
            $data['name'] = trim($data['name']);
            $data['name_ar'] = $data['name'];
        }
        if (array_key_exists('description', $data) && ! array_key_exists('description_ar', $data) && ! array_key_exists('description_en', $data)) {
            $data['description_ar'] = $data['description'];
        }

        $preferredName = $data["name_{$defaultLanguage}"] ?? null;
        $fallbackName = $data[$defaultLanguage === 'ar' ? 'name_en' : 'name_ar'] ?? null;
        if ($preferredName || $fallbackName) {
            $data['name'] = $preferredName ?: $fallbackName;
        }

        $preferredDescription = $data["description_{$defaultLanguage}"] ?? null;
        $fallbackDescription = $data[$defaultLanguage === 'ar' ? 'description_en' : 'description_ar'] ?? null;
        if (array_key_exists('description_ar', $data) || array_key_exists('description_en', $data)) {
            $data['description'] = $preferredDescription ?: $fallbackDescription;
        }

        return $data;
    }
}
