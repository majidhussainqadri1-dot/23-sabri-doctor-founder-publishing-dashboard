<?php
/** Privacy-safe aggregate analytics and Publishing Intelligence template. */
defined( 'ABSPATH' ) || exit;
$title_id = $instance_id . '-analytics-title';
$intelligence_title_id = $instance_id . '-intelligence-title';
$intelligence_catalog = class_exists( 'SPDB_Publishing_Intelligence' ) ? SPDB_Publishing_Intelligence::catalog() : array();
?>
<section class="spdb-operations-section" aria-labelledby="<?php echo esc_attr( $title_id ); ?>">
	<p class="spdb-eyebrow"><?php esc_html_e( 'Provider-owned aggregates', 'sabri-publishing-dashboard' ); ?></p>
	<h2 id="<?php echo esc_attr( $title_id ); ?>"><?php esc_html_e( 'Analytics', 'sabri-publishing-dashboard' ); ?></h2>
	<?php if ( is_wp_error( $analytics_result ) ) : ?>
		<div class="spdb-notice spdb-notice--warning" role="alert"><?php echo esc_html( $analytics_result->get_error_message() ); ?></div>
	<?php else : ?>
		<p><?php echo esc_html( (string) ( $analytics_result['privacy_notice'] ?? '' ) ); ?></p>
		<?php if ( empty( $analytics_result['metrics'] ) ) : ?>
			<div class="spdb-empty-state" role="status"><h3><?php esc_html_e( 'No eligible aggregate metrics', 'sabri-publishing-dashboard' ); ?></h3><p><?php esc_html_e( 'No compatible provider supplied privacy-safe metrics for this account and scope.', 'sabri-publishing-dashboard' ); ?></p></div>
		<?php else : ?>
			<div class="spdb-operations-grid">
				<?php foreach ( $analytics_result['metrics'] as $metric ) : ?>
					<article class="spdb-operational-card">
						<span class="spdb-eyebrow"><?php echo esc_html( $metric['provider_key'] ); ?></span>
						<h3><?php echo esc_html( $metric['label'] ); ?></h3>
						<strong class="spdb-metric-value"><?php echo ! empty( $metric['suppressed'] ) ? esc_html__( 'Suppressed', 'sabri-publishing-dashboard' ) : esc_html( (string) $metric['value'] ); ?></strong>
						<p><?php echo esc_html( $metric['definition'] ); ?></p>
						<small><?php echo esc_html( sprintf( __( '%1$s · cohort %2$d · threshold %3$d', 'sabri-publishing-dashboard' ), $metric['interval'], $metric['cohort_count'], $metric['privacy_threshold'] ) ); ?></small>
					</article>
				<?php endforeach; ?>
			</div>
		<?php endif; ?>
	<?php endif; ?>
</section>

<section class="spdb-operations-section" aria-labelledby="<?php echo esc_attr( $intelligence_title_id ); ?>">
	<p class="spdb-eyebrow"><?php esc_html_e( 'Future Publishing Intelligence Superset', 'sabri-publishing-dashboard' ); ?></p>
	<h2 id="<?php echo esc_attr( $intelligence_title_id ); ?>"><?php esc_html_e( 'Publishing Intelligence — 24 Advanced Facilities', 'sabri-publishing-dashboard' ); ?></h2>
	<p><?php esc_html_e( 'These facilities are federated, advisory or projection-based. File 23 does not become the canonical owner of content, comments, search indexes, analytics events, patient data, notifications or clinical decisions.', 'sabri-publishing-dashboard' ); ?></p>
	<?php if ( empty( $intelligence_catalog ) ) : ?>
		<div class="spdb-notice spdb-notice--warning" role="status"><?php esc_html_e( 'Publishing Intelligence is unavailable.', 'sabri-publishing-dashboard' ); ?></div>
	<?php else : ?>
		<div class="spdb-operations-grid">
			<?php foreach ( $intelligence_catalog as $feature_key => $feature ) : ?>
				<article class="spdb-operational-card" data-spdb-intelligence-feature="<?php echo esc_attr( $feature_key ); ?>">
					<span class="spdb-eyebrow"><?php echo esc_html( $feature['id'] . ' · ' . $feature['priority'] ); ?></span>
					<h3><?php echo esc_html( $feature['label'] ); ?></h3>
					<p><?php echo esc_html( $feature['purpose'] ); ?></p>
					<small><?php echo esc_html( sprintf( __( 'Ownership mode: %s · no canonical write authority', 'sabri-publishing-dashboard' ), $feature['ownership'] ) ); ?></small>
				</article>
			<?php endforeach; ?>
		</div>
		<p class="spdb-notice spdb-notice--info" role="note"><?php esc_html_e( 'Detail snapshots are available through the private File 23 REST intelligence contract; conversational answers and what-if simulations remain advisory and cannot publish, diagnose, prescribe, rank for payment, or bypass native authorization.', 'sabri-publishing-dashboard' ); ?></p>
	<?php endif; ?>
</section>
