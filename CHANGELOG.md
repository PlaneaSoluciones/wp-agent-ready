# Changelog

All notable changes to WP Agent Ready are documented in this file.

The format is based on [Keep a Changelog](https://keepachangelog.com/en/1.1.0/),
and this project adheres to [Semantic Versioning](https://semver.org/spec/v2.0.0.html).

## [Unreleased]

## [0.9.4] - 2026-06-27

### Corregido
- Al actualizar el plugin, el flush de reglas de rewrite ahora actualiza también el `.htaccess` (hard flush), no solo la base de datos. Esto elimina la necesidad de que el administrador visite manualmente Ajustes → Enlaces permanentes tras cada actualización.

## [0.9.3] - 2026-06-27

### Corregido
- `/llms.txt` ya no devuelve 404 en sitios donde las reglas de rewrite de páginas de WordPress tenían prioridad sobre la regla del plugin. La rewrite rule pasa a registrarse con prioridad `top`, igual que `/.well-known/mcp.json`, evitando que WordPress intente resolver `llms.txt` como un slug de página inexistente.

## [0.9.2] - 2026-06-27

### Corregido
- `/llms.txt` ya no recibe un redirect 301 a `/llms.txt/` cuando la estructura de permalinks usa trailing slash. El handler de discoverabilidad ahora se ejecuta antes que el mecanismo de redirect canonical de WordPress, sirviendo la respuesta directamente sin pasar por ese filtro.

## [0.9.1] - 2026-06-25

### Corregido
- `/llms.txt` ya no devuelve 404 cuando otro plugin (Yoast SEO, Rank Math…) gestionaba ese fichero y luego lo elimina. La rewrite rule se registra siempre en la base de datos; si existe un fichero físico en el raíz, el servidor web lo sirve antes de que WordPress cargue (la regla no dispara, es inocua). Si el fichero desaparece, la regla empieza a funcionar de inmediato sin intervención manual.
- Las instalaciones existentes reciben las reglas de rewrite corregidas automáticamente al actualizar, sin necesidad de desactivar y reactivar el plugin: se hace un `flush_rewrite_rules()` automático la primera vez que se carga la nueva versión.

## [0.9.0] - 2026-06-25

### Añadido
- **Log de actividad de sincronización**: la página de ajustes incluye ahora una sección «Actividad reciente» con una tabla de los últimos 100 webhooks enviados al servidor MCP, mostrando fecha, post afectado, acción y código de respuesta HTTP. Permite verificar de un vistazo que la sincronización entre el plugin y el MCP funciona correctamente.
- **Estado del MCP en el admin**: nueva sección «Estado del MCP» que consulta el endpoint `/health` del servidor MCP al cargar la página y muestra el número de páginas indexadas, la fecha del último indexado, el total de consultas recibidas de agentes y el detalle por herramienta (búsquedas, páginas, recientes).
- **Contadores de consultas en el MCP** (servidor v0.4.0): cada llamada a `search_content`, `get_page`, `list_recent` y `get_site_info` incrementa un contador persistente en SQLite. El endpoint `/health` expone `total_queries`, `by_tool` y `last_query_at`.

## [0.8.1] - 2026-06-25

### Corregido
- El plugin ya no registra la rewrite rule de `/llms.txt` cuando existe un fichero físico `llms.txt` en el raíz del sitio. Si otro plugin (Yoast SEO, Rank Math…) genera ese fichero en disco, el servidor web lo sirve directamente antes de que WordPress cargue — registrar la rule era ruido innecesario y podía confundir el diagnóstico de rutas. La comprobación es agnóstica de plugin y se evalúa en cada petición, por lo que si el fichero desaparece el comportamiento se restaura automáticamente sin reactivar el plugin.

## [0.8.0] - 2026-06-25

### Añadido
- El manifest (`/.well-known/mcp.json` y `/wp-json/wpar/v1/manifest`) incluye ahora un campo `mcp_server` con la URL del endpoint MCP y la URL del manifest cuando la URL del servidor MCP está configurada en los ajustes. Esto permite que los agentes de IA descubran el servidor MCP directamente desde el sitio WordPress.
- El archivo `/llms.txt` incluye ahora una sección **MCP Server** con el endpoint y el manifest del servidor MCP cuando está configurado.

### Corregido
- La constante `WPAR_VERSION` estaba desincronizada (`0.7.3`) respecto a la versión real del plugin (`0.7.5`). Ambos valores se alinean ahora con el número de versión correcto.

## [0.7.5] - 2026-06-22

### Añadido
- Botón **Regenerar** en el campo API key del webhook: permite generar una nueva clave sin necesidad de reactivar el plugin.

### Cambiado
- Los tres campos de la sección «Conexión con servidor MCP» ahora incluyen descripciones que explican el origen y destino de cada valor (de qué variable del MCP copiarlo y en qué cabecera se usa).
- El campo API key del webhook pasa a ser de solo lectura; su valor se gestiona mediante los botones Copiar y Regenerar.

## [0.7.4] - 2026-06-22

### Corregido
- Añadido campo **Secreto del webhook MCP** en los ajustes: el plugin ahora envía la cabecera `X-WPAR-Secret` al notificar cambios al servidor MCP, en lugar de `Authorization: Bearer`. Sin este valor los webhooks eran rechazados con 401.
- La prueba de conexión comprueba ahora el endpoint `/health` del MCP (GET) en lugar de `/webhook` (solo POST), eliminando el falso error en el test.

## [0.7.3] - 2026-06-22

### Corregido
- El manifest (`/wp-json/wpar/v1/manifest`) y el `llms.txt` ahora anuncian únicamente los tipos de contenido activados en los ajustes del plugin, en lugar de todos los tipos públicos registrados en WordPress. Esto evitaba que tipos de otros plugins (como `mailpoet_page`) aparecieran en el manifest aunque no estuvieran habilitados, causando errores 400 al intentar indexarlos.

## [0.7.2] - 2026-06-22

### Añadido
- Enlace directo a **Ajustes** en la fila del plugin dentro de la lista de plugins de WordPress

### Cambiado
- Nombre del autor actualizado a «Planea Soluciones» (nombre comercial)

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
