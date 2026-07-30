<?php
/**
 * Accessible read-only Collections and Knowledge projection.
 *
 * @var array<string,mixed>|WP_Error $collections_projection
 * @var string                       $instance_id
 */

defined( 'ABSPATH' ) || exit;

$base_url = SPDB_Dashboard_Router::route_url( 'collections' );
$collection_section_url = add_query_arg( array( 'section' => 'collections' ), $base_url );
$knowledge_section_url  = add_query_arg( array( 'section' => 'knowledge' ), $base_url );
?>
<section class="spdb-collections" aria-labelledby="<?php echo esc_attr( $instance_id . '-collections-title' ); ?>">
	<header class="spdb-collections__header">
		<div>
			<p class="spdb-eyebrow"><?php esc_html_e( 'Phase 23F — Read-only organizational metadata', 'sabri-publishing-dashboard' ); ?></p>
			<h2 id="<?php echo esc_attr( $instance_id . '-collections-title' ); ?>"><?php esc_html_e( 'Collections and Knowledge', 'sabri-publishing-dashboard' ); ?></h2>
			<p><?php esc_html_e( 'Native content, clinical records, media, analytics, and destinations remain with their canonical owners.', 'sabri-publishing-dashboard' ); ?></p>
		</div>
		<span class="spdb-collections__readonly" role="status"><?php esc_html_e( 'Read only', 'sabri-publishing-dashboard' ); ?></span>
	</header>

	<nav class="spdb-collections-tabs" aria-label="<?php esc_attr_e( 'Collections sections', 'sabri-publishing-dashboard' ); ?>">
		<a href="<?php echo esc_url( $collection_section_url ); ?>"<?php echo ! is_wp_error( $collections_projection ) && 'collections' === ( $collections_projection['section'] ?? '' ) ? ' aria-current="page"' : ''; ?>><?php esc_html_e( 'Collections and Campaigns', 'sabri-publishing-dashboard' ); ?></a>
		<a href="<?php echo esc_url( $knowledge_section_url ); ?>"<?php echo ! is_wp_error( $collections_projection ) && 'knowledge' === ( $collections_projection['section'] ?? '' ) ? ' aria-current="page"' : ''; ?>><?php esc_html_e( 'Knowledge Links', 'sabri-publishing-dashboard' ); ?></a>
	</nav>

	<?php if ( is_wp_error( $collections_projection ) ) : ?>
		<div class="spdb-collections-state spdb-collections-state--error" role="alert">
			<h3><?php esc_html_e( 'Collections view unavailable', 'sabri-publishing-dashboard' ); ?></h3>
			<p><?php echo esc_html( $collections_projection->get_error_message() ); ?></p>
			<p><a href="<?php echo esc_url( $collection_section_url ); ?>"><?php esc_html_e( 'Return to the Collections overview', 'sabri-publishing-dashboard' ); ?></a></p>
		</div>
	<?php else : ?>
		<?php
		$mode   = (string) ( $collections_projection['mode'] ?? '' );
		$query  = is_array( $collections_projection['query'] ?? null ) ? $collections_projection['query'] : array();
		$health = is_array( $collections_projection['health'] ?? null ) ? $collections_projection['health'] : array();
		$make_url = static function ( array $arguments ) use ( $base_url ): string {
			return add_query_arg( $arguments, $base_url );
		};
		$render_pagination = static function ( array $envelope, string $page_key, array $arguments ) use ( $make_url ): void {
			$page     = (int) ( $envelope['page'] ?? 1 );
			$per_page = max( 1, (int) ( $envelope['per_page'] ?? 20 ) );
			$total    = max( 0, (int) ( $envelope['total'] ?? 0 ) );
			$pages    = max( 1, (int) ceil( $total / $per_page ) );
			if ( $pages <= 1 ) { return; }
			?>
			<nav class="spdb-collections-pagination" aria-label="<?php esc_attr_e( 'Collections pagination', 'sabri-publishing-dashboard' ); ?>">
				<span><?php echo esc_html( sprintf( __( 'Page %1$d of %2$d', 'sabri-publishing-dashboard' ), $page, $pages ) ); ?></span>
				<div>
					<?php if ( $page > 1 ) : ?>
						<a href="<?php echo esc_url( $make_url( array_merge( $arguments, array( $page_key => $page - 1 ) ) ) ); ?>" rel="prev"><?php esc_html_e( 'Previous', 'sabri-publishing-dashboard' ); ?></a>
					<?php endif; ?>
					<?php if ( $page < $pages ) : ?>
						<a href="<?php echo esc_url( $make_url( array_merge( $arguments, array( $page_key => $page + 1 ) ) ) ); ?>" rel="next"><?php esc_html_e( 'Next', 'sabri-publishing-dashboard' ); ?></a>
					<?php endif; ?>
				</div>
			</nav>
			<?php
		};
		?>

		<div class="spdb-collections-readiness" aria-label="<?php esc_attr_e( 'Collections readiness', 'sabri-publishing-dashboard' ); ?>">
			<p><strong><?php esc_html_e( 'Repository reads:', 'sabri-publishing-dashboard' ); ?></strong> <?php echo ! empty( $health['read_ready'] ) ? esc_html__( 'Ready', 'sabri-publishing-dashboard' ) : esc_html__( 'Unavailable', 'sabri-publishing-dashboard' ); ?></p>
			<p><strong><?php esc_html_e( 'Collection writes:', 'sabri-publishing-dashboard' ); ?></strong> <?php echo ! empty( $health['collection_write_ready'] ) ? esc_html__( 'Explicit staging mode only', 'sabri-publishing-dashboard' ) : esc_html__( 'Disabled', 'sabri-publishing-dashboard' ); ?></p>
			<p><strong><?php esc_html_e( 'Knowledge writes:', 'sabri-publishing-dashboard' ); ?></strong> <?php echo ! empty( $health['knowledge_write_ready'] ) ? esc_html__( 'Explicit staging mode only', 'sabri-publishing-dashboard' ) : esc_html__( 'Disabled; reviewed native resolver required', 'sabri-publishing-dashboard' ); ?></p>
		</div>

		<?php if ( 'collection_list' === $mode ) : ?>
			<form class="spdb-collections-filters" method="get" action="<?php echo esc_url( $base_url ); ?>" aria-label="<?php esc_attr_e( 'Filter collections', 'sabri-publishing-dashboard' ); ?>">
				<input type="hidden" name="view" value="collections">
				<input type="hidden" name="section" value="collections">
				<label><?php esc_html_e( 'Scope', 'sabri-publishing-dashboard' ); ?>
					<select name="scope">
						<option value="own"<?php echo 'own' === ( $query['scope'] ?? '' ) ? ' selected' : ''; ?>><?php esc_html_e( 'My metadata', 'sabri-publishing-dashboard' ); ?></option>
						<option value="institution"<?php echo 'institution' === ( $query['scope'] ?? '' ) ? ' selected' : ''; ?>><?php esc_html_e( 'Institution — Founder governed', 'sabri-publishing-dashboard' ); ?></option>
					</select>
				</label>
				<label><?php esc_html_e( 'Type', 'sabri-publishing-dashboard' ); ?>
					<select name="record_type">
						<option value=""><?php esc_html_e( 'All types', 'sabri-publishing-dashboard' ); ?></option>
						<option value="collection"<?php echo 'collection' === ( $query['record_type'] ?? '' ) ? ' selected' : ''; ?>><?php esc_html_e( 'Collection', 'sabri-publishing-dashboard' ); ?></option>
						<option value="campaign"<?php echo 'campaign' === ( $query['record_type'] ?? '' ) ? ' selected' : ''; ?>><?php esc_html_e( 'Campaign', 'sabri-publishing-dashboard' ); ?></option>
					</select>
				</label>
				<label><?php esc_html_e( 'Status', 'sabri-publishing-dashboard' ); ?>
					<input type="text" name="status" value="<?php echo esc_attr( (string) ( $query['status'] ?? '' ) ); ?>" maxlength="24" pattern="[a-z_-]*" placeholder="<?php esc_attr_e( 'draft, active, archived…', 'sabri-publishing-dashboard' ); ?>">
				</label>
				<label><?php esc_html_e( 'Rows', 'sabri-publishing-dashboard' ); ?>
					<select name="per_page">
						<?php foreach ( array( 10, 20, 50 ) as $size ) : ?>
							<option value="<?php echo esc_attr( (string) $size ); ?>"<?php echo $size === (int) ( $query['per_page'] ?? 20 ) ? ' selected' : ''; ?>><?php echo esc_html( (string) $size ); ?></option>
						<?php endforeach; ?>
					</select>
				</label>
				<button type="submit"><?php esc_html_e( 'Apply read filters', 'sabri-publishing-dashboard' ); ?></button>
			</form>

			<?php $list = is_array( $collections_projection['list'] ?? null ) ? $collections_projection['list'] : array(); ?>
			<p class="spdb-collections-summary" role="status"><?php echo esc_html( sprintf( _n( '%d accessible collection record', '%d accessible collection records', (int) ( $list['total'] ?? 0 ), 'sabri-publishing-dashboard' ), (int) ( $list['total'] ?? 0 ) ) ); ?></p>
			<?php if ( empty( $list['items'] ) ) : ?>
				<div class="spdb-collections-state"><h3><?php esc_html_e( 'No accessible collections', 'sabri-publishing-dashboard' ); ?></h3><p><?php esc_html_e( 'No records matched the validated scope and filters. No data has been fabricated.', 'sabri-publishing-dashboard' ); ?></p></div>
			<?php else : ?>
				<div class="spdb-collections-table-wrap" role="region" aria-label="<?php esc_attr_e( 'Collections results', 'sabri-publishing-dashboard' ); ?>" tabindex="0">
					<table class="spdb-collections-table">
						<caption><?php esc_html_e( 'Accessible File 23 collection and campaign metadata', 'sabri-publishing-dashboard' ); ?></caption>
						<thead><tr><th scope="col"><?php esc_html_e( 'Title', 'sabri-publishing-dashboard' ); ?></th><th scope="col"><?php esc_html_e( 'Type', 'sabri-publishing-dashboard' ); ?></th><th scope="col"><?php esc_html_e( 'Scope', 'sabri-publishing-dashboard' ); ?></th><th scope="col"><?php esc_html_e( 'Status', 'sabri-publishing-dashboard' ); ?></th><th scope="col"><?php esc_html_e( 'Updated UTC', 'sabri-publishing-dashboard' ); ?></th></tr></thead>
						<tbody>
						<?php foreach ( $list['items'] as $record ) : ?>
							<tr>
								<th scope="row"><a href="<?php echo esc_url( $make_url( array( 'section' => 'collections', 'collection_id' => $record['collection_id'], 'per_page' => $query['per_page'] ) ) ); ?>"><?php echo esc_html( $record['title'] ); ?></a></th>
								<td><?php echo esc_html( $record['record_type'] ); ?></td><td><?php echo esc_html( $record['scope'] ); ?></td><td><?php echo esc_html( $record['status'] ); ?></td><td><time datetime="<?php echo esc_attr( $record['updated_at_gmt'] ); ?>"><?php echo esc_html( $record['updated_at_gmt'] ); ?></time></td>
							</tr>
						<?php endforeach; ?>
						</tbody>
					</table>
				</div>
				<?php $render_pagination( $list, 'page', array_filter( array( 'section' => 'collections', 'scope' => $query['scope'], 'record_type' => $query['record_type'], 'status' => $query['status'], 'per_page' => $query['per_page'] ), static fn( $value ) => '' !== $value ) ); ?>
			<?php endif; ?>

		<?php elseif ( 'collection_detail' === $mode || 'collection_item' === $mode ) : ?>
			<?php $detail = $collections_projection['detail']; ?>
			<p><a class="spdb-collections-back" href="<?php echo esc_url( $collection_section_url ); ?>">&larr; <?php esc_html_e( 'Back to collections', 'sabri-publishing-dashboard' ); ?></a></p>
			<article class="spdb-collections-detail" aria-labelledby="<?php echo esc_attr( $instance_id . '-collection-detail' ); ?>">
				<h3 id="<?php echo esc_attr( $instance_id . '-collection-detail' ); ?>"><?php echo esc_html( $detail['title'] ); ?></h3>
				<dl>
					<dt><?php esc_html_e( 'Record ID', 'sabri-publishing-dashboard' ); ?></dt><dd><code><?php echo esc_html( $detail['collection_id'] ); ?></code></dd>
					<dt><?php esc_html_e( 'Type', 'sabri-publishing-dashboard' ); ?></dt><dd><?php echo esc_html( $detail['record_type'] ); ?></dd>
					<dt><?php esc_html_e( 'Scope', 'sabri-publishing-dashboard' ); ?></dt><dd><?php echo esc_html( $detail['scope'] ); ?></dd>
					<dt><?php esc_html_e( 'Status', 'sabri-publishing-dashboard' ); ?></dt><dd><?php echo esc_html( $detail['status'] ); ?></dd>
					<dt><?php esc_html_e( 'Version', 'sabri-publishing-dashboard' ); ?></dt><dd><?php echo esc_html( (string) $detail['version'] ); ?></dd>
					<dt><?php esc_html_e( 'Objective', 'sabri-publishing-dashboard' ); ?></dt><dd><?php echo '' !== $detail['objective'] ? esc_html( $detail['objective'] ) : esc_html__( 'Not applicable', 'sabri-publishing-dashboard' ); ?></dd>
					<dt><?php esc_html_e( 'Ethical declaration', 'sabri-publishing-dashboard' ); ?></dt><dd><?php echo '' !== $detail['ethical_declaration'] ? esc_html( $detail['ethical_declaration'] ) : esc_html__( 'Not applicable', 'sabri-publishing-dashboard' ); ?></dd>
					<dt><?php esc_html_e( 'Created UTC', 'sabri-publishing-dashboard' ); ?></dt><dd><time datetime="<?php echo esc_attr( $detail['created_at_gmt'] ); ?>"><?php echo esc_html( $detail['created_at_gmt'] ); ?></time></dd>
					<dt><?php esc_html_e( 'Updated UTC', 'sabri-publishing-dashboard' ); ?></dt><dd><time datetime="<?php echo esc_attr( $detail['updated_at_gmt'] ); ?>"><?php echo esc_html( $detail['updated_at_gmt'] ); ?></time></dd>
				</dl>
			</article>

			<?php if ( 'collection_item' === $mode ) : ?>
				<?php $item = $collections_projection['item']; ?>
				<p><a class="spdb-collections-back" href="<?php echo esc_url( $make_url( array( 'section' => 'collections', 'collection_id' => $detail['collection_id'], 'per_page' => $query['per_page'] ) ) ); ?>">&larr; <?php esc_html_e( 'Back to collection items', 'sabri-publishing-dashboard' ); ?></a></p>
				<section class="spdb-collections-detail" aria-labelledby="<?php echo esc_attr( $instance_id . '-item-detail' ); ?>">
					<h3 id="<?php echo esc_attr( $instance_id . '-item-detail' ); ?>"><?php esc_html_e( 'Collection item reference', 'sabri-publishing-dashboard' ); ?></h3>
					<dl>
						<dt><?php esc_html_e( 'Item ID', 'sabri-publishing-dashboard' ); ?></dt><dd><code><?php echo esc_html( $item['item_id'] ); ?></code></dd>
						<dt><?php esc_html_e( 'Provider', 'sabri-publishing-dashboard' ); ?></dt><dd><?php echo esc_html( $item['provider_key'] ); ?></dd>
						<dt><?php esc_html_e( 'Native object type', 'sabri-publishing-dashboard' ); ?></dt><dd><?php echo esc_html( $item['object_type'] ); ?></dd>
						<dt><?php esc_html_e( 'Native object ID', 'sabri-publishing-dashboard' ); ?></dt><dd><code><?php echo esc_html( $item['object_id'] ); ?></code></dd>
						<dt><?php esc_html_e( 'Relation', 'sabri-publishing-dashboard' ); ?></dt><dd><?php echo esc_html( $item['relation_type'] ); ?></dd>
						<dt><?php esc_html_e( 'Observed native version', 'sabri-publishing-dashboard' ); ?></dt><dd><?php echo esc_html( $item['native_version'] ); ?></dd>
						<dt><?php esc_html_e( 'Position', 'sabri-publishing-dashboard' ); ?></dt><dd><?php echo esc_html( (string) $item['position'] ); ?></dd>
					</dl>
					<p class="spdb-collections-state"><?php esc_html_e( 'This is a canonical reference only. No native destination or native content is stored here.', 'sabri-publishing-dashboard' ); ?></p>
				</section>
			<?php else : ?>
				<?php $items = is_array( $collections_projection['items'] ?? null ) ? $collections_projection['items'] : array(); ?>
				<h3><?php esc_html_e( 'Active collection items', 'sabri-publishing-dashboard' ); ?></h3>
				<p class="spdb-collections-summary" role="status"><?php echo esc_html( sprintf( _n( '%d accessible active item', '%d accessible active items', (int) ( $items['total'] ?? 0 ), 'sabri-publishing-dashboard' ), (int) ( $items['total'] ?? 0 ) ) ); ?></p>
				<?php if ( empty( $items['items'] ) ) : ?>
					<div class="spdb-collections-state"><p><?php esc_html_e( 'This collection has no accessible active items.', 'sabri-publishing-dashboard' ); ?></p></div>
				<?php else : ?>
					<div class="spdb-collections-table-wrap" role="region" aria-label="<?php esc_attr_e( 'Collection items', 'sabri-publishing-dashboard' ); ?>" tabindex="0">
						<table class="spdb-collections-table"><caption><?php esc_html_e( 'Canonical native references in this collection', 'sabri-publishing-dashboard' ); ?></caption><thead><tr><th scope="col"><?php esc_html_e( 'Reference', 'sabri-publishing-dashboard' ); ?></th><th scope="col"><?php esc_html_e( 'Provider', 'sabri-publishing-dashboard' ); ?></th><th scope="col"><?php esc_html_e( 'Object type', 'sabri-publishing-dashboard' ); ?></th><th scope="col"><?php esc_html_e( 'Position', 'sabri-publishing-dashboard' ); ?></th></tr></thead><tbody>
						<?php foreach ( $items['items'] as $item ) : ?><tr><th scope="row"><a href="<?php echo esc_url( $make_url( array( 'section' => 'collections', 'collection_id' => $detail['collection_id'], 'item_id' => $item['item_id'], 'per_page' => $query['per_page'] ) ) ); ?>"><code><?php echo esc_html( $item['object_id'] ); ?></code></a></th><td><?php echo esc_html( $item['provider_key'] ); ?></td><td><?php echo esc_html( $item['object_type'] ); ?></td><td><?php echo esc_html( (string) $item['position'] ); ?></td></tr><?php endforeach; ?>
						</tbody></table>
					</div>
					<?php $render_pagination( $items, 'item_page', array( 'section' => 'collections', 'collection_id' => $detail['collection_id'], 'per_page' => $query['per_page'] ) ); ?>
				<?php endif; ?>
			<?php endif; ?>

		<?php elseif ( 'knowledge_list' === $mode ) : ?>
			<form class="spdb-collections-filters" method="get" action="<?php echo esc_url( $base_url ); ?>" aria-label="<?php esc_attr_e( 'Filter knowledge links', 'sabri-publishing-dashboard' ); ?>">
				<input type="hidden" name="view" value="collections"><input type="hidden" name="section" value="knowledge">
				<label><?php esc_html_e( 'Scope', 'sabri-publishing-dashboard' ); ?><select name="scope"><option value="own"<?php echo 'own' === $query['scope'] ? ' selected' : ''; ?>><?php esc_html_e( 'My metadata', 'sabri-publishing-dashboard' ); ?></option><option value="institution"<?php echo 'institution' === $query['scope'] ? ' selected' : ''; ?>><?php esc_html_e( 'Institution — Founder governed', 'sabri-publishing-dashboard' ); ?></option></select></label>
				<label><?php esc_html_e( 'Status', 'sabri-publishing-dashboard' ); ?><select name="status"><option value=""><?php esc_html_e( 'All statuses', 'sabri-publishing-dashboard' ); ?></option><option value="active"<?php echo 'active' === $query['status'] ? ' selected' : ''; ?>><?php esc_html_e( 'Active', 'sabri-publishing-dashboard' ); ?></option><option value="archived"<?php echo 'archived' === $query['status'] ? ' selected' : ''; ?>><?php esc_html_e( 'Archived', 'sabri-publishing-dashboard' ); ?></option></select></label>
				<label><?php esc_html_e( 'Rows', 'sabri-publishing-dashboard' ); ?><select name="per_page"><?php foreach ( array( 10, 20, 50 ) as $size ) : ?><option value="<?php echo esc_attr( (string) $size ); ?>"<?php echo $size === $query['per_page'] ? ' selected' : ''; ?>><?php echo esc_html( (string) $size ); ?></option><?php endforeach; ?></select></label>
				<button type="submit"><?php esc_html_e( 'Apply read filters', 'sabri-publishing-dashboard' ); ?></button>
			</form>
			<?php $list = is_array( $collections_projection['list'] ?? null ) ? $collections_projection['list'] : array(); ?>
			<p class="spdb-collections-summary" role="status"><?php echo esc_html( sprintf( _n( '%d accessible knowledge link', '%d accessible knowledge links', (int) ( $list['total'] ?? 0 ), 'sabri-publishing-dashboard' ), (int) ( $list['total'] ?? 0 ) ) ); ?></p>
			<?php if ( empty( $list['items'] ) ) : ?><div class="spdb-collections-state"><h3><?php esc_html_e( 'No accessible knowledge links', 'sabri-publishing-dashboard' ); ?></h3><p><?php esc_html_e( 'No links matched the validated scope and status.', 'sabri-publishing-dashboard' ); ?></p></div>
			<?php else : ?><div class="spdb-collections-table-wrap" role="region" aria-label="<?php esc_attr_e( 'Knowledge links results', 'sabri-publishing-dashboard' ); ?>" tabindex="0"><table class="spdb-collections-table"><caption><?php esc_html_e( 'Typed relationships between canonical native objects', 'sabri-publishing-dashboard' ); ?></caption><thead><tr><th scope="col"><?php esc_html_e( 'Relation', 'sabri-publishing-dashboard' ); ?></th><th scope="col"><?php esc_html_e( 'Source', 'sabri-publishing-dashboard' ); ?></th><th scope="col"><?php esc_html_e( 'Target', 'sabri-publishing-dashboard' ); ?></th><th scope="col"><?php esc_html_e( 'Status', 'sabri-publishing-dashboard' ); ?></th></tr></thead><tbody>
			<?php foreach ( $list['items'] as $link ) : ?><tr><th scope="row"><a href="<?php echo esc_url( $make_url( array( 'section' => 'knowledge', 'link_id' => $link['link_id'] ) ) ); ?>"><?php echo esc_html( $link['relation_type'] ); ?></a></th><td><code><?php echo esc_html( $link['source_provider_key'] . ':' . $link['source_object_type'] . ':' . $link['source_object_id'] ); ?></code></td><td><code><?php echo esc_html( $link['target_provider_key'] . ':' . $link['target_object_type'] . ':' . $link['target_object_id'] ); ?></code></td><td><?php echo esc_html( $link['status'] ); ?></td></tr><?php endforeach; ?>
			</tbody></table></div><?php $render_pagination( $list, 'page', array_filter( array( 'section' => 'knowledge', 'scope' => $query['scope'], 'status' => $query['status'], 'per_page' => $query['per_page'] ), static fn( $value ) => '' !== $value ) ); ?><?php endif; ?>

		<?php elseif ( 'knowledge_detail' === $mode ) : ?>
			<?php $link = $collections_projection['detail']; ?>
			<p><a class="spdb-collections-back" href="<?php echo esc_url( $knowledge_section_url ); ?>">&larr; <?php esc_html_e( 'Back to knowledge links', 'sabri-publishing-dashboard' ); ?></a></p>
			<article class="spdb-collections-detail" aria-labelledby="<?php echo esc_attr( $instance_id . '-knowledge-detail' ); ?>"><h3 id="<?php echo esc_attr( $instance_id . '-knowledge-detail' ); ?>"><?php echo esc_html( $link['relation_type'] ); ?></h3><dl>
				<dt><?php esc_html_e( 'Link ID', 'sabri-publishing-dashboard' ); ?></dt><dd><code><?php echo esc_html( $link['link_id'] ); ?></code></dd><dt><?php esc_html_e( 'Scope', 'sabri-publishing-dashboard' ); ?></dt><dd><?php echo esc_html( $link['scope'] ); ?></dd><dt><?php esc_html_e( 'Status', 'sabri-publishing-dashboard' ); ?></dt><dd><?php echo esc_html( $link['status'] ); ?></dd>
				<dt><?php esc_html_e( 'Source reference', 'sabri-publishing-dashboard' ); ?></dt><dd><code><?php echo esc_html( $link['source_provider_key'] . ':' . $link['source_object_type'] . ':' . $link['source_object_id'] ); ?></code></dd><dt><?php esc_html_e( 'Source observed version', 'sabri-publishing-dashboard' ); ?></dt><dd><?php echo esc_html( $link['source_native_version'] ); ?></dd>
				<dt><?php esc_html_e( 'Target reference', 'sabri-publishing-dashboard' ); ?></dt><dd><code><?php echo esc_html( $link['target_provider_key'] . ':' . $link['target_object_type'] . ':' . $link['target_object_id'] ); ?></code></dd><dt><?php esc_html_e( 'Target observed version', 'sabri-publishing-dashboard' ); ?></dt><dd><?php echo esc_html( $link['target_native_version'] ); ?></dd><dt><?php esc_html_e( 'Updated UTC', 'sabri-publishing-dashboard' ); ?></dt><dd><time datetime="<?php echo esc_attr( $link['updated_at_gmt'] ); ?>"><?php echo esc_html( $link['updated_at_gmt'] ); ?></time></dd>
			</dl><p class="spdb-collections-state"><?php esc_html_e( 'This relationship stores canonical identifiers and observed versions only. No native content or destination is stored.', 'sabri-publishing-dashboard' ); ?></p></article>
		<?php endif; ?>
	<?php endif; ?>
</section>
