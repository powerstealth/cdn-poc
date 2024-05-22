<?php

namespace Modules\Asset\Domain\Models;

use MongoDB\Laravel\Eloquent\Model;
use Illuminate\Notifications\Notifiable;
use MongoDB\Laravel\Eloquent\SoftDeletes;
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
        'owner'
    ];

    /**
     * The attributes that should be hidden for serialization.
     *
     * @var array<int, string>
     */
    protected $hidden = [];

}
