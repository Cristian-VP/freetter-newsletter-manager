# Freetter Copilot Instructions

Este workspace usa agentes personalizados para orquestar trabajo de desarrollo en VS Code.

## Scope

- El alcance de estos agentes es solo desarrollo.
- No diseñar ni introducir agentes runtime en Laravel, servidores A2A, ni orquestacion en produccion salvo peticion explicita del usuario.
- No asumir que A2A de desarrollo implica protocolo A2A runtime.

## Source Of Truth

- Priorizar [AGENTS.md](../AGENTS.md) como guia de trabajo del repositorio.
- Revisar `/.context/PROJECT_ARCHITECTURE.md`, `/.context/USE_CASES.md` y `/.context/ENTITIES.md` antes de implementar cambios funcionales.
- Verificar reglas de negocio y lenguaje del dominio en `/.context` antes de proponer cambios de arquitectura.
- Mantener cambios minimos, orientados al modulo correcto y validados.

## Laravel Boost

- Para escribir o modificar codigo Laravel, usar Laravel Boost como soporte principal de documentacion y diagnostico.
- Consultar Boost antes de aplicar patrones Laravel no triviales o cambios de arquitectura tecnica.

## Development Workflow

- `freetter-planner` define el plan y los riesgos.
- `freetter-implementer` ejecuta el plan aprobado con cambios minimos y validaciones.
- `freetter-guardian` revisa regresiones, desviaciones de arquitectura y cobertura.
- `freetter-release-manager` comunica readiness, riesgos y bloqueos para que la decision final sea del usuario.

## Skills

Los siguientes Skills están disponibles y deben activarse cuando corresponda:

- `laravel-php` — Patrones Laravel 12 / PHP 8.4 para el monolito modular de Freetter. Activa al crear o editar modelos, migraciones, providers, rutas, factories, o cualquier archivo PHP/Laravel.
- `phpunit-testing` — Patrones de testing PHPUnit para Freetter. Activa al escribir, revisar o ejecutar tests, o cuando se mencione cobertura, assertions, feature tests o fallos de test.
- `postgresql-code-review` — Revisión de código PostgreSQL. Activa al revisar migraciones, schema changes, queries, o cualquier código relacionado con la base de datos.
- `postgresql-optimization` — Optimización de queries y schema PostgreSQL. Activa al analizar rendimiento, índices, o diseño de queries.
- `conventional-commit` — Mensajes de commit convencionales. Activa al hacer commit, escribir un mensaje de commit o pushear trabajo.
- `tailwindcss-development` — Estilos con Tailwind CSS v4. Activa al trabajar con UI, estilos, componentes visuales o clases CSS.
- `gh-cli` — GitHub CLI para operaciones de repositorio, PRs, releases.
- `git-flow-branch-creator` — Crear ramas siguiendo el modelo Git Flow.
- `github-issues` — Crear y gestionar issues en GitHub.
- `agent-governance` — Patrones de gobernanza para agentes (allow/deny de tools, filtros de contenido, límites de llamadas, auditoría y niveles de control). Activa al definir o revisar políticas de seguridad y límites operativos de agentes.
- `agentic-eval` — Patrones de evaluación iterativa con límites de iteración y criterios de convergencia para evitar bucles infinitos. Activa cuando un agente itera demasiado y hay que imponer stop conditions y evaluación por criterios.
- `agent-customization` — Crear o editar archivos de personalización de agentes (`.instructions.md`, `.prompt.md`, `SKILL.md`, `copilot-instructions.md`).

## Guardrails

- Respetar la arquitectura modular en `app-modules/`.
- No mover logica entre dominios sin justificar limites y dependencias.
- Proponer y ejecutar pruebas enfocadas cuando el cambio lo requiera.
- Explicar hallazgos y riesgos antes de sugerir cambios grandes de estructura.
