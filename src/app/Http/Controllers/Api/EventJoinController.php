<?php

namespace App\Http\Controllers\Api;

use App\Enums\InviteStatus;
use App\Http\Controllers\Controller;
use App\Http\Requests\JoinEventRequest;
use App\Models\EventInvite;
use App\Models\EventMember;
use App\Services\InviteTokenService;
use Illuminate\Http\JsonResponse;

class EventJoinController extends Controller
{
    public function join(JoinEventRequest $request, InviteTokenService $tokenService): JsonResponse
    {
        $user = $request->user();
        $hash = $tokenService->hashToken($request->input('token'));

        $invite = EventInvite::query()
            ->where('token_hash', $hash)
            ->first();

        if (! $invite) {
            return response()->json(['message' => 'Invalid invite'], 404);
        }

        if ($invite->status !== InviteStatus::Pending) {
            return response()->json(['message' => 'Invite not pending'], 409);
        }

        if ($invite->revoked_at) {
            return response()->json(['message' => 'Invite revoked'], 410);
        }

        if ($invite->expires_at && $invite->expires_at->isPast()) {
            $invite->update(['status' => InviteStatus::Expired]);

            return response()->json(['message' => 'Invite expired'], 410);
        }

        // Anti-transfert : l’email du compte connecté doit matcher l’email invité
        if (strtolower($user->email) !== strtolower($invite->invited_email)) {
            return response()->json(['message' => 'This invite is for a different email address'], 403);
        }

        // Crée membership
        EventMember::updateOrCreate(
            [
                'event_id' => $invite->event_id,
                'user_id' => $user->id,
            ],
            [
                'role' => 'member',
                'joined_at' => now(),
            ]
        );

        // Lie l'invite au compte (si elle ne l'était pas)
        $invite->update([
            'invited_user_id' => $invite->invited_user_id ?? $user->id,
            'status' => InviteStatus::Accepted,
            'responded_at' => now(),
        ]);

        return response()->json(['message' => 'Joined event']);
    }
}
