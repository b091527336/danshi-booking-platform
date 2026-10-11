<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class LinePairingCode extends Model
{
    protected $guarded = [];
    protected $hidden = ['code_hash'];

    protected function casts(): array
    {
        return ['organization_ids'=>'array', 'expires_at'=>'datetime', 'used_at'=>'datetime'];
    }
}
