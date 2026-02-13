<?php

namespace App\Http\Controllers\Api;

use App\Enums\InviteStatus;
use App\Http\Controllers\Controller;
use App\Models\EventInvite;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class MeInviteController extends Controller
{
    public function index(Request $request): JsonResponse
    {
        $user = $request->user();

        $invites = EventInvite::query()
            ->where('invited_user_id', $user->id)
            ->where('status', InviteStatus::Pending)
            ->orderByDesc('created_at')
            ->simplePaginate(15);

        return response()->json($invites);
    }
}
