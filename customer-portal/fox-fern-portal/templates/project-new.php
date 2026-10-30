<?php
/**
 * New project form (staff).
 */

defined( 'ABSPATH' ) || exit;
?>
<p class="crumb"><a href="<?php echo esc_url( ffp_url() ); ?>">&larr; All projects</a></p>
<div class="page-head"><h1>New project</h1></div>
<div class="card narrow">
	<?php echo ffp_form_open( 'save_project' ); // phpcs:ignore WordPress.Security.EscapeOutput ?>
		<input type="hidden" name="project_id" value="0">
		<?php ffp_template( 'partials/project-fields', array( 'project' => null ) ); ?>
		<div class="form-foot"><button class="btn btn-gold" type="submit">Create project</button></div>
	</form>
</div>
