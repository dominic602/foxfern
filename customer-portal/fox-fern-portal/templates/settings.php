<?php
/**
 * Settings (admins): daily update fields, company details, notifications.
 */

defined( 'ABSPATH' ) || exit;

$s      = ffp_settings();
$types  = ffp_field_types();
$fields = $s['log_fields'];

$field_row = function ( $i, $f ) use ( $types ) {
	?>
	<tr class="frow">
		<td>
			<input type="hidden" name="f[<?php echo esc_attr( $i ); ?>][key]" value="<?php echo esc_attr( $f['key'] ); ?>">
			<label class="sr-only" for="fl-<?php echo esc_attr( $i ); ?>">Field name</label>
			<input id="fl-<?php echo esc_attr( $i ); ?>" type="text" name="f[<?php echo esc_attr( $i ); ?>][label]" value="<?php echo esc_attr( $f['label'] ); ?>" placeholder="Field name">
		</td>
		<td>
			<label class="sr-only" for="ft-<?php echo esc_attr( $i ); ?>">Type</label>
			<select id="ft-<?php echo esc_attr( $i ); ?>" name="f[<?php echo esc_attr( $i ); ?>][type]" data-type-select>
				<?php foreach ( $types as $key => $label ) : ?>
					<option value="<?php echo esc_attr( $key ); ?>"<?php selected( $f['type'], $key ); ?>><?php echo esc_html( $label ); ?></option>
				<?php endforeach; ?>
			</select>
			<input type="text" class="opts" name="f[<?php echo esc_attr( $i ); ?>][options]" value="<?php echo esc_attr( $f['options'] ); ?>" placeholder="Choices, separated by commas" aria-label="Dropdown choices"<?php echo 'select' === $f['type'] ? '' : ' hidden'; ?>>
		</td>
		<td class="c"><input type="checkbox" name="f[<?php echo esc_attr( $i ); ?>][staff_only]" value="1"<?php checked( ! empty( $f['staff_only'] ) ); ?> aria-label="Staff only"></td>
		<td class="c"><input type="number" class="order" name="f[<?php echo esc_attr( $i ); ?>][order]" value="<?php echo esc_attr( ( (int) $i + 1 ) * 10 ); ?>" aria-label="Order"></td>
		<td class="c"><input type="checkbox" name="f[<?php echo esc_attr( $i ); ?>][remove]" value="1" aria-label="Remove field"></td>
	</tr>
	<?php
};
?>
<div class="page-head"><h1>Settings</h1></div>

<div class="card">
	<h2 class="card-title">Daily update fields</h2>
	<p class="small muted">These are the columns of your daily progress tracker, the same idea as your Google Sheet. Rename, reorder or add fields any time. Removing a field hides it from the form and from past updates, but nothing already saved is deleted, and the CSV download still has it. <strong>Staff only</strong> fields are never shown to customers.</p>
	<?php echo ffp_form_open( 'save_settings' ); // phpcs:ignore WordPress.Security.EscapeOutput ?>
		<input type="hidden" name="section" value="fields">
		<div class="table-wrap">
			<table class="ftable">
				<thead><tr><th>Field name</th><th>Type</th><th class="c">Staff only</th><th class="c">Order</th><th class="c">Remove</th></tr></thead>
				<tbody data-rows>
					<?php
					foreach ( $fields as $i => $f ) {
						$field_row( $i, $f );
					}
					?>
				</tbody>
			</table>
		</div>
		<template id="ffp-field-row">
			<?php $field_row( '__i__', array( 'key' => '', 'label' => '', 'type' => 'text', 'options' => '', 'staff_only' => 0 ) ); ?>
		</template>
		<div class="form-foot">
			<button type="button" class="btn btn-line btn-sm" data-add-row>+ Add field</button>
			<button class="btn btn-gold" type="submit">Save fields</button>
		</div>
	</form>
</div>

<div class="card">
	<h2 class="card-title">Company & notifications</h2>
	<?php echo ffp_form_open( 'save_settings' ); // phpcs:ignore WordPress.Security.EscapeOutput ?>
		<input type="hidden" name="section" value="general">
		<div class="form-grid">
			<div class="field"><label for="st-name">Company name</label><input id="st-name" type="text" name="company_name" value="<?php echo esc_attr( $s['company_name'] ); ?>"></div>
			<div class="field"><label for="st-phone">Phone shown to customers</label><input id="st-phone" type="text" name="company_phone" value="<?php echo esc_attr( $s['company_phone'] ); ?>"></div>
			<div class="field span-2"><label for="st-emails">Email these addresses when a customer posts a message or change request</label><input id="st-emails" type="text" name="staff_emails" value="<?php echo esc_attr( $s['staff_emails'] ); ?>" placeholder="office@…, dominic@…"><span class="small muted">Separate with commas. The project's manager is always included.</span></div>
			<div class="field span-2"><label for="st-logo">Logo image URL <span class="small muted">(optional; leave blank to show "Fox &amp; Fern" in type)</span></label><input id="st-logo" type="url" name="logo_url" value="<?php echo esc_attr( $s['logo_url'] ); ?>" placeholder="https://foxandfernservices.com/assets/logo-….webp"></div>
			<div class="field span-2"><label for="st-welcome">Welcome message on the customer's home screen</label><textarea id="st-welcome" name="welcome_message" rows="3"><?php echo esc_textarea( $s['welcome_message'] ); ?></textarea></div>
			<div class="field"><label for="st-max">Largest upload allowed (MB)</label><input id="st-max" type="number" min="1" max="512" name="max_upload_mb" value="<?php echo (int) $s['max_upload_mb']; ?>"><span class="small muted">Your server currently accepts up to <?php echo esc_html( size_format( wp_max_upload_size() ) ); ?> per upload.</span></div>
			<div class="field span-2"><label for="st-cats">Spec sheet categories <span class="small muted">(one per line)</span></label><textarea id="st-cats" name="spec_categories" rows="8"><?php echo esc_textarea( implode( "\n", $s['spec_categories'] ) ); ?></textarea></div>
		</div>
		<div class="form-foot"><button class="btn btn-gold" type="submit">Save settings</button></div>
	</form>
</div>

<div class="card">
	<h2 class="card-title">Linking the portal from your website</h2>
	<p class="small">Customers sign in at <a href="<?php echo esc_url( ffp_url( 'login' ) ); ?>"><?php echo esc_html( ffp_url( 'login' ) ); ?></a>. Add a "Client Login" link to that address in your site's header or footer menu. In any WordPress page or widget you can also use the shortcode <code>[ffp_portal_link]</code>.</p>
</div>
