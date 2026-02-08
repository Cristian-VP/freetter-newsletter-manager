<?php

namespace Domains\Publishing\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Builder;
use Domains\Publishing\Models\PostVersion;
use Domains\Publishing\Models\Tag;
use Domains\Publishing\Models\Media;

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

    //Ralationships
    public function versions()
    {
        return $this->hasMany(PostVersions::class, 'post_id');
    }

    public function tags()
    {
        return $this->belongsToMany(
            Tag::class,
            'publishing__post_tags',
            'post_id', 'tag_id'
        );
    }

    public function media()
    {
        return $this->belongsToMany(Media::class, 'publishing__post_media', 'post_id', 'media_id');
    }

    /**
     * Scope: Only post was published
     */
    public function ensureWasPublish(Builder $query): Builder
    {
        return $query
        ->where('status', 'published')
        ->whereNotNull('published_at')
        ->where('published_at', '<=', now());
    }

    /**
     * Scope: Only post was published or scheduled
     */
    public function forWorkspace(Builder $query, string $id): Builder
    {
        return $query->where('workspace_id', $id);
    }

    /**
     * Scope: Only post was published
     */
    public function onDraft(Builder $query): Builder
    {
        return $query
        ->where('status', 'draft')
        ->whereNull('published_at');
    }

    /**
     * Scope: Only post was scheduled
     */
    public function onScheduled(Builder $query): Builder
    {
        return $query
        ->where('status', 'scheduled')
        ->where('published_at', '>', now());
    }

    /**
     * Scope: Ensure post type
     */
    public function ofType(string $type, Builder $query): Builder
    {
        return $query->where('type', $type);
    }

    //Helper methods


    public function getAuthorID(): ?string
    {
        return $this->author_id;
    }

    public function getWorkspaceID(): string
    {
        return $this->workspace_id;
    }

    public function shouldBeAutoPublished(): bool
    {
        return $this->status === 'scheduled'
            && $this->published_at?->isPast()
            && !$this->isPublished();
    }

    public function getStatus(): string
    {
        return match($this->status) {
            'draft' => 'Borrador',
            'published' => 'Publicado',
            'scheduled' => "Programado para {$this->published_at->format('d/m/Y H:i')}",
            default => $this->status,
        };
    }

    public function getExcerpt(int $maxLength = 160): string
    {
        $excerpt = $this->excerpt
            ?? $this->extractTextFromContent();

        if (strlen($excerpt) <= $maxLength) {
            return $excerpt;
        }

        return substr($excerpt, 0, $maxLength) . '...';
    }

    private function extractTextFromContent(): string
    {
        // Extraer texto plano desde JSON del editor
        $text = collect($this->content['blocks'] ?? [])
            ->pluck('data.text')
            ->join(' ');

        return strip_tags($text);
    }

    public function url(): string
    {
        $route = $this->type === 'newsletter'
            ? 'publishing.newsletters.show'
            : 'publishing.posts.show';

        return route($route, [
            'workspace' => $this->workspace->slug,
            'post' => $this->slug,
        ]);
    }

    public function isOverdue(): bool
    {
        return $this->status === 'scheduled'
            && $this->published_at?->isPast();
    }
}
