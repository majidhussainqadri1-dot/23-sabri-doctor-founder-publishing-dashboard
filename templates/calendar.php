<?php
/** Federated Publishing Calendar — native schedule ownership preserved. */
defined( 'ABSPATH' ) || exit;
$title_id = $instance_id . '-calendar-title';
$page     = (int) ( $calendar_result['query']['page'] ?? 1 );
$pages    = (int) ( $calendar_result['pages'] ?? 1 );
$base_query = $calendar_result['query'];
unset( $base_query['page'] );
?>
<section class="spdb-review-calendar" aria-labelledby="<?php echo esc_attr( $title_id ); ?>">
	<p class="spdb-eyebrow"><?php esc_html_e( 'Native schedule projection', 'sabri-publishing-dashboard' ); ?></p>
	<h2 id="<?php echo esc_attr( $title_id ); ?>"><?php esc_html_e( 'Federated Publishing Calendar', 'sabri-publishing-dashboard' ); ?></h2>
	<p><?php esc_html_e( 'File 23 combines native scheduled objects, time zones, failures, and conflict flags. It does not create a duplicate schedule table or treat a visual change as confirmed until the native provider validates and re-fetches the object.', 'sabri-publishing-dashboard' ); ?></p>

	<?php foreach ( $calendar_result['alerts'] as $alert ) : ?>
		<div class="spdb-notice spdb-notice--<?php echo esc_attr( $alert['level'] ); ?>" role="<?php echo 'critical' === $alert['level'] ? 'alert' : 'status'; ?>">
			<?php echo esc_html( $alert['message'] ); ?>
		</div>
	<?php endforeach; ?>

	<div class="spdb-review-calendar-summary" aria-label="<?php esc_attr_e( 'Calendar summary', 'sabri-publishing-dashboard' ); ?>">
		<article><span><?php esc_html_e( 'Providers queried', 'sabri-publishing-dashboard' ); ?></span><strong><?php echo esc_html( (string) $calendar_result['provider_count'] ); ?></strong></article>
		<article><span><?php esc_html_e( 'Current page entries', 'sabri-publishing-dashboard' ); ?></span><strong><?php echo esc_html( (string) $calendar_result['validated_count'] ); ?></strong></article>
		<article><span><?php esc_html_e( 'Accessible validated window', 'sabri-publishing-dashboard' ); ?></span><strong><?php echo esc_html( (string) $calendar_result['accessible_total'] ); ?></strong></article>
		<article><span><?php esc_html_e( 'Native reported total', 'sabri-publishing-dashboard' ); ?></span><strong><?php echo esc_html( (string) $calendar_result['reported_total'] ); ?></strong></article>
		<article><span><?php esc_html_e( 'Provider errors', 'sabri-publishing-dashboard' ); ?></span><strong><?php echo esc_html( (string) $calendar_result['provider_errors'] ); ?></strong></article>
	</div>

	<?php if ( empty( $calendar_result['items'] ) ) : ?>
		<p class="spdb-empty-state"><?php esc_html_e( 'No validated native calendar entry is available on this page for the current scope.', 'sabri-publishing-dashboard' ); ?></p>
	<?php else : ?>
		<div class="spdb-table-wrap" role="region" aria-label="<?php esc_attr_e( 'Native publishing calendar', 'sabri-publishing-dashboard' ); ?>" tabindex="0">
			<table class="spdb-review-calendar-table">
				<thead><tr><th><?php esc_html_e( 'Content', 'sabri-publishing-dashboard' ); ?></th><th><?php esc_html_e( 'Scheduled UTC', 'sabri-publishing-dashboard' ); ?></th><th><?php esc_html_e( 'Native timezone', 'sabri-publishing-dashboard' ); ?></th><th><?php esc_html_e( 'Status', 'sabri-publishing-dashboard' ); ?></th><th><?php esc_html_e( 'Conflicts', 'sabri-publishing-dashboard' ); ?></th><th><?php esc_html_e( 'Native action', 'sabri-publishing-dashboard' ); ?></th></tr></thead>
				<tbody>
				<?php foreach ( $calendar_result['items'] as $item ) : ?>
					<tr>
						<td><strong><?php echo esc_html( $item['title'] ); ?></strong><small><?php echo esc_html( $item['provider_key'] . ' · ' . $item['object_type'] . ' · ' . $item['object_id'] ); ?></small></td>
						<td><time datetime="<?php echo esc_attr( $item['scheduled_at_utc'] ); ?>"><?php echo esc_html( $item['scheduled_at_utc'] ); ?></time></td>
						<td><?php echo esc_html( $item['native_timezone'] ); ?></td>
						<td><span class="spdb-badge"><?php echo esc_html( $item['status'] ); ?></span></td>
						<td><?php echo empty( $item['conflicts'] ) ? esc_html__( 'None', 'sabri-publishing-dashboard' ) : esc_html( implode( ', ', $item['conflicts'] ) ); ?></td>
						<td><a class="spdb-button spdb-button--secondary" href="<?php echo esc_url( $item['native_edit_url'] ); ?>"><?php esc_html_e( 'Open Native Schedule', 'sabri-publishing-dashboard' ); ?></a><?php if ( ! empty( $item['allowed_operations'] ) ) : ?><small><?php echo esc_html( sprintf( __( 'Currently authorized operations: %s', 'sabri-publishing-dashboard' ), implode( ', ', $item['allowed_operations'] ) ) ); ?></small><?php endif; ?></td>
					</tr>
				<?php endforeach; ?>
				</tbody>
			</table>
		</div>
	<?php endif; ?>

	<?php if ( $pages > 1 ) : ?>
		<nav class="spdb-pagination" aria-label="<?php esc_attr_e( 'Publishing calendar pagination', 'sabri-publishing-dashboard' ); ?>">
			<?php if ( $page > 1 ) : ?><a class="spdb-button spdb-button--secondary" href="<?php echo esc_url( add_query_arg( array_merge( $base_query, array( 'page' => $page - 1 ) ), SPDB_Dashboard_Router::route_url( 'calendar' ) ) ); ?>"><?php esc_html_e( 'Previous page', 'sabri-publishing-dashboard' ); ?></a><?php endif; ?>
			<span><?php echo esc_html( sprintf( __( 'Page %1$d of %2$d', 'sabri-publishing-dashboard' ), $page, $pages ) ); ?></span>
			<?php if ( $page < $pages ) : ?><a class="spdb-button spdb-button--secondary" href="<?php echo esc_url( add_query_arg( array_merge( $base_query, array( 'page' => $page + 1 ) ), SPDB_Dashboard_Router::route_url( 'calendar' ) ) ); ?>"><?php esc_html_e( 'Next page', 'sabri-publishing-dashboard' ); ?></a><?php endif; ?>
		</nav>
	<?php endif; ?>
	<p class="spdb-caption"><?php echo esc_html( sprintf( __( 'Generated at %s. Native reported totals may exceed the bounded accessible window; native schedule state and cron reconciliation remain authoritative.', 'sabri-publishing-dashboard' ), $calendar_result['generated_at_gmt'] ) ); ?></p>
</section>
