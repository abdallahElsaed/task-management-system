<?php

declare(strict_types=1);

namespace App\Services;

use App\Exceptions\InvalidCredentialsException;
use App\Models\User;
use Illuminate\Support\Facades\Hash;
use Laravel\Sanctum\PersonalAccessToken;

final class AuthService
{
    private const TOKEN_NAME = 'api';

    /**
     * Create an account and issue its first token, so a new account holder
     * never has to call login straight after registering.
     *
     * @param  array{name: string, email: string, password: string}  $data
     * @return array{user: User, token: string}
     */
    public function register(array $data): array
    {
        $user = User::create($data);

        return [
            'user' => $user,
            'token' => $this->issueToken($user),
        ];
    }

    /**
     * @return array{user: User, token: string}
     *
     * @throws InvalidCredentialsException
     */
    public function login(string $email, string $password): array
    {
        $user = User::firstWhere('email', $email);

        if (! $user instanceof User) {
            // Spend the cost of a hash anyway, so the time taken to answer
            // never reveals whether the email is registered.
            Hash::make($password);

            throw new InvalidCredentialsException;
        }

        if (! Hash::check($password, $user->password)) {
            throw new InvalidCredentialsException;
        }

        return [
            'user' => $user,
            'token' => $this->issueToken($user),
        ];
    }

    /**
     * Revoke only the token that made the current request, leaving tokens
     * issued to the same account on other devices working.
     */
    public function logout(User $user): void
    {
        $token = $user->currentAccessToken();

        if ($token instanceof PersonalAccessToken) {
            $token->delete();
        }
    }

    private function issueToken(User $user): string
    {
        return $user->createToken(self::TOKEN_NAME)->plainTextToken;
    }
}
