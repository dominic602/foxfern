<?php
/**
 * Sign-in form.
 */

defined( 'ABSPATH' ) || exit;

// phpcs:disable WordPress.Security.NonceVerification
$login_value = isset( $_POST['log'] ) ? sanitize_text_field( wp_unslash( $_POST['log'] ) ) : '';
$redirect    = isset( $_REQUEST['redirect_to'] ) ? esc_url_raw( wp_unslash( $_REQUEST['redirect_to'] ) ) : '';
$signed_out  = ! empty( $_GET['signed_out'] );
// phpcs:enable
?>
<h1>Sign in to your project</h1>
<p class="muted">Track daily progress, see photos, and message your project manager.</p>

<?php if ( FFP_Actions::$form_error ) : ?>
	<div class="flash flash-error" role="alert"><p><?php echo esc_html( FFP_Actions::$form_error ); ?></p></div>
<?php elseif ( $signed_out ) : ?>
	<div class="flash flash-success" role="status"><p>You're signed out.</p></div>
<?php endif; ?>

<?php echo ffp_form_open( 'login', array( 'class' => 'stack' ) ); // phpcs:ignore WordPress.Security.EscapeOutput ?>
	<input type="hidden" name="redirect_to" value="<?php echo esc_attr( $redirect ); ?>">
	<div class="field">
		<label for="log">Email</label>
		<input id="log" name="log" type="text" autocomplete="username" inputmode="email" required value="<?php echo esc_attr( $login_value ); ?>" autofocus>
	</div>
	<div class="field">
		<label for="pwd">Password</label>
		<div class="pw">
			<input id="pwd" name="pwd" type="password" autocomplete="current-password" required>
			<button type="button" class="pw-toggle" data-pw-toggle aria-controls="pwd">Show</button>
		</div>
	</div>
	<label class="check"><input type="checkbox" name="remember" value="1" checked> Keep me signed in on this device</label>
	<button class="btn btn-gold btn-block" type="submit">Sign in</button>
</form>
<p class="auth-links"><a href="<?php echo esc_url( ffp_url( 'forgot' ) ); ?>">Forgot your password?</a></p>
<p class="hint">New customer? Your project manager will email you an invitation to set up your account.</p>
