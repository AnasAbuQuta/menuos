<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

#[Fillable(['restaurant_id', 'event_type', 'visitor_hash', 'subject_type', 'subject_id', 'source', 'occurred_at'])]
class AnalyticsEvent extends Model
{
    public function restaurant(): BelongsTo
    {
        return $this->belongsTo(Restaurant::class);
    }

    protected function casts(): array
    {
        return ['occurred_at' => 'datetime'];
    }
}
