<?php

namespace Modules\Auth\Domain\Contracts;

use Modules\Auth\Domain\Models\User;

interface AuthRepositoryInterface
{
    public function getUserById(string $id): User|null;
    public function getUserByEmail(string $email): User|null;
    public function createUser(string $email, string $magentoUserId): string;
}