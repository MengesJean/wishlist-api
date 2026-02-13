<?php

namespace App\Http\Controllers\Api;

use App\Enums\InviteStatus;
use App\Http\Controllers\Controller;
use App\Http\Requests\StoreEventInviteRequest;
use App\Models\Event;
use App\Models\EventInvite;
use App\Models\User;
use App\Notifications\EventInviteNotification;
use App\Services\InviteTokenService;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Notification;

class EventInviteController extends Controller
{
    public function store(StoreEventInviteRequest $request, Event $event, InviteTokenService $tokenService): JsonResponse
    {
        $event->load('members');
        $this->authorize('invite', $event);

        $auth = $request->user();

        $email = strtolower(trim($request->validated('email')));

        // Si déjà membre, on évite une invite inutile
        $alreadyMember = $event->members()
            ->whereHas('user', fn ($q) => $q->where('email', $email))
            ->exists();

        if ($alreadyMember) {
            return response()->json(['message' => 'User already a member'], 409);
        }

        // Check si un user existe déjà avec cet email
        $invitedUser = User::query()->where('email', $email)->first();

        $rawToken = null;
        $tokenHash = null;
        $expiresAt = null;
        $inviteLink = null;

        if (! $invitedUser) {
            // Invite externe => token + expiration 3 jours
            $rawToken = $tokenService->generateRawToken();
            $tokenHash = $tokenService->hashToken($rawToken);
            $expiresAt = now()->addDays(3);

            $frontUrl = rtrim(config('frontend.url'), '/');
            $inviteLink = $frontUrl.'/join?token='.$rawToken;
        }

        // Réinviter = updateOrCreate (une invite par email)
        $invite = EventInvite::updateOrCreate(
            [
                'event_id' => $event->id,
                'invited_email' => $email,
            ],
            [
                'invited_user_id' => $invitedUser?->id,
                'token_hash' => $tokenHash,            // null si user existe
                'created_by' => $auth->id,
                'status' => InviteStatus::Pending,
                'expires_at' => $expiresAt,            // null si user existe
                'responded_at' => null,
                'revoked_at' => null,
            ]
        );

        // Charger event pour éviter N+1
        $invite->load('event');

        // Envoi notification mail (MVP)
        // - Si user existe: on notifie le user (via notifications Laravel)
        // - Sinon: on envoie au mail directement
        if ($invitedUser) {
            $invitedUser->notify(new EventInviteNotification($invite));
        } else {
            Notification::route('mail', $email)->notify(new EventInviteNotification($invite, $inviteLink));
        }

        return response()->json([
            'invite' => $invite,
            // Le lien est utile pour debug/dev, et pour UI "copier-coller".
            // En prod tu peux choisir de ne l’afficher que si pas de compte.
            'invite_link' => $inviteLink,
        ], 201);
    }

    // Optionnel: owner liste des invites
    public function index(Event $event): JsonResponse
    {
        $event->load('members');
        $this->authorize('invite', $event);

        return response()->json(
            $event->invites()->orderByDesc('created_at')->simplePaginate(15)
        );
    }
}
