<?php

namespace Modules\Asset\Domain\Models;

use Illuminate\Support\Facades\Storage;
use Modules\Auth\Domain\Models\User;
use Modules\Playlist\Domain\Models\Playlist;
use MongoDB\Laravel\Eloquent\Model;
use Modules\Asset\Domain\Dto\AssetMediaDto;
use Illuminate\Notifications\Notifiable;
use MongoDB\Laravel\Eloquent\SoftDeletes;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

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
        'verification'
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
        //random signature
        $signature=md5(microtime().rand(10000,99999));
        //check the frames availability
        if(Storage::disk('s3_media')->exists($this->_id.'/frames/HD/frame_custom.jpg'))
            $frame='frame_custom.jpg';
        elseif(Storage::disk('s3_media')->exists($this->_id.'/frames/HD/frame_custom.jpg'))
            $frame='frame_00003.jpg';
        else
            $frame='frame_00001.jpg';
        $frame.="?".$signature;
        //set the frame's qualities
        $keyFrames=[
            'HD' => env("AWS_MEDIA_URL").$this->_id."/frames/HD/".$frame,
            'SD' => env("AWS_MEDIA_URL").$this->_id."/frames/SD/".$frame,
            'THUMBNAIL' => env("AWS_MEDIA_URL").$this->_id."/frames/THUMBNAIL/".$frame,
        ];
        //set the DTO
        $media=new AssetMediaDto(
            env("AWS_MEDIA_URL").$this->_id."/stream/index.m3u8",
            $keyFrames
        );
        return $media->toArray();
    }

    public function owner(): HasOne
    {
        return $this->hasOne(User::class,'_id','owner_id');
    }

    public function playlist(): BelongsTo
    {
        return $this->belongsTo(Playlist::class,'_id', 'asset_id');
    }
}
