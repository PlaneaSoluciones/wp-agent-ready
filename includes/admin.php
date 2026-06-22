<?php
/**
 * Admin settings page.
 *
 * Registers the Settings > WP Agent Ready page and all its fields.
 * Loaded on init only in admin and wp-admin/admin-ajax.php contexts.
 *
 * @package WpAgentReady
 */

add_action( 'admin_menu', 'wpar_add_settings_page' );
add_action( 'admin_init', 'wpar_register_settings' );
add_action( 'admin_enqueue_scripts', 'wpar_admin_enqueue_scripts' );
add_action( 'wp_ajax_wpar_test_connection', 'wpar_ajax_test_connection' );
add_action( 'wp_ajax_wpar_regenerate_key', 'wpar_ajax_regenerate_key' );
add_filter( 'plugin_action_links_wp-agent-ready/wp-agent-ready.php', 'wpar_plugin_action_links' );

/**
 * Add a Settings link in the plugin list table.
 *
 * @param string[] $links Existing action links.
 * @return string[]
 */
function wpar_plugin_action_links( array $links ): array {
	$settings_link = sprintf(
		'<a href="%s">%s</a>',
		esc_url( admin_url( 'options-general.php?page=wpar-settings' ) ),
		esc_html__( 'Ajustes', 'wp-agent-ready' )
	);

	array_unshift( $links, $settings_link );

	return $links;
}

/**
 * Register the plugin settings page under Settings menu.
 */
function wpar_add_settings_page(): void {
	add_options_page(
		__( 'WP Agent Ready', 'wp-agent-ready' ),
		__( 'WP Agent Ready', 'wp-agent-ready' ),
		'manage_options',
		'wpar-settings',
		'wpar_render_settings_page'
	);
}

/**
 * Register all plugin options via the Settings API.
 */
function wpar_register_settings(): void {
	// Section: MCP Connection.
	add_settings_section(
		'wpar_section_mcp',
		__( 'Conexión con servidor MCP', 'wp-agent-ready' ),
		'__return_false',
		'wpar-settings'
	);

	register_setting( 'wpar_settings', 'wpar_mcp_url', array( 'sanitize_callback' => 'esc_url_raw' ) );
	add_settings_field(
		'wpar_mcp_url',
		__( 'URL del servidor MCP', 'wp-agent-ready' ),
		'wpar_field_mcp_url',
		'wpar-settings',
		'wpar_section_mcp'
	);

	register_setting( 'wpar_settings', 'wpar_mcp_secret', array( 'sanitize_callback' => 'sanitize_text_field' ) );
	add_settings_field(
		'wpar_mcp_secret',
		__( 'Secreto del webhook MCP', 'wp-agent-ready' ),
		'wpar_field_mcp_secret',
		'wpar-settings',
		'wpar_section_mcp'
	);

	register_setting( 'wpar_settings', 'wpar_webhook_key', array( 'sanitize_callback' => 'sanitize_text_field' ) );
	add_settings_field(
		'wpar_webhook_key',
		__( 'API key del webhook', 'wp-agent-ready' ),
		'wpar_field_webhook_key',
		'wpar-settings',
		'wpar_section_mcp'
	);

	add_settings_field(
		'wpar_test_connection',
		__( 'Probar conexión', 'wp-agent-ready' ),
		'wpar_field_test_connection',
		'wpar-settings',
		'wpar_section_mcp'
	);

	// Section: Content.
	add_settings_section(
		'wpar_section_content',
		__( 'Contenido', 'wp-agent-ready' ),
		'__return_false',
		'wpar-settings'
	);

	register_setting( 'wpar_settings', 'wpar_public_access', array( 'sanitize_callback' => 'wpar_sanitize_bool' ) );
	add_settings_field(
		'wpar_public_access',
		__( 'Acceso público al endpoint', 'wp-agent-ready' ),
		'wpar_field_public_access',
		'wpar-settings',
		'wpar_section_content'
	);

	register_setting( 'wpar_settings', 'wpar_post_types', array( 'sanitize_callback' => 'wpar_sanitize_post_types' ) );
	add_settings_field(
		'wpar_post_types',
		__( 'Post types a exponer', 'wp-agent-ready' ),
		'wpar_field_post_types',
		'wpar-settings',
		'wpar_section_content'
	);

	register_setting( 'wpar_settings', 'wpar_rate_limit', array( 'sanitize_callback' => 'wpar_sanitize_rate_limit' ) );
	add_settings_field(
		'wpar_rate_limit',
		__( 'Rate limit (peticiones/hora por IP)', 'wp-agent-ready' ),
		'wpar_field_rate_limit',
		'wpar-settings',
		'wpar_section_content'
	);

	// Section: Discoverability.
	add_settings_section(
		'wpar_section_discovery',
		__( 'Discoverabilidad', 'wp-agent-ready' ),
		'__return_false',
		'wpar-settings'
	);

	register_setting( 'wpar_settings', 'wpar_llms_txt_enabled', array( 'sanitize_callback' => 'wpar_sanitize_bool' ) );
	add_settings_field(
		'wpar_llms_txt_enabled',
		__( 'Activar /llms.txt', 'wp-agent-ready' ),
		'wpar_field_llms_txt_enabled',
		'wpar-settings',
		'wpar_section_discovery'
	);

	// Section: Advanced.
	add_settings_section(
		'wpar_section_advanced',
		__( 'Avanzado', 'wp-agent-ready' ),
		'__return_false',
		'wpar-settings'
	);

	register_setting( 'wpar_settings', 'wpar_delete_on_uninstall', array( 'sanitize_callback' => 'wpar_sanitize_bool' ) );
	add_settings_field(
		'wpar_delete_on_uninstall',
		__( 'Borrar datos al desinstalar', 'wp-agent-ready' ),
		'wpar_field_delete_on_uninstall',
		'wpar-settings',
		'wpar_section_advanced'
	);
}

/**
 * Enqueue admin JS only on the plugin settings page.
 *
 * @param string $hook_suffix Current admin page hook suffix.
 */
function wpar_admin_enqueue_scripts( string $hook_suffix ): void {
	if ( 'settings_page_wpar-settings' !== $hook_suffix ) {
		return;
	}

	wp_enqueue_script(
		'wpar-admin',
		WPAR_PLUGIN_URL . 'assets/js/admin.js',
		array( 'jquery' ),
		WPAR_VERSION,
		true
	);

	wp_localize_script(
		'wpar-admin',
		'wpar_admin',
		array(
			'ajax_url'      => admin_url( 'admin-ajax.php' ),
			'nonce'         => wp_create_nonce( 'wpar_test_connection' ),
			'regen_nonce'   => wp_create_nonce( 'wpar_regenerate_key' ),
			'test_btn_text' => __( 'Probar conexión', 'wp-agent-ready' ),
			'testing_text'  => __( 'Probando…', 'wp-agent-ready' ),
		)
	);
}

// ---------------------------------------------------------------------------
// Field renderers
// ---------------------------------------------------------------------------

/**
 * Render the MCP URL field.
 */
function wpar_field_mcp_url(): void {
	printf(
		'<input type="url" id="wpar_mcp_url" name="wpar_mcp_url" value="%s" class="regular-text" placeholder="https://mcp.ejemplo.com/webhook" />
		<p class="description">%s</p>',
		esc_url( (string) get_option( 'wpar_mcp_url', '' ) ),
		esc_html__( 'Dirección del endpoint /webhook del servidor MCP. El plugin enviará aquí los avisos de publicación y actualización de contenido.', 'wp-agent-ready' )
	);
}

/**
 * Render the MCP webhook secret field (plugin → MCP auth).
 */
function wpar_field_mcp_secret(): void {
	printf(
		'<input type="text" id="wpar_mcp_secret" name="wpar_mcp_secret" value="%s" class="regular-text" autocomplete="off" />
		<p class="description">%s</p>',
		esc_attr( (string) get_option( 'wpar_mcp_secret', '' ) ),
		esc_html__( 'Cópialo desde la variable WPAR_WEBHOOK_SECRET del .env del servidor MCP. El plugin lo envía en la cabecera X-WPAR-Secret al notificar cambios de contenido al MCP.', 'wp-agent-ready' )
	);
}

/**
 * Render the webhook API key field.
 */
function wpar_field_webhook_key(): void {
	printf(
		'<input type="text" id="wpar_webhook_key" name="wpar_webhook_key" value="%s" class="regular-text" autocomplete="off" readonly />
		<button type="button" id="wpar-copy-key" class="button button-secondary" style="margin-left:6px">%s</button>
		<button type="button" id="wpar-regenerate-key" class="button button-secondary" style="margin-left:4px">%s</button>
		<p class="description">%s</p>',
		esc_attr( (string) get_option( 'wpar_webhook_key', '' ) ),
		esc_html__( 'Copiar', 'wp-agent-ready' ),
		esc_html__( 'Regenerar', 'wp-agent-ready' ),
		esc_html__( 'Clave autogenerada que protege el endpoint /wp-json/wpar/v1/sync de este WordPress. Si un servicio externo (p.ej. el servidor MCP) necesita forzar el re-índice de un post concreto, deberá enviarla en la cabecera Authorization: Bearer.', 'wp-agent-ready' )
	);
}

/**
 * Render the test connection button.
 */
function wpar_field_test_connection(): void {
	printf(
		'<button type="button" id="wpar-test-connection" class="button">%s</button>
		<span id="wpar-connection-result" style="margin-left:10px;line-height:28px"></span>',
		esc_html__( 'Probar conexión', 'wp-agent-ready' )
	);
}

/**
 * Render the public access toggle field.
 */
function wpar_field_public_access(): void {
	$enabled = (bool) get_option( 'wpar_public_access', true );

	printf(
		'<label><input type="checkbox" id="wpar_public_access" name="wpar_public_access" value="1"%s /> %s</label>
		<p class="description">%s</p>',
		checked( $enabled, true, false ),
		esc_html__( 'Permitir acceso sin autenticación al endpoint /wpar/v1/content', 'wp-agent-ready' ),
		esc_html__( 'Si está desactivado, el endpoint devuelve HTTP 403 a cualquier petición. Útil para deshabilitar temporalmente el acceso de agentes externos sin desactivar el plugin.', 'wp-agent-ready' )
	);
}

/**
 * Render the post types checkboxes field.
 */
function wpar_field_post_types(): void {
	$saved      = (array) get_option( 'wpar_post_types', array( 'post', 'page' ) );
	$post_types = get_post_types( array( 'public' => true ), 'objects' );

	foreach ( $post_types as $type ) {
		printf(
			'<label style="display:block;margin-bottom:4px"><input type="checkbox" name="wpar_post_types[]" value="%s"%s /> %s</label>',
			esc_attr( $type->name ),
			checked( in_array( $type->name, $saved, true ), true, false ),
			esc_html( $type->labels->name )
		);
	}

	echo '<p class="description">' . esc_html__( 'Solo se expondrán a través del endpoint los post types seleccionados.', 'wp-agent-ready' ) . '</p>';
}

/**
 * Render the rate limit field.
 */
function wpar_field_rate_limit(): void {
	printf(
		'<input type="number" id="wpar_rate_limit" name="wpar_rate_limit" value="%d" min="1" max="10000" class="small-text" />
		<p class="description">%s</p>',
		absint( get_option( 'wpar_rate_limit', 60 ) ),
		esc_html__( 'Número máximo de peticiones por IP en una ventana de 1 hora (por defecto: 60).', 'wp-agent-ready' )
	);
}

/**
 * Render the delete-on-uninstall checkbox.
 */
function wpar_field_delete_on_uninstall(): void {
	$enabled = (bool) get_option( 'wpar_delete_on_uninstall', false );

	printf(
		'<label><input type="checkbox" id="wpar_delete_on_uninstall" name="wpar_delete_on_uninstall" value="1"%s /> %s</label>
		<p class="description">%s</p>',
		checked( $enabled, true, false ),
		esc_html__( 'Eliminar todas las opciones del plugin al desinstalar', 'wp-agent-ready' ),
		esc_html__( 'Si está marcado, al desinstalar WP Agent Ready se borrarán de la base de datos la URL del MCP, la API key y el resto de ajustes. Esta acción no se puede deshacer.', 'wp-agent-ready' )
	);
}

/**
 * Render the llms.txt enabled checkbox.
 */
function wpar_field_llms_txt_enabled(): void {
	$enabled = (bool) get_option( 'wpar_llms_txt_enabled', true );

	printf(
		'<label><input type="checkbox" id="wpar_llms_txt_enabled" name="wpar_llms_txt_enabled" value="1"%s /> %s</label>
		<p class="description">%s</p>',
		$enabled ? ' checked' : '',
		esc_html__( 'Servir /llms.txt en este sitio', 'wp-agent-ready' ),
		esc_html__( 'Si está activo, la ruta /llms.txt devolverá una descripción del sitio y la API para consumo por LLMs.', 'wp-agent-ready' )
	);
}

// ---------------------------------------------------------------------------
// Sanitize callbacks
// ---------------------------------------------------------------------------

/**
 * Sanitize the post types option.
 *
 * @param mixed $value Raw submitted value.
 * @return string[]    Array of valid public post type names.
 */
function wpar_sanitize_post_types( mixed $value ): array {
	if ( ! is_array( $value ) || array() === $value ) {
		return array( 'post' );
	}

	$valid = array_keys( get_post_types( array( 'public' => true ) ) );

	return array_values(
		array_intersect(
			array_map( 'sanitize_key', $value ),
			$valid
		)
	);
}

/**
 * Sanitize the rate limit option.
 *
 * @param mixed $value Raw submitted value.
 * @return int         Clamped integer between 1 and 10 000.
 */
function wpar_sanitize_rate_limit( mixed $value ): int {
	$int = absint( $value );

	if ( 0 === $int ) {
		return 60;
	}

	return max( 1, min( $int, 10000 ) );
}

/**
 * Sanitize a boolean option submitted via checkbox.
 *
 * @param mixed $value Raw submitted value (1 or absent).
 * @return bool
 */
function wpar_sanitize_bool( mixed $value ): bool {
	return ! empty( $value );
}

// ---------------------------------------------------------------------------
// AJAX: test connection
// ---------------------------------------------------------------------------

/**
 * AJAX handler: send a test request to the configured MCP URL.
 *
 * Returns JSON success/error with diagnostic HTTP code.
 */
function wpar_ajax_test_connection(): void {
	check_ajax_referer( 'wpar_test_connection', 'nonce' );

	if ( ! current_user_can( 'manage_options' ) ) {
		wp_send_json_error( array( 'message' => __( 'Permisos insuficientes.', 'wp-agent-ready' ) ) );
	}

	$mcp_url = (string) get_option( 'wpar_mcp_url', '' );

	if ( '' === $mcp_url ) {
		wp_send_json_error( array( 'message' => __( 'Configura primero la URL del servidor MCP.', 'wp-agent-ready' ) ) );
	}

	// Derive base URL from the configured webhook URL and test /health (GET, no auth).
	$parsed   = wp_parse_url( $mcp_url );
	$base_url = ( $parsed['scheme'] ?? 'https' ) . '://' . ( $parsed['host'] ?? '' );
	if ( ! empty( $parsed['port'] ) ) {
		$base_url .= ':' . $parsed['port'];
	}
	$health_url = rtrim( $base_url, '/' ) . '/health';

	$response = wp_remote_get(
		$health_url,
		array(
			'timeout' => 5,
		)
	);

	if ( is_wp_error( $response ) ) {
		wp_send_json_error(
			array(
				/* translators: %s: WP_Error message */
				'message' => sprintf( __( 'No se pudo conectar: %s', 'wp-agent-ready' ), $response->get_error_message() ),
			)
		);
	}

	$code = wp_remote_retrieve_response_code( $response );

	if ( ! is_int( $code ) ) {
		wp_send_json_error( array( 'message' => __( 'Respuesta inesperada del servidor.', 'wp-agent-ready' ) ) );
	}

	if ( $code >= 200 && $code < 300 ) {
		wp_send_json_success(
			array(
				/* translators: %d: HTTP response code */
				'message' => sprintf( __( 'Conexión correcta (HTTP %d)', 'wp-agent-ready' ), $code ),
			)
		);
	} elseif ( 401 === $code || 403 === $code ) {
		wp_send_json_error(
			array(
				/* translators: %d: HTTP response code */
				'message' => sprintf( __( 'Servidor alcanzable pero clave API incorrecta (HTTP %d)', 'wp-agent-ready' ), $code ),
			)
		);
	} else {
		wp_send_json_success(
			array(
				/* translators: %d: HTTP response code */
				'message' => sprintf( __( 'Servidor alcanzable (HTTP %d)', 'wp-agent-ready' ), $code ),
			)
		);
	}
}

/**
 * AJAX handler: generate a new webhook API key and persist it.
 */
function wpar_ajax_regenerate_key(): void {
	check_ajax_referer( 'wpar_regenerate_key', 'nonce' );

	if ( ! current_user_can( 'manage_options' ) ) {
		wp_send_json_error( array( 'message' => __( 'Permisos insuficientes.', 'wp-agent-ready' ) ) );
	}

	$new_key = wp_generate_password( 48, false );
	update_option( 'wpar_webhook_key', $new_key, false );

	wp_send_json_success( array( 'key' => $new_key ) );
}

// ---------------------------------------------------------------------------
// Page renderer
// ---------------------------------------------------------------------------

/**
 * Render the WP Agent Ready settings page.
 */
function wpar_render_settings_page(): void {
	if ( ! current_user_can( 'manage_options' ) ) {
		return;
	}
	?>
	<div class="wrap">
		<h1><?php echo esc_html( get_admin_page_title() ); ?></h1>
		<form action="options.php" method="post">
			<?php
			settings_fields( 'wpar_settings' );
			do_settings_sections( 'wpar-settings' );
			submit_button( __( 'Guardar cambios', 'wp-agent-ready' ) );
			?>
		</form>
	</div>
	<?php
}
