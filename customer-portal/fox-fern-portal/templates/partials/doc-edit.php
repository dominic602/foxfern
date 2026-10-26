<?php
/**
 * Staff edit/delete controls for one document.
 *
 * @var object   $doc
 * @var object   $project
 * @var array    $cats
 * @var callable $warranty_fields
 */

defined( 'ABSPATH' ) || exit;
?>
<details class="inline-edit">
	<summary class="btn-text">Edit</summary>
	<?php echo ffp_form_open( 'update_file' ); // phpcs:ignore WordPress.Security.EscapeOutput ?>
		<input type="hidden" name="project_id" value="<?php echo (int) $project->id; ?>">
		<input type="hidden" name="file_id" value="<?php echo (int) $doc->id; ?>">
		<div class="form-grid">
			<div class="field"><?php $fid = wp_unique_id( 'ffp-' ); ?><label for="<?php echo esc_attr( $fid ); ?>">Type</label><select id="<?php echo esc_attr( $fid ); ?>" name="category" data-warranty-toggle>
					<?php foreach ( $cats as $key => $label ) : ?>
						<option value="<?php echo esc_attr( $key ); ?>"<?php selected( $doc->category, $key ); ?>><?php echo esc_html( $label ); ?></option>
					<?php endforeach; ?>
				</select>
			</div>
			<div class="field"><?php $fid = wp_unique_id( 'ffp-' ); ?><label for="<?php echo esc_attr( $fid ); ?>">Title</label><input id="<?php echo esc_attr( $fid ); ?>" type="text" name="title" value="<?php echo esc_attr( $doc->title ); ?>"></div>
			<div class="field span-2"><?php $fid = wp_unique_id( 'ffp-' ); ?><label for="<?php echo esc_attr( $fid ); ?>">Notes for the customer</label><textarea id="<?php echo esc_attr( $fid ); ?>" name="caption" rows="2"><?php echo esc_textarea( (string) $doc->caption ); ?></textarea></div>
			<?php $warranty_fields( FFP_Data::file_meta( $doc ) ); ?>
		</div>
		<div class="form-foot">
			<label class="check"><input type="checkbox" name="visible" value="1"<?php checked( (bool) $doc->visible ); ?>> Customer can see this</label>
			<button class="btn btn-sm btn-gold" type="submit">Save</button>
		</div>
	</form>
	<?php echo ffp_form_open( 'delete_file', array( 'confirm' => 'Delete this document? This cannot be undone.' ) ); // phpcs:ignore WordPress.Security.EscapeOutput ?>
		<input type="hidden" name="project_id" value="<?php echo (int) $project->id; ?>">
		<input type="hidden" name="file_id" value="<?php echo (int) $doc->id; ?>">
		<button class="btn-text danger" type="submit">Delete document</button>
	</form>
</details>
