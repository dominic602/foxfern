<?php
/**
 * Printable shell for the home record.
 *
 * @var string $page_title
 * @var string $content
 */

defined( 'ABSPATH' ) || exit;
?><!doctype html>
<html lang="en-US">
<head>
<meta charset="utf-8">
<meta name="viewport" content="width=device-width, initial-scale=1">
<meta name="robots" content="noindex, nofollow">
<title><?php echo esc_html( $page_title ); ?></title>
<link rel="stylesheet" href="<?php echo esc_url( FFP_URL . 'assets/portal.css?v=' . FFP_VERSION ); ?>">
<script src="<?php echo esc_url( FFP_URL . 'assets/portal.js?v=' . FFP_VERSION ); ?>" defer></script>
</head>
<body class="ffp ffp-print">
<main class="wrap record">
	<?php echo $content; // phpcs:ignore WordPress.Security.EscapeOutput -- escaped in templates. ?>
</main>
</body>
</html>
