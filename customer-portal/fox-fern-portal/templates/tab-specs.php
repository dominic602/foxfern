<?php
/**
 * Selections & Specs tab: every product, color and finish used, where, and what for.
 *
 * @var object $project
 * @var bool   $staff
 */

defined( 'ABSPATH' ) || exit;

$specs    = FFP_Data::specs( $project->id );
$files    = FFP_Data::files_by_owner( $project->id, 'spec', ! $staff );
$by       = ( isset( $_GET['group'] ) && 'room' === $_GET['group'] ) ? 'room' : 'category'; // phpcs:ignore WordPress.Security.NonceVerification
$cats     = ffp_settings()['spec_categories'];
$grouped  = array();
foreach ( $specs as $spec ) {
	$key               = 'room' === $by ? ( $spec->location ? $spec->location : 'Whole project' ) : ( $spec->category ? $spec->category : 'Other' );
	$grouped[ $key ][] = $spec;
}
if ( 'category' === $by ) {
	// Keep the configured category order.
	uksort(
		$grouped,
		function ( $a, $b ) use ( $cats ) {
			$ia = array_search( $a, $cats, true );
			$ib = array_search( $b, $cats, true );
			return ( false === $ia ? 999 : $ia ) <=> ( false === $ib ? 999 : $ib ) ?: strcmp( $a, $b );
		}
	);
} else {
	ksort( $grouped );
}

$spec_form = function ( $spec = null ) use ( $project, $cats ) {
	$v   = function ( $col ) use ( $spec ) {
		return $spec ? (string) $spec->$col : '';
	};
	$uid = $spec ? (int) $spec->id : 'new';
	echo ffp_form_open( 'save_spec', array( 'files' => true ) ); // phpcs:ignore WordPress.Security.EscapeOutput
	?>
	<input type="hidden" name="project_id" value="<?php echo (int) $project->id; ?>">
	<input type="hidden" name="spec_id" value="<?php echo $spec ? (int) $spec->id : 0; ?>">
	<div class="form-grid form-grid-3">
		<div class="field">
			<label for="sc-<?php echo esc_attr( $uid ); ?>">Category</label>
			<select id="sc-<?php echo esc_attr( $uid ); ?>" name="category">
				<?php
				$current = $v( 'category' );
				$options = $cats;
				if ( $current && ! in_array( $current, $options, true ) ) {
					$options[] = $current;
				}
				foreach ( $options as $cat ) {
					echo '<option' . selected( $current, $cat, false ) . '>' . esc_html( $cat ) . '</option>';
				}
				?>
			</select>
		</div>
		<div class="field"><label for="sl-<?php echo esc_attr( $uid ); ?>">Room / where</label><input id="sl-<?php echo esc_attr( $uid ); ?>" type="text" name="location" value="<?php echo esc_attr( $v( 'location' ) ); ?>" placeholder="e.g. Primary bath walls" list="ffp-rooms"></div>
		<div class="field"><label for="sp-<?php echo esc_attr( $uid ); ?>">Product</label><input id="sp-<?php echo esc_attr( $uid ); ?>" type="text" name="product" value="<?php echo esc_attr( $v( 'product' ) ); ?>" placeholder="e.g. Duration Home Interior Acrylic"></div>
		<div class="field"><label for="sb-<?php echo esc_attr( $uid ); ?>">Brand / manufacturer</label><input id="sb-<?php echo esc_attr( $uid ); ?>" type="text" name="brand" value="<?php echo esc_attr( $v( 'brand' ) ); ?>" placeholder="e.g. Sherwin-Williams"></div>
		<div class="field"><label for="so-<?php echo esc_attr( $uid ); ?>">Color name</label><input id="so-<?php echo esc_attr( $uid ); ?>" type="text" name="color" value="<?php echo esc_attr( $v( 'color' ) ); ?>" placeholder="e.g. Agreeable Gray"></div>
		<div class="field"><label for="sd-<?php echo esc_attr( $uid ); ?>">Color / product code</label><input id="sd-<?php echo esc_attr( $uid ); ?>" type="text" name="code" value="<?php echo esc_attr( $v( 'code' ) ); ?>" placeholder="e.g. SW 7029, SKU, model #"></div>
		<div class="field"><label for="sf-<?php echo esc_attr( $uid ); ?>">Finish / sheen / size</label><input id="sf-<?php echo esc_attr( $uid ); ?>" type="text" name="finish" value="<?php echo esc_attr( $v( 'finish' ) ); ?>" placeholder="e.g. Eggshell, 12x24 matte"></div>
		<div class="field"><label for="sq-<?php echo esc_attr( $uid ); ?>">Quantity</label><input id="sq-<?php echo esc_attr( $uid ); ?>" type="text" name="quantity" value="<?php echo esc_attr( $v( 'quantity' ) ); ?>" placeholder="e.g. 3 gal, 180 sq ft"></div>
		<div class="field"><label for="ss-<?php echo esc_attr( $uid ); ?>">Bought from</label><input id="ss-<?php echo esc_attr( $uid ); ?>" type="text" name="supplier" value="<?php echo esc_attr( $v( 'supplier' ) ); ?>" placeholder="e.g. Sherwin-Williams, Limerick"></div>
		<div class="field span-2"><label for="su-<?php echo esc_attr( $uid ); ?>">What it was used for</label><textarea id="su-<?php echo esc_attr( $uid ); ?>" name="purpose" rows="2" placeholder="e.g. Walls and ceiling, two coats over primer"><?php echo esc_textarea( $v( 'purpose' ) ); ?></textarea></div>
		<div class="field"><label for="si-<?php echo esc_attr( $uid ); ?>">Installed / applied on</label><input id="si-<?php echo esc_attr( $uid ); ?>" type="date" name="installed_on" value="<?php echo esc_attr( $v( 'installed_on' ) ); ?>"></div>
		<div class="field span-2"><label for="sn-<?php echo esc_attr( $uid ); ?>">Notes</label><textarea id="sn-<?php echo esc_attr( $uid ); ?>" name="notes" rows="2" placeholder="Care instructions, touch-up tips, leftover stock location"><?php echo esc_textarea( $v( 'notes' ) ); ?></textarea></div>
		<div class="field"><label for="sa-<?php echo esc_attr( $uid ); ?>">Attach data sheet or photo</label><input id="sa-<?php echo esc_attr( $uid ); ?>" type="file" name="spec_file[]" multiple></div>
	</div>
	<div class="form-foot"><button class="btn btn-gold" type="submit"><?php echo $spec ? 'Save item' : 'Add to spec sheet'; ?></button></div>
	</form>
	<?php
};
?>

<div class="toolbar">
	<p class="lede">Every product, color and finish used on your project, where it went and what it was for. Keep it for touch-ups, repairs and resale.</p>
	<div class="actions">
		<span class="seg" role="group" aria-label="Group by">
			<a href="<?php echo esc_url( ffp_project_url( $project->id, 'specs' ) ); ?>"<?php echo 'category' === $by ? ' aria-current="true"' : ''; ?>>By category</a>
			<a href="<?php echo esc_url( ffp_project_url( $project->id, 'specs', array( 'group' => 'room' ) ) ); ?>"<?php echo 'room' === $by ? ' aria-current="true"' : ''; ?>>By room</a>
		</span>
		<?php if ( $specs ) : ?>
			<a class="btn btn-sm btn-line" href="<?php echo esc_url( ffp_project_url( $project->id, 'record' ) ); ?>" target="_blank" rel="noopener">Print</a>
			<a class="btn btn-sm btn-line" href="<?php echo esc_url( ffp_export_url( $project->id, 'specs' ) ); ?>">Download CSV</a>
		<?php endif; ?>
	</div>
</div>

<?php if ( $staff ) : ?>
	<datalist id="ffp-rooms">
		<?php foreach ( array_unique( array_filter( wp_list_pluck( $specs, 'location' ) ) ) as $room ) : ?>
			<option value="<?php echo esc_attr( $room ); ?>">
		<?php endforeach; ?>
	</datalist>
	<details class="card card-form"<?php echo $specs ? '' : ' open'; ?>>
		<summary class="card-summary"><span class="btn btn-gold btn-sm">+ Add item</span> <span class="muted small">Paint, flooring, tile, fixtures, hardware…</span></summary>
		<?php $spec_form(); ?>
	</details>
<?php endif; ?>

<?php if ( ! $specs ) : ?>
	<div class="empty">
		<h2>No selections recorded yet</h2>
		<p>As materials go in, we'll list each one here with the exact product, color code and where it was used.</p>
	</div>
<?php endif; ?>

<?php foreach ( $grouped as $group => $items ) : ?>
	<section class="spec-group">
		<h2 class="day-title"><?php echo esc_html( $group ); ?> <span class="small muted"><?php echo count( $items ); ?></span></h2>
		<div class="spec-list">
			<?php foreach ( $items as $spec ) : ?>
				<article class="spec card" id="spec-<?php echo (int) $spec->id; ?>">
					<div class="spec-main">
						<p class="spec-eyebrow"><?php echo esc_html( 'room' === $by ? $spec->category : ( $spec->location ? $spec->location : 'Whole project' ) ); ?></p>
						<h3 class="spec-title"><?php echo esc_html( $spec->product ? $spec->product : $spec->color ); ?></h3>
						<?php if ( $spec->brand ) : ?>
							<p class="muted"><?php echo esc_html( $spec->brand ); ?></p>
						<?php endif; ?>
					</div>
					<dl class="spec-facts">
						<?php
						$facts = array(
							'Color'      => trim( $spec->color . ( $spec->code ? ' (' . $spec->code . ')' : '' ) ),
							'Finish'     => $spec->finish,
							'Quantity'   => $spec->quantity,
							'Bought from' => $spec->supplier,
							'Installed'  => ffp_date( $spec->installed_on ),
						);
						if ( $spec->product && ! $spec->color && $spec->code ) {
							$facts['Color'] = '';
							$facts      = array( 'Code' => $spec->code ) + $facts;
						}
						foreach ( $facts as $label => $value ) {
							if ( '' !== (string) $value ) {
								echo '<div><dt>' . esc_html( $label ) . '</dt><dd>' . esc_html( $value ) . '</dd></div>';
							}
						}
						?>
					</dl>
					<?php if ( $spec->purpose || $spec->notes ) : ?>
						<div class="spec-notes">
							<?php if ( $spec->purpose ) : ?>
								<p><strong>Used for:</strong> <?php echo ffp_text( $spec->purpose ); // phpcs:ignore WordPress.Security.EscapeOutput ?></p>
							<?php endif; ?>
							<?php if ( $spec->notes ) : ?>
								<p><strong>Notes:</strong> <?php echo ffp_text( $spec->notes ); // phpcs:ignore WordPress.Security.EscapeOutput ?></p>
							<?php endif; ?>
						</div>
					<?php endif; ?>
					<?php if ( ! empty( $files[ $spec->id ] ) ) : ?>
						<ul class="attach">
							<?php foreach ( $files[ $spec->id ] as $file ) : ?>
								<li>
									<?php if ( FFP_Files::is_image( $file ) ) : ?>
										<a class="lb-link thumb-sm" href="<?php echo esc_url( ffp_file_url( $file->id ) ); ?>" data-group="spec-<?php echo (int) $spec->id; ?>"><img src="<?php echo esc_url( ffp_file_url( $file->id, 'thumb' ) ); ?>" alt="<?php echo esc_attr( $file->title ); ?>" loading="lazy"></a>
									<?php else : ?>
										<a class="file-chip" href="<?php echo esc_url( ffp_file_url( $file->id ) ); ?>" target="_blank" rel="noopener"><span class="ficon"><?php echo esc_html( ffp_file_icon( $file->mime ) ); ?></span><?php echo esc_html( $file->title ); ?></a>
									<?php endif; ?>
									<?php if ( $staff ) : ?>
										<?php echo ffp_form_open( 'delete_file', array( 'class' => 'inline', 'confirm' => 'Remove this attachment?' ) ); // phpcs:ignore WordPress.Security.EscapeOutput ?>
											<input type="hidden" name="project_id" value="<?php echo (int) $project->id; ?>">
											<input type="hidden" name="file_id" value="<?php echo (int) $file->id; ?>">
											<button class="btn-text danger" type="submit" aria-label="Remove attachment">&times;</button>
										</form>
									<?php endif; ?>
								</li>
							<?php endforeach; ?>
						</ul>
					<?php endif; ?>
					<?php if ( $staff ) : ?>
						<details class="inline-edit">
							<summary class="btn-text">Edit</summary>
							<?php $spec_form( $spec ); ?>
							<?php echo ffp_form_open( 'delete_spec', array( 'confirm' => 'Remove this item from the spec sheet?' ) ); // phpcs:ignore WordPress.Security.EscapeOutput ?>
								<input type="hidden" name="project_id" value="<?php echo (int) $project->id; ?>">
								<input type="hidden" name="spec_id" value="<?php echo (int) $spec->id; ?>">
								<button class="btn-text danger" type="submit">Remove item</button>
							</form>
						</details>
					<?php endif; ?>
				</article>
			<?php endforeach; ?>
		</div>
	</section>
<?php endforeach; ?>
