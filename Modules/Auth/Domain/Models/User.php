<?php

namespace Modules\Auth\Domain\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Notifications\Notifiable;
use Laravel\Sanctum\HasApiTokens;
use MongoDB\Laravel\Eloquent\Model;
use Spatie\Permission\Traits\HasRoles;
use Illuminate\Foundation\Auth\User as Authenticatable;

class User extends Model
{
    use HasApiTokens, HasFactory, Notifiable, HasRoles;

    protected $connection = 'mongodb';
    protected $collection = 'users';
    protected $primaryKey = '_id';

    /**
     * The attributes that are mass assignable.
     *
     * @var array<int, string>
     */
    protected $fillable = [
        'email',
        'magento_user_id',
    ];

    /**
     * The attributes that should be hidden for serialization.
     *
     * @var array<int, string>
     */
    protected $hidden = [];

    protected $appends = ['is_admin'];

    /**
     * Is the user an admin?
     * @return bool
     */
    public function getIsAdminAttribute():bool
    {
        return $this->hasRole("admin");
    }
}
