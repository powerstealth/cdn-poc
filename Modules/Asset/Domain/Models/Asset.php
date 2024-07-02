<?php

namespace Modules\Asset\Domain\Models;

use Illuminate\Support\Facades\Storage;
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
        if(Storage::disk('s3_media')->exists($this->_id.'/frames/frame_custom.jpg'))
            $customKeyFrame='frame_custom.jpg';
        else
            $customKeyFrame='frame_00003.jpg';
        $media=new AssetMediaDto(
            env("AWS_MEDIA_URL").$this->_id."/stream/index.m3u8",
            env("AWS_MEDIA_URL").$this->_id."/frames/".$customKeyFrame
        );
        return $media->toArray();
    }

    public function owner(): HasOne
    {
        return $this->hasOne(User::class,'_id','owner_id');
    }
}
