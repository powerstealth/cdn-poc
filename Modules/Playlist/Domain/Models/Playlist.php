<?php

namespace Modules\Playlist\Domain\Models;

use Modules\Asset\Domain\Models\Asset;
use Modules\Auth\Domain\Models\User;
use MongoDB\Laravel\Eloquent\Model;
use Illuminate\Notifications\Notifiable;
use MongoDB\Laravel\Eloquent\SoftDeletes;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class Playlist extends Model
{
    use HasFactory, Notifiable, SoftDeletes;

    protected $connection = 'mongodb';
    protected $collection = 'playlists';
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

    /**
     * Asset relation
     * @return HasOne
     */
    public function asset(): HasOne
    {
        return $this->hasOne(Asset::class,'_id','asset_id');
    }
}
