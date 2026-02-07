<?php

namespace Domains\Publishing\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class Post extends Model
{
    use HasUuids, HasFactory;

    protected $table = 'publishing_post';

    public const UPDATED_AT = null;

    protected $fillable = [
        'workspace_id',
        'author_id',
        'title',
        'slug',
        'type',
        'status',
        'content',
        'excerpt',
        'carbon_score',
    ];

    protected $casts = [
        'content' => 'array',
        'published_at' => 'datetime',
    ];

    //Relaciones: workspace, author, versions, media, tags.
    //Ralationships

}
