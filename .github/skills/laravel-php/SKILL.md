---
name: laravel-php
description: "Laravel 12 + PHP 8.4 development patterns for the Freetter modular monolith. Covers Eloquent models, service providers, migrations, form requests, factories, and module bootstrapping under app-modules/. Activates when creating or editing models, migrations, providers, routes, factories, or any PHP/Laravel code in the project."
---

# Laravel PHP — Freetter Modular Patterns

Laravel 12 (PHP 8.4) development guide for Freetter's `app-modules/` architecture.

> **Important:** This skill covers **Freetter-specific conventions** (module structure, naming, FK patterns, bootstrapping paths). For Laravel framework documentation, version-specific APIs, and package patterns, always use **Laravel Boost** (`search-docs`) first. Never let this skill override Boost guidance on framework behavior.

## Module Structure

Each module lives under `app-modules/{domain}/` and follows this layout:

```
app-modules/{domain}/
├── composer.json
├── database/
│   ├── factories/
│   ├── migrations/
│   └── seeders/
├── resources/
│   └── views/
├── routes/
│   └── web.php
├── src/
│   ├── Http/
│   │   ├── Controllers/
│   │   └── Requests/
│   ├── Models/
│   ├── Providers/
│   │   └── {Domain}ServiceProvider.php
│   └── {Domain specific classes}
└── tests/
    └── Feature/
```

## Service Provider — Correct Bootstrapping

Every module must load its own migrations and routes in its service provider. The path must be resolved from `__DIR__`, which is inside `src/Providers/`.

```php
namespace Modules\Publishing\Providers;

use Illuminate\Support\ServiceProvider;

class PublishingServiceProvider extends ServiceProvider
{
    public function boot(): void
    {
        $this->loadMigrationsFrom(__DIR__ . '/../../database/migrations');
        $this->loadRoutesFrom(__DIR__ . '/../../routes/web.php');
    }
}
```

**Important:** `__DIR__` resolves to `src/Providers/`, so the correct relative path to migrations is `../../database/migrations` (not `../database/migrations`).

## Migrations — Naming Conventions

All module tables are prefixed with the module name:

```
identity_users
identity_workspaces
identity_invitations
publishing_posts
publishing_tags
publishing_post_versions
publishing_post_media  (NOT publishing__post_media)
activity_logs
audience_subscribers
```

Foreign keys must reference the correct prefixed table:

```php
// ✅ CORRECT
$table->foreignUuid('author_id')->constrained('identity_users');
$table->foreignUuid('workspace_id')->constrained('identity_workspaces');

// ❌ WRONG
$table->foreignUuid('user_id')->constrained('users');
$table->foreignUuid('workspace_id')->constrained('workspace');
```

## Models — Eloquent Conventions

```php
namespace Modules\Publishing\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Modules\Publishing\Database\Factories\PostFactory;

class Post extends Model
{
    use HasFactory, HasUuids;

    protected $table = 'publishing_posts';

    protected $fillable = [
        'title',
        'content',
        'author_id',
        'workspace_id',
        'status',
    ];

    protected static function newFactory(): PostFactory
    {
        return PostFactory::new();
    }

    public function author(): \Illuminate\Database\Eloquent\Relations\BelongsTo
    {
        // FK must match the migration column name
        return $this->belongsTo(\Modules\Identity\Models\User::class, 'author_id');
    }
}
```

## Factories — Consistent Patterns

```php
namespace Modules\Publishing\Database\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;
use Modules\Publishing\Models\Post;

class PostFactory extends Factory
{
    protected $model = Post::class;

    public function definition(): array
    {
        return [
            'title' => $this->faker->sentence(),
            'content' => $this->faker->paragraphs(3, true),
            'status' => 'draft',
        ];
    }
}
```

The factory file name must match the class name exactly (e.g., `MembershipFactory.php` for `class MembershipFactory`).

## Form Requests

Always use Form Request classes for validation — never inline in controllers:

```php
namespace Modules\Publishing\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class StorePostRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'title' => ['required', 'string', 'max:255'],
            'content' => ['required', 'string'],
        ];
    }
}
```

## Artisan Commands for Module Development

```bash
# Create a new migration inside a module
php artisan make:migration create_publishing_posts_table --no-interaction

# Run migrations (loads from all registered providers)  
php artisan migrate

# Rollback last batch
php artisan migrate:rollback

# Refresh all migrations (destructive!)
php artisan migrate:fresh

# Check migration status
php artisan migrate:status

# Run Pint formatting after PHP changes
vendor/bin/pint --dirty --format agent

# Run focused tests
php artisan test --compact --filter=testName

# Run all tests
php artisan test --compact
```

## Cross-Module Integration

Do NOT couple modules via direct model imports. Use events and listeners:

```php
// ✅ CORRECT: Fire event from publishing module
use Illuminate\Support\Facades\Event;
use Modules\Publishing\Events\PostPublished;

Event::dispatch(new PostPublished($post));

// ✅ CORRECT: Listen in activity module
use Modules\Publishing\Events\PostPublished;

class LogPostPublished
{
    public function handle(PostPublished $event): void
    {
        // activity domain logic
    }
}
```

## UUID Primary Keys

Modules use UUID primary keys via `HasUuids`:

```php
use Illuminate\Database\Eloquent\Concerns\HasUuids;

class Post extends Model
{
    use HasUuids;
}
```

In migrations, match pivot FK types exactly:

```php
// ✅ If post PK is UUID, pivot FK must also be UUID
$table->foreignUuid('post_id')->constrained('publishing_posts');

// ❌ WRONG: bigint FK for a UUID PK
$table->unsignedBigInteger('post_id');
```

## Debugging Checklist

- [ ] Service provider path resolves correctly from `src/Providers/` → `../../database/migrations`
- [ ] Provider registered in module's `composer.json` under `extra.laravel.providers`
- [ ] Table name matches `{module}_{entity}` convention
- [ ] FK references correct prefixed table
- [ ] FK column type (UUID/bigint) matches referenced column type
- [ ] Factory filename matches class name exactly
- [ ] Model `$table` property set explicitly
- [ ] Model relationship methods use correct FK column name
