---
name: phpunit-testing
description: "PHPUnit testing patterns for Freetter's Laravel 12 modular monolith. Covers feature tests, model tests, factory usage, migration assertions, and test structure conventions in app-modules/. Activates when writing, reviewing, or running tests, or when the user mentions test coverage, assertions, feature tests, or test failures."
---

# PHPUnit Testing — Freetter Patterns

PHPUnit testing guide for Laravel 12 in the Freetter modular monolith.

> **Important:** This skill covers **Freetter-specific test structure and patterns**. For PHPUnit or Laravel testing API details, use **Laravel Boost** (`search-docs`) as the primary reference.

## Test Types

- **Feature tests** — Request-to-domain behavior (HTTP, model interactions, database)
- **Unit tests** — Pure logic, no framework dependencies

Use feature tests by default. Unit tests only for isolated business logic.

## Creating Tests

```bash
# Create a feature test
php artisan make:test --phpunit Feature/Publishing/PostCreationTest

# Create a unit test
php artisan make:test --phpunit --unit Unit/Publishing/PostStatusTest
```

Tests live in `tests/Feature/` or `tests/Unit/`. Module-specific tests go under `app-modules/{domain}/tests/Feature/`.

## Feature Test Structure

```php
namespace Tests\Feature\Publishing;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Modules\Identity\Models\User;
use Modules\Identity\Models\Workspace;
use Modules\Publishing\Models\Post;
use Tests\TestCase;

class PostCreationTest extends TestCase
{
    use RefreshDatabase;

    public function test_authenticated_user_can_create_post(): void
    {
        $user = User::factory()->create();
        $workspace = Workspace::factory()->create();

        $response = $this->actingAs($user)
            ->postJson('/api/publishing/posts', [
                'title' => 'My Post',
                'content' => 'Content here',
                'workspace_id' => $workspace->id,
            ]);

        $response->assertCreated();
        $this->assertDatabaseHas('publishing_posts', [
            'title' => 'My Post',
            'author_id' => $user->id,
        ]);
    }

    public function test_unauthenticated_user_cannot_create_post(): void
    {
        $response = $this->postJson('/api/publishing/posts', [
            'title' => 'My Post',
        ]);

        $response->assertUnauthorized();
    }

    public function test_post_requires_title(): void
    {
        $user = User::factory()->create();

        $response = $this->actingAs($user)
            ->postJson('/api/publishing/posts', [
                'content' => 'Content without title',
            ]);

        $response->assertUnprocessable();
        $response->assertJsonValidationErrors(['title']);
    }
}
```

## Migration & Schema Tests

Test that migrations produce the expected schema:

```php
public function test_publishing_posts_table_has_correct_columns(): void
{
    $this->assertDatabaseTableExists('publishing_posts');

    $post = Post::factory()->create();

    $this->assertDatabaseHas('publishing_posts', [
        'id' => $post->id,
    ]);
}
```

## Factory Usage

Always use factories, not `new Model()`:

```php
// ✅ CORRECT: Use factory
$user = User::factory()->create();
$post = Post::factory()->for($user, 'author')->create();

// ✅ Use factory states when available
$admin = User::factory()->admin()->create();

// ❌ WRONG: Manual model setup
$user = new User(['name' => 'Test User']);
$user->save();
```

## Assertions Reference

```php
// Database assertions
$this->assertDatabaseHas('publishing_posts', ['title' => 'My Post']);
$this->assertDatabaseMissing('publishing_posts', ['id' => $post->id]);
$this->assertDatabaseCount('publishing_posts', 3);
$this->assertSoftDeleted($post);

// HTTP response assertions
$response->assertOk();                      // 200
$response->assertCreated();                 // 201
$response->assertNoContent();               // 204
$response->assertNotFound();                // 404
$response->assertUnauthorized();            // 401
$response->assertForbidden();               // 403
$response->assertUnprocessable();           // 422
$response->assertJsonValidationErrors(['field']);
$response->assertJsonPath('data.title', 'My Post');
$response->assertJsonStructure(['data' => ['id', 'title']]);

// Model assertions
$this->assertInstanceOf(Post::class, $result);
$this->assertTrue($post->isPublished());
```

## Running Tests

```bash
# Run all tests
php artisan test --compact

# Run a specific file
php artisan test --compact tests/Feature/Publishing/PostCreationTest.php

# Run tests matching a name
php artisan test --compact --filter=test_authenticated_user_can_create_post

# Run with coverage (requires Xdebug)
php artisan test --compact --coverage
```

## Test Coverage Guidelines

Every implemented feature should have tests covering:

1. **Happy path** — Normal successful operation
2. **Auth/authorization failure** — Unauthenticated or unauthorized access
3. **Validation failure** — Missing or invalid input
4. **Edge cases** — Boundary conditions specific to the domain

## Module Test Checklist

Before marking a module as production-ready:

- [ ] All use cases from `.context/USE_CASES.md` have a corresponding feature test
- [ ] Provider test exists and is not a `// TODO` placeholder
- [ ] Database assertions validate schema (column names, FK relationships)
- [ ] Factory produces valid models without exceptions
- [ ] Auth boundaries are tested (unauthorized returns 401/403)
- [ ] Validation rules are tested (invalid input returns 422)

## Debugging Failing Tests

```bash
# See full output for a specific test
php artisan test --compact --filter=testName --no-coverage

# Debug with tinker
php artisan tinker --execute "echo App\Models\User::count();"

# Check recent Laravel errors
php artisan pail --filter=error
```
