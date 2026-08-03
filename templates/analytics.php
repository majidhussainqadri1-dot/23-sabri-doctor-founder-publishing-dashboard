<?php
/** Privacy-safe aggregate analytics template. */
defined( 'ABSPATH' ) || exit;
$title_id = $instance_id . '-analytics-title';
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
