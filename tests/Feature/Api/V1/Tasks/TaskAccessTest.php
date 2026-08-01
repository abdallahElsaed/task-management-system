<?php

it('refuses every task endpoint reached without a token', function (string $method, string $uri) {
    $this->json($method, $uri)
        ->assertUnauthorized()
        ->assertExactJson(['success' => false, 'message' => 'Unauthenticated.']);
})->with([
    ['get', '/api/v1/tasks'],
    ['post', '/api/v1/tasks'],
    ['get', '/api/v1/tasks/1'],
    ['put', '/api/v1/tasks/1'],
    ['patch', '/api/v1/tasks/1'],
    ['delete', '/api/v1/tasks/1'],
]);
