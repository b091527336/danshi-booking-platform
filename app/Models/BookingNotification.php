<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class BookingNotification extends Model
{
    protected $guarded = [];

    protected function casts(): array
    {
        return ['body' => 'encrypted', 'next_attempt_at' => 'datetime', 'sent_at' => 'datetime'];
    }

    public function booking(): BelongsTo
    {
        return $this->belongsTo(Booking::class);
    }
}
