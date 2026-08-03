<?php
/** Generic strict native operational projection template. */
defined( 'ABSPATH' ) || exit;
$labels = array(
	'gaps'          => __( 'Knowledge Coverage', 'sabri-publishing-dashboard' ),
	'sources'       => __( 'Sources and Evidence', 'sabri-publishing-dashboard' ),
	'media'         => __( 'Federated Media Usage', 'sabri-publishing-dashboard' ),
	'interactions'  => __( 'Interactions and Community Inbox', 'sabri-publishing-dashboard' ),
	'revisions'     => __( 'Corrections, Revisions and Retractions', 'sabri-publishing-dashboard' ),
	'notifications' => __( 'Publishing Notifications', 'sabri-publishing-dashboard' ),
);
$title = $labels[ $operational_domain ] ?? __( 'Operational Projection', 'sabri-publishing-dashboard' );
$title_id = $instance_id . '-operations-title';
?>
<section class="spdb-operations-section" aria-labelledby="<?php echo esc_attr( $title_id ); ?>">
	<p class="spdb-eyebrow"><?php esc_html_e( 'Native ownership preserved', 'sabri-publishing-dashboard' ); ?></p>
	<h2 id="<?php echo esc_attr( $title_id ); ?>"><?php echo esc_html( $title ); ?></h2>
	<p><?php esc_html_e( 'This page contains bounded, privacy-filtered native projections. File 23 does not copy source registries, media binaries, message bodies, comments, patient records, review ledgers, or notification delivery records.', 'sabri-publishing-dashboard' ); ?></p>

	<?php if ( is_wp_error( $operational_result ) ) : ?>
		<div class="spdb-notice spdb-notice--warning" role="alert"><?php echo esc_html( $operational_result->get_error_message() ); ?></div>
	<?php else : ?>
		<div class="spdb-status-grid">
			<article><span><?php esc_html_e( 'Eligible items', 'sabri-publishing-dashboard' ); ?></span><strong><?php echo esc_html( (string) count( $operational_result['items'] ?? array() ) ); ?></strong></article>
			<article><span><?php esc_html_e( 'Native providers', 'sabri-publishing-dashboard' ); ?></span><strong><?php echo esc_html( (string) count( $operational_result['providers'] ?? array() ) ); ?></strong></article>
			<article><span><?php esc_html_e( 'Provider errors', 'sabri-publishing-dashboard' ); ?></span><strong><?php echo esc_html( (string) count( $operational_result['provider_errors'] ?? array() ) ); ?></strong></article>
		</div>
		<?php if ( empty( $operational_result['items'] ) ) : ?>
			<div class="spdb-empty-state" role="status"><h3><?php esc_html_e( 'No eligible native items', 'sabri-publishing-dashboard' ); ?></h3><p><?php echo esc_html( 'no_compatible_provider' === ( $operational_result['empty_reason'] ?? '' ) ? __( 'No compatible provider currently implements this projection contract.', 'sabri-publishing-dashboard' ) : __( 'No eligible items match the current authorization and filters.', 'sabri-publishing-dashboard' ) ); ?></p></div>
		<?php else : ?>
			<div class="spdb-operations-grid">
				<?php foreach ( $operational_result['items'] as $item ) : ?>
					<article class="spdb-operational-card spdb-severity--<?php echo esc_attr( $item['severity'] ); ?>">
						<div class="spdb-card-meta"><span><?php echo esc_html( $item['provider_key'] ); ?></span><span><?php echo esc_html( $item['status'] ); ?></span><span><?php echo esc_html( $item['privacy_class'] ); ?></span></div>
						<h3><?php echo esc_html( $item['title'] ); ?></h3>
						<?php if ( '' !== $item['summary'] ) : ?><p><?php echo esc_html( $item['summary'] ); ?></p><?php endif; ?>
						<dl class="spdb-definition-grid"><div><dt><?php esc_html_e( 'Type', 'sabri-publishing-dashboard' ); ?></dt><dd><?php echo esc_html( $item['object_type'] ); ?></dd></div><div><dt><?php esc_html_e( 'Updated', 'sabri-publishing-dashboard' ); ?></dt><dd><?php echo esc_html( $item['updated_at'] ); ?></dd></div><div><dt><?php esc_html_e( 'Severity', 'sabri-publishing-dashboard' ); ?></dt><dd><?php echo esc_html( $item['severity'] ); ?></dd></div></dl>
						<?php if ( ! empty( $item['metadata'] ) ) : ?><dl class="spdb-definition-grid"><?php foreach ( $item['metadata'] as $key => $value ) : ?><div><dt><?php echo esc_html( ucwords( str_replace( '_', ' ', $key ) ) ); ?></dt><dd><?php echo esc_html( $value ); ?></dd></div><?php endforeach; ?></dl><?php endif; ?>
						<?php if ( '' !== $item['destination_url'] ) : ?><a class="spdb-button spdb-button--secondary" href="<?php echo esc_url( $item['destination_url'] ); ?>"><?php esc_html_e( 'Open Native Record', 'sabri-publishing-dashboard' ); ?></a><?php endif; ?>
					</article>
				<?php endforeach; ?>
			</div>
		<?php endif; ?>
		<?php if ( ! empty( $operational_result['provider_errors'] ) ) : ?><details class="spdb-operations-panel"><summary><?php esc_html_e( 'Degraded providers', 'sabri-publishing-dashboard' ); ?></summary><ul><?php foreach ( $operational_result['provider_errors'] as $provider => $code ) : ?><li><strong><?php echo esc_html( $provider ); ?></strong>: <?php echo esc_html( $code ); ?></li><?php endforeach; ?></ul></details><?php endif; ?>
	<?php endif; ?>
</section>
