<?php

namespace Coderstm\Models\Blog;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Comment extends Model
{
    use HasFactory;

    protected $fillable = ['message'];

    protected $hidden = [
        'commentable_type',
        'commentable_id',
        'userable_type',
        'userable_id',
    ];

    protected $casts = [
        'created_at' => 'datetime:d M, Y \a\t h:i a',
    ];

    protected $appends = [
        'date_time',
        'created_at_human',
    ];

    public function getDateTimeAttribute()
    {
        return $this->created_at->format('d M, Y \a\t h:i a');
    }

    public function getCreatedAtHumanAttribute()
    {
        return $this->created_at->diffForHumans();
    }

    public function commentable()
    {
        return $this->morphTo();
    }

    public function user()
    {
        return $this->morphTo('user', 'userable_type', 'userable_id')->withOnly([]);
    }

    public function replies()
    {
        return $this->morphMany(static::class, 'commentable');
    }
}
