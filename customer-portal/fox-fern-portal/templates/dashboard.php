<?php
/**
 * Project list.
 *
 * @var object[] $projects
 * @var object[] $past      Customer's archived projects.
 * @var bool     $archived  Staff viewing archived projects.
 * @var bool     $staff
 */

defined( 'ABSPATH' ) || exit;

$s    = ffp_settings();
$user = wp_get_current_user();

$render_cards = function ( $list ) use ( $staff ) {
	echo '<div class="cards">';
	foreach ( $list as $p ) {
		$progress = FFP_Data::progress( $p->id, $staff );
		$cover    = $p->cover_file_id ? FFP_Data::file( $p->cover_file_id ) : null;
		if ( ! $cover || ( ! $staff && ! $cover->visible ) ) {
			$photos = FFP_Data::photos( $p->id, $staff );
			$cover  = $photos[0] ?? null;
		}
		$members = $staff ? FFP_Data::members( $p->id ) : array();
		?>
		<a class="pcard" href="<?php echo esc_url( ffp_project_url( $p->id ) ); ?>">
			<span class="pcard-img">
				<?php if ( $cover ) : ?>
					<img src="<?php echo esc_url( ffp_file_url( $cover->id, 'thumb' ) ); ?>" alt="" loading="lazy">
				<?php else : ?>
					<span class="pcard-ph" aria-hidden="true">F&amp;F</span>
				<?php endif; ?>
			</span>
			<span class="pcard-body">
				<?php echo ffp_status_badge( $p->status ); // phpcs:ignore WordPress.Security.EscapeOutput ?>
				<strong class="pcard-title"><?php echo esc_html( $p->title ); ?></strong>
				<?php if ( $p->address ) : ?>
					<span class="muted"><?php echo esc_html( $p->address ); ?></span>
				<?php endif; ?>
				<?php if ( null !== $progress ) : ?>
					<span class="meter" aria-label="<?php echo esc_attr( $progress . '% complete' ); ?>"><span style="width:<?php echo (int) $progress; ?>%"></span></span>
					<span class="small"><?php echo (int) $progress; ?>% complete</span>
				<?php endif; ?>
				<?php if ( $staff ) : ?>
					<span class="small muted"><?php echo $members ? esc_html( implode( ', ', wp_list_pluck( $members, 'display_name' ) ) ) : 'No customer added yet'; ?></span>
				<?php endif; ?>
				<span class="small muted">Updated <?php echo esc_html( ffp_ago( $p->updated_at ) ); ?></span>
			</span>
		</a>
		<?php
	}
	echo '</div>';
};
?>

<div class="page-head">
	<div>
		<?php if ( $staff ) : ?>
			<h1><?php echo $archived ? 'Archived projects' : 'Projects'; ?></h1>
		<?php else : ?>
			<h1>Welcome, <?php echo esc_html( $user->first_name ? $user->first_name : $user->display_name ); ?></h1>
		<?php endif; ?>
	</div>
	<?php if ( $staff ) : ?>
		<div class="actions">
			<?php if ( $archived ) : ?>
				<a class="btn btn-line" href="<?php echo esc_url( ffp_url() ); ?>">Active projects</a>
			<?php else : ?>
				<a class="btn btn-line" href="<?php echo esc_url( ffp_url( '', array( 'archived' => 1 ) ) ); ?>">Archived</a>
			<?php endif; ?>
			<a class="btn btn-gold" href="<?php echo esc_url( ffp_url( 'new-project' ) ); ?>">+ New project</a>
		</div>
	<?php endif; ?>
</div>

<?php if ( ! $staff && $s['welcome_message'] ) : ?>
	<p class="lede"><?php echo esc_html( $s['welcome_message'] ); ?></p>
<?php endif; ?>

<?php if ( $projects ) : ?>
	<?php $render_cards( $projects ); ?>
<?php elseif ( $staff ) : ?>
	<div class="empty">
		<h2><?php echo $archived ? 'No archived projects' : 'No projects yet'; ?></h2>
		<?php if ( ! $archived ) : ?>
			<p>Create a project, add your customer, and start posting daily updates.</p>
			<p><a class="btn btn-gold" href="<?php echo esc_url( ffp_url( 'new-project' ) ); ?>">Create your first project</a></p>
		<?php endif; ?>
	</div>
<?php elseif ( ! $past ) : ?>
	<div class="empty">
		<h2>Your project is being set up</h2>
		<p>It will appear here as soon as your project manager adds it.<?php echo $s['company_phone'] ? ' Questions? Call ' . esc_html( $s['company_phone'] ) . '.' : ''; ?></p>
	</div>
<?php endif; ?>

<?php if ( $past ) : ?>
	<h2 class="section-title">Past projects</h2>
	<?php $render_cards( $past ); ?>
<?php endif; ?>
