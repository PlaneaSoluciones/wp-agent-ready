/* global wpar_admin */
/* WP Agent Ready — admin settings page JS */
jQuery( function ( $ ) {
	// Test MCP connection
	$( '#wpar-test-connection' ).on( 'click', function ( e ) {
		e.preventDefault();

		var $btn    = $( this );
		var $result = $( '#wpar-connection-result' );

		$btn.prop( 'disabled', true ).text( wpar_admin.testing_text );
		$result.html( '' );

		$.ajax( {
			url:  wpar_admin.ajax_url,
			type: 'POST',
			data: {
				action: 'wpar_test_connection',
				nonce:  wpar_admin.nonce,
			},
			success: function ( response ) {
				if ( response.success ) {
					$result.html( '<span style="color:#46b450">&#10003; ' + response.data.message + '</span>' );
				} else {
					$result.html( '<span style="color:#dc3232">&#10007; ' + response.data.message + '</span>' );
				}
			},
			error: function () {
				$result.html( '<span style="color:#dc3232">&#10007; Error de comunicación con el servidor.</span>' );
			},
			complete: function () {
				$btn.prop( 'disabled', false ).text( wpar_admin.test_btn_text );
			},
		} );
	} );

	// Copy API key to clipboard
	$( '#wpar-copy-key' ).on( 'click', function ( e ) {
		e.preventDefault();

		var $btn = $( this );
		var key  = $( '#wpar_webhook_key' ).val();

		if ( ! key ) {
			return;
		}

		navigator.clipboard.writeText( key ).then( function () {
			$btn.text( '✓ Copiado' );
			setTimeout( function () {
				$btn.text( 'Copiar' );
			}, 2000 );
		} );
	} );

	// Regenerate API key
	$( '#wpar-regenerate-key' ).on( 'click', function ( e ) {
		e.preventDefault();

		if ( ! confirm( 'Se generará una nueva clave. La anterior dejará de funcionar. ¿Continuar?' ) ) {
			return;
		}

		var $btn = $( this );
		$btn.prop( 'disabled', true ).text( 'Regenerando…' );

		$.ajax( {
			url:  wpar_admin.ajax_url,
			type: 'POST',
			data: {
				action: 'wpar_regenerate_key',
				nonce:  wpar_admin.regen_nonce,
			},
			success: function ( response ) {
				if ( response.success ) {
					$( '#wpar_webhook_key' ).val( response.data.key );
					$btn.text( '✓ Regenerada' );
					setTimeout( function () {
						$btn.text( 'Regenerar' );
					}, 2000 );
				}
			},
			error: function () {
				$btn.text( 'Error' );
				setTimeout( function () {
					$btn.text( 'Regenerar' );
				}, 2000 );
			},
			complete: function () {
				$btn.prop( 'disabled', false );
			},
		} );
	} );

	// Clear activity log
	$( '#wpar-clear-log' ).on( 'click', function ( e ) {
		e.preventDefault();

		if ( ! confirm( '¿Borrar todo el log de actividad? Esta acción no se puede deshacer.' ) ) {
			return;
		}

		var $btn    = $( this );
		var $result = $( '#wpar-clear-log-result' );

		$btn.prop( 'disabled', true );

		$.ajax( {
			url:  wpar_admin.ajax_url,
			type: 'POST',
			data: {
				action: 'wpar_clear_log',
				nonce:  wpar_admin.clear_log_nonce,
			},
			success: function ( response ) {
				if ( response.success ) {
					$( '#wpar-activity-log tbody' ).html(
						'<tr><td colspan="5" style="text-align:center;color:#666;">Sin actividad registrada.</td></tr>'
					);
					$result.html( '<span style="color:#46b450">&#10003; Log borrado.</span>' );
					setTimeout( function () { $result.html( '' ); }, 3000 );
				}
			},
			complete: function () {
				$btn.prop( 'disabled', false );
			},
		} );
	} );

	// Fetch MCP stats on page load
	var $statsContainer = $( '#wpar-mcp-stats' );
	if ( $statsContainer.length ) {
		$.ajax( {
			url:  wpar_admin.ajax_url,
			type: 'POST',
			data: {
				action: 'wpar_fetch_mcp_stats',
				nonce:  wpar_admin.fetch_stats_nonce,
			},
			success: function ( response ) {
				if ( ! response.success ) {
					$statsContainer.html(
						'<p class="description" style="color:#dc3232;">&#10007; ' + response.data.message + '</p>'
					);
					return;
				}

				var d = response.data;
				var lastContentRequest = d.last_content_request
					? new Date( d.last_content_request ).toLocaleString( 'es-ES' )
					: '—';
				var lastIndexed = d.last_indexed
					? new Date( d.last_indexed ).toLocaleString( 'es-ES' )
					: '—';
				var lastQuery = d.last_query_at
					? new Date( d.last_query_at ).toLocaleString( 'es-ES' )
					: '—';

				var byToolParts = [];
				if ( d.by_tool ) {
					if ( d.by_tool.search_content ) {
						byToolParts.push( 'búsquedas: ' + d.by_tool.search_content );
					}
					if ( d.by_tool.get_page ) {
						byToolParts.push( 'páginas: ' + d.by_tool.get_page );
					}
					if ( d.by_tool.list_recent ) {
						byToolParts.push( 'recientes: ' + d.by_tool.list_recent );
					}
					if ( d.by_tool.get_site_info ) {
						byToolParts.push( 'info: ' + d.by_tool.get_site_info );
					}
				}

				var byToolStr = byToolParts.length ? ' (' + byToolParts.join( ', ' ) + ')' : '';

				$statsContainer.html(
					'<table class="widefat striped">' +
					'<tr><th>Versión MCP</th><td>' + ( d.version || '—' ) + '</td></tr>' +
					'<tr><th>Última conexión MCP → plugin</th><td>' + lastContentRequest + '</td></tr>' +
					'<tr><th>Páginas indexadas</th><td>' + ( d.total_pages !== undefined ? d.total_pages : '—' ) + '</td></tr>' +
					'<tr><th>Último indexado</th><td>' + lastIndexed + '</td></tr>' +
					'<tr><th>Consultas de agentes</th><td>' + ( d.total_queries || 0 ) + byToolStr + '</td></tr>' +
					'<tr><th>Última consulta</th><td>' + lastQuery + '</td></tr>' +
					'</table>'
				);
			},
			error: function () {
				$statsContainer.html(
					'<p class="description" style="color:#dc3232;">&#10007; No se pudo conectar con el servidor MCP.</p>'
				);
			},
		} );
	}
} );
