<?php

use App\Enums\InviteStatus;
use App\Models\Event;
use App\Models\EventInvite;
use App\Models\EventMember;
use App\Models\User;
use App\Services\InviteTokenService;
use Illuminate\Foundation\Testing\RefreshDatabase;

use function Pest\Laravel\actingAs;

uses(RefreshDatabase::class);

it('can join event with valid token', function () {
    $tokenService = app(InviteTokenService::class);
    $user = User::factory()->create(['email' => 'invitee@example.com']);
    $event = Event::factory()->create();

    $rawToken = $tokenService->generateRawToken();
    $tokenHash = $tokenService->hashToken($rawToken);

    EventInvite::factory()->create([
        'event_id' => $event->id,
        'invited_email' => 'invitee@example.com',
        'token_hash' => $tokenHash,
        'status' => InviteStatus::Pending,
    ]);

    $response = actingAs($user)->postJson('/api/events/join', [
        'token' => $rawToken,
    ]);

    $response->assertSuccessful();
    expect($response->json('message'))->toBe('Joined event');
});

it('rejects invalid token', function () {
    $user = User::factory()->create();

    $response = actingAs($user)->postJson('/api/events/join', [
        'token' => 'invalid-token',
    ]);

    $response->assertNotFound();
});

it('rejects non-pending invite', function () {
    $tokenService = app(InviteTokenService::class);
    $user = User::factory()->create(['email' => 'invitee@example.com']);
    $event = Event::factory()->create();

    $rawToken = $tokenService->generateRawToken();
    $tokenHash = $tokenService->hashToken($rawToken);

    EventInvite::factory()->create([
        'event_id' => $event->id,
        'invited_email' => 'invitee@example.com',
        'token_hash' => $tokenHash,
        'status' => InviteStatus::Accepted,
    ]);

    $response = actingAs($user)->postJson('/api/events/join', [
        'token' => $rawToken,
    ]);

    $response->assertStatus(409);
    expect($response->json('message'))->toBe('Invite not pending');
});

it('rejects revoked invite', function () {
    $tokenService = app(InviteTokenService::class);
    $user = User::factory()->create(['email' => 'invitee@example.com']);
    $event = Event::factory()->create();

    $rawToken = $tokenService->generateRawToken();
    $tokenHash = $tokenService->hashToken($rawToken);

    EventInvite::factory()->revoked()->create([
        'event_id' => $event->id,
        'invited_email' => 'invitee@example.com',
        'token_hash' => $tokenHash,
        'status' => InviteStatus::Pending,
    ]);

    $response = actingAs($user)->postJson('/api/events/join', [
        'token' => $rawToken,
    ]);

    $response->assertStatus(410);
    expect($response->json('message'))->toBe('Invite revoked');
});

it('rejects expired invite', function () {
    $tokenService = app(InviteTokenService::class);
    $user = User::factory()->create(['email' => 'invitee@example.com']);
    $event = Event::factory()->create();

    $rawToken = $tokenService->generateRawToken();
    $tokenHash = $tokenService->hashToken($rawToken);

    EventInvite::factory()->create([
        'event_id' => $event->id,
        'invited_email' => 'invitee@example.com',
        'token_hash' => $tokenHash,
        'status' => InviteStatus::Pending,
        'expires_at' => now()->subDay(),
    ]);

    $response = actingAs($user)->postJson('/api/events/join', [
        'token' => $rawToken,
    ]);

    $response->assertStatus(410);
    expect($response->json('message'))->toBe('Invite expired');
});

it('enforces email matching for anti-transfer', function () {
    $tokenService = app(InviteTokenService::class);
    $user = User::factory()->create(['email' => 'different@example.com']);
    $event = Event::factory()->create();

    $rawToken = $tokenService->generateRawToken();
    $tokenHash = $tokenService->hashToken($rawToken);

    EventInvite::factory()->create([
        'event_id' => $event->id,
        'invited_email' => 'invitee@example.com',
        'token_hash' => $tokenHash,
        'status' => InviteStatus::Pending,
    ]);

    $response = actingAs($user)->postJson('/api/events/join', [
        'token' => $rawToken,
    ]);

    $response->assertForbidden();
    expect($response->json('message'))->toBe('This invite is for a different email address');
});

it('creates membership on join', function () {
    $tokenService = app(InviteTokenService::class);
    $user = User::factory()->create(['email' => 'invitee@example.com']);
    $event = Event::factory()->create();

    $rawToken = $tokenService->generateRawToken();
    $tokenHash = $tokenService->hashToken($rawToken);

    EventInvite::factory()->create([
        'event_id' => $event->id,
        'invited_email' => 'invitee@example.com',
        'token_hash' => $tokenHash,
        'status' => InviteStatus::Pending,
    ]);

    actingAs($user)->postJson('/api/events/join', [
        'token' => $rawToken,
    ]);

    $membership = EventMember::where('event_id', $event->id)
        ->where('user_id', $user->id)
        ->first();

    expect($membership)->not->toBeNull();
});

it('updates invite status to accepted', function () {
    $tokenService = app(InviteTokenService::class);
    $user = User::factory()->create(['email' => 'invitee@example.com']);
    $event = Event::factory()->create();

    $rawToken = $tokenService->generateRawToken();
    $tokenHash = $tokenService->hashToken($rawToken);

    $invite = EventInvite::factory()->create([
        'event_id' => $event->id,
        'invited_email' => 'invitee@example.com',
        'token_hash' => $tokenHash,
        'status' => InviteStatus::Pending,
    ]);

    actingAs($user)->postJson('/api/events/join', [
        'token' => $rawToken,
    ]);

    $invite->refresh();
    expect($invite->status)->toBe(InviteStatus::Accepted);
    expect($invite->responded_at)->not->toBeNull();
});
