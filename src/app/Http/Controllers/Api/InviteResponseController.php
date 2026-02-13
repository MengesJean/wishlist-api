<?php

namespace App\Http\Controllers\Api;

use App\Enums\EventMemberRole;
use App\Enums\InviteStatus;
use App\Http\Controllers\Controller;
use App\Models\EventInvite;
use App\Models\EventMember;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class InviteResponseController extends Controller
{
    public function accept(Request $request, EventInvite $invite): JsonResponse
    {
        $validation = $this->validateInvite($request, $invite);
        if ($validation !== null) {
            return $validation;
        }

        $user = $request->user();

        // Crée membership
        EventMember::updateOrCreate(
            [
                'event_id' => $invite->event_id,
                'user_id' => $user->id,
            ],
            [
                'role' => EventMemberRole::Member,
                'joined_at' => now(),
            ]
        );

        $invite->update([
            'status' => InviteStatus::Accepted,
            'responded_at' => now(),
        ]);

        return response()->json(['message' => 'Invite accepted']);
    }

    public function decline(Request $request, EventInvite $invite): JsonResponse
    {
        $validation = $this->validateInvite($request, $invite);
        if ($validation !== null) {
            return $validation;
        }

        $invite->update([
            'status' => InviteStatus::Declined,
            'responded_at' => now(),
        ]);

        return response()->json(['message' => 'Invite declined']);
    }

    protected function validateInvite(Request $request, EventInvite $invite): ?JsonResponse
    {
        $user = $request->user();

        if ($invite->invited_user_id !== $user->id) {
            return response()->json(['message' => 'Forbidden'], 403);
        }

        if ($invite->status !== InviteStatus::Pending) {
            return response()->json(['message' => 'Invite not pending'], 409);
        }

        if ($invite->revoked_at) {
            return response()->json(['message' => 'Invite revoked'], 410);
        }

        return null;
    }
}
