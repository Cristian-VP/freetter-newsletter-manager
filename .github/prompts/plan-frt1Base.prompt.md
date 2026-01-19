## Plan: Cerrar FRT-1 Base Monolítica

Objetivo: completar la configuración inicial y la base modular (Laravel + React + Inertia) creando estructura de dominios, migraciones, modelos, rutas y esqueletos de UI. Se basa en el estado actual: solo rutas y página Home mínimas, migraciones por defecto (users/cache/jobs), sin policies ni dominio, sin `.env.example`.

### Steps
1. Crear checklist `docs/PROGRESS-FRT-1.md` con alcance FRT-1 y estado de cada ítem.
2. Añadir `.env.example` y (opcional) `config/freetter.php` con claves de magic link/redis/pgsql/mailgun.
3. Definir migraciones de dominio: workspaces, workspace_user (roles), magic_link_tokens, newsletters, subscribers, clips, sends, donation_configs; ejecutar `php artisan migrate`.
4. Implementar modelos Eloquent y relaciones (`User`, `Workspace`, `Newsletter`, `Subscriber`, `Clip`, `Send`, `DonationConfig`, `MagicLinkToken`), registrando casts/timestamps/guarded según necesidad.
5. Estructurar dominios en `app/Http/Controllers/*`, `app/Policies/*`, y middleware `ValidateWorkspaceOwnership`; stub de actions para magic links.
6. Ampliar rutas en `routes/web.php` con landing, login magic link, explorer público y recursos protegidos (newsletters, subscribers, clips, donations, settings).
7. Crear esqueletos React/Inertia: layouts compartidos, navbar/sidebar, landing, login magic link, dashboard, listado/crear/editar newsletter, listado/import subscribers, clips, settings.
8. Semillas mínimas para desarrollo: usuarios, workspace con roles, 1-2 newsletters y subscribers de prueba.

### Further Considerations
1. ¿Incluimos tests stub (Pest) para auth magic link y CRUD newsletter en FRT-1 o se dejan para FRT-2?
2. ¿Prefieres roles fijos (`owner|writer`) en pivot o enum/tabla dedicada?
