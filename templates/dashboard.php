<?php
/**
 * Main private dashboard template.
 *
 * Available variables include role, inventory, collections, review, calendar,
 * saved-view, overview, and system projections resolved by the dashboard page.
 *
 * @package Sabri_Publishing_Dashboard
 */

defined( 'ABSPATH' ) || exit;

$main_id           = $instance_id . '-main';
$saved_title_id    = $instance_id . '-saved-views-title';
$system_title_id   = $instance_id . '-system-status-title';
$overview_title_id = $instance_id . '-overview-title';
$provider_title_id = $instance_id . '-provider-title';
?>
<a class="spdb-skip-link" href="#<?php echo esc_attr( $main_id ); ?>"><?php esc_html_e( 'Skip to dashboard content', 'sabri-publishing-dashboard' ); ?></a>
<div class="spdb-shell" data-spdb-workspace="<?php echo esc_attr( (string) $workspace['key'] ); ?>" data-spdb-instance="<?php echo esc_attr( $instance_id ); ?>">
	<header class="spdb-header">
		<div>
			<p class="spdb-eyebrow"><?php esc_html_e( 'Sabri Social Homeopathy Platform', 'sabri-publishing-dashboard' ); ?></p>
			<h1><?php esc_html_e( 'Publishing Dashboard', 'sabri-publishing-dashboard' ); ?></h1>
			<p class="spdb-workspace-label">
				<?php echo esc_html( (string) $workspace['label'] ); ?>
				<?php if ( $workspace['read_only'] ) : ?><span class="spdb-badge spdb-badge--warning"><?php esc_html_e( 'Read-only', 'sabri-publishing-dashboard' ); ?></span><?php endif; ?>
			</p>
		</div>
		<div class="spdb-phase" aria-label="<?php esc_attr_e( 'Implementation phase', 'sabri-publishing-dashboard' ); ?>">
			<span><?php esc_html_e( 'Phase 23F', 'sabri-publishing-dashboard' ); ?></span>
			<strong><?php esc_html_e( 'Collections and Knowledge', 'sabri-publishing-dashboard' ); ?></strong>
		</div>
	</header>

	<div class="spdb-layout">
		<nav class="spdb-navigation" aria-label="<?php esc_attr_e( 'Publishing dashboard', 'sabri-publishing-dashboard' ); ?>">
			<ul>
				<?php foreach ( $navigation as $view_key => $item ) : ?>
					<li><a href="<?php echo esc_url( $item['url'] ); ?>" <?php echo $current === $view_key ? 'aria-current="page"' : ''; ?>><?php echo esc_html( $item['label'] ); ?></a></li>
				<?php endforeach; ?>
			</ul>
		</nav>

		<main id="<?php echo esc_attr( $main_id ); ?>" class="spdb-main" tabindex="-1">
			<?php if ( 'workspace' === $current ) : ?>
				<?php include SPDB_PLUGIN_DIR . 'templates/workspace.php'; ?>
			<?php elseif ( 'inventory' === $current ) : ?>
				<?php include SPDB_PLUGIN_DIR . 'templates/inventory.php'; ?>
			<?php elseif ( 'collections' === $current ) : ?>
				<?php include SPDB_PLUGIN_DIR . 'templates/collections.php'; ?>
			<?php elseif ( 'review' === $current ) : ?>
				<?php include SPDB_PLUGIN_DIR . 'templates/review.php'; ?>
			<?php elseif ( 'calendar' === $current ) : ?>
				<?php include SPDB_PLUGIN_DIR . 'templates/calendar.php'; ?>
			<?php elseif ( 'saved-views' === $current ) : ?>
				<section aria-labelledby="<?php echo esc_attr( $saved_title_id ); ?>">
					<div class="spdb-section-heading"><div><p class="spdb-eyebrow"><?php esc_html_e( 'Personal workspace', 'sabri-publishing-dashboard' ); ?></p><h2 id="<?php echo esc_attr( $saved_title_id ); ?>"><?php esc_html_e( 'Saved Views', 'sabri-publishing-dashboard' ); ?></h2></div></div>
					<p><?php esc_html_e( 'Saved views contain only non-clinical dashboard filters. They never grant publishing authority and never store patient information.', 'sabri-publishing-dashboard' ); ?></p>
					<?php if ( $workspace['read_only'] ) : ?>
						<div class="spdb-notice spdb-notice--warning" role="status"><?php esc_html_e( 'Your current account status permits viewing existing saved views only. Creating or deleting saved views is disabled.', 'sabri-publishing-dashboard' ); ?></div>
					<?php else : ?>
						<form class="spdb-saved-view-form" data-spdb-saved-view-form>
							<div class="spdb-form-grid">
								<label><span><?php esc_html_e( 'View name', 'sabri-publishing-dashboard' ); ?></span><input type="text" name="label" maxlength="80" required autocomplete="off"></label>
								<label><span><?php esc_html_e( 'Status filter', 'sabri-publishing-dashboard' ); ?></span><select name="status"><option value=""><?php esc_html_e( 'Any status', 'sabri-publishing-dashboard' ); ?></option><option value="draft"><?php esc_html_e( 'Draft', 'sabri-publishing-dashboard' ); ?></option><option value="submitted"><?php esc_html_e( 'Submitted', 'sabri-publishing-dashboard' ); ?></option><option value="changes_requested"><?php esc_html_e( 'Changes requested', 'sabri-publishing-dashboard' ); ?></option><option value="scheduled"><?php esc_html_e( 'Scheduled', 'sabri-publishing-dashboard' ); ?></option><option value="published"><?php esc_html_e( 'Published', 'sabri-publishing-dashboard' ); ?></option></select></label>
								<label><span><?php esc_html_e( 'Provider key', 'sabri-publishing-dashboard' ); ?></span><input type="text" name="provider" maxlength="64" pattern="[a-z0-9_-]*" autocomplete="off"></label>
								<label><span><?php esc_html_e( 'Sort order', 'sabri-publishing-dashboard' ); ?></span><select name="sort"><option value="modified"><?php esc_html_e( 'Last modified', 'sabri-publishing-dashboard' ); ?></option><option value="created"><?php esc_html_e( 'Created date', 'sabri-publishing-dashboard' ); ?></option><option value="title"><?php esc_html_e( 'Title', 'sabri-publishing-dashboard' ); ?></option></select></label>
							</div>
							<button type="submit" class="spdb-button"><?php esc_html_e( 'Save View', 'sabri-publishing-dashboard' ); ?></button>
						</form>
					<?php endif; ?>
					<div class="spdb-live-region" role="status" aria-live="polite" data-spdb-status></div>
					<p class="spdb-empty-state" <?php echo empty( $saved_views ) ? '' : 'hidden'; ?>><?php esc_html_e( 'No saved views are available.', 'sabri-publishing-dashboard' ); ?></p>
					<ul class="spdb-saved-view-list" data-spdb-saved-view-list>
						<?php foreach ( $saved_views as $saved_view ) : ?>
							<li data-view-id="<?php echo esc_attr( $saved_view['id'] ); ?>"><div><strong><?php echo esc_html( $saved_view['label'] ); ?></strong><small><?php echo esc_html( wp_json_encode( $saved_view['filters'] ) ); ?></small></div><?php if ( ! $workspace['read_only'] ) : ?><button type="button" class="spdb-button spdb-button--secondary" data-spdb-delete-view="<?php echo esc_attr( $saved_view['id'] ); ?>"><?php esc_html_e( 'Delete', 'sabri-publishing-dashboard' ); ?></button><?php endif; ?></li>
						<?php endforeach; ?>
					</ul>
				</section>
			<?php elseif ( 'system-status' === $current ) : ?>
				<section aria-labelledby="<?php echo esc_attr( $system_title_id ); ?>">
					<p class="spdb-eyebrow"><?php esc_html_e( 'Operational diagnostics', 'sabri-publishing-dashboard' ); ?></p><h2 id="<?php echo esc_attr( $system_title_id ); ?>"><?php esc_html_e( 'System Status', 'sabri-publishing-dashboard' ); ?></h2>
					<div class="spdb-status-grid">
						<article><span><?php esc_html_e( 'Environment', 'sabri-publishing-dashboard' ); ?></span><strong><?php echo esc_html( $system_state['environment'] ); ?></strong></article>
						<article><span><?php esc_html_e( 'Plugin version', 'sabri-publishing-dashboard' ); ?></span><strong><?php echo esc_html( $system_state['plugin_version'] ); ?></strong></article>
						<article><span><?php esc_html_e( 'Adapter contract', 'sabri-publishing-dashboard' ); ?></span><strong><?php echo esc_html( $system_state['contract_version'] ); ?></strong></article>
						<article><span><?php esc_html_e( 'Registered providers', 'sabri-publishing-dashboard' ); ?></span><strong><?php echo esc_html( (string) $system_state['provider_count'] ); ?></strong></article>
						<article><span><?php esc_html_e( 'Provider errors', 'sabri-publishing-dashboard' ); ?></span><strong><?php echo esc_html( (string) $system_state['provider_errors'] ); ?></strong></article>
						<article><span><?php esc_html_e( 'Membership Core', 'sabri-publishing-dashboard' ); ?></span><strong><?php echo $system_state['membership']['available'] ? esc_html__( 'Compatible', 'sabri-publishing-dashboard' ) : esc_html__( 'Unavailable', 'sabri-publishing-dashboard' ); ?></strong></article>
					</div>
					<p class="spdb-caption"><?php echo esc_html( sprintf( __( 'Generated at %s (GMT). No patient data or secrets are included.', 'sabri-publishing-dashboard' ), $system_state['generated_at_gmt'] ) ); ?></p>
				</section>
			<?php else : ?>
				<section aria-labelledby="<?php echo esc_attr( $overview_title_id ); ?>">
					<p class="spdb-eyebrow"><?php esc_html_e( 'Operational overview', 'sabri-publishing-dashboard' ); ?></p><h2 id="<?php echo esc_attr( $overview_title_id ); ?>"><?php esc_html_e( 'Overview', 'sabri-publishing-dashboard' ); ?></h2>
					<?php if ( ! empty( $overview['alerts'] ) ) : ?><div class="spdb-alerts" aria-label="<?php esc_attr_e( 'Priority notices', 'sabri-publishing-dashboard' ); ?>"><?php foreach ( $overview['alerts'] as $alert ) : ?><div class="spdb-notice spdb-notice--<?php echo esc_attr( $alert['level'] ); ?>" role="<?php echo 'critical' === $alert['level'] ? 'alert' : 'status'; ?>"><?php echo esc_html( $alert['message'] ); ?></div><?php endforeach; ?></div><?php endif; ?>
					<div class="spdb-card-grid"><?php foreach ( $overview['cards'] as $card ) : ?><article class="spdb-card"><span><?php echo esc_html( $card['label'] ); ?></span><strong><?php echo esc_html( $card['value'] ); ?></strong><small><?php echo esc_html( $card['note'] ); ?></small></article><?php endforeach; ?></div>
					<section class="spdb-provider-section" aria-labelledby="<?php echo esc_attr( $provider_title_id ); ?>">
						<h3 id="<?php echo esc_attr( $provider_title_id ); ?>"><?php esc_html_e( 'Provider Readiness', 'sabri-publishing-dashboard' ); ?></h3>
						<?php if ( empty( $overview['providers'] ) ) : ?><p><?php esc_html_e( 'No provider adapter is registered. Native content counts and actions remain unavailable by design.', 'sabri-publishing-dashboard' ); ?></p><?php else : ?>
							<div class="spdb-table-wrap" role="region" aria-label="<?php esc_attr_e( 'Provider readiness table', 'sabri-publishing-dashboard' ); ?>" tabindex="0"><table><thead><tr><th><?php esc_html_e( 'Provider', 'sabri-publishing-dashboard' ); ?></th><th><?php esc_html_e( 'Version', 'sabri-publishing-dashboard' ); ?></th><th><?php esc_html_e( 'Capability', 'sabri-publishing-dashboard' ); ?></th><th><?php esc_html_e( 'Acceptance', 'sabri-publishing-dashboard' ); ?></th></tr></thead><tbody><?php foreach ( $overview['providers'] as $provider ) : ?><tr><td><?php echo esc_html( $provider['provider_name'] ); ?><small><?php echo esc_html( $provider['provider_key'] ); ?></small></td><td><?php echo esc_html( $provider['provider_version'] ); ?></td><td><?php echo esc_html( $provider['declared_capability'] ); ?></td><td><?php echo esc_html( $provider['acceptance_state'] ); ?></td></tr><?php endforeach; ?></tbody></table></div>
						<?php endif; ?>
					</section>
				</section>
			<?php endif; ?>
		</main>
	</div>
</div>
