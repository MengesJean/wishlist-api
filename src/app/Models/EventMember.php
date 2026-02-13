<?php

namespace App\Models;

use App\Enums\EventMemberRole;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class EventMember extends Model
{
    use HasFactory;

    protected $fillable = [
        'event_id',
        'user_id',
        'role',
        'joined_at',
    ];

    protected $casts = [
        'role' => EventMemberRole::class,
        'joined_at' => 'datetime',
    ];

    public function event(): BelongsTo
    {
        return $this->belongsTo(Event::class);
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }
}
