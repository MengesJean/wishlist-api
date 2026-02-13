<?php

namespace App\Http\Controllers\Api;

use App\Enums\EventMemberRole;
use App\Http\Controllers\Controller;
use App\Http\Requests\StoreEventRequest;
use App\Models\Event;
use App\Models\EventMember;
use App\Services\InviteTokenService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class EventController extends Controller
{
    public function index(Request $request): JsonResponse
    {
        $user = $request->user();

        $events = Event::query()
            ->whereHas('members', fn ($q) => $q->where('user_id', $user->id))
            ->orderByDesc('start_at')
            ->simplePaginate(15);

        return response()->json($events);
    }

    public function store(StoreEventRequest $request, InviteTokenService $inviteTokenService): JsonResponse
    {
        $user = $request->user();

        $rawToken = $inviteTokenService->generateRawToken();
        $tokenHash = $inviteTokenService->hashToken($rawToken);

        $event = DB::transaction(function () use ($request, $user, $tokenHash) {
            $event = Event::create([
                'title' => $request->validated('title'),
                'start_at' => $request->validated('start_at'),
                'created_by' => $user->id,
                'invite_token_hash' => $tokenHash,
                'invite_token_created_at' => now(),
            ]);

            EventMember::create([
                'event_id' => $event->id,
                'user_id' => $user->id,
                'role' => EventMemberRole::Owner,
                'joined_at' => now(),
            ]);

            return $event;
        });

        return response()->json([
            'event' => $event,
            'invite_token' => $rawToken,
        ]);
    }

    public function show(Request $request, Event $event): JsonResponse
    {
        $user = $request->user();

        $isMember = $event->members()->where('user_id', $user->id)->exists();
        if (! $isMember) {
            // éviter de leak l’existence de l’event
            return response()->json(['message' => 'Not found'], 404);
        }

        $event->load(['creator', 'members.user']);

        return response()->json($event);
    }
}
