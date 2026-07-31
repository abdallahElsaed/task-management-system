<?php

use App\Models\User;
use Illuminate\Foundation\Testing\LazilyRefreshDatabase;
use Tests\TestCase;

pest()->extend(TestCase::class)
    ->use(LazilyRefreshDatabase::class)
    ->beforeEach(fn () => cache()->flush())
    ->in('Feature');

/*
|--------------------------------------------------------------------------
| Helpers
|--------------------------------------------------------------------------
*/

/**
 * A complete, valid registration body that individual tests override field by field.
 *
 * @param  array<string, mixed>  $overrides
 * @return array<string, mixed>
 */
function registrationPayload(array $overrides = []): array
{
    return array_merge([
        'name' => 'Sara',
        'email' => 'sara@mail.com',
        'password' => 'secret123',
        'password_confirmation' => 'secret123',
    ], $overrides);
}

/**
 * Issue a token directly, for tests about what a token does rather than how it is obtained.
 */
function tokenFor(User $user): string
{
    return $user->createToken('api')->plainTextToken;
}
