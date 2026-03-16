# 📋 USE CASES — FREETTER

**Versión:** 1.0  
**Actualizado:** Marzo 2026  
**Fuentes de Verdad:**
- `ENTITIES.md` — esquema de tablas y campos
- `domains-proposal.md` — semántica y reglas de negocio por dominio
- `GLOBAL_STRATEGY.md` — estado de implementación por módulo

---

## ÍNDICE

1. [IDENTITY — Autenticación y Organización](#1-identity)
2. [PUBLISHING — Gestión de Contenido](#2-publishing)
3. [AUDIENCE — Suscriptores (CRM)](#3-audience)
4. [COMMUNITY — Interacción Social](#4-community)
5. [DELIVERY — Envío de Campañas](#5-delivery)
6. [ACTIVITY — Auditoría y Trazabilidad](#6-activity)

---

## 1. IDENTITY

**Namespace:** `Modules\Identity`  
**Tablas:** `identity_users`, `identity_workspaces`, `identity_memberships`, `identity_invitations`

### UC-ID-01 — Registro de Usuario (Magic Link)

**Actor:** Visitante  
**Precondición:** Email no existe en `identity_users`.  
**Flujo:**
1. Usuario introduce su email.
2. Sistema genera token y envía Magic Link por correo.
3. Usuario hace click en el enlace.
4. Sistema crea el `User` (sin contraseña), inicia sesión.

**Regla de Negocio:** El usuario existe independientemente de si tiene o no un workspace.

---

### UC-ID-02 — Inicio de Sesión (Magic Link)

**Actor:** Usuario registrado  
**Precondición:** Email ya existe en `identity_users`.  
**Flujo:**
1. Usuario introduce email.
2. Sistema envía Magic Link (con expiración).
3. Sistema verifica token → actualiza `email_verified_at` si no estaba verificado.
4. Sesión iniciada.

---

### UC-ID-03 — Crear Workspace

**Actor:** Usuario autenticado  
**Flujo:**
1. Usuario define nombre y `slug` único.
2. Sistema valida unicidad de `slug` en `identity_workspaces`.
3. Crea Workspace + Membership con `role = 'owner'`.

**Regla de Negocio:** Un usuario puede ser Owner de múltiples workspaces.

---

### UC-ID-04 — Invitar Colaborador

**Actor:** Owner / Admin  
**Flujo:**
1. Actor introduce email y rol destino (`admin|editor|writer`).
2. Sistema crea registro en `identity_invitations` con token único y `expires_at`.
3. Se envía email con enlace de aceptación.
4. El invitado acepta → se crea `Membership` y se rellena `accepted_by_user_id`.

---

### UC-ID-05 — Transferir Ownership

**Actor:** Owner actual  
**Precondición:** Existe al menos un `Admin` en el workspace.  
**Flujo:**
1. Owner selecciona otro miembro como nuevo owner.
2. Sistema cambia `role` del actual owner a `admin`.
3. Sistema cambia `role` del nuevo owner a `owner`.

**Regla de Negocio Crítica:** Si el owner se da de baja, se debe promover al admin más antiguo automáticamente. No puede haber workspace sin owner.

---

### UC-ID-06 — Cambiar Rol de Miembro

**Actor:** Owner / Admin  
**Flujo:**
1. Actor selecciona miembro y nuevo rol.
2. Sistema actualiza `role` en `identity_memberships`.

**Restricción:** Un Admin no puede elevar a otro Admin a Owner (solo Owner puede hacerlo).

---

### UC-ID-07 — Eliminar Miembro (Revocación)

**Actor:** Owner / Admin  
**Flujo:**
1. Actor selecciona miembro a expulsar.
2. Sistema elimina el registro de `identity_memberships`.

---

## 2. PUBLISHING

**Namespace:** `Modules\Publishing`  
**Tablas:** `publishing_posts`, `publishing_post_versions`, `publishing_media`, `publishing_post_media`, `publishing_tags`, `publishing_post_tag`

### UC-PUB-01 — Crear Borrador (Post)

**Actor:** Writer / Editor / Admin / Owner  
**Flujo:**
1. Actor crea nuevo Post con `status = 'draft'`, `type = 'newsletter'|'note'`.
2. Sistema genera `slug` a partir del `title`.
3. Guarda `content` como JSONB (estructura Editor.js).

**Regla de Negocio:** El contenido nunca se guarda como HTML — siempre JSON nativo de Editor.js.

---

### UC-PUB-02 — Guardar Versión de Post

**Actor:** Sistema (automático al guardar)  
**Flujo:**
1. Al guardar cambios en un Post publicado o con versiones previas, el sistema crea un registro en `publishing_post_versions` con `version_number` incremental.

**Regla de Negocio:** Las versiones son inmutables. Permiten auditoría de "qué se envió" en newsletters ya despachadas.

---

### UC-PUB-03 — Publicar Post

**Actor:** Editor / Admin / Owner  
**Flujo:**
1. Actor revisa borrador y hace click en "Publicar".
2. Sistema cambia `status = 'published'`, graba `published_at = NOW()`.
3. Sistema crea versión en `publishing_post_versions`.
4. Si `type = 'newsletter'` → dispara evento para que Delivery prepare la campaña.

---

### UC-PUB-04 — Programar Publicación

**Actor:** Editor / Admin / Owner  
**Flujo:**
1. Actor selecciona fecha/hora futura.
2. Sistema cambia `status = 'scheduled'`, graba `published_at` con la fecha futura.
3. Job programado cambia `status → 'published'` en la fecha indicada.

---

### UC-PUB-05 — Subir Media

**Actor:** Writer / Editor / Admin / Owner  
**Flujo:**
1. Actor sube archivo (imagen, PDF).
2. Sistema crea registro en `publishing_media` con `path`, `disk`, `mime_type`, `size_kb`.
3. Se vincula al Post a través de `publishing_post_media`.

**Regla de Negocio:** Al borrar un Workspace, se eliminan físicamente todos sus archivos media.

---

### UC-PUB-06 — Gestionar Tags

**Actor:** Writer / Editor / Admin / Owner  
**Flujo:**
1. Actor asigna/crea tags al Post.
2. Los tags son **locales al Workspace** (no compartidos entre workspaces).
3. Relación a través de `publishing_post_tag`.

---

### UC-PUB-07 — Calcular Carbon Score

**Actor:** Sistema (automático al publicar)  
**Flujo:**
1. Al publicar, sistema calcula `carbon_score` del Post basado en tamaño de contenido y assets.
2. Graba en `publishing_posts.carbon_score`.

---

## 3. AUDIENCE

**Namespace:** `Modules\Audience`  
**Tablas:** `audience_subscribers`, `audience_import_jobs`

### UC-AUD-01 — Suscribir a Newsletter (formulario público)

**Actor:** Visitante anónimo  
**Flujo:**
1. Visitante introduce email en formulario del Workspace.
2. Sistema valida email y unicidad del par `(workspace_id, email)`.
3. Crea `Subscriber` con `status = 'active'`, graba `consent_given_at = NOW()` y `consent_ip`.
4. Genera `unsubscribe_token` único (UUID).

**Regla de Negocio GDPR:** El `consent_given_at` e IP son **obligatorios** para cumplimiento legal. Un suscriptor en workspace A es una entidad totalmente independiente del mismo email en workspace B.

---

### UC-AUD-02 — Desuscribir (Unsubscribe)

**Actor:** Suscriptor (a través de enlace en email)  
**Flujo:**
1. Suscriptor hace click en enlace con `unsubscribe_token`.
2. Sistema valida token → cambia `status = 'unsubscribed'`.
3. **No se elimina el registro** (soft delete lógico para mantener histórico GDPR).

---

### UC-AUD-03 — Importar Suscriptores (CSV)

**Actor:** Admin / Owner  
**Flujo:**
1. Actor sube CSV con columnas email, name (opcional).
2. Sistema crea `ImportJob` con `status = 'pending'`.
3. Job asíncrono procesa filas: inserta suscriptores o acumula errores en `error_log` (JSONB).
4. Al finalizar, `status = 'completed'` o `'failed'`.
5. Job de limpieza elimina `ImportJob` cuando `expires_at < NOW()` (30 días).

---

### UC-AUD-04 — Marcar Bounce

**Actor:** Sistema (webhook Mailgun/SES)  
**Flujo:**
1. Proveedor de email notifica bounce.
2. Sistema actualiza `Subscriber.status = 'bounced'`.

**Regla de Negocio:** El dominio Delivery gestiona el `bounce_type`; Audience gestiona el estado del suscriptor.

---

### UC-AUD-05 — Derecho al Olvido (GDPR)

**Actor:** Suscriptor / Admin  
**Flujo:**
1. Suscriptor solicita borrado de sus datos.
2. Sistema anonimiza / elimina hard el registro de `audience_subscribers`.
3. Registro en `activity_logs` con `action = 'subscriber.forgotten'`.

---

## 4. COMMUNITY

**Namespace:** `Modules\Community`  
**Tablas:** `community_comments`, `community_likes`, `community_followers`

### UC-COM-01 — Comentar en Post

**Actor:** Usuario autenticado  
**Flujo:**
1. Usuario escribe comentario en un Post.
2. Sistema crea registro en `community_comments` con `user_id`, `post_id`, `content`.
3. Puede ser respuesta a otro comentario (`parent_id`).

---

### UC-COM-02 — Dar Like a Post

**Actor:** Usuario autenticado  
**Flujo:**
1. Usuario pulsa "Me gusta".
2. Sistema inserta en `community_likes` con clave compuesta `(user_id, post_id)`.
3. Si ya existe → error controlado (un like por usuario por post, garantizado por DB).

---

### UC-COM-03 — Quitar Like

**Actor:** Usuario autenticado  
**Flujo:**
1. Usuario pulsa "Me gusta" de nuevo (toggle).
2. Sistema elimina registro de `community_likes`.

---

### UC-COM-04 — Seguir Workspace

**Actor:** Usuario autenticado  
**Flujo:**
1. Usuario sigue una publicación/workspace.
2. Sistema inserta en `community_followers` con `(follower_id, followed_workspace_id)`.

**Regla de Negocio Crítica:** En Freetter se siguen **Workspaces**, no personas.

---

### UC-COM-05 — Dejar de Seguir Workspace

**Actor:** Usuario autenticado  
**Flujo:**
1. Usuario deja de seguir.
2. Sistema elimina registro de `community_followers`.

---

### UC-COM-06 — Moderar Comentario

**Actor:** Editor / Admin / Owner  
**Flujo:**
1. Actor marca comentario como inapropiado.
2. Sistema elimina o oculta el registro de `community_comments`.
3. Opcional: registrar en `activity_logs`.

---

## 5. DELIVERY

**Namespace:** `Modules\Delivery`  
**Tablas:** `delivery_campaigns`, `delivery_bounces`

### UC-DEL-01 — Crear Campaña de Envío

**Actor:** Sistema (disparado por publicación de Post tipo `newsletter`)  
**Flujo:**
1. Post publicado con `type = 'newsletter'` → evento emitido.
2. Sistema crea `Campaign` con `status = 'queued'`, vinculada al Post y Workspace.
3. Delivery NO conoce el contenido de Publishing — solo sabe de mensajes y destinatarios.

---

### UC-DEL-02 — Enviar Campaña

**Actor:** Job asíncrono (queue)  
**Flujo:**
1. Job toma Campaign con `status = 'queued'`.
2. Cambia `status = 'sending'`, graba `started_at`.
3. Itera sobre `audience_subscribers` activos del workspace.
4. Envía email vía proveedor (Mailgun/SES).
5. Actualiza `stats` JSONB con `{ total, sent, failed, opened }`.
6. Al finalizar: `status = 'sent'`, graba `completed_at`.

---

### UC-DEL-03 — Gestionar Bounce

**Actor:** Sistema (webhook)  
**Flujo:**
1. Proveedor de email notifica bounce (hard / soft / complaint).
2. Sistema crea registro en `delivery_bounces` con `bounce_type`, `code`, `reason`.
3. Índice en `(email, workspace_id)` para consultas rápidas.
4. Si `bounce_type = 'hard'` → notificar a Audience para cambiar estado del suscriptor.

**Regla de Negocio:** `hard` = bloqueo permanente. `soft` = reintento permitido. `complaint` = baja inmediata obligatoria.

---

### UC-DEL-04 — Reintentar Campaña Fallida

**Actor:** Admin / Owner  
**Flujo:**
1. Actor solicita reintento de Campaign con `status = 'failed'`.
2. Sistema cambia `status = 'queued'` y vuelve a encolar.

---

## 6. ACTIVITY

**Namespace:** `Modules\Activity`  
**Tablas:** `activity_logs`, `activity_streams` (V1.1), `activity_alerts` (V1.1)

### UC-ACT-01 — Registrar Acción de Auditoría

**Actor:** Sistema (llamado desde cualquier módulo)  
**Flujo:**
1. Cualquier módulo dispara evento de acción crítica.
2. Sistema inserta en `activity_logs` con `action`, `entity_type`, `entity_id`, `user_id`, `ip_address`, `metadata`.

**Regla de Negocio CRÍTICA:**
- La tabla `activity_logs` es **inmutable**: solo INSERT, nunca UPDATE ni DELETE.
- Rate limit: máximo 100 inserciones/minuto por usuario o IP.
- Retención máxima: 10 años.

**Ejemplos de acciones:**
- `workspace.deleted`
- `post.published`
- `permission.changed`
- `subscriber.forgotten`
- `campaign.sent`

---

### UC-ACT-02 — Consultar Historial de Acciones

**Actor:** Admin / Owner  
**Flujo:**
1. Actor accede al panel de auditoría.
2. Sistema consulta `activity_logs` con filtros por `user_id`, `entity_type`, `entity_id`, rango de fechas.
3. Índices `(user_id, created_at)` y `(entity_type, entity_id)` garantizan eficiencia.

---

### UC-ACT-03 — Detectar Anomalía (V1.1)

**Actor:** Sistema (job de análisis)  
**Flujo:**
1. Job detecta patrón sospechoso (ej: borrado masivo, escalada de permisos).
2. Crea `activity_alerts` con `alert_type`, `severity`.
3. Notifica a admin vía email/slack.

---

### UC-ACT-04 — Feed de Actividad Pública (V1.1)

**Actor:** Seguidor de Workspace  
**Flujo:**
1. Sistema genera entradas en `activity_streams` para eventos públicos.
2. Seguidor puede ver "historial de publicaciones" del workspace.

---

## 📌 MATRIZ DE DEPENDENCIAS ENTRE DOMINIOS

| Módulo | Depende de | Es requerido por |
|:---|:---|:---|
| **Identity** | — | Publishing, Audience, Community, Activity, Delivery |
| **Publishing** | Identity | Delivery, Community, Activity |
| **Audience** | Identity | Delivery, Activity |
| **Community** | Identity, Publishing | Activity |
| **Delivery** | Publishing, Audience | Activity |
| **Activity** | Identity | (todos registran en él) |

---

## 📌 ACCIONES CRÍTICAS QUE DEBEN REGISTRAR EN ACTIVITY_LOGS

| Acción | Módulo | Descripción |
|:---|:---|:---|
| `workspace.created` | Identity | Nuevo workspace creado |
| `workspace.deleted` | Identity | Workspace eliminado |
| `permission.changed` | Identity | Cambio de rol de miembro |
| `post.published` | Publishing | Post publicado |
| `post.deleted` | Publishing | Post eliminado |
| `subscriber.added` | Audience | Nuevo suscriptor |
| `subscriber.unsubscribed` | Audience | Baja de suscriptor |
| `subscriber.forgotten` | Audience | Derecho al olvido ejecutado |
| `campaign.sent` | Delivery | Campaña enviada |
| `campaign.failed` | Delivery | Campaña fallida |
| `bounce.received` | Delivery | Bounce registrado |
