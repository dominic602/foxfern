<?php
/**
 * Not found / no access.
 *
 * @var string $message
 */

defined( 'ABSPATH' ) || exit;
?>
<div class="empty">
	<h1>Not found</h1>
	<p><?php echo esc_html( $message ); ?></p>
	<p><a class="btn btn-gold" href="<?php echo esc_url( ffp_url() ); ?>">Go to your projects</a></p>
</div>
