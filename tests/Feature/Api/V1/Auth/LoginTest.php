<?php

use App\Models\User;

it('returns a token that a protected endpoint accepts', function () {
    User::factory()->create(['email' => 'sara@mail.com', 'password' => 'secret123']);

    $response = $this->postJson(route('auth.login'), [
        'email' => 'sara@mail.com',
        'password' => 'secret123',
    ]);

    $response->assertSuccessful()->assertJsonPath('data.user.email', 'sara@mail.com');

    $this->withToken($response->json('data.token'))
        ->postJson(route('auth.logout'))
        ->assertSuccessful();
});

it('accepts an email written in a different letter case', function () {
    User::factory()->create(['email' => 'sara@mail.com', 'password' => 'secret123']);

    $this->postJson(route('auth.login'), [
        'email' => 'SARA@MAIL.COM',
        'password' => 'secret123',
    ])->assertSuccessful();
});

it('rejects a wrong password with a generic message', function () {
    User::factory()->create(['email' => 'sara@mail.com', 'password' => 'secret123']);

    $this->postJson(route('auth.login'), [
        'email' => 'sara@mail.com',
        'password' => 'wrongpass',
    ])
        ->assertUnauthorized()
        ->assertJsonPath('message', 'These credentials do not match our records.')
        ->assertJsonMissingPath('data');
});

it('answers an unregistered email exactly as it answers a wrong password', function () {
    User::factory()->create(['email' => 'sara@mail.com', 'password' => 'secret123']);

    $wrongPassword = $this->postJson(route('auth.login'), [
        'email' => 'sara@mail.com',
        'password' => 'wrongpass',
    ]);

    $unknownEmail = $this->postJson(route('auth.login'), [
        'email' => 'ghost@mail.com',
        'password' => 'secret123',
    ]);

    expect($unknownEmail->getContent())->toBe($wrongPassword->getContent())
        ->and($unknownEmail->getStatusCode())->toBe($wrongPassword->getStatusCode());
});

it('issues a distinct working token to each device', function () {
    User::factory()->create(['email' => 'sara@mail.com', 'password' => 'secret123']);

    $credentials = ['email' => 'sara@mail.com', 'password' => 'secret123'];

    $deviceA = $this->postJson(route('auth.login'), $credentials)->json('data.token');
    $deviceB = $this->postJson(route('auth.login'), $credentials)->json('data.token');

    expect($deviceA)->not->toBe($deviceB);

    $this->withToken($deviceA)->postJson(route('auth.logout'))->assertSuccessful();
    $this->withToken($deviceB)->postJson(route('auth.logout'))->assertSuccessful();
});

it('refuses a sixth attempt inside the window even when the password is correct', function () {
    $email = 'throttled@mail.com';

    User::factory()->create(['email' => $email, 'password' => 'secret123']);

    foreach (range(1, 5) as $ignored) {
        $this->postJson(route('auth.login'), ['email' => $email, 'password' => 'wrongpass'])
            ->assertUnauthorized();
    }

    $response = $this->postJson(route('auth.login'), ['email' => $email, 'password' => 'secret123']);

    $response->assertTooManyRequests();

    expect($response->json('message'))
        ->toMatch('/^Too many login attempts\. Please try again in \d+ seconds\.$/');
});

it('does not spend the throttle budget on successful logins', function () {
    $email = 'patient@mail.com';

    User::factory()->create(['email' => $email, 'password' => 'secret123']);

    foreach (range(1, 6) as $ignored) {
        $this->postJson(route('auth.login'), ['email' => $email, 'password' => 'secret123'])
            ->assertSuccessful();
    }
});

it('reports every missing credential in one response', function () {
    $this->postJson(route('auth.login'), [])
        ->assertUnprocessable()
        ->assertJsonValidationErrors(['email', 'password']);
});
