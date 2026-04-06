# CURRENT_STATE

## Fecha de corte

17 de marzo de 2026.

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

## 1. Resumen Ejecutivo

Freetter tiene dirección de producto y arquitectura bien definida en `.context`, pero la implementación está incompleta y heterogénea entre módulos.

Estado general:

- `activity`: módulo más avanzado, aun con inconsistencias de hardening
- `identity`: base funcional parcial con desajustes entre migraciones, modelos y factories
- `publishing`: estructura inicial creada, con errores de integridad en esquema y relaciones
- `audience`, `community`, `delivery`: fase de scaffolding

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
