<?php

use App\Models\User;
use Illuminate\Foundation\Testing\LazilyRefreshDatabase;
use Tests\TestCase;

pest()->extend(TestCase::class)
    ->use(LazilyRefreshDatabase::class)
    ->beforeEach(fn () => cache()->flush())
    ->in('Feature');

pest()->extend(TestCase::class)->in('Concurrency');

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
 * A complete, valid create-project body that individual tests override field by field.
 *
 * The status is left out on purpose, so the default path (BR-6) is what most tests exercise.
 *
 * @param  array<string, mixed>  $overrides
 * @return array<string, mixed>
 */
function projectPayload(array $overrides = []): array
{
    return array_merge([
        'name' => 'Website Redesign',
        'description' => 'Q3 marketing site',
    ], $overrides);
}

/**
 * A complete, valid create-task body that individual tests override field by field.
 *
 * The caller supplies the project id, because a task always belongs to one (BR-1).
 * Priority and status are left out on purpose, so the default path (BR-7, BR-8) is
 * what most tests exercise.
 *
 * @param  array<string, mixed>  $overrides
 * @return array<string, mixed>
 */
function taskPayload(int $projectId, array $overrides = []): array
{
    return array_merge([
        'project_id' => $projectId,
        'title' => 'Fix login bug',
        'description' => 'The form rejects valid credentials',
        'due_date' => '2026-08-15',
    ], $overrides);
}

/**
 * Issue a token directly, for tests about what a token does rather than how it is obtained.
 */
function tokenFor(User $user): string
{
    return $user->createToken('api')->plainTextToken;
}
