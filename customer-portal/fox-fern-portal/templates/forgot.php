<?php
/**
 * Forgot-password form.
 */

defined( 'ABSPATH' ) || exit;
?>
<h1>Reset your password</h1>

<?php if ( FFP_Actions::$form_done ) : ?>
	<div class="flash flash-success" role="status">
		<p>If that email is on file, a reset link is on its way. It works for 24 hours. Check your spam folder if you don't see it in a few minutes.</p>
	</div>
	<p class="auth-links"><a href="<?php echo esc_url( ffp_url( 'login' ) ); ?>">Back to sign in</a></p>
<?php else : ?>
	<p class="muted">Enter the email you use for the portal and we'll send you a link to choose a new password.</p>
	<?php if ( FFP_Actions::$form_error ) : ?>
		<div class="flash flash-error" role="alert"><p><?php echo esc_html( FFP_Actions::$form_error ); ?></p></div>
	<?php endif; ?>
	<?php echo ffp_form_open( 'forgot', array( 'class' => 'stack' ) ); // phpcs:ignore WordPress.Security.EscapeOutput ?>
		<div class="field">
			<label for="login">Email</label>
			<input id="login" name="login" type="email" autocomplete="email" required autofocus>
		</div>
		<button class="btn btn-gold btn-block" type="submit">Email me a reset link</button>
	</form>
	<p class="auth-links"><a href="<?php echo esc_url( ffp_url( 'login' ) ); ?>">Back to sign in</a></p>
<?php endif; ?>
