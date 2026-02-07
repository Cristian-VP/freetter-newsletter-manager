La combinación oficial en Laravel 12 para optimizar consultas pasa por:

1. diseñar bien el esquema en las migraciones (tipos, índices, claves foráneas); y  
2. modelar Eloquent de forma que **evites N+1**, limites columnas y uses cargas/paginación eficientes.

A continuación tienes los puntos clave con referencias oficiales de Laravel 12 y ejemplos reales de Spatie.

***

## 1. Estrategias en migraciones (Laravel 12.x)

### 1.1. Índices bien diseñados

Laravel 12 documenta explícitamente cómo crear índices simples y compuestos desde las migraciones mediante `index`, `unique`, `fullText` y `spatialIndex`. [laravel](https://laravel.com/docs/12.x/migrations)

```php
Schema::table('users', function (Blueprint $table) {
    $table->string('email')->unique();                 // índice único
    $table->index(['account_id', 'created_at']);       // índice compuesto
});
```

Pautas prácticas:

- Indexa columnas que uses en:
  - `WHERE`
  - `JOIN`
  - `ORDER BY`
  - combinaciones frecuentes de filtros (índices compuestos).
- Evita sobre‑indexar: cada índice encarece `INSERT/UPDATE/DELETE`. Usa los mínimos que cubran tus patrones de consulta. [needlaravelsite](https://needlaravelsite.com/blog/database-optimization-techniques-for-laravel-12-applications)

Laravel soporta:

- `primary`, `unique`, `index`,
- `fullText('body')` para búsqueda de texto,
- `spatialIndex('location')` para columnas geométricas. [laravel](https://laravel.com/docs/12.x/migrations)

```php
$table->fullText('description');    // texto largo buscable
$table->spatialIndex('location');   // para columnas geometry/geography
```

### 1.2. Claves foráneas y `foreignId`

La guía de migraciones recomienda usar `foreignId()->constrained()` para definir FKs de manera concisa y consistente. [laravel](https://laravel.com/docs/12.x/migrations)

```php
Schema::table('posts', function (Blueprint $table) {
    $table->foreignId('user_id')->constrained()
          ->cascadeOnUpdate()
          ->cascadeOnDelete();
});
```

Buenas prácticas:

- **Siempre indexar las FKs**: el propio `foreignId` + `constrained` ya prepara la columna para ser usada en joins y filtros. [laravel](https://laravel.com/docs/12.x/migrations)
- Usa acciones en cascada (`cascadeOnDelete`, `restrictOnDelete`, `nullOnDelete`) según tu modelo de negocio.

### 1.3. Columnas generadas e invisibles

Laravel 12 expone el modificador `invisible()` como parte de los “column modifiers” de Schema Builder, pensado para columnas que no quieres que salgan en `SELECT *`. [laravel](https://laravel.com/docs/12.x/migrations)

```php
Schema::table('subscribers', function (Blueprint $table) {
    $table->string('email_suffix')->nullable()->invisible();
    $table->index(['email_list_id', 'email_suffix'], 'email_suffix_index');
});
```

Spatie usa precisamente esta estrategia en Mailcoach para acelerar búsquedas por dominio de email: añade una columna invisible `email_suffix` e indexa (`email_list_id`, `email_suffix`). [spatie](https://spatie.be/blog/speeding-up-database-searches-using-an-invisible-column)

- La columna se mantiene vía hook de modelo:

```php
protected static function booted()
{
    static::saved(function (Subscriber $subscriber) {
        $subscriber->update([
            'email_suffix' => Str::after($subscriber->email, '@'),
        ]);
    });
}
```

- Así pueden transformar búsquedas con `LIKE '%example.com%'` (que no usan índices) en búsquedas que sí aprovechan el índice sobre `email_suffix`. [spatie](https://spatie.be/blog/speeding-up-database-searches-using-an-invisible-column)

Esto encaja con el soporte oficial de Laravel para:

- **columnas generadas** (`storedAs`, `virtualAs`) y
- columnas **invisibles** (`->invisible()`). [laravel](https://laravel.com/docs/12.x/migrations)

### 1.4. Tipos de clave primaria y ordenabilidad

En Eloquent 12.x se documenta el uso de `HasUuids` y `HasUlids` para claves primarias que escalen bien y sean eficientes en índices (ordered UUIDs / ULIDs lexicográficamente ordenables). [laravel](https://laravel.com/docs/12.x/eloquent)

```php
use Illuminate\Database\Eloquent\Concerns\HasUlids;

class Order extends Model
{
    use HasUlids;   // requiere columna ULID en la tabla
}
```

- Las claves “ordenables” (ULID u ordered UUID) producen mejores árboles de índices que UUID totalmente aleatorios. [laravel](https://laravel.com/docs/12.x/eloquent)

***

## 2. Estrategias en los modelos (Eloquent 12.x)

### 2.1. Definir relaciones correctas (base de consultas eficientes)

Laravel insiste en que las relaciones de Eloquent son **query builders** en sí mismos: cada método (`hasMany`, `belongsTo`, `belongsToMany`, etc.) devuelve un builder sobre el que puedes añadir condiciones (`where`, `orderBy`, etc.). [laravel](https://laravel.com/docs/12.x/eloquent-relationships)

```php
class Post extends Model
{
    public function comments(): HasMany
    {
        // Puedes incluir ordenado por defecto
        return $this->hasMany(Comment::class)->latest();
    }
}
```

- Respetar las convenciones (`user_id`, `post_id`, etc.) evita tener que sobreconfigurar claves, y mantiene las consultas sencillas y optimizables. [laravel](https://laravel.com/docs/12.x/eloquent-relationships)

### 2.2. Eager Loading para evitar N+1 (incluyendo `chaperone`)

La documentación de relaciones 12.x explica que las propiedades dinámicas (`$user->posts`) hacen **lazy loading**, mientras que `with()` hace eager loading y elimina problemas de N+1 consultas. [laravel](https://laravel.com/docs/12.x/eloquent-relationships)

```php
// Sin optimizar (N+1)
$posts = Post::all();
foreach ($posts as $post) {
    echo $post->author->name;  // 1 + N consultas
}

// Con eager loading
$posts = Post::with('author')->get();  // típicamente 2 consultas
```

Laravel 12 añade además `chaperone()` para el caso inverso: cuando accedes al **padre desde los hijos** en un bucle (comentario → post) y quieres evitar un N+1 adicional. [laravel](https://laravel.com/docs/12.x/eloquent-relationships)

```php
class Post extends Model
{
    public function comments(): HasMany
    {
        return $this->hasMany(Comment::class)->chaperone();
    }
}
```

o a nivel de consulta:

```php
$posts = Post::with([
    'comments' => fn ($q) => $q->chaperone(),
])->get();
```

Esto hidrata el `post` en cada `comment` sin disparar consultas extra dentro del bucle. [laravel](https://laravel.com/docs/12.x/eloquent-relationships)

### 2.3. Configurar Eloquent “estricto”: `preventLazyLoading`

Laravel 12 documenta `Model::preventLazyLoading()` para **forzar a detectar N+1** en desarrollo. [laravel](https://laravel.com/docs/12.x/eloquent)

```php
use Illuminate\Database\Eloquent\Model;

class AppServiceProvider extends ServiceProvider
{
    public function boot(): void
    {
        // Rompe la request si hay lazy loading en entornos no productivos
        Model::preventLazyLoading(! $this->app->isProduction());
    }
}
```

Con esto, cualquier acceso a una relación no pre‑cargada lanza una excepción, obligándote a añadir `with()`/`load()` y a mantener las consultas bajo control. [laravel](https://laravel.com/docs/12.x/eloquent)

En Laravel 12.0.8 se ha introducido además `Model::automaticallyEagerLoadRelationships()`, que permite que el framework eager‑loade automáticamente las relaciones que vas usando, reduciendo drásticamente N+1 sin repetir `with()` por todas partes. [laranepal](https://laranepal.com/blog/automatically-eager-load-relationships)

```php
public function boot(): void
{
    Model::automaticallyEagerLoadRelationships();
    Model::preventLazyLoading(! app()->isProduction());
}
```

- En la práctica, conviene medir: el equipo de Laravel ha registrado incidencias con `BelongsToMany` cuando se combina con `automaticallyEagerLoadRelationships` y `preventLazyLoading`. [github](https://github.com/laravel/framework/issues/55438)

### 2.4. Scopes y helpers para consultas expresivas y reutilizables

Las relaciones en 12.x incluyen helpers como `whereBelongsTo` para filtrar por una instancia de modelo reutilizando claves y nombres de relación. [laravel](https://laravel.com/docs/12.x/eloquent-relationships)

```php
// En vez de: Post::where('user_id', $user->id)->get();
$posts = Post::whereBelongsTo($user)->get();
```

Puedes combinarlos con **global/local scopes** para empaquetar lógicas de filtrado frecuentes (publicado, por tenant, soft‑archivado, etc.) en métodos de modelo, evitando repetir lógica en controladores y manteniendo las consultas consistentes y fáciles de optimizar. [laravel](https://laravel.com/docs/12.x/eloquent)

### 2.5. Procesar grandes volúmenes con `chunk`, `lazy`, `cursor`

El capítulo de Eloquent detalla las tres estrategias principales para recorrer grandes conjuntos de filas sin reventar memoria: [laravel](https://laravel.com/docs/12.x/eloquent)

- `chunk($size)` → procesa por bloques de N filas, cada chunk en un closure.
- `lazy()` / `lazyById()` → stream de modelos con chunks internos (compatible con eager loading).
- `cursor()` → una sola consulta y solo un modelo en memoria a la vez (pero **no puede eager‑load relaciones**). [laravel](https://laravel.com/docs/12.x/eloquent)

```php
// Cuando necesitas relaciones: mejor lazy()
foreach (Order::with('lines')->lazy() as $order) {
    // ...
}

// Cuando solo usas columnas del propio modelo:
foreach (Flight::where('destination', 'Zurich')->cursor() as $flight) {
    // ...
}
```

***

## 3. Aportaciones de Spatie relacionadas con rendimiento

### 3.1. Columnas invisibles e índices compuestos para búsquedas (Mailcoach)

El artículo de Spatie sobre “Speeding up database searches using an invisible column” describe exactamente cómo combinar:

- columna auxiliar `email_suffix` marcada como `invisible()`,  
- índice compuesto `['email_list_id', 'email_suffix']`,  
- y lógica en el modelo para mantenerla actualizada. [spatie](https://spatie.be/blog/speeding-up-database-searches-using-an-invisible-column)

La idea general que puedes reutilizar:

- Para cualquier filtro caro (ej.: sufijos, valores transformados, normalizaciones), añade una **columna derivada + índice** en migración.
- Mantén la columna con un **observer o `saved` hook** de Eloquent.
- Haz que las búsquedas usen la columna indexada (o subconsultas que la aprovechen) en lugar de `LIKE` con comodín inicial, funciones, etc.

### 3.2. Cacheo de estructuras/reflexión en Spatie Laravel Data

Spatie documenta que Laravel Data usa reflexión pesadamente y recomienda **cachear la estructura** en producción con `php artisan data:cache-structures`. [spatie](https://spatie.be/docs/laravel-data/v4/advanced-usage/performance)

- No es directamente “consulta SQL”, pero en APIs que usan Data masivamente, este caché reduce CPU y tiempo de respuesta global (menos tiempo hasta la ejecución de la query real). [spatie](https://spatie.be/docs/laravel-data/v4/advanced-usage/performance)

### 3.3. Spatie Laravel Permission: rendimiento y cache

En los “Performance Tips” de `spatie/laravel-permission` recomiendan, entre otras cosas: [spatie](https://spatie.be/docs/laravel-permission/v6/best-practices/performance)

- preferir algunas operaciones (`$permission->assignRole($role)`) sobre otras (`$role->givePermissionTo()`) en bases de datos grandes,  
- y recordar que si manipulas las tablas a bajo nivel debes **borrar la cache** del registrador de permisos.

La lección general:

- paquetes de permisos/roles suelen cachear agresivamente; cuando cambias roles/permisos, limpia sus caches para que no disparen consultas de más.

### 3.4. Spatie Query Builder: filtrar/ordenar sin consultas caóticas

Guías sobre Spatie Query Builder muestran cómo: [redberry](https://redberry.international/spatie-query-builder-in-laravel/)

- limitar columnas (`allowedFields`),
- definir filtros explícitos y scopes (`allowedFilters`, `AllowedFilter::scope(...)`),
- controlar ordenaciones permitidas (`allowedSorts`),
- y seleccionar campos de relaciones concretas.

```php
use Spatie\QueryBuilder\QueryBuilder;
use Spatie\QueryBuilder\AllowedFilter;

$apartments = QueryBuilder::for(Apartment::class)
    ->allowedFields(['id', 'name', 'price'])              // evita SELECT *
    ->allowedFilters([
        'name',
        AllowedFilter::scope('price_from'),
    ])
    ->allowedSorts(['area', 'price'])
    ->get();
```

Esto fomenta:

- **consultas predecibles** (no construyes dinámicamente `where` arbitrarios desde el request),
- solo las columnas necesarias (clave para ancho de banda y memoria),
- y filtros que puedes indexar de antemano en tus migraciones.

***

## 4. Cómo unir todo: patrones concretos de migraciones + modelos

### 4.1. Listados filtrables por múltiples campos

Supón un listado de pedidos donde lo habitual es filtrar por `customer_id`, `status` y rango de fecha:

**Migración (Laravel 12):**

```php
Schema::create('orders', function (Blueprint $table) {
    $table->ulid('id')->primary();          // buena clave para índices
    $table->foreignId('customer_id')->constrained();
    $table->enum('status', ['pending','paid','cancelled'])->index();
    $table->timestamp('placed_at')->index();

    // Índices compuestos según patrones de consulta reales
    $table->index(['customer_id', 'status', 'placed_at']);
    $table->timestamps();
});
```

**Modelo:**

```php
class Order extends Model
{
    use HasUlids;

    public function customer(): BelongsTo
    {
        return $this->belongsTo(Customer::class);
    }

    // Scope reutilizable para listados
    public function scopeForListing(Builder $query, array $filters): Builder
    {
        return $query
            ->when($filters['customer_id'] ?? null, fn ($q, $id) => $q->where('customer_id', $id))
            ->when($filters['status']      ?? null, fn ($q, $s)  => $q->where('status', $s))
            ->when($filters['from']        ?? null, fn ($q, $d)  => $q->where('placed_at', '>=', $d))
            ->when($filters['to']          ?? null, fn ($q, $d)  => $q->where('placed_at', '<=', $d))
            ->with('customer'); // eager loading por defecto para el listado
    }
}
```

Aquí:

- la migración define índices alineados con los filtros habituales; [needlaravelsite](https://needlaravelsite.com/blog/database-optimization-techniques-for-laravel-12-applications)
- el modelo concentra la lógica de filtrado + eager loading, reduciendo N+1.

### 4.2. Relaciones profundas y muchas filas

Para relaciones profundas (por ejemplo `Application -> environments -> deployments`) Laravel sugiere usar `hasManyThrough` para evitar joins manuales o bucles con subconsultas. [laravel](https://laravel.com/docs/12.x/eloquent-relationships)

```php
class Application extends Model
{
    public function deployments(): HasManyThrough
    {
        return $this->hasManyThrough(Deployment::class, Environment::class);
    }
}
```

En listados grandes:

- usa `Application::with('deployments')->lazy()` o `chunkById`,
- perfila las consultas lentas y crea índices sobre las FKs implicadas (`environment_id`, `application_id`, etc.). [needlaravelsite](https://needlaravelsite.com/blog/database-optimization-techniques-for-laravel-12-applications)

***

## 5. Checklist rápido cuando añadas tablas/modelos nuevos

1. **Análisis previo**  
   - ¿Qué filtros y ordenaciones se van a usar realmente? (no indexar todo).  
   - ¿Va a haber búsquedas tipo “contiene” sobre texto? Considera `fullText` o una columna derivada + índice. [spatie](https://spatie.be/blog/speeding-up-database-searches-using-an-invisible-column)

2. **Migración**  
   - Define PK adecuada (bigint, ULID/UUID) según volumen y necesidades.  
   - Usa `foreignId()->constrained()` para todas las relaciones.  
   - Añade índices compuestos que reflejen tus `WHERE`/`ORDER BY` típicos.  
   - Considera columnas generadas/invisibles para datos derivados costosos. [spatie](https://spatie.be/blog/speeding-up-database-searches-using-an-invisible-column)

3. **Modelo (Eloquent)**  
   - Define todas las relaciones con tipos correctos (`HasMany`, `BelongsToMany`, etc.). [laravel](https://laravel.com/docs/12.x/eloquent-relationships)
   - Decide qué relaciones se deben cargar casi siempre (`$with`) y cuáles se cargan con `with()` en endpoints concretos.  
   - Activa `Model::preventLazyLoading(!app()->isProduction())` en desarrollo. [laravel](https://laravel.com/docs/12.x/eloquent)
   - Para listados grandes, usa `lazy()`/`chunk()` en vez de `all()`/`get()` a pelo. [laravel](https://laravel.com/docs/12.x/eloquent)

4. **Usar patrones de Spatie cuando encajen**  
   - Columnas invisibles + índices para búsquedas complejas (patrón Mailcoach). [spatie](https://spatie.be/blog/speeding-up-database-searches-using-an-invisible-column)
   - Spatie Query Builder para APIs filtrables con `allowedFilters/allowedFields` evitando SELECT * y filtros arbitrarios. [redberry](https://redberry.international/spatie-query-builder-in-laravel/)
   - Cachear reflection y estructuras de datos en paquetes intensivos (Laravel Data). [spatie](https://spatie.be/docs/laravel-data/v4/advanced-usage/performance)

Si quieres, en un siguiente mensaje se puede bajar esto a un ejemplo concreto de tu dominio (por ejemplo, un modelo de cursos, posts, pedidos, etc.) y derivar de ahí las migraciones + modelos optimizados con código completo.
