<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Event extends Model
{
    use HasFactory;

    protected $fillable = [
        'title',
        'start_at',
        'created_by',
        'invite_token_hash',
        'invite_token_created_at',
    ];

    protected $casts = [
        'start_at' => 'datetime',
        'invite_token_created_at' => 'datetime',
    ];

    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function members(): HasMany
    {
        return $this->hasMany(EventMember::class, 'event_id');
    }

    public function invites(): HasMany
    {
        return $this->hasMany(EventInvite::class, 'event_id');
    }
}
