<?php
/**
 * Federated content inventory and item inspector.
 *
 * Available variables: $inventory_result, $inventory_item,
 * $inventory_providers, $workspace, and $instance_id.
 *
 * @package Sabri_Publishing_Dashboard
 */

defined( 'ABSPATH' ) || exit;

$title_id = $instance_id . '-inventory-title';
$query = is_array( $inventory_result ) && isset( $inventory_result['query'] ) && is_array( $inventory_result['query'] )
	? $inventory_result['query']
	: array(
		'page' => 1,
		'per_page' => 20,
		'search' => '',
		'providers' => array(),
		'object_types' => array(),
		'lifecycle_state' => '',
		'review_state' => '',
		'visibility_state' => '',
		'operational_state' => '',
		'language' => '',
		'topic' => '',
		'date_from' => '',
		'date_to' => '',
		'sort' => 'modified_at',
		'direction' => 'desc',
		'scope' => 'own',
	);
$selected_provider = ! empty( $query['providers'][0] ) ? (string) $query['providers'][0] : '';
$selected_type     = ! empty( $query['object_types'][0] ) ? (string) $query['object_types'][0] : '';
$base_query_args   = array(
	'view'       => 'inventory',
	'per_page'   => (int) $query['per_page'],
	'sort'       => (string) $query['sort'],
	'direction'  => (string) $query['direction'],
	'scope'      => (string) $query['scope'],
);
foreach ( array( 'search', 'lifecycle_state', 'review_state', 'visibility_state', 'operational_state', 'language', 'topic', 'date_from', 'date_to' ) as $filter_key ) {
	if ( ! empty( $query[ $filter_key ] ) ) {
		$base_query_args[ $filter_key ] = (string) $query[ $filter_key ];
	}
}
if ( '' !== $selected_provider ) {
	$base_query_args['provider'] = $selected_provider;
}
if ( '' !== $selected_type ) {
	$base_query_args['object_type'] = $selected_type;
}
$dashboard_url = home_url( '/' . SPDB_Dashboard_Router::ROUTE . '/' );
?>
<section class="spdb-inventory" aria-labelledby="<?php echo esc_attr( $title_id ); ?>">
	<div class="spdb-section-heading">
		<div>
			<p class="spdb-eyebrow"><?php esc_html_e( 'Native ownership preserved', 'sabri-publishing-dashboard' ); ?></p>
			<h2 id="<?php echo esc_attr( $title_id ); ?>"><?php esc_html_e( 'Federated Content Inventory', 'sabri-publishing-dashboard' ); ?></h2>
		</div>
	</div>
	<p><?php esc_html_e( 'This view reads validated projections from native modules. File 23 does not copy publication bodies, drafts, review ledgers, schedules, sources, media, or analytics events.', 'sabri-publishing-dashboard' ); ?></p>

	<form class="spdb-inventory-filter" method="get" action="<?php echo esc_url( $dashboard_url ); ?>">
		<input type="hidden" name="view" value="inventory">
		<div class="spdb-form-grid">
			<label><span><?php esc_html_e( 'Search', 'sabri-publishing-dashboard' ); ?></span><input type="search" name="search" value="<?php echo esc_attr( (string) $query['search'] ); ?>" maxlength="100" autocomplete="off"></label>
			<label>
				<span><?php esc_html_e( 'Provider', 'sabri-publishing-dashboard' ); ?></span>
				<select name="provider">
					<option value=""><?php esc_html_e( 'All readable providers', 'sabri-publishing-dashboard' ); ?></option>
					<?php foreach ( $inventory_providers as $provider_key => $provider ) : ?>
						<option value="<?php echo esc_attr( $provider_key ); ?>" <?php selected( $selected_provider, $provider_key ); ?>><?php echo esc_html( $provider['label'] . ' — ' . $provider['state'] ); ?></option>
					<?php endforeach; ?>
				</select>
			</label>
			<label><span><?php esc_html_e( 'Object type', 'sabri-publishing-dashboard' ); ?></span><input type="text" name="object_type" value="<?php echo esc_attr( $selected_type ); ?>" maxlength="64" pattern="[a-z0-9_-]*" autocomplete="off"></label>
			<label>
				<span><?php esc_html_e( 'Lifecycle', 'sabri-publishing-dashboard' ); ?></span>
				<select name="lifecycle_state"><option value=""><?php esc_html_e( 'Any lifecycle state', 'sabri-publishing-dashboard' ); ?></option><?php foreach ( SPDB_Projection_Validator::lifecycle_states() as $state ) : ?><option value="<?php echo esc_attr( $state ); ?>" <?php selected( (string) $query['lifecycle_state'], $state ); ?>><?php echo esc_html( ucwords( str_replace( '_', ' ', $state ) ) ); ?></option><?php endforeach; ?></select>
			</label>
			<label>
				<span><?php esc_html_e( 'Review', 'sabri-publishing-dashboard' ); ?></span>
				<select name="review_state"><option value=""><?php esc_html_e( 'Any review state', 'sabri-publishing-dashboard' ); ?></option><?php foreach ( SPDB_Projection_Validator::review_states() as $state ) : ?><option value="<?php echo esc_attr( $state ); ?>" <?php selected( (string) $query['review_state'], $state ); ?>><?php echo esc_html( ucwords( str_replace( '_', ' ', $state ) ) ); ?></option><?php endforeach; ?></select>
			</label>
			<label>
				<span><?php esc_html_e( 'Visibility', 'sabri-publishing-dashboard' ); ?></span>
				<select name="visibility_state"><option value=""><?php esc_html_e( 'Any visibility', 'sabri-publishing-dashboard' ); ?></option><?php foreach ( SPDB_Projection_Validator::visibility_states() as $state ) : ?><option value="<?php echo esc_attr( $state ); ?>" <?php selected( (string) $query['visibility_state'], $state ); ?>><?php echo esc_html( ucwords( str_replace( '_', ' ', $state ) ) ); ?></option><?php endforeach; ?></select>
			</label>
			<label>
				<span><?php esc_html_e( 'Operational', 'sabri-publishing-dashboard' ); ?></span>
				<select name="operational_state"><option value=""><?php esc_html_e( 'Any operational state', 'sabri-publishing-dashboard' ); ?></option><?php foreach ( SPDB_Projection_Validator::operational_states() as $state ) : ?><option value="<?php echo esc_attr( $state ); ?>" <?php selected( (string) $query['operational_state'], $state ); ?>><?php echo esc_html( ucwords( str_replace( '_', ' ', $state ) ) ); ?></option><?php endforeach; ?></select>
			</label>
			<label><span><?php esc_html_e( 'Language key', 'sabri-publishing-dashboard' ); ?></span><input type="text" name="language" value="<?php echo esc_attr( (string) $query['language'] ); ?>" maxlength="64" pattern="[a-z0-9_-]*" autocomplete="off"></label>
			<label><span><?php esc_html_e( 'Topic key', 'sabri-publishing-dashboard' ); ?></span><input type="text" name="topic" value="<?php echo esc_attr( (string) $query['topic'] ); ?>" maxlength="64" pattern="[a-z0-9_-]*" autocomplete="off"></label>
			<label><span><?php esc_html_e( 'Modified from', 'sabri-publishing-dashboard' ); ?></span><input type="date" name="date_from" value="<?php echo esc_attr( (string) $query['date_from'] ); ?>"></label>
			<label><span><?php esc_html_e( 'Modified to', 'sabri-publishing-dashboard' ); ?></span><input type="date" name="date_to" value="<?php echo esc_attr( (string) $query['date_to'] ); ?>"></label>
			<label>
				<span><?php esc_html_e( 'Sort', 'sabri-publishing-dashboard' ); ?></span>
				<select name="sort"><?php foreach ( array( 'modified_at' => __( 'Last modified', 'sabri-publishing-dashboard' ), 'created_at' => __( 'Created date', 'sabri-publishing-dashboard' ), 'scheduled_at' => __( 'Scheduled date', 'sabri-publishing-dashboard' ), 'published_at' => __( 'Published date', 'sabri-publishing-dashboard' ), 'title' => __( 'Title', 'sabri-publishing-dashboard' ) ) as $sort_key => $sort_label ) : ?><option value="<?php echo esc_attr( $sort_key ); ?>" <?php selected( (string) $query['sort'], $sort_key ); ?>><?php echo esc_html( $sort_label ); ?></option><?php endforeach; ?></select>
			</label>
			<label><span><?php esc_html_e( 'Direction', 'sabri-publishing-dashboard' ); ?></span><select name="direction"><option value="desc" <?php selected( (string) $query['direction'], 'desc' ); ?>><?php esc_html_e( 'Descending', 'sabri-publishing-dashboard' ); ?></option><option value="asc" <?php selected( (string) $query['direction'], 'asc' ); ?>><?php esc_html_e( 'Ascending', 'sabri-publishing-dashboard' ); ?></option></select></label>
			<?php if ( 'founder' === $workspace['key'] ) : ?>
				<label><span><?php esc_html_e( 'Scope', 'sabri-publishing-dashboard' ); ?></span><select name="scope"><option value="own" <?php selected( (string) $query['scope'], 'own' ); ?>><?php esc_html_e( 'My content', 'sabri-publishing-dashboard' ); ?></option><option value="institution" <?php selected( (string) $query['scope'], 'institution' ); ?>><?php esc_html_e( 'Institution-wide', 'sabri-publishing-dashboard' ); ?></option></select></label>
			<?php endif; ?>
			<label><span><?php esc_html_e( 'Items per page', 'sabri-publishing-dashboard' ); ?></span><select name="per_page"><?php foreach ( array( 10, 20, 30, 50 ) as $count ) : ?><option value="<?php echo esc_attr( (string) $count ); ?>" <?php selected( (int) $query['per_page'], $count ); ?>><?php echo esc_html( (string) $count ); ?></option><?php endforeach; ?></select></label>
		</div>
		<button type="submit" class="spdb-button"><?php esc_html_e( 'Apply Filters', 'sabri-publishing-dashboard' ); ?></button>
	</form>

	<?php if ( is_wp_error( $inventory_result ) ) : ?>
		<div class="spdb-notice spdb-notice--critical" role="alert"><?php echo esc_html( $inventory_result->get_error_message() ); ?></div>
	<?php else : ?>
		<p class="spdb-caption"><?php echo esc_html( sprintf( __( '%1$d native items reported; %2$d validated in the bounded window across %3$d queried providers. Scope: %4$s.', 'sabri-publishing-dashboard' ), (int) $inventory_result['total'], (int) $inventory_result['validated_window_count'], count( $inventory_result['providers_queried'] ), (string) $inventory_result['scope'] ) ); ?></p>

		<?php if ( ! empty( $inventory_result['provider_errors'] ) ) : ?>
			<div class="spdb-notice spdb-notice--warning" role="status"><strong><?php esc_html_e( 'Partial provider results', 'sabri-publishing-dashboard' ); ?></strong><ul><?php foreach ( $inventory_result['provider_errors'] as $provider_error ) : ?><li><?php echo esc_html( (string) $provider_error['provider_key'] . ': ' . (string) $provider_error['code'] ); ?></li><?php endforeach; ?></ul></div>
		<?php endif; ?>

		<?php if ( empty( $inventory_result['items'] ) ) : ?>
			<div class="spdb-empty-state" role="status"><?php esc_html_e( 'No validated native content projections match this query. This zero state is not replaced with fabricated content.', 'sabri-publishing-dashboard' ); ?></div>
		<?php else : ?>
			<div class="spdb-table-wrap" role="region" aria-label="<?php esc_attr_e( 'Federated content inventory results', 'sabri-publishing-dashboard' ); ?>" tabindex="0">
				<table class="spdb-inventory-table">
					<thead><tr><th><?php esc_html_e( 'Content', 'sabri-publishing-dashboard' ); ?></th><th><?php esc_html_e( 'Provider / Type', 'sabri-publishing-dashboard' ); ?></th><th><?php esc_html_e( 'Lifecycle', 'sabri-publishing-dashboard' ); ?></th><th><?php esc_html_e( 'Review', 'sabri-publishing-dashboard' ); ?></th><th><?php esc_html_e( 'Visibility', 'sabri-publishing-dashboard' ); ?></th><th><?php esc_html_e( 'Modified', 'sabri-publishing-dashboard' ); ?></th><th><?php esc_html_e( 'Inspector', 'sabri-publishing-dashboard' ); ?></th></tr></thead>
					<tbody>
					<?php foreach ( $inventory_result['items'] as $item ) : ?>
						<?php $inspect_url = add_query_arg( array_merge( $base_query_args, array( 'page' => (int) $inventory_result['page'], 'inspect_provider' => $item['provider_key'], 'inspect_type' => $item['object_type'], 'inspect_id' => $item['object_id'] ) ), $dashboard_url ); ?>
						<tr>
							<td><strong><?php echo esc_html( $item['title'] ); ?></strong><small><?php echo esc_html( $item['object_id'] ); ?></small></td>
							<td><?php echo esc_html( $item['provider_name'] ); ?><small><?php echo esc_html( $item['object_type'] ); ?></small></td>
							<td><?php echo esc_html( ucwords( str_replace( '_', ' ', $item['lifecycle_state'] ) ) ); ?></td>
							<td><?php echo esc_html( ucwords( str_replace( '_', ' ', $item['review_state'] ) ) ); ?></td>
							<td><?php echo esc_html( ucwords( str_replace( '_', ' ', $item['visibility_state'] ) ) ); ?></td>
							<td><?php echo esc_html( $item['modified_at'] ?: '—' ); ?></td>
							<td><a class="spdb-button spdb-button--secondary" href="<?php echo esc_url( $inspect_url ); ?>"><?php esc_html_e( 'Inspect', 'sabri-publishing-dashboard' ); ?></a></td>
						</tr>
					<?php endforeach; ?>
					</tbody>
				</table>
			</div>
		<?php endif; ?>

		<?php if ( (int) $inventory_result['pages'] > 1 ) : ?>
			<nav class="spdb-pagination" aria-label="<?php esc_attr_e( 'Inventory pages', 'sabri-publishing-dashboard' ); ?>">
				<?php if ( (int) $inventory_result['page'] > 1 ) : ?><a class="spdb-button spdb-button--secondary" href="<?php echo esc_url( add_query_arg( array_merge( $base_query_args, array( 'page' => (int) $inventory_result['page'] - 1 ) ), $dashboard_url ) ); ?>"><?php esc_html_e( 'Previous', 'sabri-publishing-dashboard' ); ?></a><?php endif; ?>
				<span><?php echo esc_html( sprintf( __( 'Page %1$d of %2$d within the %3$d-item safety window', 'sabri-publishing-dashboard' ), (int) $inventory_result['page'], (int) $inventory_result['pages'], SPDB_Inventory_Query::MAX_WINDOW ) ); ?></span>
				<?php if ( (int) $inventory_result['page'] < (int) $inventory_result['pages'] ) : ?><a class="spdb-button spdb-button--secondary" href="<?php echo esc_url( add_query_arg( array_merge( $base_query_args, array( 'page' => (int) $inventory_result['page'] + 1 ) ), $dashboard_url ) ); ?>"><?php esc_html_e( 'Next', 'sabri-publishing-dashboard' ); ?></a><?php endif; ?>
			</nav>
		<?php endif; ?>
	<?php endif; ?>

	<?php if ( null !== $inventory_item ) : ?>
		<section class="spdb-inspector" aria-labelledby="<?php echo esc_attr( $instance_id . '-inspector-title' ); ?>">
			<?php if ( is_wp_error( $inventory_item ) ) : ?>
				<h3 id="<?php echo esc_attr( $instance_id . '-inspector-title' ); ?>"><?php esc_html_e( 'Item Inspector', 'sabri-publishing-dashboard' ); ?></h3>
				<div class="spdb-notice spdb-notice--critical" role="alert"><?php echo esc_html( $inventory_item->get_error_message() ); ?></div>
			<?php else : ?>
				<p class="spdb-eyebrow"><?php echo esc_html( $inventory_item['provider_name'] . ' / ' . $inventory_item['object_type'] ); ?></p>
				<h3 id="<?php echo esc_attr( $instance_id . '-inspector-title' ); ?>"><?php echo esc_html( $inventory_item['title'] ); ?></h3>
				<?php if ( '' !== $inventory_item['summary'] ) : ?><p><?php echo esc_html( $inventory_item['summary'] ); ?></p><?php endif; ?>
				<dl class="spdb-inspector-grid">
					<div><dt><?php esc_html_e( 'Native ID', 'sabri-publishing-dashboard' ); ?></dt><dd><?php echo esc_html( $inventory_item['object_id'] ); ?></dd></div>
					<div><dt><?php esc_html_e( 'Native version', 'sabri-publishing-dashboard' ); ?></dt><dd><?php echo esc_html( $inventory_item['object_version'] ); ?></dd></div>
					<div><dt><?php esc_html_e( 'Owner user ID', 'sabri-publishing-dashboard' ); ?></dt><dd><?php echo esc_html( (string) $inventory_item['owner_user_id'] ); ?></dd></div>
					<div><dt><?php esc_html_e( 'Lifecycle', 'sabri-publishing-dashboard' ); ?></dt><dd><?php echo esc_html( $inventory_item['lifecycle_state'] ); ?></dd></div>
					<div><dt><?php esc_html_e( 'Review', 'sabri-publishing-dashboard' ); ?></dt><dd><?php echo esc_html( $inventory_item['review_state'] ); ?></dd></div>
					<div><dt><?php esc_html_e( 'Visibility', 'sabri-publishing-dashboard' ); ?></dt><dd><?php echo esc_html( $inventory_item['visibility_state'] ); ?></dd></div>
					<div><dt><?php esc_html_e( 'Operational', 'sabri-publishing-dashboard' ); ?></dt><dd><?php echo esc_html( $inventory_item['operational_state'] ); ?></dd></div>
					<div><dt><?php esc_html_e( 'Privacy', 'sabri-publishing-dashboard' ); ?></dt><dd><?php echo esc_html( $inventory_item['privacy_class'] ); ?></dd></div>
					<div><dt><?php esc_html_e( 'Adapter state', 'sabri-publishing-dashboard' ); ?></dt><dd><?php echo esc_html( $inventory_item['effective_state'] ); ?></dd></div>
				</dl>

				<?php if ( ! empty( $inventory_item['destinations'] ) ) : ?>
					<div class="spdb-destination-actions" aria-label="<?php esc_attr_e( 'Native destinations', 'sabri-publishing-dashboard' ); ?>">
						<?php foreach ( array( 'edit' => __( 'Continue Editing', 'sabri-publishing-dashboard' ), 'preview' => __( 'Preview', 'sabri-publishing-dashboard' ), 'public' => __( 'View Public Page', 'sabri-publishing-dashboard' ) ) as $destination_key => $destination_label ) : ?>
							<?php if ( ! empty( $inventory_item['destinations'][ $destination_key ] ) ) : ?><a class="spdb-button<?php echo 'edit' === $destination_key ? '' : ' spdb-button--secondary'; ?>" href="<?php echo esc_url( $inventory_item['destinations'][ $destination_key ] ); ?>" rel="noopener noreferrer"><?php echo esc_html( $destination_label ); ?></a><?php endif; ?>
						<?php endforeach; ?>
					</div>
				<?php endif; ?>

				<?php if ( ! empty( $inventory_item['allowed_operations'] ) ) : ?>
					<h4><?php esc_html_e( 'Native operation projection', 'sabri-publishing-dashboard' ); ?></h4>
					<p><?php esc_html_e( 'These operations are reported for inspection only. Phase 23C exposes no mutation endpoint or action button.', 'sabri-publishing-dashboard' ); ?></p>
					<ul><?php foreach ( $inventory_item['allowed_operations'] as $operation ) : ?><li><code><?php echo esc_html( $operation['key'] ); ?></code> — <?php echo $operation['environment_eligible'] ? esc_html__( 'environment gate eligible; execution still not exposed', 'sabri-publishing-dashboard' ) : esc_html__( 'not accepted for environment writes', 'sabri-publishing-dashboard' ); ?></li><?php endforeach; ?></ul>
				<?php endif; ?>

				<?php if ( ! empty( $inventory_item['compliance_alerts'] ) ) : ?>
					<h4><?php esc_html_e( 'Compliance notices', 'sabri-publishing-dashboard' ); ?></h4>
					<ul class="spdb-compliance-list"><?php foreach ( $inventory_item['compliance_alerts'] as $alert ) : ?><li class="spdb-notice spdb-notice--<?php echo esc_attr( $alert['level'] ); ?>"><?php echo esc_html( $alert['message'] ); ?></li><?php endforeach; ?></ul>
				<?php endif; ?>
			<?php endif; ?>
		</section>
	<?php endif; ?>
</section>
