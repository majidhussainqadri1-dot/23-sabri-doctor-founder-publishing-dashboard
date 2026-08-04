<?php
/** Secure reports and exports template. */
defined( 'ABSPATH' ) || exit;
$title_id = $instance_id . '-reports-title';
$report_options = array(
	'publication_history' => __( 'Publication history', 'sabri-publishing-dashboard' ),
	'content_performance' => __( 'Content performance', 'sabri-publishing-dashboard' ),
	'review_history' => __( 'Review history', 'sabri-publishing-dashboard' ),
	'knowledge_portfolio' => __( 'Knowledge portfolio', 'sabri-publishing-dashboard' ),
	'comment_response' => __( 'Comment response', 'sabri-publishing-dashboard' ),
	'monthly_summary' => __( 'Monthly summary', 'sabri-publishing-dashboard' ),
	'institution_publishing' => __( 'Institution publishing', 'sabri-publishing-dashboard' ),
	'doctor_contributions' => __( 'Doctor contributions', 'sabri-publishing-dashboard' ),
	'editorial_backlog' => __( 'Editorial backlog', 'sabri-publishing-dashboard' ),
	'campaign_results' => __( 'Campaign results', 'sabri-publishing-dashboard' ),
	'corrections_retractions' => __( 'Corrections and retractions', 'sabri-publishing-dashboard' ),
	'source_completeness' => __( 'Source completeness', 'sabri-publishing-dashboard' ),
	'safety_incidents' => __( 'Safety incidents', 'sabri-publishing-dashboard' ),
	'content_gaps' => __( 'Content gaps', 'sabri-publishing-dashboard' ),
	'calendar_health' => __( 'Calendar health', 'sabri-publishing-dashboard' ),
);
?>
<section class="spdb-operations-section" aria-labelledby="<?php echo esc_attr( $title_id ); ?>">
	<p class="spdb-eyebrow"><?php esc_html_e( 'Private, filtered and audited', 'sabri-publishing-dashboard' ); ?></p><h2 id="<?php echo esc_attr( $title_id ); ?>"><?php esc_html_e( 'Reports and Exports', 'sabri-publishing-dashboard' ); ?></h2>
	<form class="spdb-action-form spdb-operations-panel" data-spdb-operation-form data-endpoint="exports" data-method="POST">
		<div class="spdb-form-grid"><label><span><?php esc_html_e( 'Report', 'sabri-publishing-dashboard' ); ?></span><select name="report_key"><?php foreach ( $report_options as $key => $label ) : ?><option value="<?php echo esc_attr( $key ); ?>"><?php echo esc_html( $label ); ?></option><?php endforeach; ?></select></label><label><span><?php esc_html_e( 'Format', 'sabri-publishing-dashboard' ); ?></span><select name="format"><option value="csv">CSV</option><option value="json">JSON</option><option value="html"><?php esc_html_e( 'Printable HTML', 'sabri-publishing-dashboard' ); ?></option><option value="pdf">PDF</option><option value="ics">iCalendar</option></select></label><label><span><?php esc_html_e( 'Scope', 'sabri-publishing-dashboard' ); ?></span><select name="scope"><option value="own"><?php esc_html_e( 'Own records only', 'sabri-publishing-dashboard' ); ?></option><?php if ( SPDB_Capabilities::current_user_can( 'spdb_view_global_analytics' ) ) : ?><option value="institution"><?php esc_html_e( 'Institution', 'sabri-publishing-dashboard' ); ?></option><?php endif; ?></select></label></div><button type="submit" class="spdb-button"><?php esc_html_e( 'Queue Secure Export', 'sabri-publishing-dashboard' ); ?></button>
	</form>
	<div class="spdb-live-region" role="status" aria-live="polite" data-spdb-operation-status></div>
	<?php if ( is_wp_error( $exports_result ) ) : ?><div class="spdb-notice spdb-notice--warning" role="alert"><?php echo esc_html( $exports_result->get_error_message() ); ?></div><?php elseif ( empty( $exports_result ) ) : ?><p class="spdb-empty-state"><?php esc_html_e( 'No export jobs have been created.', 'sabri-publishing-dashboard' ); ?></p><?php else : ?><div class="spdb-table-wrap" role="region" tabindex="0" aria-label="<?php esc_attr_e( 'Export jobs', 'sabri-publishing-dashboard' ); ?>"><table><thead><tr><th><?php esc_html_e( 'Report', 'sabri-publishing-dashboard' ); ?></th><th><?php esc_html_e( 'Format', 'sabri-publishing-dashboard' ); ?></th><th><?php esc_html_e( 'Status', 'sabri-publishing-dashboard' ); ?></th><th><?php esc_html_e( 'Rows', 'sabri-publishing-dashboard' ); ?></th><th><?php esc_html_e( 'Expires', 'sabri-publishing-dashboard' ); ?></th><th><?php esc_html_e( 'Download', 'sabri-publishing-dashboard' ); ?></th></tr></thead><tbody><?php foreach ( $exports_result as $export ) : ?><tr><td><?php echo esc_html( $export['report_key'] ); ?></td><td><?php echo esc_html( strtoupper( $export['format'] ) ); ?></td><td><?php echo esc_html( $export['status'] ); ?></td><td><?php echo esc_html( (string) $export['row_count'] ); ?></td><td><?php echo esc_html( $export['expires_at_gmt'] ); ?></td><td><?php if ( ! empty( $export['download_url'] ) ) : ?><a class="spdb-button spdb-button--secondary" href="<?php echo esc_url( $export['download_url'] ); ?>"><?php esc_html_e( 'Download', 'sabri-publishing-dashboard' ); ?></a><?php else : ?>—<?php endif; ?></td></tr><?php endforeach; ?></tbody></table></div><?php endif; ?>
</section>
