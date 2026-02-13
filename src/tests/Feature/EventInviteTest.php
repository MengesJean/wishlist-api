<?php

use App\Enums\EventMemberRole;
use App\Models\Event;
use App\Models\EventInvite;
use App\Models\EventMember;
use App\Models\User;
use App\Notifications\EventInviteNotification;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Notification;

use function Pest\Laravel\actingAs;

uses(RefreshDatabase::class);

it('allows owner to invite by email', function () {
    $owner = User::factory()->create();
    $event = Event::factory()->create();

    EventMember::factory()->owner()->create([
        'event_id' => $event->id,
        'user_id' => $owner->id,
    ]);

    $response = actingAs($owner)->postJson("/api/events/{$event->id}/invites", [
        'email' => 'invitee@example.com',
    ]);

    $response->assertCreated();
    expect($response->json('invite'))->not->toBeNull();
});

it('prevents non-owner from inviting', function () {
    $member = User::factory()->create();
    $event = Event::factory()->create();

    EventMember::factory()->create([
        'event_id' => $event->id,
        'user_id' => $member->id,
        'role' => EventMemberRole::Member,
    ]);

    $response = actingAs($member)->postJson("/api/events/{$event->id}/invites", [
        'email' => 'invitee@example.com',
    ]);

    $response->assertForbidden();
});

it('creates invite for existing user without token', function () {
    $owner = User::factory()->create();
    $invitee = User::factory()->create(['email' => 'invitee@example.com']);
    $event = Event::factory()->create();

    EventMember::factory()->owner()->create([
        'event_id' => $event->id,
        'user_id' => $owner->id,
    ]);

    $response = actingAs($owner)->postJson("/api/events/{$event->id}/invites", [
        'email' => 'invitee@example.com',
    ]);

    $response->assertCreated();

    $invite = EventInvite::where('event_id', $event->id)
        ->where('invited_email', 'invitee@example.com')
        ->first();

    expect($invite->invited_user_id)->toBe($invitee->id);
    expect($invite->token_hash)->toBeNull();
    expect($invite->expires_at)->toBeNull();
});

it('creates invite for new email with token', function () {
    $owner = User::factory()->create();
    $event = Event::factory()->create();

    EventMember::factory()->owner()->create([
        'event_id' => $event->id,
        'user_id' => $owner->id,
    ]);

    $response = actingAs($owner)->postJson("/api/events/{$event->id}/invites", [
        'email' => 'newuser@example.com',
    ]);

    $response->assertCreated();

    $invite = EventInvite::where('event_id', $event->id)
        ->where('invited_email', 'newuser@example.com')
        ->first();

    expect($invite->invited_user_id)->toBeNull();
    expect($invite->token_hash)->not->toBeNull();
    expect($invite->expires_at)->not->toBeNull();
    expect($response->json('invite_link'))->not->toBeNull();
});

it('prevents duplicate invites to existing members', function () {
    $owner = User::factory()->create();
    $member = User::factory()->create(['email' => 'member@example.com']);
    $event = Event::factory()->create();

    EventMember::factory()->owner()->create([
        'event_id' => $event->id,
        'user_id' => $owner->id,
    ]);

    EventMember::factory()->create([
        'event_id' => $event->id,
        'user_id' => $member->id,
    ]);

    $response = actingAs($owner)->postJson("/api/events/{$event->id}/invites", [
        'email' => 'member@example.com',
    ]);

    $response->assertStatus(409);
    expect($response->json('message'))->toBe('User already a member');
});

it('sends notification to invited user', function () {
    Notification::fake();

    $owner = User::factory()->create();
    $invitee = User::factory()->create(['email' => 'invitee@example.com']);
    $event = Event::factory()->create();

    EventMember::factory()->owner()->create([
        'event_id' => $event->id,
        'user_id' => $owner->id,
    ]);

    actingAs($owner)->postJson("/api/events/{$event->id}/invites", [
        'email' => 'invitee@example.com',
    ]);

    Notification::assertSentTo($invitee, EventInviteNotification::class);
});

it('can list event invites for owner', function () {
    $owner = User::factory()->create();
    $event = Event::factory()->create();

    EventMember::factory()->owner()->create([
        'event_id' => $event->id,
        'user_id' => $owner->id,
    ]);

    EventInvite::factory()->count(3)->create([
        'event_id' => $event->id,
    ]);

    $response = actingAs($owner)->getJson("/api/events/{$event->id}/invites");

    $response->assertSuccessful();
    expect($response->json('data'))->toHaveCount(3);
});

it('eager loads members before authorization', function () {
    $owner = User::factory()->create();
    $event = Event::factory()->create();

    EventMember::factory()->owner()->create([
        'event_id' => $event->id,
        'user_id' => $owner->id,
    ]);

    // Count queries during request
    $queryCount = 0;
    \DB::listen(function () use (&$queryCount) {
        $queryCount++;
    });

    actingAs($owner)->postJson("/api/events/{$event->id}/invites", [
        'email' => 'test@example.com',
    ]);

    // Should not have excessive queries from authorization
    expect($queryCount)->toBeLessThan(10);
});

it('paginates invites', function () {
    $owner = User::factory()->create();
    $event = Event::factory()->create();

    EventMember::factory()->owner()->create([
        'event_id' => $event->id,
        'user_id' => $owner->id,
    ]);

    EventInvite::factory()->count(20)->create([
        'event_id' => $event->id,
    ]);

    $response = actingAs($owner)->getJson("/api/events/{$event->id}/invites");

    $response->assertSuccessful();
    expect($response->json('data'))->toHaveCount(15);
    expect($response->json('next_page_url'))->not->toBeNull();
});
