<?php
/**
 * Signed-in page shell.
 *
 * @var string $page_title
 * @var string $content
 */

defined( 'ABSPATH' ) || exit;

$s    = ffp_settings();
$user = wp_get_current_user();
$nav  = $nav ?? '';
?><!doctype html>
<html lang="en-US">
<head>
<meta charset="utf-8">
<meta name="viewport" content="width=device-width, initial-scale=1, viewport-fit=cover">
<meta name="robots" content="noindex, nofollow">
<meta name="theme-color" content="#2a2927">
<title><?php echo esc_html( $page_title . ' | ' . $s['company_name'] . ' Client Portal' ); ?></title>
<link rel="icon" href="<?php echo esc_url( home_url( '/favicon-48x48.png' ) ); ?>">
<link rel="stylesheet" href="<?php echo esc_url( FFP_URL . 'assets/portal.css?v=' . FFP_VERSION ); ?>">
<script src="<?php echo esc_url( FFP_URL . 'assets/portal.js?v=' . FFP_VERSION ); ?>" defer></script>
</head>
<body class="ffp">
<a class="skip" href="#main">Skip to content</a>
<header class="top">
	<div class="wrap top-in">
		<a class="brand" href="<?php echo esc_url( ffp_url() ); ?>">
			<?php if ( $s['logo_url'] ) : ?>
				<img src="<?php echo esc_url( $s['logo_url'] ); ?>" alt="<?php echo esc_attr( $s['company_name'] ); ?>" height="44">
			<?php else : ?>
				<span class="wordmark">Fox <em>&amp;</em> Fern</span>
			<?php endif; ?>
			<span class="brand-sub">Client Portal</span>
		</a>
		<nav class="nav" aria-label="Portal">
			<a href="<?php echo esc_url( ffp_url() ); ?>"<?php echo 'projects' === $nav ? ' aria-current="page"' : ''; ?>>Projects</a>
			<?php if ( ffp_is_staff() ) : ?>
				<a href="<?php echo esc_url( ffp_url( 'customers' ) ); ?>"<?php echo 'customers' === $nav ? ' aria-current="page"' : ''; ?>>Customers</a>
			<?php endif; ?>
			<?php if ( ffp_is_admin() ) : ?>
				<a href="<?php echo esc_url( ffp_url( 'settings' ) ); ?>"<?php echo 'settings' === $nav ? ' aria-current="page"' : ''; ?>>Settings</a>
			<?php endif; ?>
		</nav>
		<div class="who">
			<span class="avatar" aria-hidden="true"><?php echo esc_html( ffp_initials( $user->display_name ) ); ?></span>
			<span class="who-name"><?php echo esc_html( $user->display_name ); ?></span>
			<a class="signout" href="<?php echo esc_url( ffp_logout_url() ); ?>">Sign out</a>
		</div>
	</div>
</header>
<main id="main" class="main">
	<div class="wrap">
		<?php foreach ( ffp_take_flash() as $flash ) : ?>
			<div class="flash flash-<?php echo esc_attr( $flash['type'] ); ?>" role="status">
				<p><?php echo esc_html( $flash['message'] ); ?></p>
				<?php if ( $flash['copy'] ) : ?>
					<div class="copy-row">
						<input type="text" readonly value="<?php echo esc_attr( $flash['copy'] ); ?>" aria-label="Link to copy" onclick="this.select()">
						<button type="button" class="btn btn-sm" data-copy>Copy link</button>
					</div>
				<?php endif; ?>
			</div>
		<?php endforeach; ?>
		<?php echo $content; // phpcs:ignore WordPress.Security.EscapeOutput -- escaped in templates. ?>
	</div>
</main>
<footer class="foot">
	<div class="wrap foot-in">
		<span><?php echo esc_html( $s['company_name'] ); ?></span>
		<?php if ( $s['company_phone'] ) : ?>
			<a href="tel:<?php echo esc_attr( preg_replace( '/[^0-9+]/', '', $s['company_phone'] ) ); ?>"><?php echo esc_html( $s['company_phone'] ); ?></a>
		<?php endif; ?>
		<a href="<?php echo esc_url( home_url( '/' ) ); ?>">Back to website</a>
	</div>
</footer>
<div class="lb" id="lb" hidden>
	<button type="button" class="lb-close" aria-label="Close">&times;</button>
	<button type="button" class="lb-prev" aria-label="Previous photo">&#8249;</button>
	<figure><img alt=""><figcaption></figcaption></figure>
	<button type="button" class="lb-next" aria-label="Next photo">&#8250;</button>
</div>
</body>
</html>
