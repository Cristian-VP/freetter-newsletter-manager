# 📋 ENTIDADES CORREGIDAS CON RECOMENDACIONES DEL AUDIT

## Dominio: IDENTITY (sin cambios estructurales)

| Tabla | Nivel | Definición de Columnas (PostgreSQL) | Cambios |
| :--- | :---: | :--- | :--- |
| **`identity_users`** | 🟢 | • `id`: `uuid` (PK)<br>• `name`: `varchar(255)`<br>• `email`: `varchar(255)` (Unique)<br>• `email_verified_at`: `timestamp` (Nullable) **[NUEVO]**<br>• `avatar_path`: `varchar`<br>• `remember_token`: `varchar`<br>• `created_at`: `timestamp` | ✅ **Agregado:** `email_verified_at` para validación GDPR |
| **`identity_workspaces`** | 🟢 | • `id`: `uuid` (PK)<br>• `name`: `varchar(255)`<br>• `slug`: `varchar(63)` (Unique, Index)<br>• `branding_config`: `jsonb`<br>• `donation_config`: `jsonb`<br>• `created_at`: `timestamp` | Sin cambios |
| **`identity_memberships`** | 🟢 | • `id`: `uuid` (PK)<br>• `user_id`: `uuid` (FK -> identity_users)<br>• `workspace_id`: `uuid` (FK -> identity_workspaces)<br>• `role`: `varchar` ('owner', 'admin', 'editor', 'writer')<br>• `joined_at`: `timestamp`<br>**Unique:** `(user_id, workspace_id)` | ⚠️ **Regla de Negocio:** Si owner se da de baja, transferir a otro admin |
| **`identity_invitations`** | 🟡 | • `id`: `uuid` (PK)<br>• `email`: `varchar` (Index)<br>• `workspace_id`: `uuid` (FK)<br>• `role`: `varchar`<br>• `token`: `varchar` (Unique)<br>• `expires_at`: `timestamp`<br>• `accepted_by_user_id`: `uuid` (Nullable, FK) **[NUEVO]** | ✅ **Agregado:** `accepted_by_user_id` para evitar inconsistencias |

---

## Dominio: PUBLISHING (correcciones críticas)

| Tabla | Nivel | Definición de Columnas (PostgreSQL) | Cambios |
| :--- | :---: | :--- | :--- |
| **`publishing_posts`** | 🟢 | • `id`: `uuid` (PK)<br>• `workspace_id`: `uuid` (FK -> identity_workspaces)<br>• `author_id`: `uuid` (FK -> identity_users)<br>• `title`: `varchar(255)`<br>• `slug`: `varchar(255)` (Index)<br>• `type`: `varchar` ('newsletter', 'note')<br>• `status`: `varchar` ('draft', 'scheduled', 'published')<br>• `content`: `jsonb`<br>• `excerpt`: `text`<br>• `published_at`: `timestamp`<br>• `carbon_score`: `decimal(8,2)`<br>• `created_at`: `timestamp` | Sin cambios (estructura correcta) |
| **`publishing_post_versions`** | 🟢 | • `id`: `uuid` (PK)<br>• `post_id`: `uuid` (FK -> publishing_posts, ON DELETE CASCADE)<br>• `content`: `jsonb`<br>• `version_number`: `integer`<br>• `created_at`: `timestamp`<br>**Unique:** `(post_id, version_number)` | ✅ **NUEVA TABLA** - Auditoría de versiones para newsletters enviadas |
| **`publishing_post_media`** | 🟢 | • `post_id`: `uuid` (FK -> publishing_posts, ON DELETE CASCADE)<br>• `media_id`: `uuid` (FK -> publishing_media, ON DELETE CASCADE)<br>**PK Compuesta:** `(post_id, media_id)` | ✅ **NUEVA TABLA** - Relación explícita entre posts y media |
| **`publishing_media`** | 🟢 | • `id`: `uuid` (PK)<br>• `workspace_id`: `uuid` (FK)<br>• `path`: `varchar`<br>• `disk`: `varchar` ('local', 's3')<br>• `mime_type`: `varchar`<br>• `size_kb`: `integer`<br>• `created_at`: `timestamp` | Sin cambios |
| **`publishing_tags`** | 🟡 | • `id`: `uuid` (PK)<br>• `workspace_id`: `uuid` (FK)<br>• `name`: `varchar`<br>• `slug`: `varchar` | Sin cambios |
| **`publishing_post_tag`** | 🟡 | • `post_id`: `uuid` (FK -> publishing_posts)<br>• `tag_id`: `uuid` (FK -> publishing_tags)<br>**PK Compuesta:** `(post_id, tag_id)` | Sin cambios |

---

## Dominio: COMMUNITY (sin cambios estructurales)

| Tabla | Nivel | Definición de Columnas (PostgreSQL) | Cambios |
| :--- | :---: | :--- | :--- |
| **`community_comments`** | 🟢 | • `id`: `uuid` (PK)<br>• `post_id`: `uuid` (FK -> publishing_posts)<br>• `user_id`: `uuid` (FK -> identity_users)<br>• `content`: `text`<br>• `parent_id`: `uuid` (Nullable, Self Reference)<br>• `created_at`: `timestamp` | Sin cambios |
| **`community_likes`** | 🟢 | • `user_id`: `uuid` (FK)<br>• `post_id`: `uuid` (FK)<br>• `created_at`: `timestamp`<br>**PK Compuesta:** `(user_id, post_id)` | Sin cambios |
| **`community_followers`** | 🟡 | • `follower_id`: `uuid` (FK -> identity_users)<br>• `followed_workspace_id`: `uuid` (FK -> identity_workspaces)<br>• `created_at`: `timestamp`<br>**PK Compuesta:** `(follower_id, followed_workspace_id)` | 🔮 **Futuro V2:** Hacer polimórfico para seguir usuarios también |

---

## Dominio: AUDIENCE (correcciones GDPR)

| Tabla | Nivel | Definición de Columnas (PostgreSQL) | Cambios |
| :--- | :---: | :--- | :--- |
| **`audience_subscribers`** | 🟢 | • `id`: `uuid` (PK)<br>• `workspace_id`: `uuid` (FK)<br>• `email`: `varchar(255)`<br>• `status`: `varchar` ('active', 'unsubscribed', 'bounced')<br>• `name`: `varchar` (Nullable)<br>• `meta`: `jsonb`<br>• `unsubscribe_token`: `uuid` (Unique)<br>• `consent_given_at`: `timestamp` **[NUEVO]**<br>• `consent_ip`: `varchar(45)` **[NUEVO]**<br>• `subscribed_at`: `timestamp`<br>**Unique:** `(workspace_id, email)` | ✅ **Agregados:** `consent_given_at` + `consent_ip` para GDPR compliance |
| **`audience_import_jobs`** | 🟡 | • `id`: `uuid` (PK)<br>• `workspace_id`: `uuid` (FK)<br>• `filename`: `varchar`<br>• `status`: `varchar` ('pending', 'processing', 'completed', 'failed')<br>• `row_count`: `integer`<br>• `error_log`: `jsonb`<br>• `expires_at`: `timestamp` **[NUEVO]**<br>• `created_at`: `timestamp` | ✅ **Agregado:** `expires_at` para limpieza automática (30 días) |

---

## Dominio: DELIVERY (correcciones críticas)

| Tabla | Nivel | Definición de Columnas (PostgreSQL) | Cambios |
| :--- | :---: | :--- | :--- |
| **`delivery_campaigns`** | 🟢 | • `id`: `uuid` (PK)<br>• `post_id`: `uuid` (FK -> publishing_posts)<br>• `workspace_id`: `uuid` (FK)<br>• `subject`: `varchar`<br>• `status`: `varchar` ('queued', 'sending', 'sent', 'failed')<br>• `stats`: `jsonb` ({ total: 500, sent: 490, failed: 10, opened: 150 })<br>• `started_at`: `timestamp`<br>• `completed_at`: `timestamp`<br>• `created_at`: `timestamp` | Sin cambios estructurales |
| **`delivery_bounces`** | 🟡 | • `id`: `uuid` (PK)<br>• `workspace_id`: `uuid` (FK)<br>• `email`: `varchar`<br>• `bounce_type`: `varchar` ('hard', 'soft', 'complaint') **[NUEVO]**<br>• `code`: `varchar`<br>• `reason`: `varchar`<br>• `created_at`: `timestamp`<br>**Index:** `(email, workspace_id)` | ✅ **Agregado:** `bounce_type` para gestionar reintentos vs bloqueos |

---

## 🆕 Dominio: ACTIVITY (Nuevo - Auditoría y Trazabilidad)

**Namespace:** `App\Domains\Activity`  
**Prefijo de Tablas:** `activity_`

| Tabla | Nivel | Definición de Columnas (PostgreSQL) | Propósito |
| :--- | :---: | :--- | :--- |
| **`activity_logs`** | 🟢 | • `id`: `uuid` (PK)<br>• `user_id`: `uuid` (FK -> identity_users, Nullable)<br>• `action`: `varchar(100)` ('workspace.deleted', 'post.published', 'permission.changed')<br>• `entity_type`: `varchar(50)` ('workspace', 'post', 'campaign')<br>• `entity_id`: `uuid`<br>• `metadata`: `jsonb` (contexto adicional)<br>• `ip_address`: `varchar(45)`<br>• `user_agent`: `text` (Nullable)<br>• `created_at`: `timestamp`<br>**Index:** `(user_id, created_at)` DESC<br>**Index:** `(entity_type, entity_id)` | Registro inmutable de acciones críticas. Vital para auditoría legal y debugging. Rate limiting: máximo 10 años de retención. |
| **`activity_streams`** | 🟡 | • `id`: `uuid` (PK)<br>• `workspace_id`: `uuid` (FK)<br>• `log_id`: `uuid` (FK -> activity_logs)<br>• `event_type`: `varchar` ('post_published', 'subscriber_added')<br>• `visibility`: `varchar` ('public', 'admin')<br>• `created_at`: `timestamp` | **V1.1:** Feed visible de cambios. Permite mostrar "Historial de cambios" a los followers públicamente. |
| **`activity_alerts`** | 🟡 | • `id`: `uuid` (PK)<br>• `workspace_id`: `uuid` (FK)<br>• `log_id`: `uuid` (FK -> activity_logs)<br>• `alert_type`: `varchar` ('hard_delete', 'permission_escalation', 'rate_limit_exceeded')<br>• `severity`: `varchar` ('info', 'warning', 'critical')<br>• `resolved_at`: `timestamp` (Nullable)<br>• `created_at`: `timestamp` | **V1.1:** Sistema de alertas para detectar anomalías (borrado masivo, cambio sospechoso de permisos). |

---

## 📌 Resumen de Cambios Aplicados

✅ **Tabla nueva:** `publishing_post_versions` - Preservar historial de posts enviados  
✅ **Tabla nueva:** `publishing_post_media` - Relación explícita media-post  
✅ **Tabla nueva dominio ACTIVITY** - `activity_logs` (MVP) + `activity_streams` y `activity_alerts` (V1.1)  
✅ **Campos nuevos GDPR:** `email_verified_at`, `consent_given_at`, `consent_ip`, `bounce_type`  
✅ **Campos nuevos auditoría:** `accepted_by_user_id` (invitations), `expires_at` (import_jobs)  
✅ **Indexes optimizados:** Añadidos en `activity_logs` para queries eficientes

---

## ⚠️ Notas Importantes para Migraciones

### 1. Orden de Creación (Dependencias FK)
Crear en este orden:
1. IDENTITY (users, workspaces, memberships, invitations)
2. PUBLISHING (posts, post_versions, media, post_media, tags, post_tag)
3. COMMUNITY (comments, likes, followers)
4. AUDIENCE (subscribers, import_jobs)
5. DELIVERY (campaigns, bounces)
6. ACTIVITY (logs, streams, alerts)

### 2. Campos Importantes
- **UUID:** Usar `uuid()` en Laravel o `gen_random_uuid()` en PostgreSQL
- **Timestamps:** Laravel maneja automáticamente `created_at` y `updated_at` (si está configurado)
- **JSONB:** Implementar validación en Models para estructura esperada
- **Indexes:** Críticos en `activity_logs` para queries de auditoría eficientes

### 3. Reglas de Negocio a Implementar
- `identity_memberships`: Si owner abandona, promover otro admin
- `publishing_post_versions`: Crear versión al guardar post publicado
- `audience_subscribers`: Usar soft delete (campo `deleted_at`)
- `activity_logs`: Tabla inmutable, solo INSERT, jamás UPDATE/DELETE
- `audience_import_jobs`: Job cron para borrar registros con `expires_at` < NOW()

### 4. Seguridad (Rate Limiting)
```php
// Ejemplo para activity_logs rate limiting en Laravel
RateLimiter::for('activity-log', function (Request $request) {
    return Limit::perMinute(100)->by($request->user()?->id ?: $request->ip());
});
```
