<?php

namespace App\Policies;

use App\Enums\EventMemberRole;
use App\Models\Event;
use App\Models\User;

class EventPolicy
{
    /**
     * Peut voir l'event : uniquement les membres.
     */
    public function view(User $user, Event $event): bool
    {
        return $this->isMember($user, $event);
    }

    /**
     * Peut inviter : uniquement les owners.
     */
    public function invite(User $user, Event $event): bool
    {
        return $this->isOwner($user, $event);
    }

    /**
     * Peut gérer l'event (optionnel) : owner.
     */
    public function manage(User $user, Event $event): bool
    {
        return $this->isOwner($user, $event);
    }

    protected function isMember(User $user, Event $event): bool
    {
        if ($event->relationLoaded('members')) {
            return $event->members->contains('user_id', $user->id);
        }

        return $event->members()->where('user_id', $user->id)->exists();
    }

    protected function isOwner(User $user, Event $event): bool
    {
        if ($event->relationLoaded('members')) {
            return $event->members
                ->where('user_id', $user->id)
                ->where('role', EventMemberRole::Owner)
                ->isNotEmpty();
        }

        return $event->members()
            ->where('user_id', $user->id)
            ->where('role', EventMemberRole::Owner)
            ->exists();
    }
}
