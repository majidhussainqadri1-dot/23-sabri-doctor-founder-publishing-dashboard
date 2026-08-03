<?php
/** File 22 create-entry projection. */
defined( 'ABSPATH' ) || exit;
$title_id = $instance_id . '-create-title';
?>
<section class="spdb-operations-section" aria-labelledby="<?php echo esc_attr( $title_id ); ?>">
	<p class="spdb-eyebrow"><?php esc_html_e( 'Canonical authoring boundary', 'sabri-publishing-dashboard' ); ?></p>
	<h2 id="<?php echo esc_attr( $title_id ); ?>"><?php esc_html_e( 'Create New Content', 'sabri-publishing-dashboard' ); ?></h2>
	<div class="spdb-operations-panel">
		<h3><?php esc_html_e( 'Universal Post Composer', 'sabri-publishing-dashboard' ); ?></h3>
		<p><?php esc_html_e( 'File 22 is the only platform-wide create and edit surface. File 23 never creates a second composer or writes native publication bodies.', 'sabri-publishing-dashboard' ); ?></p>
		<?php if ( '' !== $composer_url ) : ?>
			<a class="spdb-button" href="<?php echo esc_url( $composer_url ); ?>"><?php esc_html_e( 'Open Universal Post Composer', 'sabri-publishing-dashboard' ); ?></a>
		<?php else : ?>
			<div class="spdb-notice spdb-notice--warning" role="status"><?php esc_html_e( 'The approved File 22 Composer destination is unavailable. No replacement form is shown.', 'sabri-publishing-dashboard' ); ?></div>
		<?php endif; ?>
	</div>
	<?php if ( SPDB_Capabilities::current_user_can( 'spdb_request_ai_assistance' ) ) : ?>
		<section class="spdb-operations-panel" aria-labelledby="<?php echo esc_attr( $instance_id . '-ai-title' ); ?>">
			<h3 id="<?php echo esc_attr( $instance_id . '-ai-title' ); ?>"><?php esc_html_e( 'Optional File 16 Assistance', 'sabri-publishing-dashboard' ); ?></h3>
			<p><?php esc_html_e( 'Suggestions require human review, source-linked citations and explicit confirmation. AI cannot publish, approve, reject, schedule, diagnose, prescribe, recommend potency or dosage, or receive patient-identifying data.', 'sabri-publishing-dashboard' ); ?></p>
			<form class="spdb-action-form" data-spdb-operation-form data-endpoint="ai-assistance" data-method="POST">
				<label><span><?php esc_html_e( 'Assistance type', 'sabri-publishing-dashboard' ); ?></span><select name="type"><option value="outline"><?php esc_html_e( 'Outline', 'sabri-publishing-dashboard' ); ?></option><option value="title"><?php esc_html_e( 'Title suggestions', 'sabri-publishing-dashboard' ); ?></option><option value="grammar"><?php esc_html_e( 'Spelling and grammar', 'sabri-publishing-dashboard' ); ?></option><option value="keywords"><?php esc_html_e( 'Keywords', 'sabri-publishing-dashboard' ); ?></option><option value="source_completeness"><?php esc_html_e( 'Source completeness', 'sabri-publishing-dashboard' ); ?></option><option value="alt_text"><?php esc_html_e( 'Alt-text draft', 'sabri-publishing-dashboard' ); ?></option><option value="summary"><?php esc_html_e( 'Summary draft', 'sabri-publishing-dashboard' ); ?></option><option value="translation"><?php esc_html_e( 'Translation draft', 'sabri-publishing-dashboard' ); ?></option><option value="related_knowledge"><?php esc_html_e( 'Related knowledge', 'sabri-publishing-dashboard' ); ?></option></select></label>
				<label><span><?php esc_html_e( 'Privacy-filtered prompt', 'sabri-publishing-dashboard' ); ?></span><textarea name="text" maxlength="4000" required></textarea></label>
				<button type="submit" class="spdb-button spdb-button--secondary"><?php esc_html_e( 'Request Suggestion', 'sabri-publishing-dashboard' ); ?></button>
			</form>
		</section>
	<?php endif; ?>
	<div class="spdb-live-region" role="status" aria-live="polite" data-spdb-operation-status></div>
</section>
