<?php

namespace App\Http\Controllers\Api\Auth;

use App\Http\Controllers\Controller;
use App\Models\User;
use Illuminate\Http\Client\Response;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Laravel\Socialite\Facades\Socialite;

class GoogleAuthController extends Controller
{
    public function redirect(): JsonResponse
    {
        $url = Socialite::driver('google')->stateless()->redirect()->getTargetUrl();

        return response()->json(['url' => $url]);
    }

    public function callback(Request $request): JsonResponse
    {
        $googleUser = Socialite::driver('google')->stateless()->user();
        $user = User::query()
            ->where('google_id', $googleUser->getId())
            ->orWhere('email', $googleUser->getEmail())
            ->first();

        if(!$user) {
            $user = User::create([
                'name' => $googleUser->getName() ?? $googleUser->getNickname() ?? $googleUser->getEmail(),
                'email' => $googleUser->getEmail(),
                'google_id' => $googleUser->getId(),
                'avatar_url' => $googleUser->getAvatar() ?? $googleUser->getAvatar(),
            ]);
        } else {
            $user->update([
                'google_id' => $user->google_id ?? $googleUser->getId(),
                'name' => $googleUser->getName() ?? $user->name,
                'avatar_url' => $googleUser->getAvatar() ?? $user->avatar_url
            ]);
        }

        $token = $user->createToken('authToken')->plainTextToken;

        return response()->json([
            'token' => $token,
            'user' => $user,
        ]);
    }

    public function logout(Request $request): JsonResponse
    {
        $request->user()->currentAccessToken()->delete();

        return response()->json(['message' => 'Logged out']);
    }
}
