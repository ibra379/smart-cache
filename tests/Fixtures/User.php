<?php

declare(strict_types=1);

namespace Tests\Fixtures;

use DialloIbrahima\SmartCache\HasSmartCache;
use Illuminate\Database\Eloquent\Model;

class User extends Model
{
    use HasSmartCache;

    protected $guarded = [];

    protected $table = 'users';
}
