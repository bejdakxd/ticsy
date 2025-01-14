<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Group extends Model
{
    use HasFactory;

    const SERVICE_DESK_ID = 1;

    function resolvers(): BelongsToMany
    {
        return $this->belongsToMany(User::class);
    }

    function incidents(): HasMany
    {
        return $this->hasMany(Incident::class);
    }

    function requests(): HasMany
    {
        return $this->hasMany(Request::class);
    }

    function configurationItems(): HasMany
    {
        return $this->hasMany(ConfigurationItem::class);
    }

    function getResolverIds(): array
    {
        return $this->resolvers()->select('id')->get()->pluck('id')->toArray();
    }

    static function serviceDesk(): self
    {
        // the first group being created is SERVICE-DESK in GroupSeeder, so ID is 1
        return self::find(1);
    }

    static function byName(string $name): self|null
    {
        return self::where('name', '=', $name)->first();
    }
}
