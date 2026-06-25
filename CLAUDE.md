# CLAUDE.md

## Qué es esto
Plugin WordPress (v0.9.1) que expone el contenido publicado de un sitio de forma limpia y estructurada para que agentes de IA y LLMs externos puedan consumirlo. Endpoint principal: `GET /wp-json/wpar/v1/content`. Instalado en producción en `planeasoluciones.com`.

## Comandos

```bash
# Instalar dependencias de desarrollo (requiere PHP >= 8.1)
composer install

# Linting (PHPCS con WordPress Coding Standards)
composer run lint
composer run lint:fix   # autocorrección

# Análisis estático (PHPStan nivel 5)
composer run analyse

# Ambos
composer run check
```

## Arquitectura

```
wp-agent-ready/
├── wp-agent-ready.php   ← cabecera + bootstrap; carga condicional en rest_api_init / init
├── includes/
│   ├── endpoint.php     ← register_rest_route() para /wpar/v1/*
│   ├── handler.php      ← lógica del handler; params: per_page, page, post_type, modified_after
│   ├── sanitizer.php    ← wp_strip_all_tags() + elimina shortcodes sin expandir
│   ├── yoast.php        ← lee _yoast_wpseo_metadesc/_title si Yoast activo
│   ├── rate-limit.php   ← sliding window configurable (wpar_rate_limit) req/hora por IP
│   ├── webhook.php      ← emite notificación en save_post; recibe POST /wpar/v1/sync
│   └── admin.php        ← página Ajustes › WP Agent Ready (Settings API + AJAX)
├── public/
│   └── well-known.php   ← /.well-known/mcp.json (incluye mcp_server cuando está configurado) +
│                           /llms.txt (solo si no existe fichero físico en ABSPATH) +
│                           /wp-json/wpar/v1/manifest + robots.txt hints
│                           Helper: wpar_get_mcp_base_url() → deriva base URL desde wpar_mcp_url
├── assets/
│   └── js/admin.js      ← jQuery: probar conexión MCP y copiar API key
└── languages/           ← text-domain: wp-agent-ready
```

Prefijo de funciones: `wpar_`
Prefijo opciones BD: `wpar_`
Nada de lógica en el fichero principal — solo define constantes y registra hooks.

## Workflows

- `pr-title-lint.yml` — valida Conventional Commits en PRs
- `lint-php.yml` — PHP syntax check + PHPCS + PHPStan en push/PR
- `release-php.yml` — crea GitHub Release + ZIP al pushear tag `v*`; notas desde `CHANGELOG.md`

## Releases

```bash
# 1. Actualizar CHANGELOG.md (mover [Unreleased] → [X.Y.Z] - YYYY-MM-DD)
#    Añadir entrada = X.Y.Z en la sección == Changelog == de readme.txt
#    Actualizar README.md si hay cambios relevantes de funcionalidad
# 2. Bump versión en wp-agent-ready.php (cabecera + constante WPAR_VERSION)
#    Bump Stable tag en readme.txt
# 3. Verificar lint ANTES del commit (evita commits rotos)
composer run check
# 4. Commit de bump (changelog + versión + readme en el mismo commit)
git add wp-agent-ready.php CHANGELOG.md readme.txt README.md
git commit -m "chore: bump version to X.Y.Z"

# 5. Tag y push (CI genera la Release automáticamente)
git tag vX.Y.Z && git push && git push --tags
```

## Fases de desarrollo

- [x] FASE 1 — CI/CD, estructura y convenciones
- [x] FASE 2 — Endpoint REST principal (`/wp-json/wpar/v1/content`)
- [x] FASE 3 — Webhook de sincronización
- [x] FASE 4 — Discoverabilidad (`/.well-known/mcp.json` + `/llms.txt`)
- [x] FASE 5 — Página de ajustes en admin
- [x] FASE 6 — MCP server URL en manifest y llms.txt (v0.8.0)
- [x] FASE 7 — llms.txt cooperativo: cede ante fichero físico de otro plugin (v0.8.1)
- [x] FASE 8 — Fix 404 en llms.txt al desaparecer el fichero físico + auto-flush por versión (v0.9.1)

## Notas técnicas

- `/.well-known/mcp.json` incluye `mcp_server.url` y `mcp_server.manifest` solo cuando `wpar_mcp_url` está configurado en ajustes.
- `/llms.txt`: la rewrite rule se registra **siempre** en la BD. Si existe un fichero físico en ABSPATH, el servidor web lo sirve antes de que WordPress cargue (la regla nunca dispara). Si el fichero desaparece, la regla empieza a funcionar de inmediato sin reactivar el plugin.
- `wpar_bootstrap_init()` hace `flush_rewrite_rules()` automático la primera vez que se carga una nueva versión (compara `wpar_version` en BD con `WPAR_VERSION`).
- `wpar_get_mcp_base_url()` en `well-known.php` extrae `scheme://host[:port]` del webhook URL configurado. Usada también en `admin.php` para el test de conexión.

## Documentación operacional
- **Referencia principal (arquitectura, API, ajustes):** https://bookstack.planea.com.es/books/geek-parade/page/wp-agent-ready-plugin-wordpress-para-agentes-ia
- **Stub WordPress:** https://bookstack.planea.com.es/books/wordpress/page/wp-agent-ready
