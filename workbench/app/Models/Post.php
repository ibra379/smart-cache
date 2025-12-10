<?php

declare(strict_types=1);

namespace Workbench\App\Models;

use DialloIbrahima\SmartCache\HasSmartCache;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Workbench\Database\Factories\PostFactory;

class Post extends Model
{
    use HasFactory;
    use HasSmartCache;

    protected $guarded = [];

    protected $table = 'posts';

    protected static function newFactory(): PostFactory
    {
        return PostFactory::new();
    }
}
