<?php

use App\Enums\EventMemberRole;
use App\Models\Event;
use App\Models\EventMember;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Gate;

uses(RefreshDatabase::class);

it('allows members to view event', function () {
    $user = User::factory()->create();
    $event = Event::factory()->create();

    EventMember::factory()->create([
        'event_id' => $event->id,
        'user_id' => $user->id,
    ]);

    $canView = Gate::forUser($user)->allows('view', $event);

    expect($canView)->toBeTrue();
});

it('prevents non-members from viewing event', function () {
    $user = User::factory()->create();
    $event = Event::factory()->create();

    $canView = Gate::forUser($user)->allows('view', $event);

    expect($canView)->toBeFalse();
});

it('allows owner to invite', function () {
    $user = User::factory()->create();
    $event = Event::factory()->create();

    EventMember::factory()->owner()->create([
        'event_id' => $event->id,
        'user_id' => $user->id,
    ]);

    $canInvite = Gate::forUser($user)->allows('invite', $event);

    expect($canInvite)->toBeTrue();
});

it('prevents member from inviting', function () {
    $user = User::factory()->create();
    $event = Event::factory()->create();

    EventMember::factory()->create([
        'event_id' => $event->id,
        'user_id' => $user->id,
        'role' => EventMemberRole::Member,
    ]);

    $canInvite = Gate::forUser($user)->allows('invite', $event);

    expect($canInvite)->toBeFalse();
});

it('uses eager-loaded members when available', function () {
    $user = User::factory()->create();
    $event = Event::factory()->create();

    EventMember::factory()->owner()->create([
        'event_id' => $event->id,
        'user_id' => $user->id,
    ]);

    // Eager load members
    $event->load('members');

    // Count queries during authorization
    $queryCount = 0;
    \DB::listen(function () use (&$queryCount) {
        $queryCount++;
    });

    $canInvite = Gate::forUser($user)->allows('invite', $event);

    expect($canInvite)->toBeTrue();
    expect($queryCount)->toBe(0); // No queries should be made
});

it('falls back to query when members not loaded', function () {
    $user = User::factory()->create();
    $event = Event::factory()->create();

    EventMember::factory()->owner()->create([
        'event_id' => $event->id,
        'user_id' => $user->id,
    ]);

    // Do NOT eager load members
    $queryCount = 0;
    \DB::listen(function () use (&$queryCount) {
        $queryCount++;
    });

    $canInvite = Gate::forUser($user)->allows('invite', $event);

    expect($canInvite)->toBeTrue();
    expect($queryCount)->toBeGreaterThan(0); // Should make a query
});

it('checks owner role correctly with enum', function () {
    $user = User::factory()->create();
    $event = Event::factory()->create();

    // Create member with owner role using enum
    EventMember::factory()->create([
        'event_id' => $event->id,
        'user_id' => $user->id,
        'role' => EventMemberRole::Owner,
    ]);

    $canInvite = Gate::forUser($user)->allows('invite', $event);

    expect($canInvite)->toBeTrue();

    // Verify the role is stored correctly as enum
    $member = EventMember::where('event_id', $event->id)
        ->where('user_id', $user->id)
        ->first();

    expect($member->role)->toBe(EventMemberRole::Owner);
    expect($member->role)->toBeInstanceOf(EventMemberRole::class);
});
