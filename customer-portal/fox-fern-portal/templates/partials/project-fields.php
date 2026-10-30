<?php
/**
 * Project detail fields (new project + manage tab).
 *
 * @var object|null $project
 */

defined( 'ABSPATH' ) || exit;

$p     = $project ?? null;
$staff = get_users( array( 'capability' => 'ffp_manage_projects', 'orderby' => 'display_name' ) );
?>
<div class="form-grid">
	<div class="field span-2">
		<label for="pf-title">Project name</label>
		<input id="pf-title" type="text" name="title" required value="<?php echo esc_attr( $p ? $p->title : '' ); ?>" placeholder="e.g. Miller primary bath remodel">
	</div>
	<div class="field span-2">
		<label for="pf-address">Job site address</label>
		<input id="pf-address" type="text" name="address" value="<?php echo esc_attr( $p ? $p->address : '' ); ?>" placeholder="e.g. 123 Main St, Royersford, PA 19468">
	</div>
	<div class="field">
		<label for="pf-status">Status</label>
		<select id="pf-status" name="status">
			<?php foreach ( ffp_project_statuses() as $key => $label ) : ?>
				<option value="<?php echo esc_attr( $key ); ?>"<?php selected( $p ? $p->status : 'planning', $key ); ?>><?php echo esc_html( $label ); ?></option>
			<?php endforeach; ?>
		</select>
	</div>
	<div class="field">
		<label for="pf-manager">Project manager</label>
		<select id="pf-manager" name="manager_id">
			<option value="0">—</option>
			<?php foreach ( $staff as $u ) : ?>
				<option value="<?php echo (int) $u->ID; ?>"<?php selected( $p ? (int) $p->manager_id : get_current_user_id(), (int) $u->ID ); ?>><?php echo esc_html( $u->display_name ); ?></option>
			<?php endforeach; ?>
		</select>
	</div>
	<div class="field">
		<label for="pf-start">Start date</label>
		<input id="pf-start" type="date" name="start_date" value="<?php echo esc_attr( $p ? (string) $p->start_date : '' ); ?>">
	</div>
	<div class="field">
		<label for="pf-target">Target completion</label>
		<input id="pf-target" type="date" name="target_date" value="<?php echo esc_attr( $p ? (string) $p->target_date : '' ); ?>">
	</div>
	<div class="field span-2">
		<label for="pf-desc">Scope of work <span class="small muted">(the customer sees this on the Overview)</span></label>
		<textarea id="pf-desc" name="description" rows="4" placeholder="What's included, rooms involved, key selections…"><?php echo esc_textarea( $p ? (string) $p->description : '' ); ?></textarea>
	</div>
	<label class="check span-2"><input type="checkbox" name="notify_customer" value="1"<?php checked( $p ? (bool) $p->notify_customer : true ); ?>> Email the customer by default when we post updates or documents</label>
</div>
