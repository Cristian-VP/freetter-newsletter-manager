# 📚 ANÁLISIS TÉCNICO DETALLADO: DOMINIO PUBLISHING
## CMS Modular, Versionado, Gestión de Media y Taxonomía

**Fecha de Creación:** 7 de febrero de 2026  
**Estado:** ✅ Listo para implementación (MVP)  
**Fuente de Verdad:**
- entidades-corregidas.md (estructura final esperada)
- domains-proposal.md (lógica de negocio / semántica)
- claude_audit.md (riesgos y correcciones)
- GLOBAL_STRATEGY.md (metodología de validación)
- rule_build_correct_migrations_models.md (Laravel 12.x best practices)

---

## 🧩 PARTE 1 — ANÁLISIS

### 1) Propósito del Dominio
El dominio **Publishing** es el **CMS (Content Management System)** del monolito modular. Su objetivo es:
- Persistir contenido generado con **Editor.js** como **JSON estructurado** (no HTML “sucio”).
- Modelar el contenido como un **Post** unificado (newsletter / note) con estado (draft / scheduled / published).
- Gestionar **assets** (media) asociados al workspace y su relación explícita con posts.
- Aportar **auditoría de lo publicado** mediante versionado (evita perder “lo que realmente se envió”).
- Registrar (y permitir calcular) un **carbon_score** por pieza de contenido.

> Relación con el producto: Publishing habilita escribir, editar y publicar; Delivery enviará campañas basadas en posts; Community interactuará sobre posts; Activity registrará eventos (post.published, post.updated…).

### 2) Alcance MVP vs V1.1
Según el audit y el diccionario de entidades, el MVP requiere **6 tablas** (las 6 ya están definidas en entidades-corregidas.md):

**MVP (CRÍTICO):**
- `publishing_posts`
- `publishing_post_versions` ✅ (tabla nueva crítica por auditoría)
- `publishing_media`
- `publishing_post_media` ✅ (tabla nueva crítica para relación explícita)

**MVP (IMPORTANTE / pero puede simplificarse):**
- `publishing_tags`
- `publishing_post_tag`

**V1.1 (opcional / evolutivo):**
- Índices avanzados (GIN en JSONB) si se consulta contenido.
- Búsqueda full-text (si se implementa discovery).
- Garbage collection de media huérfano automatizado.

### 3) Dependencias e Integraciones

**Publishing depende de:**
- Identity:
  - `identity_workspaces` (tenant / publicación)
  - `identity_users` (author)

**Módulos que dependen de Publishing:**
- Delivery: campañas referencian un `post_id`.
- Community: comentarios y likes referencian un `post_id`.
- Activity: registra eventos sobre posts/media/tags.

### 4) Estado Actual del Módulo en el Repo
El módulo [app-modules/publishing](app-modules/publishing) está **en scaffolding**:
- No hay migraciones, modelos, ni factories todavía.
- Existe `PublishingServiceProvider` vacío.

Implicación: el documento define la implementación “desde cero”, con validación estricta contra entidades-corregidas.md.

### 5) Entidades (Fuente: entidades-corregidas.md)

#### 5.1 `publishing_posts` (🟢 MVP)
**Propósito:** unidad atómica de contenido (newsletter o note).

Campos (PostgreSQL):
- `id`: uuid (PK)
- `workspace_id`: uuid (FK → identity_workspaces)
- `author_id`: uuid (FK → identity_users)
- `title`: varchar(255)
- `slug`: varchar(255) (Index)
- `type`: varchar ('newsletter', 'note')
- `status`: varchar ('draft', 'scheduled', 'published')
- `content`: jsonb (Editor.js)
- `excerpt`: text
- `published_at`: timestamp (nullable)
- `carbon_score`: decimal(8,2)
- `created_at`: timestamp

**Reglas de negocio clave (domains-proposal.md):**
- Polimorfismo simple: `type` define el render/email.
- `content` guarda JSON puro de Editor.js.
- Tiene autoría (`author_id`) y pertenencia (`workspace_id`).

**Decisión de integridad recomendada (MVP):**
- `slug` debería ser **único por workspace**: `unique(workspace_id, slug)`.
  - Razón: los tags son locales al workspace; el contenido también.
  - Evita colisiones cuando múltiples workspaces usan el mismo slug.

#### 5.2 `publishing_post_versions` (🟢 MVP, nueva crítica)
**Propósito:** preservar el contenido exacto que fue publicado/enviado.

Campos:
- `id`: uuid (PK)
- `post_id`: uuid (FK → publishing_posts, cascade)
- `content`: jsonb
- `version_number`: integer
- `created_at`: timestamp
- Unique: `(post_id, version_number)`

**Regla de negocio (claude_audit.md):**
- Si editas un post luego de enviarlo, no puedes perder el contenido original. Esta tabla garantiza auditoría.

#### 5.3 `publishing_post_media` (🟢 MVP, nueva crítica)
**Propósito:** relación explícita post ↔ media, para detectar huérfanos y controlar “garbage”.

Campos:
- `post_id`: uuid (FK → publishing_posts, cascade)
- `media_id`: uuid (FK → publishing_media, cascade)
- PK compuesta: `(post_id, media_id)`

> Nota Eloquent: Eloquent no soporta “composite primary keys” como clave del modelo, pero **sí** soporta pivot tables. Esta tabla se implementa como pivot en relaciones `belongsToMany`.

#### 5.4 `publishing_media` (🟢 MVP)
**Propósito:** referencia a archivos (local / s3), con scope por workspace.

Campos:
- `id`: uuid (PK)
- `workspace_id`: uuid (FK)
- `path`: varchar
- `disk`: varchar ('local', 's3')
- `mime_type`: varchar
- `size_kb`: integer
- `created_at`: timestamp

**Regla de negocio (domains-proposal.md):**
- Si se borra un workspace, debe eliminarse su media (cascada o proceso de limpieza).

#### 5.5 `publishing_tags` (🟡 MVP)
**Propósito:** taxonomía simple local al workspace.

Campos:
- `id`: uuid (PK)
- `workspace_id`: uuid (FK)
- `name`: varchar
- `slug`: varchar

**Regla clave:** tags son **locales al workspace** → se recomienda `unique(workspace_id, slug)`.

#### 5.6 `publishing_post_tag` (🟡 MVP)
**Propósito:** pivot post ↔ tag.

Campos:
- `post_id`: uuid (FK → publishing_posts)
- `tag_id`: uuid (FK → publishing_tags)
- PK compuesta: `(post_id, tag_id)`

---

## 🧠 PARTE 2 — REFLEXIÓN (validación, riesgos, decisiones)

### 1) Validación “Plan vs Realidad”
Metodología aplicada (GLOBAL_STRATEGY.md):
1. Fuente de verdad: **entidades-corregidas.md + audit**.
2. En Publishing no hay migraciones reales → el diseño final nace del audit.
3. Evitar “inventar tablas extra” para MVP.

### 2) Riesgos identificados (claude_audit.md) y mitigaciones

- **Riesgo: perder el contenido enviado** al editar un post luego de publicarlo.
  - ✅ Mitigación: `publishing_post_versions` con snapshot y `version_number`.

- **Riesgo: media huérfano** (basura digital, coste, límite 2GB).
  - ✅ Mitigación: `publishing_post_media` + queries de “orphan detection”.

- **Riesgo: XSS por contenido rico**.
  - ✅ Mitigación: no persistir HTML; sanitizar al renderizar / al generar HTML (DOMPurify u otra estrategia definida por el proyecto).

- **Riesgo: queries N+1** en listados (posts con author/tags/media).
  - ✅ Mitigación: `with()` / `load()`; activar `Model::preventLazyLoading(!app()->isProduction())` en desarrollo.

### 3) Decisiones técnicas importantes (Laravel 12.49 verificado)

- **UUIDs ordenables (UUID v7)**: `HasUuids` en Laravel 12.49 genera UUID v7 por defecto (mejor para índices que UUID aleatorio).
- **JSONB**: usar `jsonb()` en migraciones para `content` y snapshots.
- **Pivots con PK compuesta**: válido a nivel DB, modelado en Eloquent como `belongsToMany` (sin modelo con PK compuesta).
- **Enums DB vs varchar**:
  - Entidades proponen `varchar`; Laravel permite `enum()`.
  - Recomendación: usar `enum()` en DB para `type`, `status`, `disk` si se quiere máxima integridad. Alternativa: `string` + validación y PHP `enum`.

---

## 🗂️ PARTE 3 — ORGANIZACIÓN (estructura del módulo y checklist)

### 1) Estructura esperada del módulo
Siguiendo el patrón de Activity:

- `app-modules/publishing/database/migrations/`
  - create_publishing_posts_table
  - create_publishing_post_versions_table
  - create_publishing_media_table
  - create_publishing_post_media_table
  - create_publishing_tags_table
  - create_publishing_post_tag_table

- `app-modules/publishing/src/Models/`
  - `Post`
  - `PostVersion`
  - `Media`
  - `Tag`
  - (No modelo para pivots en MVP, salvo que se requiera lógica)

- `app-modules/publishing/database/factories/`
  - `PostFactory`
  - `PostVersionFactory`
  - `MediaFactory`
  - `TagFactory`

- `app-modules/publishing/tests/Feature/`
  - tests de creación, relaciones, versionado, eager loading.

### 2) Checklist (implementación futura)
- Migraciones correctas y con índices alineados a patrones de consulta.
- Modelos con relaciones tipadas, casts JSONB y scopes reutilizables.
- Factories con estados (draft/scheduled/published; newsletter/note; media local/s3).
- Tests: evitar N+1, versionado inmutable, pivots.

---

## 🛠️ PARTE 4 — DESARROLLO DEL DOCUMENTO (diseño técnico completo)

## 1. Diseño de Migraciones (Laravel 12.49)

### 1.1 Estrategia General
- PKs UUID: `uuid('id')->primary()`.
- FKs UUID: `foreignUuid('...')->constrained('...')->cascadeOnDelete()`.
- JSON: `jsonb()`.
- Índices compuestos según filtros reales.

> Nota sobre timestamps: en este proyecto ya existen tablas (Identity/Activity) que usan `timestamps()` aunque el esquema “corregido” solo liste `created_at`. En Publishing se recomienda:
> - **Posts**: permitir `updated_at` (son editables) → `timestamps()`.
> - **Versiones**: inmutables → solo `created_at`.
> - **Media/Tags**: normalmente editables (name, slug) o gestionables → `timestamps()` es razonable; si se busca minimizar, usar `created_at` + campos explícitos.
>
> Este documento opta por lo pragmático: **mutable = timestamps(); inmutable = created_at único**.

---

### 1.2 Migración: `publishing_posts`

Patrones de consulta esperados:
1. Listado por workspace: `WHERE workspace_id ORDER BY created_at DESC`.
2. Listado publicados: `WHERE workspace_id AND status='published' ORDER BY published_at DESC`.
3. Resolver post por slug: `WHERE workspace_id AND slug`.
4. Filtrar por autor: `WHERE workspace_id AND author_id`.

Migración (borrador de referencia):

```php
Schema::create('publishing_posts', function (Blueprint $table) {
    $table->uuid('id')->primary();

    $table->foreignUuid('workspace_id')
        ->constrained('identity_workspaces', 'id')
        ->cascadeOnDelete();

    $table->foreignUuid('author_id')
        ->constrained('identity_users', 'id')
        ->restrictOnDelete();

    $table->string('title', 255);
    $table->string('slug', 255);

    $table->enum('type', ['newsletter', 'note']);
    $table->enum('status', ['draft', 'scheduled', 'published'])->index();

    $table->jsonb('content');
    $table->text('excerpt')->nullable();

    $table->timestamp('published_at')->nullable()->index();
    $table->decimal('carbon_score', 8, 2)->default(0);

    $table->timestamps();

    // Índices
    $table->index(['workspace_id', 'created_at'], 'idx_publishing_posts_workspace_created');
    $table->index(['workspace_id', 'status', 'published_at'], 'idx_publishing_posts_published_feed');
    $table->unique(['workspace_id', 'slug'], 'uq_publishing_posts_workspace_slug');
    $table->index(['workspace_id', 'author_id', 'created_at'], 'idx_publishing_posts_workspace_author');
});
```

**Notas de integridad**
- `restrictOnDelete()` en `author_id`: evita borrar usuario si hay posts (consistencia histórica). Alternativa: `nullOnDelete()` si el negocio permite “autor eliminado”.
- `carbon_score` default 0: permite crear drafts sin cálculo inicial.

---

### 1.3 Migración: `publishing_post_versions` (inmutable)

Patrones de consulta:
1. Traer versiones de un post: `WHERE post_id ORDER BY version_number DESC`.
2. Traer versión específica: `WHERE post_id AND version_number`.

```php
Schema::create('publishing_post_versions', function (Blueprint $table) {
    $table->uuid('id')->primary();

    $table->foreignUuid('post_id')
        ->constrained('publishing_posts', 'id')
        ->cascadeOnDelete();

    $table->jsonb('content');
    $table->unsignedInteger('version_number');

    $table->timestamp('created_at')->useCurrent();

    $table->unique(['post_id', 'version_number'], 'uq_publishing_versions_post_version');
    $table->index(['post_id', 'created_at'], 'idx_publishing_versions_post_created');
});
```

---

### 1.4 Migración: `publishing_media`

Patrones de consulta:
1. Listar media por workspace: `WHERE workspace_id ORDER BY created_at DESC`.
2. Detectar huérfanos: `LEFT JOIN publishing_post_media`.

```php
Schema::create('publishing_media', function (Blueprint $table) {
    $table->uuid('id')->primary();

    $table->foreignUuid('workspace_id')
        ->constrained('identity_workspaces', 'id')
        ->cascadeOnDelete();

    $table->string('path');
    $table->enum('disk', ['local', 's3']);
    $table->string('mime_type');
    $table->unsignedInteger('size_kb');

    $table->timestamps();

    $table->index(['workspace_id', 'created_at'], 'idx_publishing_media_workspace_created');
    $table->index(['workspace_id', 'disk'], 'idx_publishing_media_workspace_disk');
});
```

---

### 1.5 Migración: `publishing_post_media` (pivot)

```php
Schema::create('publishing_post_media', function (Blueprint $table) {
    $table->foreignUuid('post_id')
        ->constrained('publishing_posts', 'id')
        ->cascadeOnDelete();

    $table->foreignUuid('media_id')
        ->constrained('publishing_media', 'id')
        ->cascadeOnDelete();

    $table->primary(['post_id', 'media_id'], 'pk_publishing_post_media');

    // Índices de acceso (opcionales, pero útiles)
    $table->index('media_id', 'idx_publishing_post_media_media');
});
```

---

### 1.6 Migración: `publishing_tags`

```php
Schema::create('publishing_tags', function (Blueprint $table) {
    $table->uuid('id')->primary();

    $table->foreignUuid('workspace_id')
        ->constrained('identity_workspaces', 'id')
        ->cascadeOnDelete();

    $table->string('name');
    $table->string('slug');

    $table->timestamps();

    $table->unique(['workspace_id', 'slug'], 'uq_publishing_tags_workspace_slug');
    $table->index(['workspace_id', 'name'], 'idx_publishing_tags_workspace_name');
});
```

---

### 1.7 Migración: `publishing_post_tag` (pivot)

```php
Schema::create('publishing_post_tag', function (Blueprint $table) {
    $table->foreignUuid('post_id')
        ->constrained('publishing_posts', 'id')
        ->cascadeOnDelete();

    $table->foreignUuid('tag_id')
        ->constrained('publishing_tags', 'id')
        ->cascadeOnDelete();

    $table->primary(['post_id', 'tag_id'], 'pk_publishing_post_tag');

    $table->index('tag_id', 'idx_publishing_post_tag_tag');
});
```

---

## 2. Diseño de Modelos Eloquent (Laravel 12.x)

### 2.1 Principios
- Tipado de relaciones (`BelongsTo`, `HasMany`, `BelongsToMany`).
- Casts para JSONB (`content` → array, `published_at` → datetime).
- Scopes para queries repetidas (por workspace, published, drafts…).
- Evitar N+1 con `with()` y strictness.

### 2.2 Modelo `Post`
Responsabilidades del modelo:
- Relaciones: workspace, author, versions, media, tags.
- Scopes: `forWorkspace`, `published`, `drafts`, `scheduled`, `ofType`.
- Helper: `publish()` / `schedule()` (si el proyecto define acciones)
- Hook/acción: crear version cuando se publique.

Relaciones propuestas:
- `belongsTo(Workspace::class, 'workspace_id')`
- `belongsTo(User::class, 'author_id')`
- `hasMany(PostVersion::class, 'post_id')->orderByDesc('version_number')`
- `belongsToMany(Media::class, 'publishing_post_media', 'post_id', 'media_id')`
- `belongsToMany(Tag::class, 'publishing_post_tag', 'post_id', 'tag_id')`

**Detalle actual (Laravel 12):**
- `chaperone()` está disponible para inversas (útil al iterar versiones y acceder al post sin disparar N+1), por ejemplo:
  - `$post->versions()->chaperone()`.

### 2.3 Modelo `PostVersion` (inmutable)
- `public const UPDATED_AT = null;`
- Cast `content` array.
- Relación `post()`.

### 2.4 Modelo `Media`
- Pertenece a workspace.
- Relación `posts()` belongsToMany.
- Scopes: `forWorkspace`, `onDisk`, `orphans()` (query join/pivot) como helper.

### 2.5 Modelo `Tag`
- Pertenece a workspace.
- Relación `posts()` belongsToMany.
- Scope `forWorkspace`.

---

## 3. Optimizaciones de Performance

### 3.1 N+1 en listados de posts
Caso típico: dashboard lista posts con author y tags.

- ✅ Correcto:
  - `Post::with(['author', 'tags'])->forWorkspace($id)->latest()->paginate()`

- ✅ Estricto en desarrollo:
  - `Model::preventLazyLoading(! app()->isProduction());`

### 3.2 Column selection
En listados, evitar `content` completo si no se necesita (puede ser pesado):
- Usar `select(['id', 'workspace_id', 'author_id', 'title', 'slug', 'type', 'status', 'published_at', 'carbon_score', 'created_at'])`.

### 3.3 Índices y cardinalidad (resumen)
- `publishing_posts`:
  - `(workspace_id, created_at)` alto valor.
  - `(workspace_id, status, published_at)` alto valor para feed.
  - `unique(workspace_id, slug)` para resolución.
- Pivots:
  - índices por `tag_id` y `media_id` ayudan en reversas.

### 3.4 JSONB indexing (posponer)
Solo añadir índices JSONB (GIN / path ops) cuando exista un patrón real de consulta por dentro de `content`.

---

## 4. Factories y Testing (plan)

### 4.1 Factories
- `PostFactory` con estados:
  - `newsletter()` / `note()`
  - `draft()` / `scheduled()` / `published()`
  - `forWorkspace($workspace)` / `forAuthor($user)`
- `PostVersionFactory`:
  - `forPost($post)`
  - `version($n)`
- `MediaFactory`:
  - `local()` / `s3()`
  - `forWorkspace($workspace)`
- `TagFactory`:
  - `forWorkspace($workspace)`

### 4.2 Tests mínimos (alineados al estilo Activity)
- Crear post (draft).
- Publicar post crea versión (version_number = 1) con snapshot.
- Slug único por workspace.
- Pivot tags/media funciona.
- Eager loading evita N+1 (author + tags + media).

---

## 5. Patrones de Implementación recomendados

### 5.1 Versionado en transición a published
En lugar de versionar “en cada update”, versionar en eventos de negocio:
- primer publish → crea `version_number=1` con snapshot.
- republish (si existe) → incrementa `version_number`.

Se puede implementar vía:
- Action (recomendado para claridad): `PublishPostAction`.
- Observer (si se prefiere automático): `PostObserver@updated` detectando transición `draft/scheduled → published`.

### 5.2 Registro en Activity
Cuando se cree/publíque/edite:
- `ActivityLog::record(action: 'post.published', entityType: 'post', entityId: $post->id, ...)`

> Importante: Publishing no debe acoplarse fuerte a Activity si se quiere modularidad; idealmente el logging se hace desde Actions de aplicación o listeners.

---

## ✅ Resumen de Decisiones Técnicas
- **Posts mutables**: `timestamps()`.
- **PostVersion inmutable**: solo `created_at` y `UPDATED_AT = null`.
- **FKs UUID** con `foreignUuid()->constrained()`.
- **JSONB** para `content` en posts y versiones.
- **Pivots** con PK compuesta y modelado via `belongsToMany`.
- **Slug y tags**: unicidad por workspace.
- **Strictness**: `preventLazyLoading` en desarrollo.

---

## 📚 Referencias
- Laravel 12 (Eloquent, Strictness, UUIDs): ver [laravel-docs/eloquent.md](.context/laravel-docs/eloquent.md)
- Migraciones/Schema Builder: rule_build_correct_migrations_models.md
- Auditoría y correcciones: claude_audit.md
- Especificación de entidades: entidades-corregidas.md

---

**Documento Generado:** 7 de febrero de 2026  
**Estado:** ✅ Validado contra el audit y listo para implementación  
**Versión:** 1.0 (MVP Focus)
