<?php

it('refuses every project endpoint reached without a token', function (string $method, string $uri) {
    $this->json($method, $uri)
        ->assertUnauthorized()
        ->assertExactJson(['success' => false, 'message' => 'Unauthenticated.']);
})->with([
    ['get', '/api/v1/projects'],
    ['post', '/api/v1/projects'],
    ['get', '/api/v1/projects/1'],
    ['put', '/api/v1/projects/1'],
    ['patch', '/api/v1/projects/1'],
    ['delete', '/api/v1/projects/1'],
]);
