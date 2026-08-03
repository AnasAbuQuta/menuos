<?php

namespace App\Http\Requests\Analytics;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StoreAnalyticsEventsRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'visitor_id' => ['required', 'string', 'min:16', 'max:128'],
            'events' => ['required', 'array', 'min:1', 'max:20'],
            'events.*.type' => ['required', Rule::in(['menu_view', 'search', 'category_click', 'item_click', 'whatsapp_click', 'phone_click', 'qr_visit'])],
            'events.*.subject_id' => ['nullable', 'integer', 'min:1'],
            'events.*.source' => ['nullable', Rule::in(['direct', 'qr', 'search', 'social', 'referral'])],
            'events.*.occurred_at' => ['nullable', 'date', 'before_or_equal:now', 'after:-1 day'],
        ];
    }
}
