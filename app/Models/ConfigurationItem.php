<?php

namespace App\Models;

use App\Enums\ConfigurationItemStatus;
use App\Enums\ConfigurationItemType;
use App\Enums\Location;
use App\Enums\OperatingSystem;
use App\Interfaces\Activitable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Spatie\Activitylog\LogOptions;
use Spatie\Activitylog\Traits\LogsActivity;

class ConfigurationItem extends Model implements Activitable
{
    use HasFactory, LogsActivity;

    protected $casts = [
        'location' => Location::class,
        'operating_system' => OperatingSystem::class,
        'status' => ConfigurationItemStatus::class,
        'type' => ConfigurationItemType::class,
    ];

    function group(): BelongsTo
    {
        return $this->belongsTo(Group::class);
    }

    function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function getActivityLogOptions(): LogOptions
    {
        return LogOptions::defaults()
            ->logOnly([
                'user.name',
                'group.name',
                'location',
                'status',
                'type',
                'serial_number',
                'operating_system',
            ])
            ->logOnlyDirty();
    }
}