<?php
/**
 * Overview tab: latest update, schedule, key details, open change requests, warranties.
 *
 * @var object   $project
 * @var bool     $staff
 * @var int|null $progress
 */

defined( 'ABSPATH' ) || exit;

$latest     = FFP_Data::logs( $project->id, $staff, 1 );
$latest     = $latest[0] ?? null;
$milestones = FFP_Data::milestones( $project->id );
$open_crs   = FFP_Data::open_change_requests( $project->id );
$warranties = array_filter( FFP_Data::files( $project->id, 'document', null, ! $staff ), fn( $f ) => 'warranty' === $f->category );
$manager    = $project->manager_id ? get_userdata( (int) $project->manager_id ) : null;
$statuses   = ffp_milestone_statuses();
$cr_labels  = ffp_change_statuses();
$s          = ffp_settings();
?>
<div class="cols">
	<div class="col-main">

		<?php if ( $project->description ) : ?>
			<div class="card">
				<h2 class="card-title">Project scope</h2>
				<div class="prose"><?php echo ffp_text( $project->description ); // phpcs:ignore WordPress.Security.EscapeOutput ?></div>
			</div>
		<?php endif; ?>

		<div class="card">
			<div class="card-head">
				<h2 class="card-title">Latest update</h2>
				<a class="link" href="<?php echo esc_url( ffp_project_url( $project->id, 'updates' ) ); ?>">All updates &rarr;</a>
			</div>
			<?php if ( $latest ) : ?>
				<p class="log-date"><?php echo esc_html( ffp_date( $latest->log_date, 'l, F j, Y' ) ); ?>
					<?php if ( ! $latest->visible ) : ?>
						<?php echo ffp_badge( 'Draft', 'st-draft' ); // phpcs:ignore WordPress.Security.EscapeOutput ?>
					<?php endif; ?>
				</p>
				<dl class="kv">
					<?php
					$shown = 0;
					foreach ( ffp_log_fields( $staff ) as $field ) {
						if ( $shown >= 4 || 'percent' === $field['type'] ) {
							continue;
						}
						$html = ffp_field_display( $field, $latest->values[ $field['key'] ] ?? '' );
						if ( '' === $html ) {
							continue;
						}
						++$shown;
						echo '<dt>' . esc_html( $field['label'] ) . '</dt><dd>' . $html . '</dd>'; // phpcs:ignore WordPress.Security.EscapeOutput
					}
					?>
				</dl>
				<?php $photos = FFP_Data::files( $project->id, 'log', $latest->id, ! $staff ); ?>
				<?php if ( $photos ) : ?>
					<div class="strip">
						<?php foreach ( array_slice( array_reverse( $photos ), 0, 6 ) as $photo ) : ?>
							<a class="lb-link" href="<?php echo esc_url( ffp_file_url( $photo->id ) ); ?>" data-group="latest" data-caption="<?php echo esc_attr( $photo->caption ); ?>">
								<img src="<?php echo esc_url( ffp_file_url( $photo->id, 'thumb' ) ); ?>" alt="<?php echo esc_attr( $photo->caption ? $photo->caption : 'Progress photo' ); ?>" loading="lazy">
							</a>
						<?php endforeach; ?>
					</div>
				<?php endif; ?>
			<?php else : ?>
				<p class="muted">No daily updates yet. <?php echo $staff ? 'Post the first one from the Daily Updates tab.' : "You'll see one here after each day on site."; ?></p>
			<?php endif; ?>
		</div>

		<div class="card" id="schedule">
			<h2 class="card-title">Schedule</h2>
			<?php if ( $milestones ) : ?>
				<ol class="timeline">
					<?php foreach ( $milestones as $m ) : ?>
						<li class="tl-<?php echo esc_attr( $m->status ); ?>">
							<span class="tl-mark" aria-hidden="true"></span>
							<div class="tl-body">
								<strong><?php echo esc_html( $m->title ); ?></strong>
								<span class="small muted">
									<?php echo esc_html( $statuses[ $m->status ] ?? '' ); ?>
									<?php if ( $m->target_date ) : ?>
										&middot; <?php echo esc_html( ( 'done' === $m->status ? '' : 'Target ' ) . ffp_date( $m->target_date ) ); ?>
									<?php elseif ( 'done' === $m->status && $m->completed_at ) : ?>
										&middot; <?php echo esc_html( ffp_date( $m->completed_at ) ); ?>
									<?php endif; ?>
								</span>
								<?php if ( $staff ) : ?>
									<div class="row-actions">
										<?php foreach ( $statuses as $key => $label ) : ?>
											<?php if ( $key !== $m->status ) : ?>
												<?php echo ffp_form_open( 'milestone_status', array( 'class' => 'inline' ) ); // phpcs:ignore WordPress.Security.EscapeOutput ?>
													<input type="hidden" name="project_id" value="<?php echo (int) $project->id; ?>">
													<input type="hidden" name="milestone_id" value="<?php echo (int) $m->id; ?>">
													<input type="hidden" name="status" value="<?php echo esc_attr( $key ); ?>">
													<button class="btn-text" type="submit">Mark <?php echo esc_html( strtolower( $label ) ); ?></button>
												</form>
											<?php endif; ?>
										<?php endforeach; ?>
										<details class="inline-edit">
											<summary class="btn-text">Edit</summary>
											<?php echo ffp_form_open( 'save_milestone', array( 'class' => 'form-grid' ) ); // phpcs:ignore WordPress.Security.EscapeOutput ?>
												<input type="hidden" name="project_id" value="<?php echo (int) $project->id; ?>">
												<input type="hidden" name="milestone_id" value="<?php echo (int) $m->id; ?>">
												<div class="field span-2"><label>Step</label><input type="text" name="title" value="<?php echo esc_attr( $m->title ); ?>" required></div>
												<div class="field"><label>Target date</label><input type="date" name="target_date" value="<?php echo esc_attr( (string) $m->target_date ); ?>"></div>
												<div class="field"><label>Status</label><select name="status">
													<?php foreach ( $statuses as $key => $label ) : ?>
														<option value="<?php echo esc_attr( $key ); ?>"<?php selected( $m->status, $key ); ?>><?php echo esc_html( $label ); ?></option>
													<?php endforeach; ?>
												</select></div>
												<div class="form-actions span-2"><button class="btn btn-sm btn-gold" type="submit">Save</button></div>
											</form>
											<?php echo ffp_form_open( 'delete_milestone', array( 'confirm' => 'Remove this schedule step?' ) ); // phpcs:ignore WordPress.Security.EscapeOutput ?>
												<input type="hidden" name="project_id" value="<?php echo (int) $project->id; ?>">
												<input type="hidden" name="milestone_id" value="<?php echo (int) $m->id; ?>">
												<button class="btn-text danger" type="submit">Remove step</button>
											</form>
										</details>
									</div>
								<?php endif; ?>
							</div>
						</li>
					<?php endforeach; ?>
				</ol>
			<?php else : ?>
				<p class="muted"><?php echo $staff ? 'Add the main phases (demo, rough-in, inspections, finishes, final walkthrough) so the customer can see where things stand.' : 'Your project schedule will appear here.'; ?></p>
			<?php endif; ?>

			<?php if ( $staff ) : ?>
				<details class="add-box">
					<summary class="btn btn-sm btn-line">+ Add schedule step</summary>
					<?php echo ffp_form_open( 'save_milestone', array( 'class' => 'form-grid' ) ); // phpcs:ignore WordPress.Security.EscapeOutput ?>
						<input type="hidden" name="project_id" value="<?php echo (int) $project->id; ?>">
						<div class="field span-2"><label for="ms-title">Step</label><input id="ms-title" type="text" name="title" placeholder="e.g. Rough plumbing inspection" required></div>
						<div class="field"><label for="ms-date">Target date</label><input id="ms-date" type="date" name="target_date"></div>
						<div class="field"><label for="ms-status">Status</label><select id="ms-status" name="status">
							<?php foreach ( $statuses as $key => $label ) : ?>
								<option value="<?php echo esc_attr( $key ); ?>"><?php echo esc_html( $label ); ?></option>
							<?php endforeach; ?>
						</select></div>
						<div class="form-actions span-2"><button class="btn btn-sm btn-gold" type="submit">Add step</button></div>
					</form>
				</details>
			<?php endif; ?>
		</div>
	</div>

	<aside class="col-side">
		<div class="card">
			<h2 class="card-title">Details</h2>
			<dl class="kv kv-tight">
				<dt>Status</dt><dd><?php echo ffp_status_badge( $project->status ); // phpcs:ignore WordPress.Security.EscapeOutput ?></dd>
				<?php if ( $project->start_date ) : ?>
					<dt>Start</dt><dd><?php echo esc_html( ffp_date( $project->start_date ) ); ?></dd>
				<?php endif; ?>
				<?php if ( $project->target_date ) : ?>
					<dt>Target completion</dt><dd><?php echo esc_html( ffp_date( $project->target_date ) ); ?></dd>
				<?php endif; ?>
				<?php if ( null !== $progress ) : ?>
					<dt>Progress</dt><dd><?php echo (int) $progress; ?>%</dd>
				<?php endif; ?>
			</dl>
			<?php if ( $manager ) : ?>
				<div class="pm">
					<span class="avatar avatar-lg" aria-hidden="true"><?php echo esc_html( ffp_initials( $manager->display_name ) ); ?></span>
					<div>
						<span class="small muted">Your project manager</span>
						<strong><?php echo esc_html( $manager->display_name ); ?></strong>
						<?php $pm_phone = get_user_meta( $manager->ID, 'ffp_phone', true ); ?>
						<?php $pm_phone = $pm_phone ? $pm_phone : $s['company_phone']; ?>
						<?php if ( $pm_phone ) : ?>
							<a href="tel:<?php echo esc_attr( preg_replace( '/[^0-9+]/', '', $pm_phone ) ); ?>"><?php echo esc_html( $pm_phone ); ?></a>
						<?php endif; ?>
						<a href="mailto:<?php echo esc_attr( $manager->user_email ); ?>"><?php echo esc_html( $manager->user_email ); ?></a>
					</div>
				</div>
			<?php endif; ?>
		</div>

		<div class="card">
			<h2 class="card-title">Questions or changes?</h2>
			<div class="stack-sm">
				<a class="btn btn-gold btn-block" href="<?php echo esc_url( ffp_project_url( $project->id, 'messages', array( 'type' => 'comment' ) ) ); ?>#compose">Send a message</a>
				<a class="btn btn-line btn-block" href="<?php echo esc_url( ffp_project_url( $project->id, 'messages', array( 'type' => 'change_request' ) ) ); ?>#compose">Request a change</a>
			</div>
			<?php if ( $open_crs ) : ?>
				<h3 class="mini-title">Open change requests</h3>
				<ul class="plain">
					<?php foreach ( $open_crs as $cr ) : ?>
						<li>
							<a href="<?php echo esc_url( ffp_project_url( $project->id, 'messages' ) . '#c-' . $cr->id ); ?>"><?php echo esc_html( wp_trim_words( $cr->body, 10, '…' ) ); ?></a>
							<?php echo ffp_badge( $cr_labels[ $cr->status ] ?? $cr->status, 'cr-' . $cr->status ); // phpcs:ignore WordPress.Security.EscapeOutput ?>
						</li>
					<?php endforeach; ?>
				</ul>
			<?php endif; ?>
		</div>

		<div class="card">
			<div class="card-head">
				<h2 class="card-title">Warranties</h2>
				<a class="link" href="<?php echo esc_url( ffp_project_url( $project->id, 'documents' ) ); ?>">Documents &rarr;</a>
			</div>
			<?php if ( $warranties ) : ?>
				<ul class="plain">
					<?php foreach ( $warranties as $w ) : ?>
						<?php $meta = FFP_Data::file_meta( $w ); ?>
						<li>
							<a href="<?php echo esc_url( ffp_file_url( $w->id ) ); ?>" target="_blank" rel="noopener"><?php echo esc_html( $w->title ); ?></a>
							<?php if ( ! empty( $meta['expires'] ) ) : ?>
								<span class="small muted">Covered through <?php echo esc_html( ffp_date( $meta['expires'] ) ); ?></span>
							<?php endif; ?>
						</li>
					<?php endforeach; ?>
				</ul>
			<?php else : ?>
				<p class="muted small">Warranty certificates will be posted here when your project wraps up.</p>
			<?php endif; ?>
			<p><a class="link" href="<?php echo esc_url( ffp_project_url( $project->id, 'record' ) ); ?>" target="_blank" rel="noopener">Print your home record (specs + warranties)</a></p>
		</div>
	</aside>
</div>
