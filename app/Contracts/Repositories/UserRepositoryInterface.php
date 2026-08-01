<?php

declare(strict_types=1);

namespace App\Contracts\Repositories;

use App\Models\User;
use App\Repositories\UserRepository;
use Illuminate\Container\Attributes\Bind;

#[Bind(UserRepository::class)]
interface UserRepositoryInterface
{
    /**
     * @param  array{name: string, email: string, password: string}  $data
     */
    public function create(array $data): User;

    public function findByEmail(string $email): ?User;

    public function find(int $userId): ?User;
}
