<?php
/**
 * Customers (staff): every portal customer, their projects and sign-in status.
 */

defined( 'ABSPATH' ) || exit;

$customers = get_users( array( 'role' => 'ffp_customer', 'orderby' => 'display_name' ) );
$projects  = array_merge( FFP_Data::projects_for( get_current_user_id() ), FFP_Data::projects_for( get_current_user_id(), true ) );
?>
<div class="page-head"><h1>Customers</h1></div>

<div class="cols">
	<div class="col-main">
		<?php if ( ! $customers ) : ?>
			<div class="empty">
				<h2>No customers yet</h2>
				<p>Invite a customer here or from a project's Manage tab. They'll get an email to set their password.</p>
			</div>
		<?php else : ?>
			<ul class="people people-wide">
				<?php foreach ( $customers as $c ) : ?>
					<?php
					list( $state, $state_label ) = FFP_Auth::customer_state( $c );
					$phone                       = get_user_meta( $c->ID, 'ffp_phone', true );
					$their                       = FFP_Data::member_projects( $c->ID );
					?>
					<li class="card">
						<span class="avatar" aria-hidden="true"><?php echo esc_html( ffp_initials( $c->display_name ) ); ?></span>
						<div class="person">
							<strong><?php echo esc_html( $c->display_name ); ?></strong>
							<span class="small">
								<a href="mailto:<?php echo esc_attr( $c->user_email ); ?>"><?php echo esc_html( $c->user_email ); ?></a>
								<?php if ( $phone ) : ?>
									&middot; <a href="tel:<?php echo esc_attr( preg_replace( '/[^0-9+]/', '', $phone ) ); ?>"><?php echo esc_html( $phone ); ?></a>
								<?php endif; ?>
							</span>
							<span class="small state state-<?php echo esc_attr( $state ); ?>"><?php echo esc_html( $state_label ); ?></span>
							<span class="small">
								<?php if ( $their ) : ?>
									<?php
									echo implode( // phpcs:ignore WordPress.Security.EscapeOutput
										', ',
										array_map(
											fn( $p ) => '<a href="' . esc_url( ffp_project_url( $p->id ) ) . '">' . esc_html( $p->title ) . '</a>' . ( $p->archived ? ' <span class="muted">(archived)</span>' : '' ),
											$their
										)
									);
									?>
								<?php else : ?>
									<span class="muted">Not on any project</span>
								<?php endif; ?>
							</span>
							<div class="row-actions">
								<?php if ( 'pending' === $state ) : ?>
									<?php echo ffp_form_open( 'resend_invite', array( 'class' => 'inline' ) ); // phpcs:ignore WordPress.Security.EscapeOutput ?>
										<input type="hidden" name="user_id" value="<?php echo (int) $c->ID; ?>">
										<button class="btn-text" type="submit">Send invite again</button>
									</form>
								<?php endif; ?>
								<details class="inline-edit">
									<summary class="btn-text">Edit</summary>
									<?php echo ffp_form_open( 'update_customer', array( 'class' => 'form-grid' ) ); // phpcs:ignore WordPress.Security.EscapeOutput ?>
										<input type="hidden" name="user_id" value="<?php echo (int) $c->ID; ?>">
										<div class="field"><label for="cn-<?php echo (int) $c->ID; ?>">Name</label><input id="cn-<?php echo (int) $c->ID; ?>" type="text" name="name" value="<?php echo esc_attr( $c->display_name ); ?>"></div>
										<div class="field"><label for="ce-<?php echo (int) $c->ID; ?>">Email (also their sign-in)</label><input id="ce-<?php echo (int) $c->ID; ?>" type="email" name="email" value="<?php echo esc_attr( $c->user_email ); ?>" required></div>
										<div class="field"><label for="cp-<?php echo (int) $c->ID; ?>">Mobile</label><input id="cp-<?php echo (int) $c->ID; ?>" type="tel" name="phone" value="<?php echo esc_attr( (string) $phone ); ?>"></div>
										<div class="form-actions"><button class="btn btn-sm btn-gold" type="submit">Save</button></div>
									</form>
								</details>
								<?php echo ffp_form_open( 'toggle_customer', array( 'class' => 'inline', 'confirm' => 'disabled' === $state ? '' : 'Turn off portal access for this customer? They will be signed out.' ) ); // phpcs:ignore WordPress.Security.EscapeOutput ?>
									<input type="hidden" name="user_id" value="<?php echo (int) $c->ID; ?>">
									<button class="btn-text<?php echo 'disabled' === $state ? '' : ' danger'; ?>" type="submit"><?php echo 'disabled' === $state ? 'Turn access back on' : 'Turn off access'; ?></button>
								</form>
							</div>
						</div>
					</li>
				<?php endforeach; ?>
			</ul>
		<?php endif; ?>
	</div>

	<aside class="col-side">
		<div class="card">
			<h2 class="card-title">Invite a customer</h2>
			<?php echo ffp_form_open( 'invite_customer', array( 'class' => 'stack-sm' ) ); // phpcs:ignore WordPress.Security.EscapeOutput ?>
				<div class="field"><label for="ic-name">Name</label><input id="ic-name" type="text" name="name" required autocomplete="off"></div>
				<div class="field"><label for="ic-email">Email</label><input id="ic-email" type="email" name="email" required autocomplete="off"></div>
				<div class="field"><label for="ic-phone">Mobile <span class="small muted">(optional)</span></label><input id="ic-phone" type="tel" name="phone" autocomplete="off"></div>
				<div class="field"><label for="ic-project">Project</label>
					<select id="ic-project" name="project_id">
						<option value="0">None yet</option>
						<?php foreach ( $projects as $p ) : ?>
							<option value="<?php echo (int) $p->id; ?>"><?php echo esc_html( $p->title ); ?></option>
						<?php endforeach; ?>
					</select>
				</div>
				<label class="check"><input type="checkbox" name="send_invite" value="1" checked> Email them a link to set their password</label>
				<button class="btn btn-gold btn-block" type="submit">Invite</button>
			</form>
		</div>
		<div class="card">
			<h2 class="card-title">Team accounts</h2>
			<p class="small muted">To give an employee access to every project, add them in WordPress under Users &gt; Add New with the role <strong>Portal Staff</strong>. Staff can post updates, photos, specs and documents, but can't change portal settings.</p>
		</div>
	</aside>
</div>
