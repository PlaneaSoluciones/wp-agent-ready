# Changelog

All notable changes to WP Agent Ready are documented in this file.

The format is based on [Keep a Changelog](https://keepachangelog.com/en/1.1.0/),
and this project adheres to [Semantic Versioning](https://semver.org/spec/v2.0.0.html).

## [Unreleased]

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
