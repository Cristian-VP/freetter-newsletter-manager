## Sprint 1: Navegación Real + Subscripciones MVP
Completas
FRT-19 - Contrato de navegación autenticada
Descripción: Convertir Home, Subscripciones, Crear Newsletter y Perfil de estado local a navegación real por rutas, manteniendo paridad móvil y desktop.
FRT-20 - Endpoint de listado de subscripciones del usuario
Descripción: Exponer endpoint para workspaces seguidos por el usuario autenticado con metadatos mínimos para render de lista.
FRT-21 - Pantalla Subscripciones en Inertia
Descripción: Implementar vista de subscripciones con estados loading, vacío y error, reutilizando patrones de Home.

Pendientes
FRT-22 - Acción dejar de seguir desde Subscripciones
Descripción: Integrar unfollow con actualización optimista y rollback en error.
FRT-23 - Acciones secundarias de item en Subscripciones
Descripción: Añadir copiar enlace y compartir por item con fallback web.
FRT-24 - Validación Sprint 1
Descripción: Ejecutar pruebas backend focalizadas, build frontend y smoke test de navegación y subscripciones en mobile y desktop.

## Sprint 2: Perfil Read-Only Productivo

FRT-25 - Ruta y controlador de perfil público por workspace slug
Descripción: Exponer ruta pública de perfil con resolución por workspace y fallback 404 consistente.
FRT-26 - Endpoint de payload público de perfil
Descripción: Devolver cabecera pública y listado paginado de newsletters publicadas para cualquier visitante.
FRT-27 - Pantalla de perfil público
Descripción: Implementar vista pública con cabecera y listado de newsletters, incluyendo estados loading, vacío y error.
FRT-28 - Zona privada del owner en perfil
Descripción: Mostrar secciones privadas solo cuando el visitante es dueño del perfil, sin exposición a terceros.
FRT-29 - Preferencias de privacidad del perfil
Descripción: Permitir configurar visibilidad de bloques privados y preparar flags de extensión futura.
FRT-30 - Validación del sprint de perfil
Descripción: Pruebas de autorización y visibilidad por rol de visitante (anónimo, autenticado no owner, owner), más build frontend y smoke tests.

## Sprint 3: Crear Newsletter como Índice Editorial

FRT-30 - Contrato backend del índice editorial
Descripción: Definir endpoint para listar newsletters por estado draft, scheduled y published con filtros y paginación.
FRT-31 - Pantalla Crear Newsletter por estado
Descripción: Construir vista editorial con pestañas por estado y acciones principales de crear nueva o abrir existente.
FRT-32 - Crear draft de newsletter
Descripción: Crear flujo de alta de draft inicial y redirección al editor.
FRT-33 - Apertura de newsletter existente en editor
Descripción: Permitir abrir draft, scheduled o published según reglas de estado.
FRT-34 - Validación Sprint 3
Descripción: Validar rutas editoriales y regresión de Home, Perfil y Subscripciones.
Sprint 4: Newsletter Builder MVP

FRT-35 - Contrato de guardado incremental de newsletter
Descripción: Exponer endpoint para guardar cambios de draft sin publicar, con versionado mínimo.
FRT-36 - Builder MVP bloque Header, Image y Divider
Descripción: Implementar primeros bloques con configuración básica y persistencia.
FRT-37 - Builder MVP bloque Button y Signup
Descripción: Implementar bloques de CTA y Signup con opciones de texto, enlace, alineación y estilo base.
FRT-38 - Publicar newsletter desde builder
Descripción: Integrar publicación desde builder y transición de draft a published.
FRT-39 - Programar newsletter desde builder
Descripción: Integrar programación y transición a scheduled cuando el contrato esté disponible.
FRT-40 - Validación Sprint 4
Descripción: Probar flujo end-to-end de crear, guardar, programar y publicar newsletter.
Sprint 5: Hardening UI + QA de Release

FRT-41 - Refuerzo de estados UX transversales
Descripción: Estandarizar skeletons, errores y empty states en Home, Subscripciones, Perfil y Crear Newsletter.
FRT-42 - Accesibilidad y navegación por teclado
Descripción: Revisar foco, aria-labels y navegación de menús, tabs y modales.
FRT-43 - Pruebas de regresión cruzada
Descripción: Ejecutar pruebas feature y smoke manual end-to-end del flujo autenticado completo.
FRT-44 - Actualización documental de release
Descripción: Sincronizar CURRENT_STATE y CHANGELOG con entregables FRT-19 a FRT-43 y riesgos residuales.

Quiero implementar el Jira FRT-21 - Pantalla Subscripciones en Inertia
Descripción: Implementar vista de subscripciones con estados loading, vacío y error, reutilizando patrones de Home.


