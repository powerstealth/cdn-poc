<?php
namespace Modules\Auth\Domain\Repositories;

use Modules\Auth\Domain\Models\User;
use Modules\Auth\Domain\Contracts\AuthRepositoryInterface;
use MongoDB\BSON\ObjectId;

class AuthRepository implements AuthRepositoryInterface
{

    /**
     * Constructor
     */
    public function __construct(){}

    /**
     * Get user by ID
     * @param string $id
     * @return User|null
     */
    public function getUserById(string $id): User|null{
        try {
            return User::find(new \MongoDB\BSON\ObjectId($id));
        }catch (\Exception $e){dd($e);
            return null;
        }
    }

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