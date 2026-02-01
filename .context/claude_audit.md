AUDITORÍA TÉCNICA DEL PROYECTO FREETTER
Auditor: Development Manager
Fecha: 2 de noviembre de 2025
Proyecto: Freetter - Plataforma Newsletter Open Source
Alumno: Cristian Andrés Vacacela Procel
PRA: Miquel Antoni Capellà Arrom

📋 RESUMEN EJECUTIVO
Valoración General: 8.5/10 ✅
Freetter es un proyecto sólido y bien fundamentado que demuestra madurez técnica y visión estratégica. La propuesta combina arquitectura moderna, responsabilidad social y un alcance realista para un TFG. La documentación es exhaustiva y profesional.
Puntos Fuertes:

Arquitectura modular bien justificada
Enfoque ético diferenciador (carbon tracking, AGPLv3, sin comisiones)
Stack tecnológico apropiado y actualizado
Documentación técnica de alta calidad
Mitigación proactiva de riesgos

Áreas de Mejora:

Algunas entidades podrían refinarse para evitar complejidad futura
Falta claridad en ciertos flujos de autorización
Algunas decisiones de arquitectura podrían simplificarse aún más para el MVP


🏗️ AUDITORÍA DE ARQUITECTURA
✅ Decisiones Acertadas
1. Monolito Modular con Laravel + Inertia.js
Justificación: Excelente para un TFG con recursos limitados (2GB RAM). Evita la complejidad de microservicios y APIs REST separadas.
Recomendación: Mantener esta arquitectura. Es escalable mediante "Vertical Scaling" antes de requerir distribución.
2. PostgreSQL con JSONB
Justificación: Perfecta para el contenido de Editor.js. Permite flexibilidad sin sacrificar integridad relacional.
⚠️ Precaución: Asegúrate de indexar correctamente los campos JSONB que se consulten frecuentemente (ej: content->>'title').
3. Redis para Colas y Caché
Justificación: Apropiado. Configurar AOF es fundamental.
Recomendación Crítica: Documentar el plan de contingencia si Redis cae:

¿Fallback a sync driver?
¿Alertas de monitorización?


📊 AUDITORÍA DEL MODELO DE DATOS
Dominio: IDENTITY
✅ Entidades Correctas
1. identity_users

Decisión acertada: No almacenar contraseñas.
⚠️ Falta: Campo email_verified_at para validar que el usuario confirmó su email.

2. identity_workspaces

slug como subdominio es correcto.
✅ branding_config y donation_config como JSONB: Flexible y apropiado.

3. identity_memberships

Excelente implementación de RBAC.
⚠️ Riesgo: Si un owner se da de baja, ¿qué pasa con el workspace? Definir regla de negocio explícita (ej: transfer automático a otro admin).

⚠️ Entidad con Riesgo: identity_invitations
Problema: Puede generar estado inconsistente si el usuario se registra antes de aceptar la invitación.
Solución Propuesta:
sqlCopy-- Añadir campo para vincular invitación aceptada
ALTER TABLE identity_invitations 
ADD COLUMN accepted_by_user_id UUID NULL REFERENCES identity_users(id);

Dominio: PUBLISHING
✅ Entidades Correctas
1. publishing_posts

Tabla unificada (newsletter/note) es inteligente.
carbon_score como parte del modelo: Excelente para el objetivo ético.

🔴 Problemas Críticos
Problema 1: Falta published_version
Actualmente, si editas un post después de enviarlo, pierdes el contenido original enviado.
Solución:
sqlCopyCREATE TABLE publishing_post_versions (
    id UUID PRIMARY KEY,
    post_id UUID REFERENCES publishing_posts(id) ON DELETE CASCADE,
    content JSONB NOT NULL,
    version_number INTEGER NOT NULL,
    created_at TIMESTAMP,
    UNIQUE(post_id, version_number)
);
Justificación: Necesario para auditoría y para mostrar "lo que realmente se envió" a los suscriptores.
Problema 2: publishing_media sin relación explícita a post
¿Cómo sabes qué media está en uso y cuál es huérfano?
Solución:
sqlCopyCREATE TABLE publishing_post_media (
    post_id UUID REFERENCES publishing_posts(id) ON DELETE CASCADE,
    media_id UUID REFERENCES publishing_media(id) ON DELETE CASCADE,
    PRIMARY KEY (post_id, media_id)
);

Dominio: COMMUNITY
✅ community_comments - Correcto
Anidamiento mediante parent_id es estándar.
⚠️ community_followers
Problema Conceptual:
La relación es follower_id (user) → followed_workspace_id (workspace).
Pregunta Crítica: ¿Qué pasa si un usuario quiere seguir a un autor específico en lugar del workspace?
Recomendación para V2:
Añadir flexibilidad:
sqlCopyALTER TABLE community_followers 
ADD COLUMN followed_type VARCHAR(50) CHECK (followed_type IN ('workspace', 'user'));
ADD COLUMN followed_id UUID; -- Polimórfico
Esto permite seguir tanto workspaces como autores individuales.

Dominio: AUDIENCE
✅ audience_subscribers - Correcto
Crítico para GDPR:

✅ unsubscribe_token: Correcto.
⚠️ Falta: Campo consent_given_at para demostrar cumplimiento de GDPR.

Añadir:
sqlCopyALTER TABLE audience_subscribers 
ADD COLUMN consent_given_at TIMESTAMP NOT NULL DEFAULT NOW(),
ADD COLUMN consent_ip VARCHAR(45);
🔴 Problema Crítico: audience_import_jobs
Falta un mecanismo de limpieza automática.
Solución:
sqlCopyALTER TABLE audience_import_jobs 
ADD COLUMN expires_at TIMESTAMP DEFAULT (NOW() + INTERVAL '30 days');
Crear un job scheduler que borre registros antiguos:
phpCopy// Laravel Scheduler
$schedule->command('cleanup:import-jobs')->daily();

Dominio: DELIVERY
✅ delivery_campaigns - Correcto
Campo stats como JSONB es eficiente.
⚠️ delivery_bounces
Problema: No tienes distinción entre hard_bounce y soft_bounce.
Solución:
sqlCopyALTER TABLE delivery_bounces 
ADD COLUMN bounce_type VARCHAR(20) CHECK (bounce_type IN ('hard', 'soft', 'complaint'));
Regla de Negocio:

hard_bounce: Bloquear permanentemente.
soft_bounce: Reintentar hasta 3 veces.
complaint: Marcar como spam report.


🔒 AUDITORÍA DE SEGURIDAD
✅ Decisiones Correctas

Magic Links sin contraseñas: Reduce superficie de ataque.
Sanitización de HTML (DOMPurify): Crítico para evitar XSS.
Middleware de ownership: Verificar pertenencia a workspace en cada request.

🔴 Riesgos Identificados
1. Magic Links sin Rate Limiting Documentado
Problema: Un atacante puede solicitar 1000 magic links por minuto para admin@victim.com.
Solución:
phpCopy// app/Http/Middleware/ThrottleRequests.php
RateLimiter::for('magic-link', function (Request $request) {
    return Limit::perMinute(3)->by($request->input('email'));
});
2. Falta de Auditoría de Acciones Críticas
Problema: Si un owner borra el workspace, no hay registro de quién y cuándo.
Solución:
sqlCopyCREATE TABLE audit_log (
    id UUID PRIMARY KEY,
    user_id UUID REFERENCES identity_users(id),
    action VARCHAR(100), -- 'workspace.deleted', 'post.published'
    entity_type VARCHAR(50),
    entity_id UUID,
    metadata JSONB,
    ip_address VARCHAR(45),
    created_at TIMESTAMP
);

⚡ AUDITORÍA DE RENDIMIENTO
✅ Optimizaciones Implementadas

Cursors y Chunks para envíos masivos: Excelente.
Throttling de colas a 60 emails/min: Apropiado.
OPcache activado: Crítico para 2GB RAM.

⚠️ Posibles N+1 Queries
Ejemplo de Riesgo:
phpCopy// ❌ MALO: N+1 Query
$posts = Post::all();
foreach ($posts as $post) {
    echo $post->author->name; // Query extra por post
}
Solución:
phpCopy// ✅ BUENO: Eager Loading
$posts = Post::with('author')->get();
Recomendación: Usar Laravel Telescope en desarrollo para detectar N+1.

📦 AUDITORÍA DE DEPENDENCIAS
Stack Tecnológico: ✅ Apropiado
TecnologíaVersiónEstadoLaravel12✅ Última versiónReact19✅ Última versiónPostgreSQL17✅ Última versiónNode.js22 LTS✅ Soporte largo plazo
⚠️ Dependencias Críticas No Mencionadas
Falta documentar:

DOMPurify: ¿Versión? ¿Se ejecuta en backend (PHP) o frontend (JS)?
Editor.js: ¿Qué plugins exactos del editor usarás? (paragraph, header, list, image, quote)
MJML: ¿Cómo se integra? ¿Compilación en build time o runtime?

Recomendación:
Crear un archivo dependencies.lock.md que liste:
markdownCopy## Backend
- laravel/framework: ^12.0
- guzzlehttp/guzzle: ^7.8 (para Mailgun API)

## Frontend
- @editorjs/editorjs: ^2.29
- dompurify: ^3.0

🎯 RECOMENDACIONES PARA MVP
Qué Implementar SÍ o SÍ (Sprint 1-4)
Sprint 1: Fundamentos (Core)
markdownCopy**JIRA-001: Configuración Inicial del Proyecto**
- Instalar Laravel 12 + PostgreSQL 17 + Redis 7
- Configurar Vite + React 19 + Inertia.js 2.0
- Configurar .env con credenciales Mailgun
- AC: `npm run dev` y `php artisan serve` funcionan
- Estimación: 3 Story Points (4h)

**JIRA-002: Implementar Magic Link Authentication**
- Modelo User sin password
- Endpoint POST /auth/magic-link (envía email)
- Endpoint GET /auth/verify/{token} (valida y logea)
- Rate limiting: 3 intentos/minuto por email
- AC: Usuario puede entrar sin contraseña
- Estimación: 5 SP (8h)

**JIRA-003: CRUD de Workspaces**
- Migración identity_workspaces (slug único, JSONB configs)
- Controlador WorkspaceController (create, update, delete)
- Validación de slug (solo alfanumérico + guiones)
- AC: Crear/editar workspace desde dashboard
- Estimación: 5 SP (8h)

**JIRA-004: Sistema de Membresías (RBAC)**
- Migración identity_memberships con roles
- Middleware CheckWorkspaceMembership
- Policy WorkspacePolicy (authorize actions by role)
- AC: Owner puede borrar, Writer solo escribir
- Estimación: 8 SP (12h)
Sprint 2: Editor y Contenido
markdownCopy**JIRA-005: Integrar Editor.js**
- Instalar @editorjs/editorjs + plugins básicos (5 bloques)
- Componente React <EditorComponent />
- Guardar output JSON en publishing_posts.content (JSONB)
- AC: Crear draft con editor de bloques
- Estimación: 8 SP (12h)

**JIRA-006: CRUD de Posts (Newsletters)**
- Migración publishing_posts (type, status, content JSONB)
- Controlador PostController (CRUD completo)
- Incluir cálculo básico de carbon_score
- AC: Guardar borrador y verlo en listado
- Estimación: 8 SP (12h)

**JIRA-007: Sanitización de Contenido HTML**
- Instalar DOMPurify en backend (via Node?)
- Action SanitizePostContentAction
- Aplicar antes de guardar content
- AC: Test: script tag es eliminado
- Estimación: 5 SP (8h)

**JIRA-008: Previsualización de Newsletter**
- Convertir JSON de Editor.js a HTML con MJML
- Vista /newsletters/{id}/preview
- Mostrar carbon_score estimado
- AC: Ver email renderizado antes de enviar
- Estimación: 5 SP (8h)
Sprint 3: Audiencia y Suscriptores
markdownCopy**JIRA-009: CRUD de Subscribers**
- Migración audience_subscribers (email unique per workspace)
- Generar unsubscribe_token automáticamente
- Controlador SubscriberController (add, list, delete)
- AC: Añadir suscriptor manual
- Estimación: 5 SP (8h)

**JIRA-010: Importación CSV de Suscriptores**
- Job ProcessSubscriberImportJob (async)
- Validar CSV: detectar duplicados, emails inválidos
- Guardar en audience_import_jobs (status, error_log)
- AC: Importar 100 suscriptores sin timeout
- Estimación: 13 SP (20h)

**JIRA-011: Página Pública de Unsubscribe**
- Ruta GET /unsubscribe/{token}
- Marcar subscriber como 'unsubscribed'
- Vista minimalista de confirmación
- AC: Enlace en email funciona
- Estimación: 3 SP (4h)
Sprint 4: Envío Masivo (Delivery)
markdownCopy**JIRA-012: Configurar Redis Queue + Workers**
- Configurar QUEUE_CONNECTION=redis en .env
- Job SendCampaignEmailJob (procesa chunks de 100)
- Throttling: 60 emails/min
- AC: Queue worker procesa envíos sin agotar RAM
- Estimación: 8 SP (12h)

**JIRA-013: Integración con Mailgun**
- Crear MailgunService con Guzzle
- Método sendTransactional($to, $subject, $html)
- Manejar errores (429, 5xx)
- AC: Enviar email de prueba exitoso
- Estimación: 5 SP (8h)

**JIRA-014: Crear Campaign y Envío Masivo**
- Migración delivery_campaigns (stats JSONB)
- Action SendCampaignAction (dispatch job por subscriber)
- Actualizar campaign.stats al finalizar
- AC: Enviar newsletter a 10 suscriptores
- Estimación: 13 SP (20h)

**JIRA-015: Gestión de Bounces**
- Webhook POST /webhooks/mailgun/bounce
- Guardar en delivery_bounces (hard/soft)
- Marcar subscriber como 'bounced'
- AC: Hard bounce bloquea futuros envíos
- Estimación: 8 SP (12h)

Qué POSPONER a V1.1 (Post-MVP)
markdownCopy**JIRA-101: Estadísticas de Apertura (Open Rate)**
- Tracking pixel en emails
- Webhook de Mailgun /opened
- AC: Ver % de aperturas en dashboard

**JIRA-102: Programación de Envíos**
- Campo scheduled_at en campaigns
- Job CheckScheduledCampaignsJob (cron cada minuto)
- AC: Programar envío para mañana 10am

**JIRA-103: Segmentación de Suscriptores**
- Tags en subscribers (JSONB)
- Filtrar por tag al crear campaign
- AC: Enviar solo a "premium" subscribers

**JIRA-104: Exportación de Subscribers CSV**
- Botón "Export" en /subscribers
- Job GenerateSubscriberExportJob
- AC: Descargar CSV con todos los datos

🚨 RIESGOS TÉCNICOS Y MITIGACIONES
RiesgoProbabilidadImpactoMitigaciónMemoria agotada en envío de 10k emailsAltaCríticoUsar cursor() + chunks de 100Redis caída = colas detenidasMediaCríticoAOF + Supervisor auto-restart + AlertasRate limit de MailgunAltaAltoThrottling a 60/min + retry logicXSS via Editor.jsMediaCríticoDOMPurify en backend SIEMPREN+1 Queries en dashboardAltaMedioEager loading + Telescope para detectarMagic link reutilizadoBajaAltoToken de un solo uso + expiración 15min

✅ APROBACIÓN CONDICIONADA
Requerimientos Obligatorios Antes de Comenzar Desarrollo

Añadir a Modelo de Datos:

identity_users.email_verified_at
audience_subscribers.consent_given_at
delivery_bounces.bounce_type
Tabla publishing_post_versions


Documentar en README.md:

Versiones exactas de DOMPurify y Editor.js plugins
Configuración de Supervisor para queue workers
Plan de backups de PostgreSQL


Crear Tests Obligatorios (Mínimo):
phpCopy// tests/Feature/AuthTest.php
test('magic link can only be used once')

// tests/Feature/PostTest.php
test('writer cannot delete post from workspace')

// tests/Feature/CampaignTest.php
test('bounced emails are skipped in campaign')

Configurar GitHub Actions CI:
yamlCopy# .github/workflows/tests.yml
name: Tests
on: [push, pull_request]
jobs:
  test:
    runs-on: ubuntu-latest
    services:
      postgres:
        image: postgres:17
    steps:
      - uses: actions/checkout@v3
      - run: composer install
      - run: php artisan test



📊 PUNTUACIÓN FINAL POR ÁREA
ÁreaPuntuaciónComentarioArquitectura General9/10Monolito modular es la decisión correctaModelo de Datos7/10Necesita refinamientos en versioning y bouncesSeguridad8/10Falta rate limiting documentado y audit logRendimiento9/10Estrategia de chunks/cursors es excelenteDocumentación9/10Muy completa, solo falta detalle en dependenciasViabilidad MVP9/10Alcance realista para un TFGVisión Ética10/10Carbon tracking y AGPLv3 son diferenciadores
PUNTUACIÓN GLOBAL: 8.5/10 ✅

🎯 CONCLUSIÓN
Freetter está APROBADO para comenzar desarrollo con las correcciones indicadas en el modelo de datos.
El proyecto demuestra:

✅ Comprensión profunda de arquitectura web
✅ Justificación técnica sólida de decisiones
✅ Enfoque realista en alcance MVP
✅ Conciencia de riesgos y mitigaciones
✅ Propuesta de valor ética diferenciadora

Próximos Pasos Inmediatos:

Semana 1: Aplicar correcciones al modelo de datos (versioning, bounces, GDPR)
Semana 2: Implementar Sprint 1 (JIRA-001 a JIRA-004)
Semana 3: Implementar Sprint 2 (JIRA-005 a JIRA-008)
Semana 4: Code Review de frontend + tests

¡Mucho éxito con el desarrollo, Cristian! 🚀

Auditor: Development Manager
Firma: ✍️ [Aprobado con recomendaciones]
Fecha: 2 de noviembre de 2025Add to Conversation5811
