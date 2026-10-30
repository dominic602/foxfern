<?php
/**
 * Documents tab: warranty certificates first, then contracts, permits, manuals, etc.
 *
 * @var object $project
 * @var bool   $staff
 */

defined( 'ABSPATH' ) || exit;

$cats    = ffp_doc_categories();
$docs    = FFP_Data::files( $project->id, 'document', null, ! $staff );
$grouped = array();
foreach ( $docs as $doc ) {
	$grouped[ isset( $cats[ $doc->category ] ) ? $doc->category : 'other' ][] = $doc;
}
$today = current_time( 'Y-m-d' );

$warranty_fields = function ( $meta = array() ) {
	?>
	<fieldset class="warranty-only span-2">
		<legend>Warranty details</legend>
		<div class="form-grid">
			<div class="field"><?php $fid = wp_unique_id( 'ffp-' ); ?><label for="<?php echo esc_attr( $fid ); ?>">Warranty provider</label><input id="<?php echo esc_attr( $fid ); ?>" type="text" name="w_provider" value="<?php echo esc_attr( $meta['provider'] ?? '' ); ?>" placeholder="e.g. Fox & Fern, GAF, Trex"></div>
			<div class="field"><?php $fid = wp_unique_id( 'ffp-' ); ?><label for="<?php echo esc_attr( $fid ); ?>">Coverage starts</label><input id="<?php echo esc_attr( $fid ); ?>" type="date" name="w_starts" value="<?php echo esc_attr( $meta['starts'] ?? '' ); ?>"></div>
			<div class="field"><?php $fid = wp_unique_id( 'ffp-' ); ?><label for="<?php echo esc_attr( $fid ); ?>">Coverage ends</label><input id="<?php echo esc_attr( $fid ); ?>" type="date" name="w_expires" value="<?php echo esc_attr( $meta['expires'] ?? '' ); ?>"></div>
			<div class="field span-2"><?php $fid = wp_unique_id( 'ffp-' ); ?><label for="<?php echo esc_attr( $fid ); ?>">What's covered</label><textarea id="<?php echo esc_attr( $fid ); ?>" name="w_coverage" rows="2" placeholder="e.g. 2-year workmanship warranty on all installed tile and grout"><?php echo esc_textarea( $meta['coverage'] ?? '' ); ?></textarea></div>
		</div>
	</fieldset>
	<?php
};
?>

<?php if ( $staff ) : ?>
	<details class="card card-form"<?php echo $docs ? '' : ' open'; ?>>
		<summary class="card-summary"><span class="btn btn-gold btn-sm">+ Upload document</span> <span class="muted small">Warranty certificates, contracts, permits, manuals (PDF, Word, Excel or photos)</span></summary>
		<?php echo ffp_form_open( 'upload_document', array( 'files' => true ) ); // phpcs:ignore WordPress.Security.EscapeOutput ?>
			<input type="hidden" name="project_id" value="<?php echo (int) $project->id; ?>">
			<div class="form-grid">
				<div class="field">
					<label for="doc-cat">Type</label>
					<select id="doc-cat" name="category" data-warranty-toggle>
						<?php foreach ( $cats as $key => $label ) : ?>
							<option value="<?php echo esc_attr( $key ); ?>"><?php echo esc_html( $label ); ?></option>
						<?php endforeach; ?>
					</select>
				</div>
				<div class="field">
					<label for="doc-title">Title</label>
					<input id="doc-title" type="text" name="title" placeholder="e.g. Workmanship warranty certificate">
				</div>
				<div class="field span-2">
					<label for="doc-file">File(s)</label>
					<input id="doc-file" type="file" name="documents[]" multiple required data-file-list>
					<div class="file-list" aria-live="polite"></div>
				</div>
				<div class="field span-2">
					<label for="doc-notes">Notes for the customer <span class="small muted">(optional)</span></label>
					<textarea id="doc-notes" name="caption" rows="2"></textarea>
				</div>
				<?php $warranty_fields(); ?>
			</div>
			<div class="form-foot">
				<label class="check"><input type="checkbox" name="visible" value="1" checked> Customer can see this</label>
				<label class="check"><input type="checkbox" name="notify" value="1"<?php checked( (bool) $project->notify_customer ); ?>> Email the customer</label>
				<button class="btn btn-gold" type="submit">Upload</button>
			</div>
		</form>
	</details>
<?php endif; ?>

<?php if ( ! $docs ) : ?>
	<div class="empty">
		<h2>No documents yet</h2>
		<p>Your contract, permits, product manuals and warranty certificates will be stored here so you always know where to find them.</p>
	</div>
<?php endif; ?>

<?php if ( ! empty( $grouped['warranty'] ) ) : ?>
	<h2 class="day-title">Warranty certificates</h2>
	<div class="warranty-grid">
		<?php foreach ( $grouped['warranty'] as $doc ) : ?>
			<?php
			$meta    = FFP_Data::file_meta( $doc );
			$expires = $meta['expires'] ?? '';
			$state   = '';
			if ( $expires ) {
				$days  = (int) floor( ( strtotime( $expires ) - strtotime( $today ) ) / DAY_IN_SECONDS );
				$state = $days < 0 ? array( 'expired', 'Expired' ) : ( $days <= 90 ? array( 'soon', 'Ends in ' . $days . ' days' ) : array( 'active', 'Active' ) );
			}
			?>
			<article class="card warranty<?php echo $doc->visible ? '' : ' is-hidden'; ?>">
				<div class="warranty-seal" aria-hidden="true">&#10003;</div>
				<div class="warranty-body">
					<h3><?php echo esc_html( $doc->title ); ?></h3>
					<?php if ( $state ) : ?>
						<?php echo ffp_badge( $state[1], 'w-' . $state[0] ); // phpcs:ignore WordPress.Security.EscapeOutput ?>
					<?php endif; ?>
					<?php if ( ! $doc->visible ) : ?>
						<?php echo ffp_badge( 'Hidden from customer', 'st-draft' ); // phpcs:ignore WordPress.Security.EscapeOutput ?>
					<?php endif; ?>
					<dl class="kv kv-tight">
						<?php if ( ! empty( $meta['provider'] ) ) : ?>
							<dt>Provider</dt><dd><?php echo esc_html( $meta['provider'] ); ?></dd>
						<?php endif; ?>
						<?php if ( ! empty( $meta['starts'] ) || $expires ) : ?>
							<dt>Coverage</dt><dd><?php echo esc_html( implode( ' – ', array_filter( array( ffp_date( $meta['starts'] ?? '' ), ffp_date( $expires ) ) ) ) ); ?></dd>
						<?php endif; ?>
						<?php if ( ! empty( $meta['coverage'] ) ) : ?>
							<dt>Covers</dt><dd><?php echo ffp_text( $meta['coverage'] ); // phpcs:ignore WordPress.Security.EscapeOutput ?></dd>
						<?php endif; ?>
					</dl>
					<?php if ( $doc->caption ) : ?>
						<p class="small"><?php echo ffp_text( $doc->caption ); // phpcs:ignore WordPress.Security.EscapeOutput ?></p>
					<?php endif; ?>
					<p class="doc-actions">
						<a class="btn btn-sm btn-gold" href="<?php echo esc_url( ffp_file_url( $doc->id ) ); ?>" target="_blank" rel="noopener">View certificate</a>
						<a class="btn btn-sm btn-line" href="<?php echo esc_url( ffp_file_url( $doc->id, '', true ) ); ?>">Download</a>
					</p>
					<?php
					if ( $staff ) {
						ffp_template( 'partials/doc-edit', array( 'doc' => $doc, 'project' => $project, 'cats' => $cats, 'warranty_fields' => $warranty_fields ) );
					}
					?>
				</div>
			</article>
		<?php endforeach; ?>
	</div>
<?php endif; ?>

<?php foreach ( $cats as $key => $label ) : ?>
	<?php
	if ( 'warranty' === $key || empty( $grouped[ $key ] ) ) {
		continue;
	}
	?>
	<h2 class="day-title"><?php echo esc_html( $label ); ?></h2>
	<ul class="doc-list">
		<?php foreach ( $grouped[ $key ] as $doc ) : ?>
			<li class="card doc<?php echo $doc->visible ? '' : ' is-hidden'; ?>">
				<span class="ficon"><?php echo esc_html( ffp_file_icon( $doc->mime ) ); ?></span>
				<div class="doc-body">
					<a class="doc-title" href="<?php echo esc_url( ffp_file_url( $doc->id ) ); ?>" target="_blank" rel="noopener"><?php echo esc_html( $doc->title ); ?></a>
					<span class="small muted"><?php echo esc_html( size_format( (int) $doc->size ) . ' · Added ' . ffp_date( $doc->created_at ) ); ?></span>
					<?php if ( ! $doc->visible ) : ?>
						<?php echo ffp_badge( 'Hidden from customer', 'st-draft' ); // phpcs:ignore WordPress.Security.EscapeOutput ?>
					<?php endif; ?>
					<?php if ( $doc->caption ) : ?>
						<p class="small"><?php echo ffp_text( $doc->caption ); // phpcs:ignore WordPress.Security.EscapeOutput ?></p>
					<?php endif; ?>
					<?php
					if ( $staff ) {
						ffp_template( 'partials/doc-edit', array( 'doc' => $doc, 'project' => $project, 'cats' => $cats, 'warranty_fields' => $warranty_fields ) );
					}
					?>
				</div>
				<a class="btn btn-sm btn-line" href="<?php echo esc_url( ffp_file_url( $doc->id, '', true ) ); ?>">Download</a>
			</li>
		<?php endforeach; ?>
	</ul>
<?php endforeach; ?>
