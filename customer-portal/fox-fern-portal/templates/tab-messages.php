<?php
/**
 * Messages tab: comments, questions and change requests between the customer and the team.
 *
 * @var object $project
 * @var bool   $staff
 */

defined( 'ABSPATH' ) || exit;

$types    = ffp_comment_types();
$statuses = ffp_change_statuses();
$filter   = isset( $_GET['show'] ) && 'change_request' === $_GET['show'] ? 'change_request' : ''; // phpcs:ignore WordPress.Security.NonceVerification
$threads  = FFP_Data::threads( $project->id, $filter );
$files    = FFP_Data::files_by_owner( $project->id, 'message', false );
$preset   = isset( $_GET['type'] ) ? sanitize_key( $_GET['type'] ) : 'comment'; // phpcs:ignore WordPress.Security.NonceVerification
$preset   = array_key_exists( $preset, $types ) ? $preset : 'comment';

$render_attachments = function ( $comment_id ) use ( $files ) {
	if ( empty( $files[ $comment_id ] ) ) {
		return;
	}
	echo '<div class="attach">';
	foreach ( $files[ $comment_id ] as $file ) {
		if ( FFP_Files::is_image( $file ) ) {
			echo '<a class="lb-link thumb-sm" href="' . esc_url( ffp_file_url( $file->id ) ) . '" data-group="c-' . (int) $comment_id . '"><img src="' . esc_url( ffp_file_url( $file->id, 'thumb' ) ) . '" alt="' . esc_attr( $file->title ) . '" loading="lazy"></a>';
		} else {
			echo '<a class="file-chip" href="' . esc_url( ffp_file_url( $file->id ) ) . '" target="_blank" rel="noopener"><span class="ficon">' . esc_html( ffp_file_icon( $file->mime ) ) . '</span>' . esc_html( $file->title ) . '</a>';
		}
	}
	echo '</div>';
};

$author_line = function ( $c ) {
	$is_team = ffp_is_staff( (int) $c->user_id );
	$name    = ffp_user_name( $c->user_id );
	echo '<span class="avatar' . ( $is_team ? ' avatar-team' : '' ) . '" aria-hidden="true">' . esc_html( ffp_initials( $name ) ) . '</span>';
	echo '<span class="msg-who"><strong>' . esc_html( $name ) . '</strong>';
	echo $is_team ? ' <span class="tag tag-team">Fox &amp; Fern</span>' : ''; // phpcs:ignore WordPress.Security.EscapeOutput
	echo '<span class="small muted"> · ' . esc_html( ffp_ago( $c->created_at ) ) . '</span></span>';
};
?>

<div class="card compose" id="compose">
	<h2 class="card-title"><?php echo $staff ? 'Post a message to the customer' : 'Message your project team'; ?></h2>
	<?php echo ffp_form_open( 'add_comment', array( 'files' => true ) ); // phpcs:ignore WordPress.Security.EscapeOutput ?>
		<input type="hidden" name="project_id" value="<?php echo (int) $project->id; ?>">
		<div class="type-pick" role="radiogroup" aria-label="Message type">
			<?php foreach ( $types as $key => $label ) : ?>
				<label class="type-opt"><input type="radio" name="type" value="<?php echo esc_attr( $key ); ?>"<?php checked( $preset, $key ); ?>> <span><?php echo esc_html( $label ); ?></span></label>
			<?php endforeach; ?>
		</div>
		<p class="hint cr-hint">Describe what you'd like changed. We'll review it and reply here with any cost or schedule impact before any work changes.</p>
		<div class="field">
			<label for="msg-body" class="sr-only">Message</label>
			<textarea id="msg-body" name="body" rows="4" placeholder="<?php echo esc_attr( $staff ? 'Write to the customer…' : 'Write your comment, question or change request…' ); ?>"></textarea>
		</div>
		<div class="form-foot">
			<label class="file-btn"><input type="file" name="attachments[]" multiple accept="image/*,.pdf" data-file-list> <span>Attach photos or PDFs</span></label>
			<div class="file-list" aria-live="polite"></div>
			<button class="btn btn-gold" type="submit">Send</button>
		</div>
	</form>
</div>

<div class="toolbar chips" role="group" aria-label="Filter messages">
	<a class="chip" href="<?php echo esc_url( ffp_project_url( $project->id, 'messages' ) ); ?>"<?php echo '' === $filter ? ' aria-current="true"' : ''; ?>>All messages</a>
	<a class="chip" href="<?php echo esc_url( ffp_project_url( $project->id, 'messages', array( 'show' => 'change_request' ) ) ); ?>"<?php echo $filter ? ' aria-current="true"' : ''; ?>>Change requests</a>
</div>

<?php if ( ! $threads ) : ?>
	<div class="empty">
		<h2><?php echo $filter ? 'No change requests' : 'No messages yet'; ?></h2>
		<p>Questions, comments and change requests for this project will show up here, along with our replies.</p>
	</div>
<?php endif; ?>

<div class="threads">
	<?php foreach ( $threads as $t ) : ?>
		<article class="card thread type-<?php echo esc_attr( $t->type ); ?>" id="c-<?php echo (int) $t->id; ?>">
			<header class="msg-head">
				<?php $author_line( $t ); ?>
				<span class="msg-badges">
					<?php echo ffp_badge( $types[ $t->type ] ?? 'Comment', 'type-' . $t->type ); // phpcs:ignore WordPress.Security.EscapeOutput ?>
					<?php if ( 'change_request' === $t->type && $t->status ) : ?>
						<?php echo ffp_badge( $statuses[ $t->status ] ?? $t->status, 'cr-' . $t->status ); // phpcs:ignore WordPress.Security.EscapeOutput ?>
					<?php endif; ?>
				</span>
			</header>
			<div class="msg-body"><?php echo ffp_text( $t->body ); // phpcs:ignore WordPress.Security.EscapeOutput ?></div>
			<?php $render_attachments( (int) $t->id ); ?>

			<?php if ( 'change_request' === $t->type && $t->response ) : ?>
				<div class="response">
					<span class="small"><strong>Fox &amp; Fern response</strong></span>
					<div><?php echo ffp_text( $t->response ); // phpcs:ignore WordPress.Security.EscapeOutput ?></div>
				</div>
			<?php endif; ?>

			<?php if ( $t->replies ) : ?>
				<ol class="replies">
					<?php foreach ( $t->replies as $r ) : ?>
						<li class="reply" id="c-<?php echo (int) $r->id; ?>">
							<div class="msg-head"><?php $author_line( $r ); ?></div>
							<div class="msg-body"><?php echo ffp_text( $r->body ); // phpcs:ignore WordPress.Security.EscapeOutput ?></div>
							<?php $render_attachments( (int) $r->id ); ?>
						</li>
					<?php endforeach; ?>
				</ol>
			<?php endif; ?>

			<div class="row-actions">
				<details class="inline-edit">
					<summary class="btn btn-sm btn-line">Reply</summary>
					<?php echo ffp_form_open( 'add_comment', array( 'files' => true ) ); // phpcs:ignore WordPress.Security.EscapeOutput ?>
						<input type="hidden" name="project_id" value="<?php echo (int) $project->id; ?>">
						<input type="hidden" name="parent_id" value="<?php echo (int) $t->id; ?>">
						<div class="field"><label class="sr-only" for="r-<?php echo (int) $t->id; ?>">Reply</label><textarea id="r-<?php echo (int) $t->id; ?>" name="body" rows="3" placeholder="Write a reply…"></textarea></div>
						<div class="form-foot">
							<label class="file-btn"><input type="file" name="attachments[]" multiple accept="image/*,.pdf" data-file-list> <span>Attach</span></label>
							<div class="file-list" aria-live="polite"></div>
							<button class="btn btn-sm btn-gold" type="submit">Send reply</button>
						</div>
					</form>
				</details>

				<?php if ( $staff && 'change_request' === $t->type ) : ?>
					<details class="inline-edit">
						<summary class="btn btn-sm btn-line">Update request status</summary>
						<?php echo ffp_form_open( 'change_status' ); // phpcs:ignore WordPress.Security.EscapeOutput ?>
							<input type="hidden" name="project_id" value="<?php echo (int) $project->id; ?>">
							<input type="hidden" name="comment_id" value="<?php echo (int) $t->id; ?>">
							<div class="form-grid">
								<div class="field"><label for="cs-<?php echo (int) $t->id; ?>">Status</label>
									<select id="cs-<?php echo (int) $t->id; ?>" name="status">
										<?php foreach ( $statuses as $key => $label ) : ?>
											<option value="<?php echo esc_attr( $key ); ?>"<?php selected( $t->status, $key ); ?>><?php echo esc_html( $label ); ?></option>
										<?php endforeach; ?>
									</select>
								</div>
								<div class="field span-2"><label for="cr-<?php echo (int) $t->id; ?>">Response to customer <span class="small muted">(cost, schedule impact, next steps)</span></label>
									<textarea id="cr-<?php echo (int) $t->id; ?>" name="response" rows="3"><?php echo esc_textarea( (string) $t->response ); ?></textarea>
								</div>
							</div>
							<div class="form-foot">
								<label class="check"><input type="checkbox" name="notify" value="1" checked> Email the customer</label>
								<button class="btn btn-sm btn-gold" type="submit">Save</button>
							</div>
						</form>
					</details>
				<?php endif; ?>

				<?php if ( $staff ) : ?>
					<?php echo ffp_form_open( 'delete_comment', array( 'class' => 'inline', 'confirm' => 'Delete this message and its replies?' ) ); // phpcs:ignore WordPress.Security.EscapeOutput ?>
						<input type="hidden" name="project_id" value="<?php echo (int) $project->id; ?>">
						<input type="hidden" name="comment_id" value="<?php echo (int) $t->id; ?>">
						<button class="btn-text danger" type="submit">Delete</button>
					</form>
				<?php endif; ?>
			</div>
		</article>
	<?php endforeach; ?>
</div>
