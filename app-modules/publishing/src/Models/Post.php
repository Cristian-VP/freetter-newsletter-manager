<?php

namespace Domains\Publishing\Models;

use Domains\Identity\Models\User;
use Domains\Identity\Models\Workspace;
use Domains\Publishing\Database\Factories\PostFactory;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Facades\Schema;

class Post extends Model
{
    use HasFactory, HasUuids;

    protected $table = 'publishing_posts';

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
        'published_at',
    ];

    protected $casts = [
        'content' => 'array',
        'published_at' => 'datetime',
    ];

    protected static function newFactory(): PostFactory
    {
        return PostFactory::new();
    }

    public function workspace(): BelongsTo
    {
        return $this->belongsTo(Workspace::class, 'workspace_id');
    }

    public function author(): BelongsTo
    {
        return $this->belongsTo(User::class, 'author_id');
    }

    public function versions(): HasMany
    {
        return $this->hasMany(PostVersion::class, 'post_id');
    }

    public function tags(): BelongsToMany
    {
        return $this->belongsToMany(Tag::class, 'publishing_post_tag', 'post_id', 'tag_id');
    }

    public function media(): BelongsToMany
    {
        return $this->belongsToMany(Media::class, $this->resolvePostMediaPivotTable(), 'post_id', 'media_id');
    }

    public function scopePublished(Builder $query): Builder
    {
        return $query
            ->where('status', 'published')
            ->whereNotNull('published_at')
            ->where('published_at', '<=', now());
    }

    public function scopeForWorkspace(Builder $query, string $workspaceId): Builder
    {
        return $query->where('workspace_id', $workspaceId);
    }

    public function scopeDraft(Builder $query): Builder
    {
        return $query
            ->where('status', 'draft')
            ->whereNull('published_at');
    }

    public function scopeScheduled(Builder $query): Builder
    {
        return $query
            ->where('status', 'scheduled')
            ->where('published_at', '>', now());
    }

    public function scopeOfType(Builder $query, string $type): Builder
    {
        return $query->where('type', $type);
    }

    // Helper methods

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
            && $this->published_at?->isPast();
    }

    public function getStatus(): string
    {
        return match ($this->status) {
            'draft' => 'Borrador',
            'published' => 'Publicado',
            'scheduled' => "Programado para {$this->published_at->format('d/m/Y H:i')}",
            default => $this->status,
        };
    }

    public function getExcerpt(int $maxLength = 160): string
    {
        $excerpt = $this->excerpt ?? $this->extractTextFromContent();

        if (strlen($excerpt) <= $maxLength) {
            return $excerpt;
        }

        return substr($excerpt, 0, $maxLength).'...';
    }

    private function extractTextFromContent(): string
    {
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

    private function resolvePostMediaPivotTable(): string
    {
        if (Schema::hasTable('publishing_post_media')) {
            return 'publishing_post_media';
        }

        if (Schema::hasTable('publishing__post_media')) {
            return 'publishing__post_media';
        }

        return 'publishing_post_media';
    }
}
