<?php

namespace App\Models;

use App\Enums\InviteStatus;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class EventInvite extends Model
{
    use HasFactory;

    protected $fillable = [
        'event_id',
        'invited_email',
        'invited_user_id',
        'token_hash',
        'created_by',
        'status',
        'expires_at',
        'responded_at',
        'revoked_at',
    ];

    protected $casts = [
        'status' => InviteStatus::class,
        'expires_at' => 'datetime',
        'responded_at' => 'datetime',
        'revoked_at' => 'datetime',
    ];

    public function event(): BelongsTo
    {
        return $this->belongsTo(Event::class);
    }

    public function invitedUser(): BelongsTo
    {
        return $this->belongsTo(User::class, 'invited_user_id');
    }

    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function isPending(): bool
    {
        return $this->status === InviteStatus::Pending;
    }
}
