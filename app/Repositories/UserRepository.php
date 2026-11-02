<?php

declare(strict_types=1);

namespace App\Repositories;

use App\Contracts\Repositories\UserRepositoryInterface;
use App\Models\User;

final readonly class UserRepository implements UserRepositoryInterface
{
    /**
     * @param  array{name: string, email: string, password: string}  $data
     */
    public function create(array $data): User
    {
        return User::create($data);
    }

    public function findByEmail(string $email): ?User
    {
        return User::firstWhere('email', $email);
    }

    /**
     * The queued overdue digest carries an id rather than a serialized account, so
     * it reads the current name and address at the moment it sends.
     */
    public function find(int $userId): ?User
    {
        return User::find($userId);
    }
}
