# CURRENT_STATE

## Fecha de corte

17 de marzo de 2026.

Este documento describe el estado real del repositorio hoy: avance funcional, brechas tecnicas y prioridades inmediatas.

## 0. Actualizacion Incremental (17-03-2026)

- fecha: 17 de marzo de 2026
- que cambio realmente en codigo:
	- se completo la implementacion del modulo `publishing` en capa de persistencia y validacion focalizada
	- modelos corregidos/completados: `Post`, `PostVersion`, `Tag`, `Media`
	- provider actualizado: `PublishingServiceProvider` ahora registra migraciones del modulo
	- factories creadas: `PostFactory`, `PostVersionFactory`, `TagFactory`, `MediaFactory`
	- tests de publishing implementados: provider + modelos (`Post`, `Tag`, `PostVersion`, `Media`)
- validacion ejecutada:
	- `vendor/bin/pint --dirty --format agent` -> pass
	- `php artisan test --compact app-modules/publishing/tests/Feature/Models/PostVersionTest.php app-modules/publishing/tests/Feature/Models/MediaTest.php` -> 5 passed (6 assertions)
	- `php artisan test --compact app-modules/publishing/tests/Feature/Providers/PublishingServiceProviderTest.php app-modules/publishing/tests/Feature/Models/PostTest.php app-modules/publishing/tests/Feature/Models/TagTest.php` -> 11 passed (15 assertions)
- que riesgos se cerraron:
	- cierre de bugs de integridad en `publishing` (naming de tabla, FKs, pivots, scopes, class rename `PostVersion`)
	- cierre del gap de bootstrapping de migraciones en `publishing`
	- cierre de tests `TODO` del provider de `publishing` y cobertura funcional minima de modelos/factories
- que riesgos nuevos aparecieron:
	- no se detectaron riesgos nuevos bloqueantes para `publishing`
	- cobertura aun enfocada a capa de persistencia; quedan pendientes pruebas de casos limite e integracion HTTP cuando entren controladores/casos de uso

## 1. Resumen Ejecutivo

Freetter tiene direccion de producto y arquitectura bien definida en `.context`, pero la implementacion esta incompleta y heterogenea entre modulos.

Estado general:

- `activity`: modulo mas avanzado, aun con inconsistencias de hardening
- `identity`: base funcional parcial con desajustes entre migraciones, modelos y factories
- `publishing`: estructura inicial creada, con errores de integridad en esquema y relaciones
- `audience`, `community`, `delivery`: fase de scaffolding

## 2. Inventario Objetivo Por Modulo

Conteo de artefactos en codigo (no implica calidad ni completitud):

| Modulo | Migraciones | Modelos | Providers | Archivos de rutas | Tests |
| --- | ---: | ---: | ---: | ---: | ---: |
| `identity` | 4 | 4 | 1 | 1 | 1 |
| `publishing` | 6 | 4 | 1 | 1 | 1 |
| `activity` | 3 | 3 | 1 | 1 | 2 |
| `audience` | 0 | 0 | 1 | 1 | 1 |
| `community` | 0 | 0 | 1 | 1 | 1 |
| `delivery` | 0 | 0 | 1 | 1 | 1 |

Otros indicadores:

- controladores de modulo: `0`
- tests en `tests/` raiz: `2`

## 3. Hallazgos Criticos Actuales

### 3.1 Esquema y persistencia

- existen inconsistencias entre nombres de tabla definidos en docs y nombres realmente creados en migraciones (plural vs singular)
- en `publishing` hay FKs apuntando a tablas incorrectas (`workspace`, `users`, `identity_workspace`)
- pivots de `publishing` con defectos de naming y tipo (`publishing__post_media`, `tag_id` bigint contra PK uuid)
- `identity_invitations` no tiene `accepted_at`, pero `Invitation` y su factory si lo usan
- rol `writer` esta en use cases/factories pero no en enum de `identity_invitations`

### 3.2 Bootstrapping de modulos

- solo `ActivityServiceProvider` intenta cargar migraciones, con ruta incorrecta
- providers de `identity`, `publishing`, `audience`, `community`, `delivery` estan vacios
- todos los archivos de rutas de modulo estan comentados

### 3.3 Capa de aplicacion y HTTP

- no hay controladores en `app-modules/*/src/Http/Controllers`
- predominan placeholders y clases sin casos de uso aplicados

### 3.4 Calidad y pruebas

- tests de providers en todos los modulos estan en `TODO`
- cobertura orientada a casos de uso es insuficiente para el alcance documentado

## 4. Estado De La Integracion AI

- `laravel/ai` esta instalado y funcional a nivel SDK
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
