<?php

/*
 * A single verb rather than a matrix: the dashboard answers GET alone, and the
 * router rejects any other method with 405 before the guard is ever reached.
 */
it('refuses the dashboard reached without a token', function () {
    $this->getJson('/api/v1/dashboard')
        ->assertUnauthorized()
        ->assertExactJson(['success' => false, 'message' => 'Unauthenticated.']);
});
