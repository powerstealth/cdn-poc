<?php

namespace Modules\Tv\Domain\Models;

use Illuminate\Support\Facades\Storage;
use Modules\Auth\Domain\Models\User;
use MongoDB\Laravel\Eloquent\Model;
use Modules\Asset\Domain\Dto\AssetMediaDto;
use Illuminate\Notifications\Notifiable;
use MongoDB\Laravel\Eloquent\SoftDeletes;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class Home extends Model
{
    use HasFactory, Notifiable, SoftDeletes;

    protected $connection = 'mongodb';
    protected $collection = 'tv_home';
    protected $primaryKey = '_id';

    /**
     * The attributes that are mass assignable.
     *
     * @var array<int, string>
     */
    protected $fillable = [
        'asset_id',
        'section',
        'position',
    ];

    /**
     * The attributes that should be hidden for serialization.
     *
     * @var array<int, string>
     */
    protected $hidden = [];

}
