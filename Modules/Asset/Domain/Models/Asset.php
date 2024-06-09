<?php

namespace Modules\Asset\Domain\Models;

use Modules\Auth\Domain\Models\User;
use MongoDB\Laravel\Eloquent\Model;
use Modules\Asset\Domain\Dto\AssetMediaDto;
use Illuminate\Notifications\Notifiable;
use MongoDB\Laravel\Eloquent\SoftDeletes;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class Asset extends Model
{
    use HasFactory, Notifiable, SoftDeletes;

    protected $connection = 'mongodb';
    protected $collection = 'assets';
    protected $primaryKey = '_id';

    /**
     * The attributes that are mass assignable.
     *
     * @var array<int, string>
     */
    protected $fillable = [
        'status',
        'data',
        'ingest',
        'media_info',
        'owner_id',
        'published',
        'scopes'
    ];

    protected $appends = ['media'];

    /**
     * The attributes that should be hidden for serialization.
     *
     * @var array<int, string>
     */
    protected $hidden = [];

    public function getMediaAttribute():array
    {
        $media=new AssetMediaDto(
            env("AWS_MEDIA_URL").$this->_id."/stream/index.m3u8",
            env("AWS_MEDIA_URL").$this->_id."/frames/frame_00003.jpg",
        );
        return $media->toArray();
    }

    public function owner(): HasOne
    {
        return $this->hasOne(User::class,'_id','owner_id');
    }
}
