<?php
namespace Modules\Auth\Domain\Repositories;

use Modules\Auth\Domain\Models\User;
use Modules\Auth\Domain\Contracts\AuthRepositoryInterface;

class AuthRepository implements AuthRepositoryInterface
{
    /**
     * Constructor
     */
    public function __construct(){}

    /**
     * Get user by email
     * @param string $email
     * @return User|null
     * @throws \Exception
     */
    public function getUserByEmail(string $email): User|null{
        return User::where('email',$email)->first();
    }

    /**
     * Create a new user
     * @param string $email
     * @param string $magentoUserId
     * @return string
     */
    public function createUser(string $email, string $magentoUserId): string{
        $user = User::create([
            'email' => $email,
            'magento_user_id' => $magentoUserId,
        ]);
        return $user->createToken('api_token')->plainTextToken;
    }

}