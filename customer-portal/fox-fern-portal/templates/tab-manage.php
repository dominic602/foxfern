<?php
/**
 * Manage tab (staff): project details, customer access, Google Sheets import, exports, archive/delete.
 *
 * @var object $project
 */

defined( 'ABSPATH' ) || exit;

$members    = FFP_Data::members( $project->id );
$member_ids = wp_list_pluck( $members, 'ID' );
$customers  = array_filter( get_users( array( 'role' => 'ffp_customer', 'orderby' => 'display_name' ) ), fn( $u ) => ! in_array( $u->ID, $member_ids, true ) );
?>
<div class="cols">
	<div class="col-main">
		<div class="card">
			<h2 class="card-title">Project details</h2>
			<?php echo ffp_form_open( 'save_project' ); // phpcs:ignore WordPress.Security.EscapeOutput ?>
				<input type="hidden" name="project_id" value="<?php echo (int) $project->id; ?>">
				<?php ffp_template( 'partials/project-fields', array( 'project' => $project ) ); ?>
				<div class="form-foot"><button class="btn btn-gold" type="submit">Save details</button></div>
			</form>
		</div>

		<div class="card" id="import">
			<h2 class="card-title">Import your daily log from Google Sheets</h2>
			<ol class="steps small">
				<li>In Google Sheets, open your daily log and choose <strong>File &gt; Download &gt; Comma-separated values (.csv)</strong>.</li>
				<li>The first row must be column names, and one column must be called <strong>Date</strong>.</li>
				<li>Other columns are matched to your daily update fields by name (for example "Work completed today" or "Crew on site"). Rows already imported are skipped, so it's safe to import the same sheet again later.</li>
			</ol>
			<p class="small muted">Current fields: <?php echo esc_html( implode( ', ', array_column( ffp_log_fields(), 'label' ) ) ); ?>. <?php if ( ffp_is_admin() ) : ?><a href="<?php echo esc_url( ffp_url( 'settings' ) ); ?>">Change fields</a><?php endif; ?></p>
			<?php echo ffp_form_open( 'import_csv', array( 'files' => true ) ); // phpcs:ignore WordPress.Security.EscapeOutput ?>
				<input type="hidden" name="project_id" value="<?php echo (int) $project->id; ?>">
				<div class="field"><label for="csv-file">CSV file</label><input id="csv-file" type="file" name="csv" accept=".csv,text/csv" required></div>
				<label class="check"><input type="checkbox" name="add_missing" value="1" checked> Add unmatched columns as new daily update fields</label>
				<label class="check"><input type="checkbox" name="visible" value="1"> Share imported entries with the customer right away</label>
				<p class="small muted">Leave sharing off to review imported entries first; each one gets a "Share with customer" button. Imports never send emails.</p>
				<div class="form-foot"><button class="btn btn-gold" type="submit">Import</button></div>
			</form>
		</div>
	</div>

	<aside class="col-side">
		<div class="card">
			<h2 class="card-title">Customer access</h2>
			<?php if ( $members ) : ?>
				<ul class="people">
					<?php foreach ( $members as $m ) : ?>
						<?php list( $state, $state_label ) = FFP_Auth::customer_state( $m ); ?>
						<li>
							<span class="avatar" aria-hidden="true"><?php echo esc_html( ffp_initials( $m->display_name ) ); ?></span>
							<div class="person">
								<strong><?php echo esc_html( $m->display_name ); ?></strong>
								<a class="small" href="mailto:<?php echo esc_attr( $m->user_email ); ?>"><?php echo esc_html( $m->user_email ); ?></a>
								<?php $phone = get_user_meta( $m->ID, 'ffp_phone', true ); ?>
								<?php if ( $phone ) : ?>
									<a class="small" href="tel:<?php echo esc_attr( preg_replace( '/[^0-9+]/', '', $phone ) ); ?>"><?php echo esc_html( $phone ); ?></a>
								<?php endif; ?>
								<span class="small state state-<?php echo esc_attr( $state ); ?>"><?php echo esc_html( $state_label ); ?></span>
								<div class="row-actions">
									<?php if ( 'pending' === $state ) : ?>
										<?php echo ffp_form_open( 'resend_invite', array( 'class' => 'inline' ) ); // phpcs:ignore WordPress.Security.EscapeOutput ?>
											<input type="hidden" name="project_id" value="<?php echo (int) $project->id; ?>">
											<input type="hidden" name="user_id" value="<?php echo (int) $m->ID; ?>">
											<button class="btn-text" type="submit">Send invite again</button>
										</form>
									<?php endif; ?>
									<?php echo ffp_form_open( 'remove_member', array( 'class' => 'inline', 'confirm' => 'Remove this person\'s access to this project?' ) ); // phpcs:ignore WordPress.Security.EscapeOutput ?>
										<input type="hidden" name="project_id" value="<?php echo (int) $project->id; ?>">
										<input type="hidden" name="user_id" value="<?php echo (int) $m->ID; ?>">
										<button class="btn-text danger" type="submit">Remove access</button>
									</form>
								</div>
							</div>
						</li>
					<?php endforeach; ?>
				</ul>
			<?php else : ?>
				<p class="muted small">No one can see this project yet. Invite your customer below.</p>
			<?php endif; ?>

			<h3 class="mini-title">Invite a customer</h3>
			<?php echo ffp_form_open( 'invite_customer', array( 'class' => 'stack-sm' ) ); // phpcs:ignore WordPress.Security.EscapeOutput ?>
				<input type="hidden" name="project_id" value="<?php echo (int) $project->id; ?>">
				<div class="field"><label for="iv-name">Name</label><input id="iv-name" type="text" name="name" required autocomplete="off"></div>
				<div class="field"><label for="iv-email">Email</label><input id="iv-email" type="email" name="email" required autocomplete="off"></div>
				<div class="field"><label for="iv-phone">Mobile <span class="small muted">(optional)</span></label><input id="iv-phone" type="tel" name="phone" autocomplete="off"></div>
				<label class="check"><input type="checkbox" name="send_invite" value="1" checked> Email them a link to set their password</label>
				<button class="btn btn-gold btn-block" type="submit">Give access</button>
			</form>

			<?php if ( $customers ) : ?>
				<h3 class="mini-title">Or add an existing customer</h3>
				<?php echo ffp_form_open( 'add_member', array( 'class' => 'stack-sm' ) ); // phpcs:ignore WordPress.Security.EscapeOutput ?>
					<input type="hidden" name="project_id" value="<?php echo (int) $project->id; ?>">
					<label class="sr-only" for="am-user">Customer</label>
					<select id="am-user" name="user_id">
						<?php foreach ( $customers as $c ) : ?>
							<option value="<?php echo (int) $c->ID; ?>"><?php echo esc_html( $c->display_name . ' (' . $c->user_email . ')' ); ?></option>
						<?php endforeach; ?>
					</select>
					<button class="btn btn-line btn-block" type="submit">Add to project</button>
				</form>
			<?php endif; ?>
		</div>

		<div class="card">
			<h2 class="card-title">Downloads</h2>
			<ul class="plain">
				<li><a href="<?php echo esc_url( ffp_export_url( $project->id, 'logs' ) ); ?>">Daily log (CSV, includes staff-only fields)</a></li>
				<li><a href="<?php echo esc_url( ffp_export_url( $project->id, 'specs' ) ); ?>">Selections &amp; specs (CSV)</a></li>
				<li><a href="<?php echo esc_url( ffp_project_url( $project->id, 'record' ) ); ?>" target="_blank" rel="noopener">Printable home record</a></li>
			</ul>
		</div>

		<div class="card card-danger">
			<h2 class="card-title">Archive or delete</h2>
			<?php echo ffp_form_open( 'archive_project', array( 'class' => 'stack-sm' ) ); // phpcs:ignore WordPress.Security.EscapeOutput ?>
				<input type="hidden" name="project_id" value="<?php echo (int) $project->id; ?>">
				<p class="small muted"><?php echo $project->archived ? 'This project is archived. Restore it to move it back to active projects.' : 'Archive finished jobs to tidy your list. Customers keep access to everything under "Past projects".'; ?></p>
				<button class="btn btn-line btn-block" type="submit"><?php echo $project->archived ? 'Restore project' : 'Archive project'; ?></button>
			</form>
			<details class="inline-edit">
				<summary class="btn-text danger">Delete project permanently…</summary>
				<?php echo ffp_form_open( 'delete_project', array( 'class' => 'stack-sm', 'confirm' => 'Permanently delete this project, its photos, documents and messages?' ) ); // phpcs:ignore WordPress.Security.EscapeOutput ?>
					<input type="hidden" name="project_id" value="<?php echo (int) $project->id; ?>">
					<p class="small">This removes every update, photo, document and message. Customer accounts are kept. Type <strong>DELETE</strong> to confirm.</p>
					<label class="sr-only" for="del-confirm">Type DELETE</label>
					<input id="del-confirm" type="text" name="confirm" autocomplete="off" required>
					<button class="btn btn-danger btn-block" type="submit">Delete forever</button>
				</form>
			</details>
		</div>
	</aside>
</div>
