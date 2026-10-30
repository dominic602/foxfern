<?php
/**
 * Set or reset password (used by invites and "forgot password").
 *
 * @var string           $key
 * @var string           $login
 * @var WP_User|WP_Error $user
 * @var string           $heading
 */

defined( 'ABSPATH' ) || exit;
?>
<h1><?php echo esc_html( $heading ); ?></h1>

<?php if ( is_wp_error( $user ) ) : ?>
	<div class="flash flash-error" role="alert"><p><?php echo esc_html( $user->get_error_message() ); ?></p></div>
	<p class="muted">You can request a fresh link below, or ask your project manager to resend your invite.</p>
	<p><a class="btn btn-gold btn-block" href="<?php echo esc_url( ffp_url( 'forgot' ) ); ?>">Send me a new link</a></p>
<?php else : ?>
	<p class="muted">Signing in as <strong><?php echo esc_html( $user->user_email ); ?></strong>. Choose a password with at least 10 characters.</p>
	<?php if ( FFP_Actions::$form_error ) : ?>
		<div class="flash flash-error" role="alert"><p><?php echo esc_html( FFP_Actions::$form_error ); ?></p></div>
	<?php endif; ?>
	<?php echo ffp_form_open( 'reset', array( 'class' => 'stack' ) ); // phpcs:ignore WordPress.Security.EscapeOutput ?>
		<input type="hidden" name="key" value="<?php echo esc_attr( $key ); ?>">
		<input type="hidden" name="login" value="<?php echo esc_attr( $login ); ?>">
		<input type="text" name="username" autocomplete="username" value="<?php echo esc_attr( $user->user_email ); ?>" hidden>
		<div class="field">
			<label for="pass1">New password</label>
			<div class="pw">
				<input id="pass1" name="pass1" type="password" autocomplete="new-password" minlength="10" required autofocus>
				<button type="button" class="pw-toggle" data-pw-toggle aria-controls="pass1">Show</button>
			</div>
		</div>
		<div class="field">
			<label for="pass2">Type it again</label>
			<input id="pass2" name="pass2" type="password" autocomplete="new-password" minlength="10" required>
		</div>
		<button class="btn btn-gold btn-block" type="submit">Save password and sign in</button>
	</form>
<?php endif; ?>
