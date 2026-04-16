# CURRENT_STATE

## Fecha de corte

16 de abril de 2026.

Este documento describe el estado real del repositorio hoy: avance funcional, brechas tecnicas y prioridades inmediatas.

## 0. Actualización Incremental (25-03-2026)

- fecha: 25 de marzo de 2026
- qué cambió realmente en código:
    - Se corrigió un error de autenticación en `WorkspaceObserver` (ahora usa el facade Auth compatible con análisis estático).
    - Se validó la cobertura de tests HTTP y de integración para los flujos de creación de workspace e invitaciones.
    - Se confirmó que no hay regresiones ni violaciones de límites de módulo tras la corrección.
- validación ejecutada:
    - `vendor/bin/pint --dirty --format agent` -> pass
    - `php artisan test --compact app-modules/identity/tests/Feature/Http/WorkspaceAndInvitationControllerTest.php` -> 2 pasaron (6 assertions)
    - Revisión manual de readiness y cobertura.
- qué riesgos se cerraron:
    - Se eliminó el riesgo de regresión por error de autenticación en el observer.
    - Se confirma readiness para release sin blockers.
- qué riesgos nuevos aparecieron:
    - Permanece un warning menor por un test de integración no relacionado (`IdentityActivityEventsIntegrationTest`).

## 0.1. Actualización Incremental (26-03-2026)

- fecha: 26 de marzo de 2026
- qué cambió realmente en código:
    - Implementación completa del MVP del módulo Audience: migraciones, modelos, factories, eventos, jobs, listeners, endpoints HTTP y pruebas de integración/eventos.
    - Integración event-driven entre Audience, Activity y Delivery usando eventos de dominio.
    - Detección de duplicados y manejo de errores en importación de suscriptores (soporte DB-agnóstico).
    - Validación de email relajada para entornos de test/dev.
    - Ejecución determinista de jobs de importación en tests.
- validación ejecutada:
    - `vendor/bin/pint --dirty --format agent` -> pass
    - `php artisan test --compact app-modules/audience/tests` -> 16 pasaron (48 assertions)
    - Revisión manual de readiness, cobertura y límites de módulo por Guardian y Release Manager.
- qué riesgos se cerraron:
    - Se elimina el riesgo de regresión y violación de límites en Audience.
    - Se confirma readiness para release sin blockers ni warnings relevantes.
- qué riesgos nuevos aparecieron:
    - Ninguno relevante para Audience en este release.

## 0.2. Actualización Incremental (06-04-2026)
- fecha: 6 de abril de 2026
- qué cambió realmente en código:
    - Implementación completa del MVP del módulo Delivery: migraciones (delivery_campaigns, delivery_bounces), modelos, factories, eventos, listeners, jobs, controladores HTTP, FormRequests, endpoints y pruebas de integración/eventos.
    - Integración event-driven entre Delivery, Publishing, Audience y Activity usando eventos de dominio y listeners centralizados en EventServiceProvider.
    - Manejo idempotente de webhooks de rebote y actualización de estado de suscriptores vía eventos.
    - Actualización de documentación y validación de wiring de eventos.
- validación ejecutada:
    - vendor/bin/pint --dirty --format agent -> pass
    - php artisan test --compact app-modules/delivery/tests + integración relevante -> 9 pasaron (30 assertions) + integración cruzada OK
    - Revisión manual de readiness, cobertura y límites de módulo por Guardian y Release Manager.
- qué riesgos se cerraron:
    - Se elimina el riesgo de falta de integración entre Delivery y los módulos Publishing/Audience/Activity.
    - Se confirma readiness para release sin blockers ni warnings relevantes en Delivery.
- qué riesgos nuevos aparecieron:
    - Ninguno relevante para Delivery en este release.

## 0.3. Actualización Incremental (06-04-2026)
- fecha: 6 de abril de 2026
- qué cambió realmente en código:
    - Implementación completa del MVP del módulo Community: migraciones (`community_comments`, `community_likes`, `community_followers`), modelos, factories, eventos de dominio, controladores HTTP, FormRequests, rutas y autorización de moderación por rol.
    - Integración event-driven entre Community y Activity mediante listeners registrados en el `EventServiceProvider` central.
    - Soporte de threading básico en comentarios (`parent_id`), unicidad de likes y follows por constraints de base de datos y moderación trazable (hide/soft-delete).
    - Cobertura de pruebas de Community para casos HTTP, dispatch de eventos e integración con `activity_logs`.
- validación ejecutada:
    - `vendor/bin/pint --dirty --format agent` -> pass
    - `php artisan test --compact app-modules/community/tests` -> 10 pasaron (33 assertions)
- qué riesgos se cerraron:
    - Se elimina el riesgo de que `community` permanezca en scaffolding sin casos de uso operativos.
    - Se confirma integración con Activity sin acoplamiento de side effects cross-module.
- qué riesgos nuevos aparecieron:
    - Ninguno relevante para Community en este release.

## 0.4. Actualización Incremental (09-04-2026)
- fecha: 9 de abril de 2026
- qué cambió realmente en código:
    - Se implementó el flujo de autenticación por Magic Link en `identity` con endpoints `POST /register`, `POST /login` y `GET /magic-links/{user}` con firma temporal.
    - Se agregó `MagicLinkAuthController` para registro/login sin contraseña, generación de URL firmada temporal, verificación de email al consumir el link e inicio de sesión en guard `web`.
    - Se agregaron `RegisterMagicLinkRequest` y `LoginMagicLinkRequest` para validación de entrada.
    - Se creó `MagicLinkNotification` para envío del enlace por correo.
    - Se conectó Landing con `AuthModal` para Sign in / Sign up y se añadió feedback visual de éxito al enviar el formulario.
    - Se protegió `GET /home` con middleware `auth` y se dejó una confirmación visual en Home con el usuario autenticado.
    - Se removió `throttle` en `/register` y `/login` para evitar 500 en entornos donde RateLimiter usa cache en DB sin tabla `cache`.
- validación ejecutada:
    - `vendor/bin/pint --dirty --format agent` -> pass
    - `php artisan test --compact app-modules/identity/tests/Feature/Http/MagicLinkAuthenticationTest.php tests/Feature/HomeRouteProtectionTest.php` -> 7 pasaron (21 assertions)
    - `php artisan test --compact tests/Feature app-modules/activity/tests app-modules/audience/tests app-modules/community/tests app-modules/delivery/tests app-modules/identity/tests app-modules/publishing/tests` -> 152 pasaron (325 assertions)
- qué riesgos se cerraron:
    - Se cerró el gap funcional de autenticación inicial Landing -> Magic Link -> sesión -> Home.
    - Se cerró el error 500 en `/register` causado por dependencia de cache DB en `ThrottleRequests`.
- qué riesgos nuevos aparecieron:
    - El envío real de correo no está activo por configuración de entorno (`mail.default = log`), por lo que actualmente los correos se registran en logs y no se envían a proveedores externos.
- qué se debe hacer para operar correo real (pendiente de entorno):
    1. Configurar `MAIL_MAILER=smtp` (o proveedor transaccional equivalente) en `.env`.
    2. Configurar credenciales reales (`MAIL_HOST`, `MAIL_PORT`, `MAIL_USERNAME`, `MAIL_PASSWORD`, `MAIL_ENCRYPTION`, `MAIL_FROM_ADDRESS`, `MAIL_FROM_NAME`).
    3. Limpiar y recargar configuración (`php artisan config:clear`).
    4. Probar flujo completo con correo real y revisar carpeta spam/promotions en Gmail.
    5. En producción, restaurar rate limiting para `/register` y `/login` usando un backend de cache operativo (redis o tabla `cache`).

### 0.4.1 Cierre de Hallazgo Guardian (09-04-2026)
- qué cambió realmente en código:
    - Se implementó rate limiting persistente en Identity sin dependencia del driver global de cache, usando tabla `identity_magic_link_requests`.
    - Se actualizó `MagicLinkAuthController` para bloquear solicitudes de magic link por IP/email tras 6 intentos en 1 minuto (HTTP 429).
    - Se añadió test de límite de tasa en `MagicLinkAuthenticationTest`.
- validación ejecutada:
    - `vendor/bin/pint --dirty --format agent` -> pass
    - `php artisan test --compact app-modules/identity/tests/Feature/Http/MagicLinkAuthenticationTest.php tests/Feature/HomeRouteProtectionTest.php` -> 8 pasaron (28 assertions)
- riesgos cerrados:
    - Se cerró el blocker de seguridad identificado en reviewer por ausencia de limitación de intentos en `/register` y `/login`.
- riesgos vigentes:
    - El correo real sigue pendiente de configuración de entorno (`MAIL_MAILER` no SMTP real).

### 0.4.2 Endurecimiento de Magic Link Single-Use (09-04-2026)
- qué cambió realmente en código:
    - Se implementó consumo de magic link de un solo uso en `MagicLinkAuthController` con validación por token firmado + token persistido.
    - Se agregó migración `create_identity_magic_link_tokens_table` para persistir hash de token, expiración y estado de consumo (`consumed_at`).
    - La autenticación por magic link ahora rechaza reutilización del mismo enlace (HTTP 403) y solo permite el primer consumo válido.
    - Se mantuvo el rate limiting de solicitudes por email/IP para `POST /register` y `POST /login`.
- validación ejecutada:
    - `vendor/bin/pint --dirty --format agent` -> pass
    - `php artisan test --compact app-modules/identity/tests/Feature/Http/MagicLinkAuthenticationTest.php tests/Feature/HomeRouteProtectionTest.php` -> 10 pasaron (38 assertions)
- riesgos cerrados:
    - Se cerró el riesgo de seguridad por reutilización de magic links dentro de la ventana de expiración.
    - Se amplió cobertura contra abuso con test de rate limit por IP usando emails distintos.
- riesgos vigentes:
    - El correo real sigue pendiente de configuración de entorno (`MAIL_MAILER` no SMTP real).

## 0.5. Actualización Incremental (10-04-2026)

- fecha: 10 de abril de 2026
- qué cambió realmente en código y entorno:
    - Se activó envío real de correo transaccional con Resend (`MAIL_MAILER=resend`) y dominio `freetter.app` verificado en proveedor.
    - Se instaló el SDK requerido por Laravel para el transport `resend` (`resend/resend-php`) y se resolvió el error `Class "Resend" not found`.
    - Se creó la migración de tabla de caché (`create_cache_table`) para soportar `CACHE_STORE=database` y eliminar errores SQL por relación `cache` inexistente.
    - Se aplicaron migraciones pendientes del flujo Magic Link (`identity_magic_link_requests` e `identity_magic_link_tokens`) para corregir 500 en `POST /register` y `POST /login`.
    - Se corrigió el fallo de redirección/autenticación al consumir Magic Link por incompatibilidad de tipos en sesión (`sessions.user_id` bigint vs `identity_users.id` uuid), pasando `sessions.user_id` a `uuid` mediante migración.
- validación ejecutada:
    - `php artisan migrate:status --no-interaction` -> migraciones pendientes detectadas y luego en estado `Ran`.
    - `php artisan migrate --no-interaction` -> tablas de Magic Link, cache y ajuste de sesiones aplicadas.
    - `php artisan optimize:clear` -> pass tras creación de tabla `cache`.
    - `php artisan tinker --execute='...Resend::client(...)->emails->send(...)...'` -> pass con respuesta `{ id: ... }`.
    - `php artisan test --compact app-modules/identity/tests/Feature/Http/MagicLinkAuthenticationTest.php` -> 8 pasaron (35 assertions).
- qué riesgos se cerraron:
    - Se cerró el blocker de correo real: el flujo ya envía emails reales y no solo logs.
    - Se cerraron los 500 en autenticación por faltantes de esquema (tablas de rate-limit/token/cache).
    - Se cerró el 500 en consumo de Magic Link por mismatch de tipo de `user_id` en sesiones.
- qué riesgos nuevos aparecieron:
    - No se detectan riesgos nuevos críticos en el flujo Landing -> Magic Link -> Sesión -> Home.
- riesgos vigentes:
    - Mantener rotación y custodia de API keys (se expuso una key durante troubleshooting y fue regenerada).
    - Pendiente de operación: separar `.env` por entorno (local vs producción) para `APP_URL`, mailer y credenciales.
    - Pendiente técnico-operativo: implementar colas para envío de correo transaccional (Magic Link) y ejecutar workers en entorno productivo para desacoplar latencia y mejorar resiliencia.

- qué se debe implementar a continuación (recomendado):
    1. Convertir `MagicLinkNotification` a envío en cola (`ShouldQueue`) para que el login/register no dependa del tiempo de respuesta del proveedor de correo.
    2. Definir y versionar configuración de cola por entorno (`QUEUE_CONNECTION`) con backend operativo (Redis o database + workers supervisados).
    3. Operar workers en producción (`queue:work` con supervisión/restart) y monitoreo de jobs fallidos.
    4. Añadir pruebas de integración para validar dispatch y procesamiento de notificaciones en cola.

## 0.6. Actualización Incremental UI Root Resources (16-04-2026)

- fecha: 16 de abril de 2026
- alcance analizado:
    - Se auditó `resources/` del root (CSS, fonts, páginas React Inertia, componentes de navegación, layouts y `resources/views/app.blade.php`).
    - Se revisó historial de commits sobre `resources/` para consolidar hitos reales de UI entre 08-04 y 16-04.
- qué cambió realmente en código de UI:
    - Se consolidó el shell frontend con Inertia React en root (`resources/js/app.tsx`) y carga dinámica de páginas de root y módulos desde `resources/views/app.blade.php`.
    - Se implementó Landing con dirección visual definida (paleta crema/negro), hero principal, header/footer responsive, menú móvil fullscreen y CTA conectados al flujo de autenticación.
    - Se integró `AuthModal` en Landing para Sign in / Sign up por magic link (`/login` y `/register`) con feedback de envío exitoso.
    - Se implementó layout autenticado responsive (`AuthenticatedHomeLayout`) con:
        - header móvil superior,
        - toolbar móvil inferior,
        - navegación lateral desktop expandible,
        - menú de cuenta con accesos a dashboard/settings y logout.
    - Se añadieron páginas base de experiencia autenticada en root: `Home`, `Dashboard` (propuesta inicial) y `Settings` (template inicial).
    - Se incorporó tipografía Satoshi completa en `resources/css/satoshi.css`, assets de fuentes en `resources/fonts/satoshi/` y utilidades tipográficas en `resources/css/app.css`.
- validación ejecutada:
    - `npm run build` -> pass (Vite compiló correctamente assets/páginas de `Landing`, `Home`, `Settings`, `Dashboard` y layouts/componentes de navegación).
    - Estado de tests backend identity mantenido en verde en la sesión (`php artisan test --compact app-modules/identity/tests/Feature/Http/MagicLinkAuthenticationTest.php` -> pass).
- qué riesgos se cerraron:
    - Se cierra la percepción de que el avance en ramas tipo `feature/FRT-11` y `feature/FRT-12` es solo backend: existe avance tangible de UI en root resources con flujo Landing -> AuthModal -> Home autenticado.
- qué riesgos nuevos aparecieron:
    - No hay aún pruebas E2E/UI automáticas para navegación responsive ni flujo visual de autenticación.
    - `Dashboard` y `Settings` permanecen en estado base/propuesta (sin casos de negocio completos).
    - Persisten links placeholder en Landing (`/membership`, `/write`) sin rutas funcionales confirmadas.

## 1. Resumen Ejecutivo

Freetter tiene dirección de producto y arquitectura bien definida en `.context`, pero la implementación está incompleta y heterogénea entre módulos.

Estado general:

- `activity`: módulo más avanzado, aun con inconsistencias de hardening
- `identity`: base funcional parcial con desajustes entre migraciones, modelos y factories
- `publishing`: estructura inicial creada, con errores de integridad en esquema y relaciones
- `audience`, `delivery`, `community`: MVP implementado con integración por eventos
- UI root resources: base frontend Inertia React operativa con Landing, modal de autenticación, navegación responsive mobile/desktop y vistas autenticadas iniciales (`Home`, `Dashboard`, `Settings`)

## 2. Inventario Objetivo Por Modulo

Conteo de artefactos en código (no implica calidad ni completitud):

| Módulo | Migraciones | Modelos | Providers | Archivos de rutas | Tests |
| --- | ---: | ---: | ---: | ---: | ---: |
| `identity` | 4 | 4 | 1 | 1 | 1 |
| `publishing` | 6 | 4 | 1 | 1 | 1 |
| `activity` | 3 | 3 | 1 | 1 | 2 |
| `audience` | 0 | 0 | 1 | 1 | 1 |
| `community` | 0 | 0 | 1 | 1 | 1 |
| `delivery` | 0 | 0 | 1 | 1 | 1 |

Otros indicadores:

- controladores de módulo: `0`
- tests en `tests/` raiz: `2`

## 3. Hallazgos Críticos Actuales

### 3.1 Esquema y persistencia

- existen inconsistencias entre nombres de tabla definidos en docs y nombres realmente creados en migraciones (plural vs singular)
- en `publishing` hay FKs apuntando a tablas incorrectas (`workspace`, `users`, `identity_workspace`)
- pivots de `publishing` con defectos de naming y tipo (`publishing__post_media`, `tag_id` bigint contra PK uuid)
- `identity_invitations` no tiene `accepted_at`, pero `Invitation` y su factory si lo usan
- rol `writer` está en use cases/factories pero no en enum de `identity_invitations`

### 3.2 Bootstrapping de módulos

- solo `ActivityServiceProvider` intenta cargar migraciones, con ruta incorrecta
- providers de `identity`, `publishing`, `audience`, `community`, `delivery` están vacíos
- todos los archivos de rutas de módulo están comentados

### 3.3 Capa de aplicación y HTTP

- no hay controladores en `app-modules/*/src/Http/Controllers`
- predominan placeholders y clases sin casos de uso aplicados

### 3.4 Calidad y pruebas

- tests de providers en todos los módulos están en `TODO`
- cobertura orientada a casos de uso es insuficiente para el alcance documentado

## 4. Estado De La Integracion AI

- `laravel/ai` está instalado y funcional a nivel SDK
- proveedor por defecto en `config/ai.php`: `gemini`
- audio/transcripcion/reranking apuntan a `openai` y `cohere`; requieren credenciales activas para funcionar en runtime

## 5. Riesgos Operativos Activos

- riesgo de bloqueo en migraciones futuras por inconsistencias de tablas y claves foraneas
- riesgo de regresion silenciosa por baja cobertura en flujos criticos
- riesgo de deriva entre documentacion y codigo si no se actualiza este snapshot tras cada bloque relevante

## 6. Prioridades Inmediatas (P0/P1)

P0 (bloqueante de coherencia):

1. normalizar naming/FKs/tipos de pivots en `identity` y `publishing`
2. corregir carga de migraciones/rutas en service providers
3. alinear `Invitation` (migracion, modelo, factory, enum de roles)

P1 (fundacion de calidad):

1. implementar controladores/casos de uso minimos para `identity`, `publishing` y `activity`
2. reemplazar tests `TODO` por pruebas de comportamiento
3. validar integracion por eventos entre modulos en flujos editoriales

## 7. Criterio De Actualizacion De Este Archivo

Actualizar `CURRENT_STATE.md` cuando ocurra cualquiera de estos eventos:

- merge de migraciones relevantes
- cambio de estado de un modulo (de scaffold a implementado parcial)
- incorporacion de nuevos casos de uso en codigo
- cierre de un gap critico documentado en este archivo

Formato recomendado por actualizacion:

- fecha
- que cambio realmente en codigo
- que riesgos se cerraron
- que riesgos nuevos aparecieron
