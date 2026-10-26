<?php
/**
 * Daily Updates tab: the daily progress log, with photos. Staff can post, edit and publish.
 *
 * @var object $project
 * @var bool   $staff
 */

defined( 'ABSPATH' ) || exit;

$per_page = 20;
$page     = max( 1, isset( $_GET['pg'] ) ? absint( $_GET['pg'] ) : 1 ); // phpcs:ignore WordPress.Security.NonceVerification
$total    = FFP_Data::count_logs( $project->id, $staff );
$logs     = FFP_Data::logs( $project->id, $staff, $per_page, ( $page - 1 ) * $per_page );
$photos   = FFP_Data::files_by_owner( $project->id, 'log', ! $staff );
$fields   = ffp_log_fields( $staff );
$pages    = (int) ceil( $total / $per_page );

/** Renders the add/edit form for a log entry. */
$log_form = function ( $log = null ) use ( $project ) {
	$values = $log ? $log->values : array();
	$uid    = $log ? (int) $log->id : 'new';
	echo ffp_form_open( 'save_log', array( 'files' => true, 'class' => 'log-form' ) ); // phpcs:ignore WordPress.Security.EscapeOutput
	?>
	<input type="hidden" name="project_id" value="<?php echo (int) $project->id; ?>">
	<input type="hidden" name="log_id" value="<?php echo $log ? (int) $log->id : 0; ?>">
	<div class="form-grid">
		<div class="field">
			<label for="ld-<?php echo esc_attr( $uid ); ?>">Date</label>
			<input id="ld-<?php echo esc_attr( $uid ); ?>" type="date" name="log_date" value="<?php echo esc_attr( $log ? $log->log_date : current_time( 'Y-m-d' ) ); ?>" required>
		</div>
		<?php
		foreach ( ffp_log_fields( true ) as $field ) {
			$wide = in_array( $field['type'], array( 'textarea' ), true ) ? ' span-2' : '';
			echo '<div class="cell' . esc_attr( $wide ) . '">' . ffp_field_input( $field, $values[ $field['key'] ] ?? '' ) . '</div>'; // phpcs:ignore WordPress.Security.EscapeOutput
		}
		?>
		<div class="field span-2">
			<label for="lp-<?php echo esc_attr( $uid ); ?>">Photos <span class="small muted">(add as many as you like; phones can take them straight from the camera)</span></label>
			<input id="lp-<?php echo esc_attr( $uid ); ?>" type="file" name="photos[]" accept="image/*" multiple data-file-list>
			<div class="file-list" aria-live="polite"></div>
		</div>
	</div>
	<div class="form-foot">
		<label class="check"><input type="checkbox" name="visible" value="1"<?php checked( ! $log || $log->visible ); ?>> Share with the customer</label>
		<?php if ( ! $log || ! $log->notified ) : ?>
			<label class="check"><input type="checkbox" name="notify" value="1"<?php checked( (bool) $project->notify_customer ); ?>> Email the customer when shared</label>
		<?php endif; ?>
		<button class="btn btn-gold" type="submit"><?php echo $log ? 'Save changes' : 'Post update'; ?></button>
	</div>
	</form>
	<?php
};
?>

<?php if ( $staff ) : ?>
	<details class="card card-form"<?php echo $logs ? '' : ' open'; ?>>
		<summary class="card-summary"><span class="btn btn-gold btn-sm">+ New daily update</span> <span class="muted small">Fields are set under Settings &gt; Daily update fields</span></summary>
		<?php $log_form(); ?>
	</details>
	<p class="toolbar small">
		<a href="<?php echo esc_url( ffp_project_url( $project->id, 'manage' ) ); ?>#import">Import from Google Sheets</a>
		&middot; <a href="<?php echo esc_url( ffp_export_url( $project->id, 'logs' ) ); ?>">Download as CSV</a>
	</p>
<?php endif; ?>

<?php if ( ! $logs ) : ?>
	<div class="empty">
		<h2>No daily updates yet</h2>
		<p><?php echo $staff ? 'Post the first update above, or import your existing Google Sheet from the Manage tab.' : "Your crew posts an update after each day on site: what got done, what's next, and photos."; ?></p>
	</div>
<?php endif; ?>

<div class="feed">
	<?php foreach ( $logs as $log ) : ?>
		<article class="card log<?php echo $log->visible ? '' : ' is-draft'; ?>" id="log-<?php echo (int) $log->id; ?>">
			<header class="log-head">
				<div>
					<h2 class="log-date"><?php echo esc_html( ffp_date( $log->log_date, 'l, F j, Y' ) ); ?></h2>
					<span class="small muted">Posted by <?php echo esc_html( ffp_user_name( $log->author_id ) ); ?></span>
				</div>
				<?php if ( ! $log->visible ) : ?>
					<?php echo ffp_badge( 'Draft: customer can\'t see this', 'st-draft' ); // phpcs:ignore WordPress.Security.EscapeOutput ?>
				<?php endif; ?>
			</header>

			<dl class="kv">
				<?php
				foreach ( $fields as $field ) {
					$html = ffp_field_display( $field, $log->values[ $field['key'] ] ?? '' );
					if ( '' === $html ) {
						continue;
					}
					$lock = ! empty( $field['staff_only'] ) ? ' <span class="lock">Staff only</span>' : '';
					echo '<dt>' . esc_html( $field['label'] ) . $lock . '</dt><dd>' . $html . '</dd>'; // phpcs:ignore WordPress.Security.EscapeOutput
				}
				?>
			</dl>

			<?php if ( ! empty( $photos[ $log->id ] ) ) : ?>
				<div class="grid-photos">
					<?php foreach ( $photos[ $log->id ] as $photo ) : ?>
						<a class="lb-link" href="<?php echo esc_url( ffp_file_url( $photo->id ) ); ?>" data-group="log-<?php echo (int) $log->id; ?>" data-caption="<?php echo esc_attr( $photo->caption ); ?>">
							<img src="<?php echo esc_url( ffp_file_url( $photo->id, 'thumb' ) ); ?>" alt="<?php echo esc_attr( $photo->caption ? $photo->caption : 'Progress photo from ' . ffp_date( $log->log_date ) ); ?>" loading="lazy">
						</a>
					<?php endforeach; ?>
				</div>
			<?php endif; ?>

			<?php if ( $staff ) : ?>
				<div class="row-actions">
					<?php if ( ! $log->visible ) : ?>
						<?php echo ffp_form_open( 'publish_log', array( 'class' => 'inline' ) ); // phpcs:ignore WordPress.Security.EscapeOutput ?>
							<input type="hidden" name="project_id" value="<?php echo (int) $project->id; ?>">
							<input type="hidden" name="log_id" value="<?php echo (int) $log->id; ?>">
							<button class="btn btn-sm btn-gold" type="submit">Share with customer</button>
						</form>
					<?php endif; ?>
					<details class="inline-edit">
						<summary class="btn btn-sm btn-line">Edit</summary>
						<?php $log_form( $log ); ?>
						<?php if ( ! empty( $photos[ $log->id ] ) ) : ?>
							<?php echo ffp_form_open( 'save_log', array( 'class' => 'remove-photos' ) ); // phpcs:ignore WordPress.Security.EscapeOutput ?>
								<input type="hidden" name="project_id" value="<?php echo (int) $project->id; ?>">
								<input type="hidden" name="log_id" value="<?php echo (int) $log->id; ?>">
								<input type="hidden" name="log_date" value="<?php echo esc_attr( $log->log_date ); ?>">
								<?php if ( $log->visible ) : ?>
									<input type="hidden" name="visible" value="1">
								<?php endif; ?>
								<p class="small muted">Remove photos from this update:</p>
								<div class="pick-photos">
									<?php foreach ( $photos[ $log->id ] as $photo ) : ?>
										<label><input type="checkbox" name="delete_files[]" value="<?php echo (int) $photo->id; ?>"><img src="<?php echo esc_url( ffp_file_url( $photo->id, 'thumb' ) ); ?>" alt=""></label>
									<?php endforeach; ?>
								</div>
								<button class="btn btn-sm btn-line" type="submit">Remove checked photos</button>
							</form>
						<?php endif; ?>
						<?php echo ffp_form_open( 'delete_log', array( 'confirm' => 'Delete this daily update and its photos? This cannot be undone.' ) ); // phpcs:ignore WordPress.Security.EscapeOutput ?>
							<input type="hidden" name="project_id" value="<?php echo (int) $project->id; ?>">
							<input type="hidden" name="log_id" value="<?php echo (int) $log->id; ?>">
							<button class="btn-text danger" type="submit">Delete this update</button>
						</form>
					</details>
				</div>
			<?php endif; ?>
		</article>
	<?php endforeach; ?>
</div>

<?php if ( $pages > 1 ) : ?>
	<nav class="pager" aria-label="More updates">
		<?php if ( $page > 1 ) : ?>
			<a class="btn btn-line btn-sm" href="<?php echo esc_url( ffp_project_url( $project->id, 'updates', array( 'pg' => $page - 1 ) ) ); ?>">&larr; Newer</a>
		<?php endif; ?>
		<span class="small muted">Page <?php echo (int) $page; ?> of <?php echo (int) $pages; ?></span>
		<?php if ( $page < $pages ) : ?>
			<a class="btn btn-line btn-sm" href="<?php echo esc_url( ffp_project_url( $project->id, 'updates', array( 'pg' => $page + 1 ) ) ); ?>">Older &rarr;</a>
		<?php endif; ?>
	</nav>
<?php endif; ?>
