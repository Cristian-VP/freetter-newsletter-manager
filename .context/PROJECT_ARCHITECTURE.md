# PROJECT ARCHITECTURE

## 1. Proposito

Este documento define las reglas estructurales de Freetter para mantener coherencia arquitectonica mientras el producto evoluciona.

No describe avance ni deuda tecnica puntual; ese rol corresponde a `CURRENT_STATE.md`.

## 2. Decision Arquitectonica Principal

Freetter adopta un monolito modular en Laravel 12 con bounded contexts en `app-modules/`.

Principios de esta decision:

- despliegue unico y operacion simple para un equipo pequeno
- separacion por dominio funcional en lugar de separacion por capas globales
- posibilidad de evolucion incremental sin reescritura total

## 3. Modulos y Ownership

Los modulos actuales y su ownership de negocio son:

- `identity`: usuarios, workspaces, membresias, invitaciones, control de pertenencia
- `publishing`: posts, versiones, media, taxonomia y ciclo editorial
- `audience`: suscriptores, consentimientos, importaciones y estado de suscripcion
- `community`: comentarios, likes y follows
- `delivery`: campanas, envio, bounces y telemetria de entrega
- `activity`: auditoria inmutable, stream de actividad y alertas de anomalias

Regla obligatoria:

- la logica de negocio vive en el modulo dueno del concepto
- no mover logica entre modulos sin justificacion explicita de frontera de dominio

## 4. Regla de Integracion Entre Dominios

Integracion preferida: eventos de dominio o aplicacion + listeners.

Integracion no permitida por defecto:

- usar modelos de otro dominio para ejecutar side effects de negocio

Ejemplo esperado:

- `publishing` emite evento de publicacion
- `activity` registra auditoria como reaccion
- `delivery` decide si crea campana segun tipo de contenido

Referencia de patron: `DDD_EVENTS_ARCHITECTURE_ANALYSIS.md`.

## 5. Capas Esperadas Dentro De Cada Modulo

Cada modulo debe evolucionar hacia estas capas:

- entrada: rutas/controladores/commands
- aplicacion: acciones/casos de uso/servicios de orquestacion
- dominio: reglas de negocio, politicas y eventos
- persistencia: modelos, relaciones, factories y migraciones
- validacion: tests de comportamiento y regresion

Regla:

- la logica de negocio no debe quedar incrustada en migraciones, rutas o controladores CRUD genericos

## 6. Contratos Tecnicos Transversales

Convenciones de arquitectura de datos:

- identificadores UUID en entidades principales
- JSONB cuando el dominio requiere contenido estructurado flexible
- claves e indices definidos por patron de consulta, no por intuicion
- consistencia de nombres entre migracion, modelo, factory y caso de uso

Convenciones de desarrollo:

- cambios pequenos, locales y reversibles
- tests focalizados en el comportamiento afectado
- trazabilidad de decisiones en PR o nota tecnica

## 7. Gobernanza De Cambios

Antes de implementar:

1. leer `USE_CASES.md` del dominio afectado
2. validar esquema objetivo en `ENTITIES.md`
3. revisar reglas de integracion en `DDD_EVENTS_ARCHITECTURE_ANALYSIS.md`
4. comprobar estado real en `CURRENT_STATE.md`

Durante implementacion:

- preferir contratos explicitos sobre dependencias implicitas
- no introducir infraestructura runtime de agentes/A2A salvo solicitud explicita
- no modificar dependencias del proyecto sin aprobacion

## 8. Uso De Laravel Boost

Laravel Boost es obligatorio como soporte tecnico para cambios no triviales en Laravel:

- consultar docs versionadas antes de aplicar patrones de framework
- usar herramientas de esquema, logs y diagnostico cuando aplique
- ajustar decisiones a Laravel 12 y paquetes instalados en este repo

Boost no reemplaza las reglas de dominio; las operacionaliza.

## 9. Limites Del Documento

Este archivo no contiene:

- estado de avance por modulo
- lista de gaps activos
- roadmap de estabilizacion

Esa informacion se mantiene en `CURRENT_STATE.md` para evitar duplicidades.
