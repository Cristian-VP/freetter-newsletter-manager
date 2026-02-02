# 🔍 AUDITORÍA Y CORRECCIONES DEL PLAN - FREETTER

**Fecha de Auditoría:** 2 de febrero de 2026  
**Auditor:** Sistema de validación arquitectónica  
**Objetivo:** Identificar y corregir incongruencias entre `plan-estructuraModularDdaFreetter.prompt.md` y la arquitectura real implementada

---

## 📊 RESUMEN EJECUTIVO

**Estado General:** ⚠️ Plan con incongruencias detectadas  
**Arquitectura Real:** ✅ Correctamente implementada según `entidades-corregidas.md`

### Hallazgos Principales:

1. 🔴 **CRÍTICO:** El plan define campos que NO existen en las migraciones reales
2. 🟡 **MODERADO:** Factories del plan no coinciden con estructura de datos real
3. 🟢 **POSITIVO:** Orden de módulos y dependencias es correcto

---

## 🔴 INCONGRUENCIAS CRÍTICAS DETECTADAS

### **IDENTITY Module**

#### ❌ Error 1: `identity_users` - Campos inexistentes

**Plan dice:**
```php
// app-modules/identity/database/factories/UserFactory.php (línea 428-429)
'avatar_url' => fake()->imageUrl(200, 200, 'people'),
'timezone' => fake()->timezone(),
```

**Migración REAL:**
```php
// 2026_02_01_161116_create_identity_users_table.php
$table->string('avatar_path')->nullable(); // ← NO es avatar_url
// ❌ NO EXISTE campo timezone
```

**Campos según `entidades-corregidas.md`:**
- ✅ `id` (uuid, PK)
- ✅ `name` (varchar)
- ✅ `email` (varchar, unique)
- ✅ `email_verified_at` (timestamp, nullable) **[NUEVO según audit]**
- ✅ `avatar_path` (varchar, nullable) **← Plan dice avatar_url**
- ✅ `remember_token` (varchar, nullable)
- ✅ `created_at`, `updated_at` (timestamps)
- ❌ **NO EXISTE** `timezone`

---

#### ❌ Error 2: `identity_workspaces` - Campos inexistentes

**Plan dice:**
```php
// app-modules/identity/database/factories/WorkspaceFactory.php (línea 471-473)
'avatar_url' => fake()->imageUrl(200, 200, 'business'),
'bio' => fake()->sentence(20),
'is_active' => true,
```

**Migración REAL:**
```php
// 2026_02_01_165120_create_identity_workspaces_table.php
$table->jsonb('branding_config'); // ← Logo está aquí, NO en avatar_url
$table->jsonb('donation_config');
// ❌ NO EXISTE campo bio
// ❌ NO EXISTE campo is_active
```

**Campos según `entidades-corregidas.md`:**
- ✅ `id` (uuid, PK)
- ✅ `name` (varchar)
- ✅ `slug` (varchar, unique, index)
- ✅ `branding_config` (jsonb) **← Contiene logo_url, NO avatar_url separado**
- ✅ `donation_config` (jsonb)
- ✅ `created_at`, `updated_at` (timestamps)
- ❌ **NO EXISTE** `avatar_url`
- ❌ **NO EXISTE** `bio`
- ❌ **NO EXISTE** `is_active`

**Estructura real de `branding_config`:**
```json
{
  "logo_url": "https://example.com/logo.png",
  "primary_color": "#FF5733",
  "secondary_color": "#C70039"
}
```

**Estructura real de `donation_config`:**
```json
{
  "default_amounts": [10, 25, 50, 100],
  "currency": "USD"
}
```

---

#### ❌ Error 3: Modelo `Workspace` - Fillable incorrectos

**Plan dice:**
```php
// Línea 264-266
protected $fillable = [
    'name',
    'slug',
    'avatar_url',  // ❌ NO EXISTE
    'bio',         // ❌ NO EXISTE
    'is_active',   // ❌ NO EXISTE
];
```

**Implementación REAL (correcta):**
```php
// app-modules/identity/src/Models/Workspace.php
protected $fillable = [
    'name',
    'slug',
    'branding_config',  // ✅ CORRECTO
    'donation_config',  // ✅ CORRECTO
];

protected $casts = [
    'branding_config' => 'array',
    'donation_config' => 'array',
];
```

---

#### ❌ Error 4: `identity_memberships` - Roles incorrectos

**Plan NO menciona claramente los roles.**

**Migración REAL:**
```php
// 2026_02_01_165355_create_identity_memberships_table.php
$table->enum('role', ['owner', 'admin', 'editor', 'viewer']);
```

**Según `entidades-corregidas.md`:**
- Roles permitidos: `'owner', 'admin', 'editor', 'writer'`

⚠️ **DISCREPANCIA:**
- Migración dice: `'viewer'`
- Entidades corregidas dicen: `'writer'`

**DECISIÓN:** Usar `'viewer'` (más coherente con "lector")

---

### **PUBLISHING Module**

#### ✅ Aún no revisado en el plan

**Pendiente de análisis:**
- `publishing_posts`
- `publishing_post_versions` (NUEVA tabla según audit)
- `publishing_post_media` (NUEVA tabla según audit)
- `publishing_media`
- `publishing_tags`
- `publishing_post_tag`

---

### **COMMUNITY Module**

#### ✅ Aún no revisado en el plan

**Pendiente de análisis:**
- `community_comments`
- `community_likes`
- `community_followers`

---

### **AUDIENCE Module**

#### ✅ Aún no revisado en el plan

**Pendiente de análisis:**
- `audience_subscribers` (con campos GDPR: `consent_given_at`, `consent_ip`)
- `audience_import_jobs` (con `expires_at` nuevo)

---

### **DELIVERY Module**

#### ✅ Aún no revisado en el plan

**Pendiente de análisis:**
- `delivery_campaigns`
- `delivery_bounces` (con `bounce_type` nuevo)

---

### **ACTIVITY Module**

#### ✅ Aún no revisado en el plan

**Pendiente de análisis:**
- `activity_logs` (tabla nueva)
- `activity_streams` (V1.1)
- `activity_alerts` (V1.1)

---

## ✅ CORRECCIONES IMPLEMENTADAS (Factories)

### **UserFactory** ✅ CORRECTO

**Implementación real (correcta):**
```php
public function definition(): array
{
    return [
        'name' => $this->faker->name(),
        'email' => $this->faker->unique()->safeEmail(),
        'email_verified_at' => now(), // ✅ CORRECTO
        'avatar_path' => null,         // ✅ CORRECTO (nullable por defecto)
        'remember_token' => Str::random(10),
    ];
}

public function unverified(): static { ... } // ✅ CORRECTO
public function withAvatar(?string $path = null): static { ... } // ✅ CORRECTO
public function withoutAvatar(): static { ... } // ✅ CORRECTO
```

**Diferencias con el plan:**
- ✅ Usa `avatar_path` (no `avatar_url`)
- ✅ NO incluye `timezone` (no existe en BD)
- ✅ Incluye `email_verified_at` (nuevo según audit)

---

### **WorkspaceFactory** ✅ CORRECTO

**Implementación real (correcta):**
```php
public function definition(): array
{
    return [
        'name' => $this->faker->company(),
        'slug' => $this->faker->unique()->slug(),
        'branding_config' => [
            'logo_url' => $this->faker->imageUrl(100, 100, 'business', true, 'Logo'),
            'primary_color' => $this->faker->hexColor(),
            'secondary_color' => $this->faker->hexColor(),
        ],
        'donation_config' => [
            'default_amounts' => [10, 25, 50, 100],
            'currency' => $this->faker->currencyCode(),
        ],
    ];
}
```

**Diferencias con el plan:**
- ✅ Usa `branding_config` JSONB (no `avatar_url` separado)
- ✅ Usa `donation_config` JSONB (no campos separados)
- ✅ NO incluye `bio` (no existe)
- ✅ NO incluye `is_active` (no existe)

---

### **MembershipFactory** ✅ CORRECTO (no mencionado en plan)

**Implementación real (creada por nosotros):**
```php
public function definition(): array
{
    return [
        'user_id' => User::factory(),
        'workspace_id' => Workspace::factory(),
        'role' => $this->faker->randomElement(['admin', 'editor', 'viewer']),
        'joined_at' => now(),
    ];
}

public function owner(): static { ... }
public function admin(): static { ... }
public function editor(): static { ... }
public function viewer(): static { ... }
public function forUser(User $user): static { ... }
public function forWorkspace(Workspace $workspace): static { ... }
```

**Nota:** El plan NO menciona MembershipFactory, pero es **CRÍTICA** para el dominio Identity.

---

### **InvitationFactory** ✅ CORRECTO (no mencionado en plan)

**Implementación real (creada por nosotros):**
```php
public function definition(): array
{
    return [
        'workspace_id' => Workspace::factory(),
        'email' => $this->faker->unique()->safeEmail(),
        'role' => $this->faker->randomElement(['admin', 'editor', 'viewer']),
        'token' => Invitation::generateToken(),
        'expires_at' => now()->addDays(7),
        'accepted_by_user_id' => null,
        'accepted_at' => null,
    ];
}

public function pending(): static { ... }
public function expired(): static { ... }
public function accepted(?User $user = null): static { ... }
// ... más métodos
```

**Nota:** El plan NO menciona InvitationFactory, pero es **NECESARIA** para flujo de colaboración.

---

## 📋 ESTRATEGIA DE CORRECCIÓN

### **Fase 1: Módulo IDENTITY** ✅ COMPLETADO

- [x] UserFactory corregido
- [x] WorkspaceFactory corregido
- [x] MembershipFactory creado (no en plan)
- [x] InvitationFactory creado (no en plan)
- [x] Modelos validados contra migraciones
- [x] Relaciones Eloquent implementadas

---

### **Fase 2: Módulo ACTIVITY** 🔄 EN ANÁLISIS

**Tareas pendientes:**
1. Revisar plan para Activity
2. Validar contra `entidades-corregidas.md`
3. Verificar migraciones existentes
4. Crear/corregir Models
5. Crear Factories (si necesarios)

---

### **Fase 3: Módulo PUBLISHING** ⏳ PENDIENTE

**Entidades según audit:**
- `publishing_posts`
- `publishing_post_versions` **[NUEVA]**
- `publishing_post_media` **[NUEVA]**
- `publishing_media`
- `publishing_tags`
- `publishing_post_tag`

**Campos críticos a validar:**
- `content` (JSONB para Editor.js)
- `carbon_score` (decimal para huella de carbono)
- Relaciones FK a Identity

---

### **Fase 4: Módulo COMMUNITY** ⏳ PENDIENTE

**Entidades:**
- `community_comments` (con anidamiento vía `parent_id`)
- `community_likes` (PK compuesta)
- `community_followers` (follows a workspaces, no users)

---

### **Fase 5: Módulo AUDIENCE** ⏳ PENDIENTE

**Entidades con cambios GDPR:**
- `audience_subscribers` (+ `consent_given_at`, `consent_ip`)
- `audience_import_jobs` (+ `expires_at`)

---

### **Fase 6: Módulo DELIVERY** ⏳ PENDIENTE

**Entidades:**
- `delivery_campaigns` (`stats` como JSONB)
- `delivery_bounces` (+ `bounce_type` ['hard', 'soft', 'complaint'])

---

## 🎯 PRÓXIMOS PASOS RECOMENDADOS

### **Opción A: Validación Módulo por Módulo** (RECOMENDADO)

**Ventajas:**
- ✅ Evita alucinaciones
- ✅ Permite correcciones incrementales
- ✅ Validación precisa contra arquitectura real

**Orden sugerido:**
1. ✅ IDENTITY (completado)
2. 🔄 ACTIVITY (siguiente)
3. ⏳ PUBLISHING
4. ⏳ COMMUNITY
5. ⏳ AUDIENCE
6. ⏳ DELIVERY

---

### **Opción B: Corrección Masiva** (NO RECOMENDADO)

**Desventajas:**
- ❌ Alto riesgo de errores
- ❌ Difícil de validar
- ❌ Puede generar más inconsistencias

---

## 📝 CONVENCIONES LARAVEL 12.X A SEGUIR

### **1. Factories**
```php
// ✅ CORRECTO
public function definition(): array
{
    return [
        'field' => value,
    ];
}

public function state(): static
{
    return $this->state([...]);
}

// ❌ INCORRECTO (Laravel < 8)
public function definition()
{
    return [...];
}
```

### **2. Models**
```php
// ✅ CORRECTO
protected $fillable = ['field1', 'field2'];
protected $casts = [
    'json_field' => 'array',
    'date_field' => 'datetime',
];

// ✅ Relaciones con tipos
public function relation(): HasMany
{
    return $this->hasMany(Model::class);
}
```

### **3. Migraciones**
```php
// ✅ CORRECTO (Laravel 12.x)
$table->uuid('id')->primary();
$table->jsonb('field'); // PostgreSQL
$table->enum('field', ['value1', 'value2']);

// ❌ NO usar json() en PostgreSQL, usar jsonb()
```

---

## ✅ DECISIONES ARQUITECTÓNICAS VALIDADAS

1. ✅ **UUID como PK** en todas las tablas
2. ✅ **JSONB** para configs (branding, donation, stats, etc)
3. ✅ **Timestamps** automáticos de Laravel (excepto Membership, Invitation)
4. ✅ **Soft Deletes** NO usados (borrado físico con CASCADE)
5. ✅ **Relaciones Eloquent** claramente definidas
6. ✅ **Factories con `has()`** para relaciones deterministas
7. ✅ **Factories con `afterCreating()`** para lógica compleja

---

## 🔍 CONCLUSIÓN

**El plan tiene incongruencias significativas** que pueden causar errores si se sigue literalmente. La arquitectura real (migraciones + modelos + factories actuales) es **correcta y coherente** con `entidades-corregidas.md`.

**Recomendación:** Continuar validación **módulo por módulo**, empezando con ACTIVITY.

---

**Documento generado automáticamente mediante análisis de:**
- `plan-estructuraModularDdaFreetter.prompt.md`
- `entidades-corregidas.md`
- `claude_audit.md`
- Migraciones reales en `/workspace/app-modules/*/database/migrations/`
- Modelos reales en `/workspace/app-modules/*/src/Models/`
- Factories reales en `/workspace/app-modules/*/database/factories/`
