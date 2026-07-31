<?php

use App\Models\User;
use Laravel\Sanctum\PersonalAccessToken;

it('revokes the token that made the request', function () {
    $user = User::factory()->create();

    $this->withToken(tokenFor($user))
        ->postJson(route('auth.logout'))
        ->assertSuccessful()
        ->assertJsonPath('message', 'Logged out successfully.');

    expect(PersonalAccessToken::count())->toBe(0);
});

it('treats a revoked token exactly like no token at all', function () {
    $token = tokenFor(User::factory()->create());

    $this->withToken($token)->postJson(route('auth.logout'))->assertSuccessful();

    $this->withToken($token)
        ->postJson(route('auth.logout'))
        ->assertUnauthorized()
        ->assertJsonPath('message', 'Unauthenticated.');
});

it('leaves the other device signed in', function () {
    $user = User::factory()->create();

    $deviceA = tokenFor($user);
    $deviceB = tokenFor($user);

    $this->withToken($deviceA)->postJson(route('auth.logout'))->assertSuccessful();

    $this->withToken($deviceB)->postJson(route('auth.logout'))->assertSuccessful();
});
