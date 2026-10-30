<?php
/**
 * Signed-out page shell (sign in, forgot password, set password).
 *
 * @var string $page_title
 * @var string $content
 */

defined( 'ABSPATH' ) || exit;

$s = ffp_settings();
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
<body class="ffp ffp-auth">
<main class="auth" id="main">
	<a class="brand brand-auth" href="<?php echo esc_url( home_url( '/' ) ); ?>">
		<?php if ( $s['logo_url'] ) : ?>
			<img src="<?php echo esc_url( $s['logo_url'] ); ?>" alt="<?php echo esc_attr( $s['company_name'] ); ?>" height="64">
		<?php else : ?>
			<span class="wordmark">Fox <em>&amp;</em> Fern</span>
		<?php endif; ?>
	</a>
	<div class="auth-card">
		<p class="eyebrow">Client Portal</p>
		<?php echo $content; // phpcs:ignore WordPress.Security.EscapeOutput -- escaped in templates. ?>
	</div>
	<p class="auth-foot">
		<?php if ( $s['company_phone'] ) : ?>
			Need help? Call <a href="tel:<?php echo esc_attr( preg_replace( '/[^0-9+]/', '', $s['company_phone'] ) ); ?>"><?php echo esc_html( $s['company_phone'] ); ?></a> &middot;
		<?php endif; ?>
		<a href="<?php echo esc_url( home_url( '/' ) ); ?>">Back to website</a>
	</p>
</main>
</body>
</html>
