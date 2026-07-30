<?php
/** Universal Review Inbox — projection only; native review ownership preserved. */
defined( 'ABSPATH' ) || exit;
$title_id = $instance_id . '-review-title';
$page     = (int) ( $review_result['query']['page'] ?? 1 );
$pages    = (int) ( $review_result['pages'] ?? 1 );
$base_query = $review_result['query'];
unset( $base_query['page'] );
?>
<section class="spdb-review-calendar" aria-labelledby="<?php echo esc_attr( $title_id ); ?>">
	<p class="spdb-eyebrow"><?php esc_html_e( 'Read and action projection only', 'sabri-publishing-dashboard' ); ?></p>
	<h2 id="<?php echo esc_attr( $title_id ); ?>"><?php esc_html_e( 'Universal Review Inbox', 'sabri-publishing-dashboard' ); ?></h2>
	<p><?php esc_html_e( 'Review decisions, assignments, notes, appeals, and audit records remain with each native module. File 23 validates and projects the queue, then links to the native review surface.', 'sabri-publishing-dashboard' ); ?></p>

	<?php foreach ( $review_result['alerts'] as $alert ) : ?>
		<div class="spdb-notice spdb-notice--<?php echo esc_attr( $alert['level'] ); ?>" role="<?php echo 'critical' === $alert['level'] ? 'alert' : 'status'; ?>">
			<?php echo esc_html( $alert['message'] ); ?>
		</div>
	<?php endforeach; ?>

	<div class="spdb-review-calendar-summary" aria-label="<?php esc_attr_e( 'Review queue summary', 'sabri-publishing-dashboard' ); ?>">
		<article><span><?php esc_html_e( 'Providers queried', 'sabri-publishing-dashboard' ); ?></span><strong><?php echo esc_html( (string) $review_result['provider_count'] ); ?></strong></article>
		<article><span><?php esc_html_e( 'Current page items', 'sabri-publishing-dashboard' ); ?></span><strong><?php echo esc_html( (string) $review_result['validated_count'] ); ?></strong></article>
		<article><span><?php esc_html_e( 'Accessible validated window', 'sabri-publishing-dashboard' ); ?></span><strong><?php echo esc_html( (string) $review_result['accessible_total'] ); ?></strong></article>
		<article><span><?php esc_html_e( 'Native reported total', 'sabri-publishing-dashboard' ); ?></span><strong><?php echo esc_html( (string) $review_result['reported_total'] ); ?></strong></article>
		<article><span><?php esc_html_e( 'Provider errors', 'sabri-publishing-dashboard' ); ?></span><strong><?php echo esc_html( (string) $review_result['provider_errors'] ); ?></strong></article>
	</div>

	<?php if ( empty( $review_result['items'] ) ) : ?>
		<p class="spdb-empty-state"><?php esc_html_e( 'No validated native review item is available on this page for the current reviewer scope.', 'sabri-publishing-dashboard' ); ?></p>
	<?php else : ?>
		<div class="spdb-table-wrap" role="region" aria-label="<?php esc_attr_e( 'Native review queue', 'sabri-publishing-dashboard' ); ?>" tabindex="0">
			<table class="spdb-review-calendar-table">
				<thead><tr><th><?php esc_html_e( 'Content', 'sabri-publishing-dashboard' ); ?></th><th><?php esc_html_e( 'Author', 'sabri-publishing-dashboard' ); ?></th><th><?php esc_html_e( 'Review state', 'sabri-publishing-dashboard' ); ?></th><th><?php esc_html_e( 'Reviewer / Due', 'sabri-publishing-dashboard' ); ?></th><th><?php esc_html_e( 'Flags', 'sabri-publishing-dashboard' ); ?></th><th><?php esc_html_e( 'Native action', 'sabri-publishing-dashboard' ); ?></th></tr></thead>
				<tbody>
				<?php foreach ( $review_result['items'] as $item ) : ?>
					<tr>
						<td><strong><?php echo esc_html( $item['title'] ); ?></strong><small><?php echo esc_html( $item['provider_key'] . ' · ' . $item['object_type'] . ' · ' . $item['object_id'] ); ?></small></td>
						<td><?php echo esc_html( $item['author_name'] ); ?></td>
						<td><span class="spdb-badge"><?php echo esc_html( $item['review_state'] ); ?></span></td>
						<td><?php echo '' !== $item['assigned_reviewer_name'] ? esc_html( $item['assigned_reviewer_name'] ) : esc_html__( 'Unassigned', 'sabri-publishing-dashboard' ); ?><small><?php echo '' !== $item['due_at'] ? esc_html( $item['due_at'] ) : esc_html__( 'No due date', 'sabri-publishing-dashboard' ); ?></small></td>
						<td><?php $all_flags = array_merge( $item['privacy_flags'], $item['safety_flags'], $item['source_flags'], $item['copyright_flags'] ); echo empty( $all_flags ) ? esc_html__( 'None', 'sabri-publishing-dashboard' ) : esc_html( implode( ', ', $all_flags ) ); ?></td>
						<td><a class="spdb-button spdb-button--secondary" href="<?php echo esc_url( $item['native_review_url'] ); ?>"><?php esc_html_e( 'Open Native Review', 'sabri-publishing-dashboard' ); ?></a><?php if ( ! empty( $item['allowed_operations'] ) ) : ?><small><?php echo esc_html( sprintf( __( 'Currently authorized operations: %s', 'sabri-publishing-dashboard' ), implode( ', ', $item['allowed_operations'] ) ) ); ?></small><?php endif; ?></td>
					</tr>
				<?php endforeach; ?>
				</tbody>
			</table>
		</div>
	<?php endif; ?>

	<?php if ( $pages > 1 ) : ?>
		<nav class="spdb-pagination" aria-label="<?php esc_attr_e( 'Review queue pagination', 'sabri-publishing-dashboard' ); ?>">
			<?php if ( $page > 1 ) : ?><a class="spdb-button spdb-button--secondary" href="<?php echo esc_url( add_query_arg( array_merge( $base_query, array( 'page' => $page - 1 ) ), SPDB_Dashboard_Router::route_url( 'review' ) ) ); ?>"><?php esc_html_e( 'Previous page', 'sabri-publishing-dashboard' ); ?></a><?php endif; ?>
			<span><?php echo esc_html( sprintf( __( 'Page %1$d of %2$d', 'sabri-publishing-dashboard' ), $page, $pages ) ); ?></span>
			<?php if ( $page < $pages ) : ?><a class="spdb-button spdb-button--secondary" href="<?php echo esc_url( add_query_arg( array_merge( $base_query, array( 'page' => $page + 1 ) ), SPDB_Dashboard_Router::route_url( 'review' ) ) ); ?>"><?php esc_html_e( 'Next page', 'sabri-publishing-dashboard' ); ?></a><?php endif; ?>
		</nav>
	<?php endif; ?>
	<p class="spdb-caption"><?php echo esc_html( sprintf( __( 'Generated at %s. Native reported totals may exceed the bounded accessible window; the native module remains the final source of truth.', 'sabri-publishing-dashboard' ), $review_result['generated_at_gmt'] ) ); ?></p>
</section>
