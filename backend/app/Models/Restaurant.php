<?php

namespace App\Models;

use App\Models\Concerns\HasLocalizedContent;
use Database\Factories\RestaurantFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

#[Fillable([
    'owner_id', 'name', 'name_ar', 'name_en', 'slug', 'description', 'description_ar', 'description_en', 'default_language', 'logo', 'cover_image',
    'whatsapp', 'phone', 'address', 'opening_hours', 'currency',
    'primary_color', 'theme_key', 'is_active',
])]
class Restaurant extends Model
{
    /** @use HasFactory<RestaurantFactory> */
    use HasFactory;

    use HasLocalizedContent;

    public function owner(): BelongsTo
    {
        return $this->belongsTo(User::class, 'owner_id');
    }

    public function categories(): HasMany
    {
        return $this->hasMany(Category::class);
    }

    public function menuItems(): HasMany
    {
        return $this->hasMany(MenuItem::class);
    }

    protected function casts(): array
    {
        return ['opening_hours' => 'array', 'is_active' => 'boolean'];
    }
}
