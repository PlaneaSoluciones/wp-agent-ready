# Changelog

All notable changes to WP Agent Ready are documented in this file.

The format is based on [Keep a Changelog](https://keepachangelog.com/en/1.1.0/),
and this project adheres to [Semantic Versioning](https://semver.org/spec/v2.0.0.html).

## [Unreleased]

## [0.7.1] - 2026-06-22

### Corregido
- El requisito mínimo de PHP se ha bajado a 8.1, eliminando el bloqueo de instalación en servidores con PHP 8.1–8.3

## [0.7.0] - 2026-06-21

### Añadido
- Integración con `robots.txt`: el plugin añade automáticamente las directivas `X-llms-txt` y `X-Content-API` al `robots.txt` del sitio, mejorando la descubribilidad para crawlers de IA aunque `/llms.txt` esté gestionado por otro plugin
- Nuevo filtro PHP `wpar_serve_llms_txt` que permite a temas y otros plugins desactivar la generación de `/llms.txt` de forma programática, sin acceder a los ajustes del panel

### Cambiado
- La rewrite rule de `/llms.txt` pasa a prioridad `bottom`, cediendo el control a otros plugins (como Yoast SEO) si en el futuro añaden soporte propio para este fichero

## [0.6.0] - 2026-06-21

### Añadido
- Opción **«Acceso público al endpoint»** en Ajustes › WP Agent Ready › Contenido: si se desactiva, `/wpar/v1/content` devuelve HTTP 403 a cualquier petición, independientemente de la visibilidad del sitio para buscadores

## [0.5.1] - 2026-06-21

### Corregido
- `wp-agent-ready-brief.md` y `phpstan-bootstrap.php` ya no se incluyen en el ZIP de release

## [0.5.0] - 2026-06-21

### Añadido
- Opción **«Borrar datos al desinstalar»** en Ajustes › WP Agent Ready: si está marcada, al eliminar el plugin se borran de la base de datos todos los ajustes del plugin y las entradas de rate limiting
- `uninstall.php` que ejecuta la limpieza de opciones y transients de rate limiting cuando la opción está activa

## [0.4.0] - 2026-06-21

### Añadido
- Página de ajustes en **Ajustes › WP Agent Ready** con tres secciones:
  - **Conexión con servidor MCP**: URL del endpoint MCP, API key del webhook editable, botón «Probar conexión» con resultado AJAX en tiempo real y botón «Copiar» la clave al portapapeles
  - **Contenido**: selección de post types a exponer mediante checkboxes (por defecto `post` y `page`) y límite de peticiones por hora configurable (por defecto: 60)
  - **Discoverabilidad**: opción para activar o desactivar la ruta `/llms.txt`
- El endpoint `/wpar/v1/content` solo acepta ahora los post types configurados en ajustes (antes aceptaba cualquier tipo público)
- La ruta `/llms.txt` respeta la opción de discoverabilidad y devuelve 404 si está desactivada

## [0.3.0] - 2026-06-21

### Añadido
- `/.well-known/mcp.json` — manifest de discoverabilidad con nombre del sitio, endpoints y tipos de contenido disponibles
- `/llms.txt` — descripción en formato Markdown del sitio y la API para consumo por LLMs
- `GET /wp-json/wpar/v1/manifest` — endpoint REST que devuelve el mismo manifest JSON
- Ambas rutas con cabeceras `Cache-Control: public, max-age=3600`
- Hook de desactivación que limpia las rewrite rules del plugin

## [0.2.0] - 2026-06-21

### Añadido
- Emisor de webhook: WordPress notifica al servidor MCP en cada publicación, actualización o eliminación de contenido
- Receptor `POST /wp-json/wpar/v1/sync` protegido por API key Bearer para re-sincronización bajo demanda
- Reintentos automáticos con backoff exponencial si el MCP no responde (hasta 3 intentos: 5 min, 15 min)
- Generación automática de API key segura en la activación del plugin
- Soporte para los tres eventos: `publish` (nueva publicación), `update` (edición), `delete` (eliminación o despublicación)

## [0.1.0] - 2026-06-21

### Añadido
- Endpoint REST `GET /wp-json/wpar/v1/content` con paginación y filtros (`per_page`, `page`, `post_type`, `modified_after`)
- Limpieza de HTML y shortcodes del contenido para consumo por LLMs
- Integración opcional con Yoast SEO (meta descripción y título SEO)
- Rate limiting de 60 peticiones/hora por IP con transients de WordPress
- Cabeceras de paginación `X-WP-Total` y `X-WP-TotalPages` en la respuesta
