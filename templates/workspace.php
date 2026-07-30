<?php
/**
 * Role-specific Founder and Doctor publishing workspace.
 *
 * Available variables: $role_workspace, $workspace, and $instance_id.
 *
 * @package Sabri_Publishing_Dashboard
 */

defined( 'ABSPATH' ) || exit;

$title_id    = $instance_id . '-role-workspace-title';
$policy_id   = $instance_id . '-publishing-policy-title';
$profile_id  = $instance_id . '-profile-status-title';
$knowledge_id= $instance_id . '-knowledge-status-title';
$activity_id = $instance_id . '-workspace-activity-title';
?>
<section class="spdb-role-workspace" aria-labelledby="<?php echo esc_attr( $title_id ); ?>">
	<p class="spdb-eyebrow"><?php esc_html_e( 'Role-specific operations', 'sabri-publishing-dashboard' ); ?></p>
	<h2 id="<?php echo esc_attr( $title_id ); ?>"><?php echo esc_html( (string) $workspace['label'] ); ?></h2>
	<p><?php esc_html_e( 'This view contains validated native projections and safe native destinations only. File 23 does not copy publication bodies, profiles, knowledge records, or publishing state.', 'sabri-publishing-dashboard' ); ?></p>

	<?php if ( ! empty( $role_workspace['alerts'] ) ) : ?>
		<div class="spdb-alerts" aria-label="<?php esc_attr_e( 'Workspace notices', 'sabri-publishing-dashboard' ); ?>">
			<?php foreach ( $role_workspace['alerts'] as $alert ) : ?>
				<div class="spdb-notice spdb-notice--<?php echo esc_attr( (string) $alert['level'] ); ?>" role="<?php echo 'critical' === $alert['level'] ? 'alert' : 'status'; ?>">
					<?php echo esc_html( (string) $alert['message'] ); ?>
				</div>
			<?php endforeach; ?>
		</div>
	<?php endif; ?>

	<section class="spdb-workspace-policy" aria-labelledby="<?php echo esc_attr( $policy_id ); ?>">
		<div class="spdb-section-heading">
			<div>
				<p class="spdb-eyebrow"><?php esc_html_e( 'Publishing policy', 'sabri-publishing-dashboard' ); ?></p>
				<h3 id="<?php echo esc_attr( $policy_id ); ?>"><?php echo esc_html( (string) $role_workspace['publishing_policy']['label'] ); ?></h3>
			</div>
			<span class="spdb-badge"><?php echo esc_html( (string) $role_workspace['publishing_policy']['mode'] ); ?></span>
		</div>
		<p><?php echo esc_html( (string) $role_workspace['publishing_policy']['summary'] ); ?></p>
		<?php if ( ! empty( $role_workspace['publishing_policy']['content_classes'] ) ) : ?>
			<ul class="spdb-policy-tags" aria-label="<?php esc_attr_e( 'Authorized content classes', 'sabri-publishing-dashboard' ); ?>">
				<?php foreach ( $role_workspace['publishing_policy']['content_classes'] as $content_class ) : ?>
					<li><?php echo esc_html( (string) $content_class ); ?></li>
				<?php endforeach; ?>
			</ul>
		<?php endif; ?>
	</section>

	<?php if ( ! empty( $role_workspace['cards'] ) ) : ?>
		<div class="spdb-workspace-card-grid" aria-label="<?php esc_attr_e( 'Native workspace summary', 'sabri-publishing-dashboard' ); ?>">
			<?php foreach ( $role_workspace['cards'] as $card ) : ?>
				<article class="spdb-workspace-card spdb-workspace-card--<?php echo esc_attr( (string) $card['priority'] ); ?>">
					<span><?php echo esc_html( (string) $card['label'] ); ?></span>
					<strong><?php echo esc_html( (string) $card['value'] ); ?></strong>
					<?php if ( '' !== $card['note'] ) : ?><small><?php echo esc_html( (string) $card['note'] ); ?></small><?php endif; ?>
					<small><?php echo esc_html( (string) $card['provider_key'] ); ?><?php echo '' !== $card['source_timestamp'] ? ' · ' . esc_html( (string) $card['source_timestamp'] ) : ''; ?></small>
				</article>
			<?php endforeach; ?>
		</div>
	<?php else : ?>
		<p class="spdb-empty-state"><?php esc_html_e( 'No validated native workspace summary is available.', 'sabri-publishing-dashboard' ); ?></p>
	<?php endif; ?>

	<section class="spdb-workspace-actions" aria-labelledby="<?php echo esc_attr( $instance_id . '-quick-actions-title' ); ?>">
		<h3 id="<?php echo esc_attr( $instance_id . '-quick-actions-title' ); ?>"><?php esc_html_e( 'Verified Native Actions', 'sabri-publishing-dashboard' ); ?></h3>
		<?php if ( ! empty( $role_workspace['actions'] ) ) : ?>
			<div class="spdb-action-grid">
				<?php foreach ( $role_workspace['actions'] as $action ) : ?>
					<a class="spdb-action-card<?php echo ! empty( $action['mutating'] ) ? ' spdb-action-card--mutating' : ''; ?>" href="<?php echo esc_url( (string) $action['destination'] ); ?>">
						<strong><?php echo esc_html( (string) $action['label'] ); ?></strong>
						<?php if ( '' !== $action['description'] ) : ?><span><?php echo esc_html( (string) $action['description'] ); ?></span><?php endif; ?>
						<small><?php echo esc_html( (string) $action['provider_key'] ); ?> · <?php echo esc_html( (string) $action['action_type'] ); ?></small>
					</a>
				<?php endforeach; ?>
			</div>
		<?php else : ?>
			<p class="spdb-empty-state"><?php esc_html_e( 'No native action currently satisfies capability, account-state, ownership, Founder-policy, destination-safety, and adapter-acceptance gates.', 'sabri-publishing-dashboard' ); ?></p>
		<?php endif; ?>
	</section>

	<div class="spdb-workspace-columns">
		<section aria-labelledby="<?php echo esc_attr( $profile_id ); ?>">
			<h3 id="<?php echo esc_attr( $profile_id ); ?>"><?php esc_html_e( 'Profile and Eligibility', 'sabri-publishing-dashboard' ); ?></h3>
			<?php if ( empty( $role_workspace['profiles'] ) ) : ?>
				<p class="spdb-empty-state"><?php esc_html_e( 'No validated native profile projection is available.', 'sabri-publishing-dashboard' ); ?></p>
			<?php else : ?>
				<?php foreach ( $role_workspace['profiles'] as $profile ) : ?>
					<article class="spdb-projection-panel">
						<dl>
							<div><dt><?php esc_html_e( 'Provider', 'sabri-publishing-dashboard' ); ?></dt><dd><?php echo esc_html( (string) $profile['provider_key'] ); ?></dd></div>
							<div><dt><?php esc_html_e( 'Completion', 'sabri-publishing-dashboard' ); ?></dt><dd><?php echo null === $profile['completion_percent'] ? esc_html__( 'Unavailable', 'sabri-publishing-dashboard' ) : esc_html( (string) $profile['completion_percent'] . '%' ); ?></dd></div>
							<div><dt><?php esc_html_e( 'Eligibility', 'sabri-publishing-dashboard' ); ?></dt><dd><?php echo esc_html( (string) $profile['eligibility'] ); ?></dd></div>
							<div><dt><?php esc_html_e( 'Verification', 'sabri-publishing-dashboard' ); ?></dt><dd><?php echo '' === $profile['verification_state'] ? esc_html__( 'Unavailable', 'sabri-publishing-dashboard' ) : esc_html( (string) $profile['verification_state'] ); ?></dd></div>
						</dl>
						<div class="spdb-inline-actions">
							<?php if ( '' !== $profile['edit_destination'] ) : ?><a href="<?php echo esc_url( (string) $profile['edit_destination'] ); ?>"><?php esc_html_e( 'Edit Native Profile', 'sabri-publishing-dashboard' ); ?></a><?php endif; ?>
							<?php if ( '' !== $profile['public_destination'] ) : ?><a href="<?php echo esc_url( (string) $profile['public_destination'] ); ?>"><?php esc_html_e( 'View Public Profile', 'sabri-publishing-dashboard' ); ?></a><?php endif; ?>
						</div>
					</article>
				<?php endforeach; ?>
			<?php endif; ?>
		</section>

		<section aria-labelledby="<?php echo esc_attr( $knowledge_id ); ?>">
			<h3 id="<?php echo esc_attr( $knowledge_id ); ?>"><?php esc_html_e( 'Knowledge Portfolio', 'sabri-publishing-dashboard' ); ?></h3>
			<?php if ( empty( $role_workspace['knowledge'] ) ) : ?>
				<p class="spdb-empty-state"><?php esc_html_e( 'No validated native knowledge projection is available.', 'sabri-publishing-dashboard' ); ?></p>
			<?php else : ?>
				<?php foreach ( $role_workspace['knowledge'] as $knowledge ) : ?>
					<article class="spdb-projection-panel">
						<dl>
							<div><dt><?php esc_html_e( 'Provider', 'sabri-publishing-dashboard' ); ?></dt><dd><?php echo esc_html( (string) $knowledge['provider_key'] ); ?></dd></div>
							<div><dt><?php esc_html_e( 'Linked items', 'sabri-publishing-dashboard' ); ?></dt><dd><?php echo null === $knowledge['linked_items'] ? esc_html__( 'Unavailable', 'sabri-publishing-dashboard' ) : esc_html( (string) $knowledge['linked_items'] ); ?></dd></div>
							<div><dt><?php esc_html_e( 'Unlinked items', 'sabri-publishing-dashboard' ); ?></dt><dd><?php echo null === $knowledge['unlinked_items'] ? esc_html__( 'Unavailable', 'sabri-publishing-dashboard' ) : esc_html( (string) $knowledge['unlinked_items'] ); ?></dd></div>
							<div><dt><?php esc_html_e( 'Successful cases', 'sabri-publishing-dashboard' ); ?></dt><dd><?php echo null === $knowledge['successful_cases'] ? esc_html__( 'Unavailable', 'sabri-publishing-dashboard' ) : esc_html( (string) $knowledge['successful_cases'] ); ?></dd></div>
						</dl>
						<?php if ( '' !== $knowledge['destination'] ) : ?><a href="<?php echo esc_url( (string) $knowledge['destination'] ); ?>"><?php esc_html_e( 'Open Native Knowledge Portfolio', 'sabri-publishing-dashboard' ); ?></a><?php endif; ?>
					</article>
				<?php endforeach; ?>
			<?php endif; ?>
		</section>
	</div>

	<section aria-labelledby="<?php echo esc_attr( $activity_id ); ?>">
		<h3 id="<?php echo esc_attr( $activity_id ); ?>"><?php esc_html_e( 'Recent Native Activity', 'sabri-publishing-dashboard' ); ?></h3>
		<?php if ( empty( $role_workspace['activity'] ) ) : ?>
			<p class="spdb-empty-state"><?php esc_html_e( 'No validated activity projection is available.', 'sabri-publishing-dashboard' ); ?></p>
		<?php else : ?>
			<ol class="spdb-activity-list">
				<?php foreach ( $role_workspace['activity'] as $event ) : ?>
					<li class="spdb-activity-list__item spdb-activity-list__item--<?php echo esc_attr( (string) $event['level'] ); ?>">
						<strong><?php echo esc_html( (string) $event['label'] ); ?></strong>
						<time datetime="<?php echo esc_attr( (string) $event['occurred_at'] ); ?>"><?php echo esc_html( (string) $event['occurred_at'] ); ?></time>
						<small><?php echo esc_html( (string) $event['provider_key'] ); ?></small>
					</li>
				<?php endforeach; ?>
			</ol>
		<?php endif; ?>
	</section>

	<p class="spdb-caption">
		<?php echo esc_html( sprintf( __( 'Generated at %1$s (GMT). Workspace providers: %2$d. Provider errors: %3$d. Hidden actions: %4$d.', 'sabri-publishing-dashboard' ), (string) $role_workspace['generated_at_gmt'], (int) $role_workspace['provider_count'], (int) $role_workspace['provider_errors'], (int) $role_workspace['blocked_action_count'] ) ); ?>
	</p>
</section>
