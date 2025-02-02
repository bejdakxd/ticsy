<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\MorphTo;
use Illuminate\Support\Carbon;

class Sla extends Model
{
    protected $casts = [
        'expires_at' => 'datetime',
        'closed_at' => 'datetime',
    ];

    public function slable(): MorphTo
    {
        return $this->morphTo();
    }

    public function minutes(): int
    {
        return $this->expires_at->diffInMinutes($this->created_at);
    }

    public function toPercentage(): int
    {
        return round($this->minutesTillExpires() / $this->minutes() * 100);
    }

    public function minutesTillExpires(): int
    {
        return $this->expires_at->diffInMinutes(Carbon::now());
    }

    public function scopeOpened(Builder $query): Builder
    {
        return $query->where('closed_at', '=', null);
    }
}
