🟢 Esencial (MVP Core): Hazlo sí o sí.

🟡 Recomendado: Si te sobra tiempo.

🔴 Descartado/Futuro: No lo hagas ahora.


### Tablas del Dominio: IDENTITY (Prefijo: `identity_`)

| Tabla | Nivel | Definición de Columnas (PostgreSQL) | Propósito Técnico |
| :--- | :---: | :--- | :--- |
| **`identity_users`** | 🟢 | • `id`: `uuid` (PK)<br>• `name`: `varchar(255)`<br>• `email`: `varchar(255)` (Unique)<br>• `avatar_path`: `varchar`<br>• `remember_token`: `varchar`<br>• `created_at`: `timestamp` | La persona física. No tiene password porque usas Magic Links. |
| **`identity_workspaces`** | 🟢 | • `id`: `uuid` (PK)<br>• `name`: `varchar(255)`<br>• `slug`: `varchar(63)` (Unique, Index)<br>• `branding_config`: `jsonb` (Logo, colores)<br>• `donation_config`: `jsonb` (Provider, URL)<br>• `created_at`: `timestamp` | Representa al **Grupo/Redacción**. <br>⚠️ **Importante:** El `slug` será el subdominio (`slug.freetter.com`), así que debe validarse (solo letras, números y guiones). |
| **`identity_memberships`** | 🟢 | • `id`: `uuid` (PK)<br>• `user_id`: `uuid` (FK -> identity_users)<br>• `workspace_id`: `uuid` (FK -> identity_workspaces)<br>• `role`: `varchar` ('owner', 'admin', 'editor', 'writer')<br>• `joined_at`: `timestamp`<br>**Unique:** `(user_id, workspace_id)` | **Motor de Colaboración.**<br>Define que el Usuario X trabaja en la Redacción Y con el Rol Z. Un usuario puede tener múltiples filas aquí (varios grupos). |
| **`identity_invitations`** | 🟡 | • `id`: `uuid` (PK)<br>• `email`: `varchar` (Index)<br>• `workspace_id`: `uuid` (FK)<br>• `role`: `varchar`<br>• `token`: `varchar` (Unique)<br>• `expires_at`: `timestamp` | Permite invitar a futuros colaboradores por email antes de que tengan cuenta. |

### Tablas del Dominio: PUBLISHING (Prefijo: `publishing_`)

| Tabla | Nivel | Definición de Columnas (PostgreSQL) | Propósito Técnico |
| :--- | :---: | :--- | :--- |
| **`publishing_posts`** | 🟢 | • `id`: `uuid` (PK)<br>• `workspace_id`: `uuid` (FK -> identity_workspaces)<br>• `author_id`: `uuid` (FK -> identity_users)<br>• `title`: `varchar(255)`<br>• `slug`: `varchar(255)` (Index)<br>• `type`: `varchar` ('newsletter', 'note')<br>• `status`: `varchar` ('draft', 'scheduled', 'published')<br>• `content`: `jsonb` (Output de Editor.js)<br>• `excerpt`: `text`<br>• `published_at`: `timestamp`<br>• `carbon_score`: `decimal(8,2)` | Tabla unificada. <br>• `author_id`: Es quien firmó el post.<br>• `workspace_id`: Es a quien pertenece legalmente el post.<br>• `content`: Guarda todo el JSON estructurado. |
| **`publishing_media`** | 🟢 | • `id`: `uuid` (PK)<br>• `workspace_id`: `uuid` (FK)<br>• `path`: `varchar`<br>• `disk`: `varchar` ('local', 's3')<br>• `mime_type`: `varchar`<br>• `size_kb`: `integer` | Biblioteca de medios. Vital para saber cuánto espacio ocupa cada Workspace y limpiar basura. |
| **`publishing_tags`** | 🟡 | • `id`: `uuid` (PK)<br>• `workspace_id`: `uuid` (FK)<br>• `name`: `varchar`<br>• `slug`: `varchar` | Categorización simple para el perfil público. |
| **`publishing_post_tag`** | 🟡 | • `post_id`: `uuid`<br>• `tag_id`: `uuid` | Tabla pivote estándar para etiquetas. |

### Tablas del Dominio: COMMUNITY (Prefijo: `community_`)

| Tabla | Nivel | Definición de Columnas (PostgreSQL) | Propósito Técnico |
| :--- | :---: | :--- | :--- |
| **`community_comments`** | 🟢 | • `id`: `uuid` (PK)<br>• `post_id`: `uuid` (FK -> publishing_posts)<br>• `user_id`: `uuid` (FK -> identity_users)<br>• `content`: `text`<br>• `parent_id`: `uuid` (Nullable - Self Reference)<br>• `created_at`: `timestamp` | Sistema de comentarios anidados. `parent_id` permite "responder a un comentario". |
| **`community_likes`** | 🟢 | • `user_id`: `uuid` (FK)<br>• `post_id`: `uuid` (FK)<br>• `created_at`: `timestamp`<br>**PK Compuesta:** `(user_id, post_id)` | Feedback positivo. La PK compuesta evita que alguien de "like" dos veces. |
| **`community_followers`** | 🟡 | • `follower_id`: `uuid` (FK -> identity_users)<br>• `followed_workspace_id`: `uuid` (FK -> identity_workspaces)<br>• `created_at`: `timestamp`<br>**PK Compuesta:** `(follower_id, followed_workspace_id)` | Permite construir el **Feed**. Un usuario sigue a una Redacción (Workspace), no a una persona individual. |

### Tablas del Dominio: AUDIENCE (Prefijo: `audience_`)

| Tabla | Nivel | Definición de Columnas (PostgreSQL) | Propósito Técnico |
| :--- | :---: | :--- | :--- |
| **`audience_subscribers`** | 🟢 | • `id`: `uuid` (PK)<br>• `workspace_id`: `uuid` (FK)<br>• `email`: `varchar(255)`<br>• `status`: `varchar` ('active', 'unsubscribed', 'bounced')<br>• `name`: `varchar` (Nullable)<br>• `meta`: `jsonb` (Origen, tags importados)<br>• `unsubscribe_token`: `uuid` (Unique)<br>• `subscribed_at`: `timestamp`<br>**Unique:** `(workspace_id, email)` | Los lectores fieles. El `unsubscribe_token` es vital para el enlace de baja en el email. |
| **`audience_import_jobs`** | 🟡 | • `id`: `uuid` (PK)<br>• `workspace_id`: `uuid` (FK)<br>• `filename`: `varchar`<br>• `status`: `varchar` ('pending', 'processing', 'completed', 'failed')<br>• `row_count`: `integer`<br>• `error_log`: `jsonb` | Para gestionar importaciones grandes de CSV sin bloquear el servidor. Guarda el estado del proceso. |

### Tablas del Dominio: DELIVERY (Prefijo: `delivery_`)

| Tabla | Nivel | Definición de Columnas (PostgreSQL) | Propósito Técnico |
| :--- | :---: | :--- | :--- |
| **`delivery_campaigns`** | 🟢 | • `id`: `uuid` (PK)<br>• `post_id`: `uuid` (FK -> publishing_posts)<br>• `workspace_id`: `uuid` (FK)<br>• `subject`: `varchar`<br>• `status`: `varchar` ('queued', 'sending', 'sent', 'failed')<br>• `stats`: `jsonb` ({ total: 500, sent: 490, failed: 10 })<br>• `started_at`: `timestamp`<br>• `completed_at`: `timestamp` | Representa el "acto" de enviar. Aquí guardas los contadores agregados para no saturar la DB con logs individuales. |
| **`delivery_bounces`** | 🟡 | • `id`: `uuid` (PK)<br>• `workspace_id`: `uuid` (FK)<br>• `email`: `varchar`<br>• `code`: `varchar` (Mailgun error code)<br>• `reason`: `varchar`<br>• `created_at`: `timestamp` | Lista negra local. Si un email rebota hard, lo guardas aquí para bloquear futuros envíos a esa dirección. |
