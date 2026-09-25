<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class EventTemplate extends Model
{
    protected $fillable = [
        'event_id',
        'template_image',
        'use_event_logo',
        'signature_city',
        'signature_date',
        'print_only_name_type',
        'text_color',
    ];

    protected $casts = [
        'use_event_logo' => 'boolean',
        'print_only_name_type' => 'boolean',
    ];

    public function event(): BelongsTo
    {
        return $this->belongsTo(Event::class);
    }
}
