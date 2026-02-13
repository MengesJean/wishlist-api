<?php

use App\Enums\InviteStatus;
use App\Models\Event;
use App\Models\EventInvite;
use App\Models\EventMember;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;

use function Pest\Laravel\actingAs;

uses(RefreshDatabase::class);

it('can accept invite', function () {
    $user = User::factory()->create();
    $event = Event::factory()->create();

    $invite = EventInvite::factory()->forUser($user)->create([
        'event_id' => $event->id,
        'status' => InviteStatus::Pending,
    ]);

    $response = actingAs($user)->postJson("/api/invites/{$invite->id}/accept");

    $response->assertSuccessful();
    expect($response->json('message'))->toBe('Invite accepted');
});

it('creates membership on accept', function () {
    $user = User::factory()->create();
    $event = Event::factory()->create();

    $invite = EventInvite::factory()->forUser($user)->create([
        'event_id' => $event->id,
        'status' => InviteStatus::Pending,
    ]);

    actingAs($user)->postJson("/api/invites/{$invite->id}/accept");

    $membership = EventMember::where('event_id', $event->id)
        ->where('user_id', $user->id)
        ->first();

    expect($membership)->not->toBeNull();

    $invite->refresh();
    expect($invite->status)->toBe(InviteStatus::Accepted);
});

it('can decline invite', function () {
    $user = User::factory()->create();
    $event = Event::factory()->create();

    $invite = EventInvite::factory()->forUser($user)->create([
        'event_id' => $event->id,
        'status' => InviteStatus::Pending,
    ]);

    $response = actingAs($user)->postJson("/api/invites/{$invite->id}/decline");

    $response->assertSuccessful();
    expect($response->json('message'))->toBe('Invite declined');

    $invite->refresh();
    expect($invite->status)->toBe(InviteStatus::Declined);
});

it('prevents accepting another user\'s invite', function () {
    $user = User::factory()->create();
    $otherUser = User::factory()->create();
    $event = Event::factory()->create();

    $invite = EventInvite::factory()->forUser($otherUser)->create([
        'event_id' => $event->id,
        'status' => InviteStatus::Pending,
    ]);

    $response = actingAs($user)->postJson("/api/invites/{$invite->id}/accept");

    $response->assertForbidden();
});

it('prevents accepting non-pending invite', function () {
    $user = User::factory()->create();
    $event = Event::factory()->create();

    $invite = EventInvite::factory()->forUser($user)->accepted()->create([
        'event_id' => $event->id,
    ]);

    $response = actingAs($user)->postJson("/api/invites/{$invite->id}/accept");

    $response->assertStatus(409);
    expect($response->json('message'))->toBe('Invite not pending');
});

it('prevents accepting revoked invite', function () {
    $user = User::factory()->create();
    $event = Event::factory()->create();

    $invite = EventInvite::factory()->forUser($user)->revoked()->create([
        'event_id' => $event->id,
        'status' => InviteStatus::Pending,
    ]);

    $response = actingAs($user)->postJson("/api/invites/{$invite->id}/accept");

    $response->assertStatus(410);
    expect($response->json('message'))->toBe('Invite revoked');
});
