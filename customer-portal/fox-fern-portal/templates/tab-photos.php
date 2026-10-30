<?php
/**
 * Photos tab: every project photo (daily updates + gallery uploads), grouped by day.
 *
 * @var object $project
 * @var bool   $staff
 */

defined( 'ABSPATH' ) || exit;

$tags   = ffp_photo_tags();
$filter = isset( $_GET['tag'] ) ? sanitize_key( $_GET['tag'] ) : ''; // phpcs:ignore WordPress.Security.NonceVerification
$filter = array_key_exists( $filter, $tags ) ? $filter : '';
$photos = FFP_Data::photos( $project->id, $staff, $filter );
$groups = array();
foreach ( $photos as $photo ) {
	$day              = $photo->log_date ? $photo->log_date : substr( $photo->created_at, 0, 10 );
	$groups[ $day ][] = $photo;
}
?>

<div class="toolbar chips" role="group" aria-label="Filter photos">
	<a class="chip" href="<?php echo esc_url( ffp_project_url( $project->id, 'photos' ) ); ?>"<?php echo '' === $filter ? ' aria-current="true"' : ''; ?>>All</a>
	<?php foreach ( $tags as $key => $label ) : ?>
		<a class="chip" href="<?php echo esc_url( ffp_project_url( $project->id, 'photos', array( 'tag' => $key ) ) ); ?>"<?php echo $key === $filter ? ' aria-current="true"' : ''; ?>><?php echo esc_html( $label ); ?></a>
	<?php endforeach; ?>
</div>

<?php if ( $staff ) : ?>
	<details class="card card-form">
		<summary class="card-summary"><span class="btn btn-gold btn-sm">+ Upload photos</span> <span class="muted small">Before/after shots and anything not tied to a daily update</span></summary>
		<?php echo ffp_form_open( 'upload_photos', array( 'files' => true ) ); // phpcs:ignore WordPress.Security.EscapeOutput ?>
			<input type="hidden" name="project_id" value="<?php echo (int) $project->id; ?>">
			<div class="form-grid">
				<div class="field span-2">
					<label for="gp-files">Photos</label>
					<input id="gp-files" type="file" name="photos[]" accept="image/*" multiple required data-file-list>
					<div class="file-list" aria-live="polite"></div>
				</div>
				<div class="field">
					<label for="gp-tag">Tag</label>
					<select id="gp-tag" name="category">
						<?php foreach ( $tags as $key => $label ) : ?>
							<option value="<?php echo esc_attr( $key ); ?>"<?php selected( $key, 'progress' ); ?>><?php echo esc_html( $label ); ?></option>
						<?php endforeach; ?>
					</select>
				</div>
				<div class="field">
					<label for="gp-caption">Caption <span class="small muted">(optional, applies to all)</span></label>
					<input id="gp-caption" type="text" name="caption" placeholder="e.g. Primary bath, before demo">
				</div>
			</div>
			<div class="form-foot">
				<label class="check"><input type="checkbox" name="visible" value="1" checked> Customer can see these</label>
				<button class="btn btn-gold" type="submit">Upload</button>
			</div>
		</form>
	</details>
<?php endif; ?>

<?php if ( ! $photos ) : ?>
	<div class="empty">
		<h2>No photos<?php echo $filter ? ' with this tag' : ' yet'; ?></h2>
		<p>Photos from daily updates and before-and-after shots will collect here.</p>
	</div>
<?php endif; ?>

<?php foreach ( $groups as $day => $list ) : ?>
	<h2 class="day-title"><?php echo esc_html( ffp_date( $day, 'l, F j, Y' ) ); ?> <span class="small muted"><?php echo count( $list ); ?> photo<?php echo 1 === count( $list ) ? '' : 's'; ?></span></h2>
	<div class="gallery">
		<?php foreach ( $list as $photo ) : ?>
			<figure class="ph<?php echo $photo->visible ? '' : ' is-hidden'; ?>">
				<a class="lb-link" href="<?php echo esc_url( ffp_file_url( $photo->id ) ); ?>" data-group="gallery" data-caption="<?php echo esc_attr( $photo->caption ? $photo->caption . ' · ' . ffp_date( $day ) : ffp_date( $day ) ); ?>">
					<img src="<?php echo esc_url( ffp_file_url( $photo->id, 'thumb' ) ); ?>" alt="<?php echo esc_attr( $photo->caption ? $photo->caption : 'Project photo from ' . ffp_date( $day ) ); ?>" loading="lazy">
				</a>
				<figcaption>
					<?php if ( isset( $tags[ $photo->category ] ) ) : ?>
						<span class="tag tag-<?php echo esc_attr( $photo->category ); ?>"><?php echo esc_html( $tags[ $photo->category ] ); ?></span>
					<?php endif; ?>
					<?php if ( ! $photo->visible ) : ?>
						<span class="tag tag-hidden">Hidden</span>
					<?php endif; ?>
					<?php echo esc_html( $photo->caption ); ?>
				</figcaption>
				<?php if ( $staff ) : ?>
					<details class="ph-edit">
						<summary class="btn-text">Edit</summary>
						<?php echo ffp_form_open( 'update_file', array( 'class' => 'stack-sm' ) ); // phpcs:ignore WordPress.Security.EscapeOutput ?>
							<input type="hidden" name="project_id" value="<?php echo (int) $project->id; ?>">
							<input type="hidden" name="file_id" value="<?php echo (int) $photo->id; ?>">
							<input type="text" name="caption" value="<?php echo esc_attr( $photo->caption ); ?>" placeholder="Caption" aria-label="Caption">
							<select name="category" aria-label="Tag">
								<?php foreach ( $tags as $key => $label ) : ?>
									<option value="<?php echo esc_attr( $key ); ?>"<?php selected( $photo->category, $key ); ?>><?php echo esc_html( $label ); ?></option>
								<?php endforeach; ?>
							</select>
							<label class="check"><input type="checkbox" name="visible" value="1"<?php checked( (bool) $photo->visible ); ?>> Customer can see</label>
							<label class="check"><input type="checkbox" name="make_cover" value="1"<?php checked( (int) $project->cover_file_id, (int) $photo->id ); ?>> Use as project cover</label>
							<button class="btn btn-sm btn-gold" type="submit">Save</button>
						</form>
						<?php echo ffp_form_open( 'delete_file', array( 'confirm' => 'Delete this photo?' ) ); // phpcs:ignore WordPress.Security.EscapeOutput ?>
							<input type="hidden" name="project_id" value="<?php echo (int) $project->id; ?>">
							<input type="hidden" name="file_id" value="<?php echo (int) $photo->id; ?>">
							<button class="btn-text danger" type="submit">Delete photo</button>
						</form>
					</details>
				<?php endif; ?>
			</figure>
		<?php endforeach; ?>
	</div>
<?php endforeach; ?>
