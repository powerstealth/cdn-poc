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
        }catch (\Exception $e){
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

    /**
     * List users
     * @param int         $page
     * @param int         $limit
     * @param string      $sortField
     * @param string      $sortOrder
     * @param array       $filters
     * @param string|null $search
     * @param bool        $setPagination
     * @return array|\Exception
     */
    public function listUsers(int $page, int $limit, string $sortField, string $sortOrder, array $filters, ?string $search, bool $setPagination=true):array|\Exception
    {
        try {
            //select
            $users=User::select('*');
            //add filters
            if(count($filters)>0){
                foreach ($filters as $filter){
                    if($filter[1]=="in"){
                        $users=$users->whereIn($filter[0],(is_array($filter[2]) ? $filter[2] : [$filter[2]]));
                    }else{
                        $users=$users->where($filter[0],$filter[1],$filter[2]);
                    }
                }
            }
            //search
            if($search!==null){
                $users=$users->where(function ($query) use ($search) {
                    //email
                    $query->orWhere('email', 'like', '%' . $search . '%');
                });
            }
            //sort query
            $users->orderBy($sortField,$sortOrder);
            //set pagination
            if($setPagination){
                $users=$users->paginate($limit);
                return $users->toArray();
            }else{
                return $users->skip($limit*($page-1))->take($limit)->get()->toArray();
            }
        }catch (\Exception $e){
            return $e;
        }
    }

}