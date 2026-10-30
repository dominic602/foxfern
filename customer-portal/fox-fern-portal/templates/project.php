<?php
/**
 * Project page: header, tabs and the active tab.
 *
 * @var object   $project
 * @var string   $tab
 * @var bool     $staff
 * @var int[]    $badges
 * @var int|null $progress
 */

defined( 'ABSPATH' ) || exit;

$tabs = array(
	'overview'  => 'Overview',
	'updates'   => 'Daily Updates',
	'photos'    => 'Photos',
	'specs'     => 'Selections & Specs',
	'documents' => 'Documents',
	'messages'  => 'Messages',
);
if ( $staff ) {
	$tabs['manage'] = 'Manage';
}
$customer_projects = $staff ? array() : FFP_Data::projects_for( get_current_user_id() );
?>

<?php if ( $staff || count( $customer_projects ) > 1 ) : ?>
	<p class="crumb"><a href="<?php echo esc_url( ffp_url( '', $staff ? array() : array( 'all' => 1 ) ) ); ?>">&larr; All projects</a></p>
<?php endif; ?>

<div class="phead">
	<div class="phead-main">
		<div class="phead-badges">
			<?php echo ffp_status_badge( $project->status ); // phpcs:ignore WordPress.Security.EscapeOutput ?>
			<?php if ( $project->archived ) : ?>
				<?php echo ffp_badge( 'Archived', 'st-archived' ); // phpcs:ignore WordPress.Security.EscapeOutput ?>
			<?php endif; ?>
		</div>
		<h1><?php echo esc_html( $project->title ); ?></h1>
		<?php if ( $project->address ) : ?>
			<p class="muted">
				<a class="quiet" href="<?php echo esc_url( 'https://www.google.com/maps/search/?api=1&query=' . rawurlencode( $project->address ) ); ?>" target="_blank" rel="noopener"><?php echo esc_html( $project->address ); ?></a>
			</p>
		<?php endif; ?>
	</div>
	<?php if ( null !== $progress ) : ?>
		<div class="phead-progress">
			<span class="big-pct"><?php echo (int) $progress; ?><small>%</small></span>
			<span class="meter meter-lg" aria-label="<?php echo esc_attr( $progress . '% complete' ); ?>"><span style="width:<?php echo (int) $progress; ?>%"></span></span>
			<span class="small muted">complete, as of the latest update</span>
		</div>
	<?php endif; ?>
</div>

<nav class="tabs" aria-label="Project sections">
	<?php foreach ( $tabs as $key => $label ) : ?>
		<a href="<?php echo esc_url( ffp_project_url( $project->id, $key ) ); ?>"<?php echo $key === $tab ? ' aria-current="page"' : ''; ?>>
			<?php echo esc_html( $label ); ?>
			<?php if ( ! empty( $badges[ $key ] ) ) : ?>
				<span class="dot" title="<?php echo esc_attr( $badges[ $key ] . ' new since your last visit' ); ?>"><?php echo (int) $badges[ $key ]; ?></span>
			<?php endif; ?>
		</a>
	<?php endforeach; ?>
</nav>

<section class="tab-body">
	<?php
	ffp_template(
		'tab-' . $tab,
		array(
			'project'  => $project,
			'staff'    => $staff,
			'progress' => $progress,
		)
	);
	?>
</section>
