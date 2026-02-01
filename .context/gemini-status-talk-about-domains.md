Aquí tienes el **Checkpoint de Arquitectura v2.0** completo y formateado en Markdown, listo para copiar y pegar en tu Notion, Obsidian o documentación del proyecto.

---

# 💾 Freetter: Checkpoint de Arquitectura Técnica v2.0

**Fecha:** Enero 2026
**Estado:** Definición "Bleeding Edge" & Collaborative
**Tipo:** Monolito Modular

## 1. Stack Tecnológico (Estado del Arte)

Definición de tecnologías para garantizar la longevidad del proyecto y el uso de las últimas funcionalidades disponibles.

| Componente | Tecnología | Versión | Justificación |
| --- | --- | --- | --- |
| **Backend** | PHP / Laravel | **PHP 8.4 + Laravel 12** | Soporte nativo para arquitectura modular, tipado fuerte y rendimiento (JIT). |
| **Frontend** | React / Inertia | **React 19 + Inertia 2.0** | Renderizado híbrido, Server Components (si aplica) y gestión de estado simplificada sin API REST. |
| **Estilos** | Tailwind CSS | **v4.0** | Motor de estilos JIT optimizado. |
| **Base de Datos** | PostgreSQL | **v17** | Motor relacional robusto. Uso intensivo de tipos `JSONB` para contenido flexible. |
| **Colas & Caché** | Redis | **v7.x (Alpine)** | Persistencia AOF para colas críticas y caché de sesiones de alta velocidad. |
| **Runtime JS** | Node.js | **v22 LTS** | Entorno de compilación para Vite y SSR. |
| **Infraestructura** | Docker | **DevContainer** | Entorno de desarrollo reproducible y aislado. |

---

## 2. Arquitectura de Datos y Diseño

El sistema sigue el patrón de **Monolito Modular** para equilibrar la mantenibilidad del código con la eficiencia de recursos (objetivo: correr en 2GB RAM).

* **Patrón de DB:** **Shared Database** (Base de Datos Compartida).
* Una única instancia física de PostgreSQL.
* Aislamiento lógico mediante **Prefijos de Tabla** (`identity_`, `publishing_`, etc.).


* **Conexión entre Módulos:**
* **Estricta:** Los módulos no pueden realizar consultas SQL (`JOIN`) directas a tablas de otros módulos.
* **Interficie:** La comunicación se realiza mediante **Clases PHP Públicas** (Actions/Services) inyectadas en los controladores.


* **Gestión Multi-Tenant:**
* Aislamiento de datos de clientes mediante columna discriminadora `workspace_id` en todas las tablas principales.



---

## 3. Estrategia de Colaboración (RBAC)

La funcionalidad de "Colaboración y Grupos" se integra directamente en el núcleo del sistema de identidad, evitando la duplicidad de lógica.

* **Grupo / Redacción**  Entidad `Workspace`.
* Representa la "publicación" o la entidad legal.


* **Colaborador**  Entidad `Membership`.
* Es la tabla pivote entre `User` y `Workspace`.
* Contiene el atributo `role` (ej: `owner`, `editor`, `writer`), permitiendo que un mismo usuario tenga diferentes niveles de permiso en distintos grupos.



---

## 4. Mapa de Dominios (Bounded Contexts)

Definición de los límites lógicos de la aplicación. Cada dominio debe tener su propia estructura de carpetas dentro de `app/Domains`.

| Dominio | Prefijo Tabla | Responsabilidad (Bounded Context) | Entidades Clave |
| --- | --- | --- | --- |
| **Identity** | `identity_` | **IAM & Organización.** Gestiona el acceso, la seguridad y la estructura organizativa. Incluye Usuarios, Grupos (Workspaces), Colaboradores (Memberships) y la configuración financiera global (Donaciones). | `User`<br>

<br>`Workspace`<br>

<br>`Membership`<br>

<br>`Invitation`<br>

<br>`DonationConfig` |
| **Publishing** | `publishing_` | **CMS (Contenido).** El motor creativo. Gestión del editor, almacenamiento de contenido híbrido (Newsletters y Notas), gestión de biblioteca de medios y metadatos SEO. | `Post` (type: newsletter/note)<br>

<br>`Media`<br>

<br>`Tag`<br>

<br>`PostAuthor` |
| **Community** | `community_` | **Social Layer.** Capa de interacción bidireccional y descubrimiento. Gestiona el Feed global, "Me gusta", comentarios, seguidores y perfiles públicos de creadores. | `Comment`<br>

<br>`Like`<br>

<br>`Follower`<br>

<br>`FeedActivity` |
| **Audience** | `audience_` | **CRM (Lectores).** Gestión pura de la lista de contactos. Importación/Exportación masiva de CSVs, gestión de estados de suscripción y cumplimiento de GDPR (bajas). | `Subscriber`<br>

<br>`ImportJob` |
| **Delivery** | `delivery_` | **MTA (Envíos).** Infraestructura técnica de salida. Conexión con proveedores (Mailgun), gestión de colas de envío, throttling, procesamiento de webhooks de rebote (Bounces) y logs. | `Campaign`<br>

<br>`DeliveryLog`<br>

<br>`Bounce` |

---

## 5. Próximos Pasos de Implementación

Para materializar esta arquitectura, el orden de desarrollo recomendado (Sprints) es:

1. **Infraestructura (FRT-1):** Setup de Docker, Laravel 12 y limpieza de Breeze.
2. **Dominio Identity (FRT-2):** Implementación de Migraciones para `identity_users`, `identity_workspaces` y `identity_memberships`. Esto habilita el login y la creación de grupos.
3. **Dominio Publishing:** Implementación de la tabla `publishing_posts` con soporte JSONB para el editor.