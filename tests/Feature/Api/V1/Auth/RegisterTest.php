<?php

use App\Models\User;

it('creates the account and issues a usable token', function () {
    $response = $this->postJson(route('auth.register'), registrationPayload());

    $response->assertCreated()
        ->assertJsonPath('data.user.name', 'Sara')
        ->assertJsonPath('data.user.email', 'sara@mail.com');

    expect($response->json('data.token'))->toBeString()->not->toBeEmpty();

    $this->withToken($response->json('data.token'))
        ->postJson(route('auth.logout'))
        ->assertSuccessful();
});

it('rejects an email that differs from an existing one only by letter case', function () {
    User::factory()->create(['email' => 'sara@mail.com']);

    $this->postJson(route('auth.register'), registrationPayload(['email' => 'SARA@MAIL.COM']))
        ->assertUnprocessable()
        ->assertJsonPath('errors.email.0', 'This email address is already registered.');

    expect(User::count())->toBe(1);
});

it('rejects a password confirmation that does not match', function () {
    $this->postJson(route('auth.register'), registrationPayload(['password_confirmation' => 'secret124']))
        ->assertUnprocessable()
        ->assertJsonPath('errors.password.0', 'The password confirmation does not match.');

    expect(User::count())->toBe(0);
});

it('rejects a password shorter than eight characters', function () {
    $this->postJson(route('auth.register'), registrationPayload([
        'password' => 'pass12',
        'password_confirmation' => 'pass12',
    ]))
        ->assertUnprocessable()
        ->assertJsonPath('errors.password.0', 'The password must be at least 8 characters.');

    expect(User::count())->toBe(0);
});

it('never returns the password or its hash', function () {
    $response = $this->postJson(route('auth.register'), registrationPayload());

    expect($response->json('data.user'))->not->toHaveKey('password')
        ->and($response->getContent())->not->toContain('secret123')
        ->and($response->getContent())->not->toContain(User::sole()->password);
});

it('returns an Arabic name character for character', function () {
    $response = $this->postJson(route('auth.register'), registrationPayload(['name' => 'عبدالله']));

    $response->assertCreated()->assertJsonPath('data.user.name', 'عبدالله');

    expect($response->getContent())->toContain('عبدالله');
});

it('rejects a name shorter than two characters', function () {
    $this->postJson(route('auth.register'), registrationPayload(['name' => 'S']))
        ->assertUnprocessable()
        ->assertJsonValidationErrors('name');
});

it('reports every missing field in one response', function () {
    $this->postJson(route('auth.register'), [])
        ->assertUnprocessable()
        ->assertJsonValidationErrors(['name', 'email', 'password']);
});

it('stores the email lower-cased', function () {
    $this->postJson(route('auth.register'), registrationPayload(['email' => 'Sara@Mail.com']))
        ->assertCreated()
        ->assertJsonPath('data.user.email', 'sara@mail.com');

    expect(User::sole()->email)->toBe('sara@mail.com');
});

it('refuses an eleventh registration from the same caller inside the window', function () {
    foreach (range(1, 10) as $attempt) {
        $this->postJson(route('auth.register'), registrationPayload(['email' => "sara{$attempt}@mail.com"]))
            ->assertCreated();
    }

    $response = $this->postJson(route('auth.register'), registrationPayload(['email' => 'late@mail.com']));

    $response->assertTooManyRequests()
        ->assertJsonPath('success', false)
        ->assertHeader('Retry-After');

    expect($response->json('message'))
        ->toMatch('/^Too many registration attempts\. Please try again in \d+ seconds\.$/');

    expect(User::count())->toBe(10);
});

it('counts a rejected registration against the limit', function () {
    foreach (range(1, 10) as $ignored) {
        $this->postJson(route('auth.register'), registrationPayload(['email' => 'not-an-email']))
            ->assertUnprocessable();
    }

    $this->postJson(route('auth.register'), registrationPayload())->assertTooManyRequests();
});

it('keeps one caller\'s attempts from spending another caller\'s limit', function () {
    foreach (range(1, 10) as $attempt) {
        $this->postJson(route('auth.register'), registrationPayload(['email' => "sara{$attempt}@mail.com"]))
            ->assertCreated();
    }

    $this->withServerVariables(['REMOTE_ADDR' => '203.0.113.9'])
        ->postJson(route('auth.register'), registrationPayload(['email' => 'other@mail.com']))
        ->assertCreated();
});
