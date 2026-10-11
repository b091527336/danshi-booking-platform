<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class LineRecipient extends Model
{
    protected $guarded = [];
    protected $hidden = ['line_id'];

    protected function casts(): array
    {
        return ['line_id' => 'encrypted'];
    }
}
