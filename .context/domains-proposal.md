---

# 📘 Freetter: Diccionario de Dominios y Entidades

**Versión del Documento:** 1.0
**Arquitectura:** Monolito Modular
**Objetivo:** Definir la semántica, responsabilidad y reglas de negocio de cada módulo del sistema.

---

## 1. DOMINIO: IDENTITY (Identidad y Organización)

**Namespace:** `App\Domains\Identity`
**Prefijo de Tablas:** `identity_`

### 📝 Definición Extendida

Este es el **Núcleo de Seguridad y Estructura**. No solo maneja el "Login", sino que define la estructura jerárquica de la plataforma. Su concepto central es la separación entre la **Persona** (User) y la **Entidad Legal/Publicación** (Workspace).
En Freetter, nadie "es" una newsletter; las personas "trabajan en" newsletters (Workspaces). Este dominio es el guardián de la colaboración (RBAC).

### ⚙️ Responsabilidades Clave

1. **Autenticación:** Gestión de acceso sin contraseñas (Magic Links).
2. **Multi-Tenancy:** Gestión de los espacios de trabajo (Redacciones).
3. **Autorización (RBAC):** Determinar quién puede hacer qué dentro de un workspace mediante roles.
4. **Routing Público:** Gestionar los `slugs` que se convierten en subdominios (`tech.freetter.com`).

### 🔍 Entidades al Detalle

#### A. `User` (El Actor)

* **Qué es:** Una persona física real.
* **Regla de Negocio:** Un usuario existe independientemente de si tiene o no un workspace. Puede ser simplemente un lector o un colaborador invitado.
* **Dato Curioso:** No tiene contraseña. Su identidad se valida vía email.

#### B. `Workspace` (El Escenario)

* **Qué es:** La entidad que agrupa el contenido y la audiencia. Es la "Revista", el "Blog" o la "Newsletter".
* **Regla de Negocio:**
* Debe tener un `slug` único globalmente, ya que este define su URL pública (`slug.freetter.com`).
* Contiene la configuración financiera (`donation_config`) y visual (`branding`) en formato JSON para flexibilidad.


* **Relación:** Un Workspace *no* pertenece a un usuario directamente (FK), se vincula a través de `Membership`.

#### C. `Membership` (El Contrato)

* **Qué es:** La relación vinculante entre un `User` y un `Workspace`.
* **Regla de Negocio:**
* Define el **Rol**:
* `Owner`: Dueño legal. Puede borrar el workspace y gestionar pagos.
* `Admin`: Puede gestionar suscriptores y configuración, pero no borrar la cuenta.
* `Editor`: Puede publicar y editar posts de otros.
* `Writer`: Solo puede escribir borradores propios.


* Un usuario puede tener múltiples membresías (ser Owner en su blog y Writer en el de un amigo).



#### D. `Invitation` (El Puente)

* **Qué es:** Un estado transitorio. Permite invitar a un email que aún no está registrado en la plataforma para unirse a un equipo.

---

## 2. DOMINIO: PUBLISHING (Gestión de Contenido)

**Namespace:** `App\Domains\Publishing`
**Prefijo de Tablas:** `publishing_`

### 📝 Definición Extendida

Este es el **CMS (Content Management System)**. Su trabajo es permitir la creatividad. Abstrae la idea de "publicar" algo, ya sea un correo largo o una nota corta. Es responsable de que el contenido se guarde, se procese (Editor.js) y se categorice.

### ⚙️ Responsabilidades Clave

1. **Persistencia Híbrida:** Guardar tanto Newsletters (Email) como Notas (Web) en una estructura unificada.
2. **Gestión de Assets:** Controlar las imágenes subidas para evitar basura digital.
3. **Cálculo de Impacto:** Calcular y almacenar la Huella de Carbono del contenido.

### 🔍 Entidades al Detalle

#### A. `Post` (La Unidad de Contenido)

* **Qué es:** La pieza atómica de información. Puede ser una Newsletter o una Nota.
* **Regla de Negocio:**
* **Polimorfismo Simple:** El campo `type` define si se debe renderizar como Email (`newsletter`) o como Tweet (`note`).
* **Inmutabilidad del Contenido:** El campo `content` guarda la estructura JSON pura de Editor.js. Nunca guardamos HTML sucio aquí (el HTML se genera al vuelo o se cachea aparte).
* **Propiedad:** Tiene un `author_id` (quién lo escribió) y un `workspace_id` (quién lo publica).



#### B. `Media` (El Archivo)

* **Qué es:** Referencia a un archivo físico (imagen, PDF) almacenado en disco o S3.
* **Regla de Negocio:**
* Si se borra un `Workspace`, se deben borrar físicamente todos sus `Media` asociados.
* Sirve para auditoría de espacio en disco (2GB limit).



#### C. `Tag` (La Etiqueta)

* **Qué es:** Taxonomía simple para organizar contenido dentro de un Workspace.
* **Regla de Negocio:** Los tags son locales al Workspace (el tag "Tech" de mi blog no es el mismo que el tag "Tech" de tu blog).

---

## 3. DOMINIO: COMMUNITY (Interacción Social)

**Namespace:** `App\Domains\Community`
**Prefijo de Tablas:** `community_`

### 📝 Definición Extendida

Este es el **Cerebro Social**. Transforma una herramienta de publicación unidireccional en una red bidireccional. Gestiona el grafo social (quién sigue a quién) y el feedback (qué gusta a quién).

### ⚙️ Responsabilidades Clave

1. **Engagement:** Capturar la reacción del público (Likes, Comentarios).
2. **Discovery:** Construir el "Feed" personalizado de cada usuario basado en sus seguidos.
3. **Moderación:** Permitir la gestión de comentarios tóxicos.

### 🔍 Entidades al Detalle

#### A. `Comment` (La Conversación)

* **Qué es:** Texto enriquecido que un usuario deja en un Post.
* **Regla de Negocio:**
* Puede ser anidado (respuestas a respuestas) gracias al `parent_id`.
* Pertenece a un `User` (autor) y a un `Post` (destino).



#### B. `Like` (El Voto)

* **Qué es:** Una señal binaria de aprobación.
* **Regla de Negocio:** Un usuario solo puede dar 1 like por post (garantizado por clave compuesta en DB).

#### C. `Follower` (El Grafo)

* **Qué es:** La conexión entre un lector y una publicación.
* **Regla de Negocio Crítica:** En Freetter, **sigues a Workspaces, no a personas**.
* Si sigo a "The Tech Times", veré sus posts en mi feed.
* No sigo a "Juan el Editor", sigo a su revista.



---

## 4. DOMINIO: AUDIENCE (Audiencia y CRM)

**Namespace:** `App\Domains\Audience`
**Prefijo de Tablas:** `audience_`

### 📝 Definición Extendida

Este es el **Activo del Negocio**. Aquí residen los datos privados de los lectores. Es un dominio aislado legalmente: contiene PII (Información Personal Identificable) y debe cumplir estrictamente con GDPR.

### ⚙️ Responsabilidades Clave

1. **Gestión de Lista:** Altas, bajas y rebotes.
2. **Importación Masiva:** Procesar CSVs de miles de filas sin bloquear el servidor.
3. **Privacidad:** Gestionar los tokens de desuscripción y el derecho al olvido.

### 🔍 Entidades al Detalle

#### A. `Subscriber` (El Lector)

* **Qué es:** Una dirección de email vinculada a un Workspace.
* **Regla de Negocio:**
* **Aislamiento:** El suscriptor `pepe@gmail.com` en el Workspace A es una entidad distinta al `pepe@gmail.com` en el Workspace B. Si se da de baja de A, sigue activo en B.
* **Estado:** Un suscriptor nunca se borra "hard" inmediatamente, pasa a estado `unsubscribed` para mantener histórico (Soft Delete lógico).



#### B. `ImportJob` (El Proceso)

* **Qué es:** Registro de auditoría de una importación masiva.
* **Regla de Negocio:** Permite al usuario ver si su CSV de 5,000 filas terminó o falló, y descargar un reporte de errores (ej: "Fila 40: Email inválido").

---

## 5. DOMINIO: DELIVERY (Infraestructura de Envío)

**Namespace:** `App\Domains\Delivery`
**Prefijo de Tablas:** `delivery_`

### 📝 Definición Extendida

Este es el **Mecanismo de Salida (Tubería)**. Es un dominio técnico que "no sabe" de contenido, solo sabe de "mensajes" y "destinatarios". Se encarga de hablar con el mundo exterior (Mailgun/SMTP).

### ⚙️ Responsabilidades Clave

1. **Abstracción del Proveedor:** Si mañana cambias Mailgun por AWS SES, solo tocas este dominio.
2. **Fiabilidad:** Reintentos, colas y gestión de errores.
3. **Reputación:** Gestionar las listas negras (Bounces) para no caer en Spam.

### 🔍 Entidades al Detalle

#### A. `Campaign` (El Evento de Envío)

* **Qué es:** Representa la acción masiva de enviar un `Post` a una lista de `Subscribers`.
* **Regla de Negocio:**
* Almacena estadísticas agregadas (`sent_count`, `open_count`) en un JSON para no saturar la base de datos con millones de filas de logs individuales.
* Es inmutable: Una vez lanzada, no se puede editar (solo cancelar si está en cola).



#### B. `Bounce` (La Lista Negra)

* **Qué es:** Registro de emails que han fallado permanentemente (Hard Bounce).
* **Regla de Negocio:** Antes de enviar cualquier email, el sistema consulta esta tabla. Si el email está aquí, se bloquea el envío preventivamente para proteger la reputación del dominio.

---

### 🧠 Resumen de Interacciones (Para el Modelo Mental)

1. **User** entra al sistema (Identity).
2. Selecciona un **Workspace** donde es Editor (Identity).
3. Escribe un **Post** (Publishing).
4. Decide enviarlo como **Campaign** (Delivery).
5. El sistema selecciona los **Subscribers** (Audience) de ese Workspace.
6. El sistema crea la Campaña y encola el envío (Delivery).
7. Al publicarse, aparece en el Feed de los **Followers** (Community).
8. Los lectores dejan **Comments** (Community).
