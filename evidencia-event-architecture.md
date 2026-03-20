
╔═════════════════════════════════════════════════════════════════╗
║      DEMOSTRACIÓN: Event-Driven Architecture                    ║
║       Identity → Activity (Comunicación entre Módulos)          ║
╚═════════════════════════════════════════════════════════════════╝

   ESTADO INICIAL DE LA BASE DE DATOS:
   ├─ identity_users: 4 registros
   ├─ identity_workspaces: 4 registros
   ├─ identity_memberships: 4 registros
   └─ activity_logs: 12 registros

━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━
🔹 PASO 1: Crear Usuario en Identity
━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━
✓ Registro creado en: identity_users
  ID: 019c3e66-f8ef-71f2-b101-576c7498d43a
  Nombre: Demo User
  Email: demo@freetter.com
  Created_at: 2026-02-08 17:57:41

🔹 EVENTO DISPARADO: UserRegistered
  Observer: UserObserver::created()
  Listener: LogUserRegistered::handle()

✓ Registro automático en: activity_logs
  ID: 019c3e66-f8ff-70c6-864d-a90050aa6363
  Action: user.registered
  Entity Type: user
  Entity ID: 019c3e66-f8ef-71f2-b101-576c7498d43a
  IP Address: 127.0.0.1
  Metadata: {"name": "Demo User", "email": "demo@freetter.com", "context": {"ip": "127.0.0.1", "user_agent": "Symfony", "created_via": "observer"}}
  Created_at: 2026-02-08 17:57:41

━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━
🔹 PASO 2: Crear Workspace en Identity
━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━
✓ Registro creado en: identity_workspaces
  ID: 019c3e66-f90c-7270-9d5e-fa8f62e19b64
  Name: Demo Newsletter
  Slug: demo-newsletter-1770573461
  Created_at: 2026-02-08 17:57:41

🔹 EVENTO DISPARADO: WorkspaceCreated
  Observer: WorkspaceObserver::created()
  Listener: LogWorkspaceCreated::handle()

✓ Registro automático en: activity_logs
  ID: 019c3e66-f915-72ea-bfb4-0cadd531b4f4
  Action: workspace.created
  Entity Type: workspace
  Entity ID: 019c3e66-f90c-7270-9d5e-fa8f62e19b64
  Metadata: {"name": "Demo Newsletter", "slug": "demo-newsletter-1770573461"}
  Created_at: 2026-02-08 17:57:41

━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━
🔹 PASO 3: Crear Membership en Identity
━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━
✓ Registro creado en: identity_memberships
  ID: 019c3e66-f91c-72e6-9abb-9c5e0b44672a
  User ID: 019c3e66-f8ef-71f2-b101-576c7498d43a
  Workspace ID: 019c3e66-f90c-7270-9d5e-fa8f62e19b64
  Role: owner
  Joined_at: 2026-02-08 17:57:41

🔹 EVENTO DISPARADO: MembershipCreated
  Observer: MembershipObserver::created()
  Listener: LogMembershipCreated::handle()

✓ Registro automático en: activity_logs
  ID: 019c3e66-f925-734e-b705-5a35f5327819
  Action: membership.created
  Entity Type: membership
  Entity ID: 019c3e66-f91c-72e6-9abb-9c5e0b44672a
  User ID: 019c3e66-f8ef-71f2-b101-576c7498d43a
  Metadata: {"role": "owner", "joined_at": "2026-02-08T17:57:41+00:00", "workspace_id": "019c3e66-f90c-7270-9d5e-fa8f62e19b64", "workspace_name": "Demo Newsletter"}
  Created_at: 2026-02-08 17:57:41

━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━
RESUMEN FINAL DE BASE DE DATOS
━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━
   ├─ identity_users: 5 registros (+1)
   ├─ identity_workspaces: 5 registros (+1)
   ├─ identity_memberships: 5 registros (+1)
   └─ activity_logs: 15 registros (+3)

TODOS LOS LOGS EN activity_logs:
   [1] membership.created        | membership   | 019c3e66-f91c-72e6-9...
   [2] workspace.created         | workspace    | 019c3e66-f90c-7270-9...
   [3] user.registered           | user         | 019c3e66-f8ef-71f2-b...
   [4] membership.created        | membership   | 019c3e62-9206-7110-b...
   [5] workspace.created         | workspace    | 019c3e62-91f5-709e-a...
   [6] user.registered           | user         | 019c3e62-91d0-7051-a...
   [7] membership.created        | membership   | 019c3e61-90f7-72ce-b...
   [8] workspace.created         | workspace    | 019c3e61-90e6-7367-9...
   [9] user.registered           | user         | 019c3e61-90cc-70c7-8...
   [10] membership.created        | membership   | 019c3e5e-ff2d-7016-a...

╔═════════════════════════════════════════════════════════════════╗
║     EVIDENCIA COMPLETA: Event-Driven Architecture OK           ║
║     • 3 entidades creadas en Identity                          ║
║     • 3 logs registrados automáticamente en Activity           ║
║     • Desacoplamiento total entre módulos                      ║
║     • Comunicación 100% vía eventos                            ║
╚═════════════════════════════════════════════════════════════════╝

