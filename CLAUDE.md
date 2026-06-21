# CLAUDE.md

## Qué es esto
Plugin WordPress que expone el contenido publicado de un sitio de forma limpia y estructurada para que agentes de IA y LLMs externos puedan consumirlo. Endpoint principal: `GET /wp-json/wpar/v1/content`.

## Comandos

```bash
# Instalar dependencias de desarrollo
# Nota: PHP local puede ser < 8.4; usar --ignore-platform-reqs para desarrollo local
composer install --ignore-platform-reqs

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
│   └── well-known.php   ← rewrite rules para /.well-known/mcp.json y /llms.txt
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
# 2. Bump versión en wp-agent-ready.php (cabecera + constante WPAR_VERSION)
#    Bump Stable tag en readme.txt
# 3. Verificar lint ANTES del commit (evita commits rotos)
composer run check
# 4. Commit de bump
git add wp-agent-ready.php CHANGELOG.md readme.txt
git commit -m "chore: bump version to X.Y.Z"

# 4. Tag y push (CI genera la Release automáticamente)
git tag vX.Y.Z && git push && git push --tags
```

## Fases de desarrollo

- [x] FASE 1 — CI/CD, estructura y convenciones
- [x] FASE 2 — Endpoint REST principal (`/wp-json/wpar/v1/content`)
- [x] FASE 3 — Webhook de sincronización
- [x] FASE 4 — Discoverabilidad (llms.txt + mcp.json)
- [x] FASE 5 — Página de ajustes en admin

## Documentación operacional
_Pendiente de crear página en BookStack._
