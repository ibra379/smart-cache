<?php

declare(strict_types=1);

namespace Workbench\App\Models;

use DialloIbrahima\SmartCache\HasSmartCache;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Workbench\Database\Factories\CommentFactory;

class Comment extends Model
{
    use HasFactory;
    use HasSmartCache;

    protected $guarded = [];

    protected $table = 'comments';

    /**
     * When a Comment changes, also invalidate Post cache.
     *
     * @return array<class-string<Model>>
     */
    public static function invalidatesSmartCacheOf(): array
    {
        return [Post::class];
    }

    public function post(): BelongsTo
    {
        return $this->belongsTo(Post::class);
    }

    protected static function newFactory(): CommentFactory
    {
        return CommentFactory::new();
    }
}
