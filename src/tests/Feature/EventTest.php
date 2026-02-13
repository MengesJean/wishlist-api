<?php

use App\Enums\EventMemberRole;
use App\Models\Event;
use App\Models\EventMember;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;

use function Pest\Laravel\actingAs;

uses(RefreshDatabase::class);

it('can list user events', function () {
    $user = User::factory()->create();
    $otherUser = User::factory()->create();

    // Create events for user
    $event1 = Event::factory()->create();
    EventMember::factory()->create([
        'event_id' => $event1->id,
        'user_id' => $user->id,
    ]);

    $event2 = Event::factory()->create();
    EventMember::factory()->create([
        'event_id' => $event2->id,
        'user_id' => $user->id,
    ]);

    // Create event for other user (should not appear)
    $event3 = Event::factory()->create();
    EventMember::factory()->create([
        'event_id' => $event3->id,
        'user_id' => $otherUser->id,
    ]);

    $response = actingAs($user)->getJson('/api/events');

    $response->assertSuccessful();
    expect($response->json('data'))->toHaveCount(2);
});

it('returns raw token not hash when creating event', function () {
    $user = User::factory()->create();

    $response = actingAs($user)->postJson('/api/events', [
        'title' => 'Test Event',
        'start_at' => now()->addDays(7)->toDateString(),
    ]);

    $response->assertSuccessful();

    $token = $response->json('invite_token');
    expect($token)->not->toBeNull();

    // Verify it's NOT a hash (sha256 = 64 chars)
    expect(strlen($token))->toBeLessThan(64);

    // Verify hash of token matches DB
    $event = Event::latest()->first();
    expect(hash('sha256', $token))->toBe($event->invite_token_hash);
});

it('creates owner membership when creating event', function () {
    $user = User::factory()->create();

    $response = actingAs($user)->postJson('/api/events', [
        'title' => 'Test Event',
        'start_at' => now()->addDays(7)->toDateString(),
    ]);

    $response->assertSuccessful();

    $event = Event::latest()->first();

    $membership = EventMember::where('event_id', $event->id)
        ->where('user_id', $user->id)
        ->first();

    expect($membership)->not->toBeNull();
    expect($membership->role)->toBe(EventMemberRole::Owner);
});

it('can show event details to members', function () {
    $user = User::factory()->create();
    $event = Event::factory()->create();

    EventMember::factory()->create([
        'event_id' => $event->id,
        'user_id' => $user->id,
    ]);

    $response = actingAs($user)->getJson("/api/events/{$event->id}");

    $response->assertSuccessful();
    expect($response->json('id'))->toBe($event->id);
    expect($response->json('title'))->toBe($event->title);
});

it('prevents non-members from viewing event', function () {
    $user = User::factory()->create();
    $otherUser = User::factory()->create();
    $event = Event::factory()->create();

    EventMember::factory()->create([
        'event_id' => $event->id,
        'user_id' => $otherUser->id,
    ]);

    $response = actingAs($user)->getJson("/api/events/{$event->id}");

    $response->assertNotFound();
});

it('loads relationships efficiently on show', function () {
    $user = User::factory()->create();
    $event = Event::factory()->create();

    EventMember::factory()->create([
        'event_id' => $event->id,
        'user_id' => $user->id,
    ]);

    $response = actingAs($user)->getJson("/api/events/{$event->id}");

    $response->assertSuccessful();
    expect($response->json('creator'))->not->toBeNull();
    expect($response->json('members'))->toBeArray();
});

it('paginates event list', function () {
    $user = User::factory()->create();

    // Create 20 events
    for ($i = 0; $i < 20; $i++) {
        $event = Event::factory()->create();
        EventMember::factory()->create([
            'event_id' => $event->id,
            'user_id' => $user->id,
        ]);
    }

    $response = actingAs($user)->getJson('/api/events');

    $response->assertSuccessful();
    expect($response->json('data'))->toHaveCount(15); // simplePaginate(15)
    expect($response->json('next_page_url'))->not->toBeNull();
});
