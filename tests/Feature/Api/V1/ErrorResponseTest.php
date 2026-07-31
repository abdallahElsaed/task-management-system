<?php

it('refuses a protected endpoint reached without a token', function () {
    $this->postJson(route('auth.logout'))
        ->assertUnauthorized()
        ->assertExactJson(['success' => false, 'message' => 'Unauthenticated.']);
});

it('answers a token that was never issued exactly as it answers no token', function () {
    $noToken = $this->postJson(route('auth.logout'));
    $forgedToken = $this->withToken('abc.def.ghi')->postJson(route('auth.logout'));

    expect($forgedToken->getContent())->toBe($noToken->getContent())
        ->and($forgedToken->getStatusCode())->toBe($noToken->getStatusCode());
});

it('returns JSON rather than HTML even when the caller sends no Accept header', function () {
    $response = $this->post(route('auth.logout'));

    $response->assertUnauthorized();

    expect($response->headers->get('content-type'))->toContain('application/json')
        ->and($response->getContent())->not->toContain('<html');
});

it('names an address that matches no endpoint', function () {
    $this->getJson('/api/v1/nope')
        ->assertNotFound()
        ->assertExactJson(['success' => false, 'message' => 'Endpoint not found.']);
});

it('names a method the endpoint does not accept', function () {
    $this->getJson(route('auth.login'))
        ->assertMethodNotAllowed()
        ->assertExactJson([
            'success' => false,
            'message' => 'This method is not allowed for this endpoint.',
        ]);
});

it('carries a message and a field-keyed errors object on a validation failure', function () {
    $response = $this->postJson(route('auth.register'), []);

    $response->assertUnprocessable();

    expect($response->json())->toHaveKeys(['success', 'message', 'errors'])
        ->and($response->json('success'))->toBeFalse()
        ->and($response->json('errors'))->toHaveKeys(['name', 'email', 'password']);
});
