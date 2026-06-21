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
			$btn.text( '&#10003; Copiado' );
			setTimeout( function () {
				$btn.text( 'Copiar' );
			}, 2000 );
		} );
	} );
} );
