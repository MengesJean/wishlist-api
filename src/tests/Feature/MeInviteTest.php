<?php

use App\Enums\InviteStatus;
use App\Models\Event;
use App\Models\EventInvite;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;

use function Pest\Laravel\actingAs;

uses(RefreshDatabase::class);

it('lists pending invites for authenticated user', function () {
    $user = User::factory()->create();

    // Create 3 invites for different events
    for ($i = 0; $i < 3; $i++) {
        $event = Event::factory()->create();
        EventInvite::factory()->forUser($user)->create([
            'event_id' => $event->id,
            'status' => InviteStatus::Pending,
        ]);
    }

    $response = actingAs($user)->getJson('/api/me/invites');

    $response->assertSuccessful();
    expect($response->json('data'))->toHaveCount(3);
});

it('excludes accepted invites', function () {
    $user = User::factory()->create();

    $event1 = Event::factory()->create();
    EventInvite::factory()->forUser($user)->create([
        'event_id' => $event1->id,
        'status' => InviteStatus::Pending,
    ]);

    $event2 = Event::factory()->create();
    EventInvite::factory()->forUser($user)->accepted()->create([
        'event_id' => $event2->id,
    ]);

    $response = actingAs($user)->getJson('/api/me/invites');

    $response->assertSuccessful();
    expect($response->json('data'))->toHaveCount(1);
});

it('excludes declined invites', function () {
    $user = User::factory()->create();

    $event1 = Event::factory()->create();
    EventInvite::factory()->forUser($user)->create([
        'event_id' => $event1->id,
        'status' => InviteStatus::Pending,
    ]);

    $event2 = Event::factory()->create();
    EventInvite::factory()->forUser($user)->declined()->create([
        'event_id' => $event2->id,
    ]);

    $response = actingAs($user)->getJson('/api/me/invites');

    $response->assertSuccessful();
    expect($response->json('data'))->toHaveCount(1);
});

it('excludes other users\' invites', function () {
    $user = User::factory()->create();
    $otherUser = User::factory()->create();
    $event = Event::factory()->create();

    EventInvite::factory()->forUser($user)->create([
        'event_id' => $event->id,
        'status' => InviteStatus::Pending,
    ]);

    EventInvite::factory()->forUser($otherUser)->create([
        'event_id' => $event->id,
        'status' => InviteStatus::Pending,
    ]);

    $response = actingAs($user)->getJson('/api/me/invites');

    $response->assertSuccessful();
    expect($response->json('data'))->toHaveCount(1);
});

it('paginates results', function () {
    $user = User::factory()->create();

    // Create 20 invites for different events
    for ($i = 0; $i < 20; $i++) {
        $event = Event::factory()->create();
        EventInvite::factory()->forUser($user)->create([
            'event_id' => $event->id,
            'status' => InviteStatus::Pending,
        ]);
    }

    $response = actingAs($user)->getJson('/api/me/invites');

    $response->assertSuccessful();
    expect($response->json('data'))->toHaveCount(15);
    expect($response->json('next_page_url'))->not->toBeNull();
});
