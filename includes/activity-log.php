<?php
/**
 * Activity log: records outgoing webhook events and renders them in the admin.
 *
 * Loaded on init (all contexts: admin, cron) so webhook sends during cron retries
 * are captured even when the admin is not active.
 *
 * @package WpAgentReady
 */

/**
 * Prepend an entry to the activity log (circular buffer, max 100 entries).
 *
 * @param string $type Event type (e.g. 'webhook_out').
 * @param array  $data Associative data for the entry.
 */
function wpar_log_activity( string $type, array $data ): void {
	$log = (array) get_option( 'wpar_activity_log', array() );

	array_unshift(
		$log,
		array_merge(
			array(
				'type' => $type,
				'time' => gmdate( 'c' ),
			),
			$data
		)
	);

	if ( count( $log ) > 100 ) {
		$log = array_slice( $log, 0, 100 );
	}

	update_option( 'wpar_activity_log', $log, false );
}

/**
 * Render the activity log HTML table.
 *
 * Intended to be called from wpar_render_settings_page() in admin.php.
 */
function wpar_render_activity_log(): void {
	$log = (array) get_option( 'wpar_activity_log', array() );
	?>
	<h2><?php esc_html_e( 'Actividad reciente', 'wp-agent-ready' ); ?></h2>
	<table class="widefat striped" id="wpar-activity-log">
		<thead>
			<tr>
				<th><?php esc_html_e( 'Fecha', 'wp-agent-ready' ); ?></th>
				<th><?php esc_html_e( 'Evento', 'wp-agent-ready' ); ?></th>
				<th><?php esc_html_e( 'Post', 'wp-agent-ready' ); ?></th>
				<th><?php esc_html_e( 'Acción', 'wp-agent-ready' ); ?></th>
				<th><?php esc_html_e( 'Estado', 'wp-agent-ready' ); ?></th>
			</tr>
		</thead>
		<tbody>
		<?php if ( empty( $log ) ) : ?>
			<tr>
				<td colspan="5" style="text-align:center;color:#666;">
					<?php esc_html_e( 'Sin actividad registrada.', 'wp-agent-ready' ); ?>
				</td>
			</tr>
		<?php else : ?>
			<?php foreach ( $log as $entry ) : ?>
				<?php
				$entry       = (array) $entry;
				$time        = isset( $entry['time'] ) ? esc_html( wp_date( 'Y-m-d H:i:s', strtotime( $entry['time'] ) ) ) : '—';
				$event_label = 'webhook_out' === ( $entry['type'] ?? '' )
					? esc_html__( 'Webhook enviado', 'wp-agent-ready' )
					: esc_html( $entry['type'] ?? '—' );
				$post_title  = esc_html( $entry['post_title'] ?? '' );
				$post_id     = isset( $entry['post_id'] ) ? (int) $entry['post_id'] : 0;
				$action      = esc_html( $entry['action'] ?? '—' );
				$status      = $entry['status'] ?? '—';

				if ( is_int( $status ) && $status >= 200 && $status < 300 ) {
					$status_html = '<span style="color:#2ecc71">&#10003; ' . esc_html( (string) $status ) . '</span>';
				} elseif ( is_int( $status ) ) {
					$status_html = '<span style="color:#e67e22">&#9888; ' . esc_html( (string) $status ) . '</span>';
				} else {
					$status_html = '<span style="color:#e74c3c">&#10007; ' . esc_html( (string) $status ) . '</span>';
				}

				$post_link = $post_id > 0
					? '<a href="' . esc_url( get_edit_post_link( $post_id ) ?? '' ) . '">' . $post_title . '</a>'
					: $post_title;
				?>
				<tr>
					<td><?php echo $time; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- already escaped above ?></td>
					<td><?php echo $event_label; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?></td>
					<td><?php echo $post_link; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?></td>
					<td><?php echo $action; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?></td>
					<td><?php echo $status_html; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?></td>
				</tr>
			<?php endforeach; ?>
		<?php endif; ?>
		</tbody>
	</table>
	<p>
		<button type="button" id="wpar-clear-log" class="button button-secondary">
			<?php esc_html_e( 'Borrar log', 'wp-agent-ready' ); ?>
		</button>
		<span id="wpar-clear-log-result" style="margin-left:8px;"></span>
	</p>
	<?php
}

/**
 * AJAX handler: clear the activity log.
 */
function wpar_ajax_clear_activity_log(): void {
	check_ajax_referer( 'wpar_clear_log', 'nonce' );

	if ( ! current_user_can( 'manage_options' ) ) {
		wp_send_json_error( array( 'message' => __( 'Permisos insuficientes.', 'wp-agent-ready' ) ) );
	}

	delete_option( 'wpar_activity_log' );

	wp_send_json_success();
}
