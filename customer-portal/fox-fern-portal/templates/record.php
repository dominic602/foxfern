<?php
/**
 * Printable "home record": project summary, every selection/spec, and warranties.
 *
 * @var object $project
 * @var bool   $staff
 */

defined( 'ABSPATH' ) || exit;

$s          = ffp_settings();
$specs      = FFP_Data::specs( $project->id );
$docs       = FFP_Data::files( $project->id, 'document', null, true );
$warranties = array_filter( $docs, fn( $d ) => 'warranty' === $d->category );
$others     = array_filter( $docs, fn( $d ) => 'warranty' !== $d->category );
$cats       = ffp_doc_categories();
$grouped    = array();
foreach ( $specs as $spec ) {
	$grouped[ $spec->category ? $spec->category : 'Other' ][] = $spec;
}
$order = $s['spec_categories'];
uksort(
	$grouped,
	function ( $a, $b ) use ( $order ) {
		$ia = array_search( $a, $order, true );
		$ib = array_search( $b, $order, true );
		return ( false === $ia ? 999 : $ia ) <=> ( false === $ib ? 999 : $ib );
	}
);
?>
<div class="no-print record-bar">
	<a class="btn btn-line btn-sm" href="<?php echo esc_url( ffp_project_url( $project->id ) ); ?>">&larr; Back to project</a>
	<button class="btn btn-gold btn-sm" type="button" data-print>Print or save as PDF</button>
</div>

<header class="record-head">
	<p class="eyebrow"><?php echo esc_html( $s['company_name'] ); ?> &middot; Home record</p>
	<h1><?php echo esc_html( $project->title ); ?></h1>
	<p><?php echo esc_html( $project->address ); ?></p>
	<p class="small muted">
		<?php
		echo esc_html(
			implode(
				' · ',
				array_filter(
					array(
						$project->start_date ? 'Started ' . ffp_date( $project->start_date ) : '',
						$project->target_date ? 'Completion ' . ffp_date( $project->target_date ) : '',
						$s['company_phone'] ? 'Questions: ' . $s['company_phone'] : '',
						'Printed ' . ffp_date( current_time( 'mysql' ) ),
					)
				)
			)
		);
		?>
	</p>
</header>

<?php if ( $project->description ) : ?>
	<section>
		<h2>Scope of work</h2>
		<div class="prose"><?php echo ffp_text( $project->description ); // phpcs:ignore WordPress.Security.EscapeOutput ?></div>
	</section>
<?php endif; ?>

<section>
	<h2>Selections &amp; materials</h2>
	<?php if ( ! $specs ) : ?>
		<p class="muted">No selections recorded.</p>
	<?php endif; ?>
	<?php foreach ( $grouped as $cat => $items ) : ?>
		<h3><?php echo esc_html( $cat ); ?></h3>
		<table class="rtable">
			<thead><tr><th>Where</th><th>Product</th><th>Color / code</th><th>Finish</th><th>Qty</th><th>Source</th><th>Used for</th><th>Installed</th></tr></thead>
			<tbody>
				<?php foreach ( $items as $spec ) : ?>
					<tr>
						<td><?php echo esc_html( $spec->location ); ?></td>
						<td><strong><?php echo esc_html( $spec->product ); ?></strong><?php echo $spec->brand ? '<br><span class="muted">' . esc_html( $spec->brand ) . '</span>' : ''; ?></td>
						<td><?php echo esc_html( trim( $spec->color . ( $spec->code ? ' (' . $spec->code . ')' : '' ) ) ); ?></td>
						<td><?php echo esc_html( $spec->finish ); ?></td>
						<td><?php echo esc_html( $spec->quantity ); ?></td>
						<td><?php echo esc_html( $spec->supplier ); ?></td>
						<td><?php echo esc_html( $spec->purpose ); ?><?php echo $spec->notes ? '<br><span class="muted">' . esc_html( $spec->notes ) . '</span>' : ''; ?></td>
						<td><?php echo esc_html( ffp_date( $spec->installed_on ) ); ?></td>
					</tr>
				<?php endforeach; ?>
			</tbody>
		</table>
	<?php endforeach; ?>
</section>

<section>
	<h2>Warranties</h2>
	<?php if ( ! $warranties ) : ?>
		<p class="muted">No warranty certificates on file yet.</p>
	<?php else : ?>
		<table class="rtable">
			<thead><tr><th>Warranty</th><th>Provider</th><th>Coverage</th><th>What's covered</th></tr></thead>
			<tbody>
				<?php foreach ( $warranties as $w ) : ?>
					<?php $meta = FFP_Data::file_meta( $w ); ?>
					<tr>
						<td><strong><?php echo esc_html( $w->title ); ?></strong></td>
						<td><?php echo esc_html( $meta['provider'] ?? '' ); ?></td>
						<td><?php echo esc_html( implode( ' – ', array_filter( array( ffp_date( $meta['starts'] ?? '' ), ffp_date( $meta['expires'] ?? '' ) ) ) ) ); ?></td>
						<td><?php echo esc_html( $meta['coverage'] ?? '' ); ?></td>
					</tr>
				<?php endforeach; ?>
			</tbody>
		</table>
		<p class="small muted">Certificates can be downloaded from the Documents tab of your project portal.</p>
	<?php endif; ?>
</section>

<?php if ( $others ) : ?>
	<section>
		<h2>Other documents on file</h2>
		<ul>
			<?php foreach ( $others as $d ) : ?>
				<li><?php echo esc_html( $d->title ); ?> <span class="muted">(<?php echo esc_html( $cats[ $d->category ] ?? 'Document' ); ?>, <?php echo esc_html( ffp_date( $d->created_at ) ); ?>)</span></li>
			<?php endforeach; ?>
		</ul>
	</section>
<?php endif; ?>
