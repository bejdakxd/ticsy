<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class OnHoldReason extends Model
{
    use HasFactory;

    public function tickets()
    {
        return $this->hasMany(Ticket::class);
    }

    public function getNameAttribute($value): string
    {
        $value = str_replace('_', ' ', $value);
        $value = ucwords($value);

        return $value;
    }
}