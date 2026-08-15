<?php
/**
 * Site-wide Text Replace admin view.
 *
 * @var string             $page_title Page title.
 * @var array<int, array>  $rules      Live rules.
 * @var string             $notice     Notice key.
 * @var string             $message    Message.
 * @var array<string, int> $counts     Last DB counts.
 *
 * @package SEO_Campaign_Hub\Admin\Views
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

$rules   = ( isset( $rules ) && is_array( $rules ) ) ? $rules : [];
$notice  = isset( $notice ) ? (string) $notice : '';
$message = isset( $message ) ? (string) $message : '';
$counts  = ( isset( $counts ) && is_array( $counts ) ) ? $counts : [];

$notices = [
	'saved'   => [ 'success', $message ?: __( 'Saved.', 'seo-campaign-hub' ) ],
	'error'   => [ 'error', $message ?: __( 'Could not save.', 'seo-campaign-hub' ) ],
	'dry_ok'  => [ 'success', $message ?: __( 'Dry run complete.', 'seo-campaign-hub' ) ],
	'db_ok'   => [ 'success', $message ?: __( 'Database replace complete.', 'seo-campaign-hub' ) ],
	'deleted' => [ 'success', __( 'Rule removed.', 'seo-campaign-hub' ) ],
];
?>
<div class="wrap">
	<h1><?php echo esc_html( $page_title ?? __( 'Text Replace', 'seo-campaign-hub' ) ); ?></h1>

	<?php if ( $notice && isset( $notices[ $notice ] ) ) : ?>
		<div class="seo-campaign-hub-notice <?php echo esc_attr( $notices[ $notice ][0] ); ?>">
			<?php echo esc_html( $notices[ $notice ][1] ); ?>
		</div>
	<?php endif; ?>

	<p class="description">
		<?php esc_html_e( 'Find and replace any text across the public site. Live rules apply immediately (change anytime). Database replace permanently updates stored content — run Dry run first. Use unique phrases, not short common words.', 'seo-campaign-hub' ); ?>
	</p>

	<div class="seo-campaign-hub-admin">
		<div class="seo-campaign-hub-content">

			<section class="sch-ie-panel" style="margin-bottom:24px">
				<h2><?php esc_html_e( 'Live rules (anytime)', 'seo-campaign-hub' ); ?></h2>
				<p><?php esc_html_e( 'These swap visible text in the live HTML (between tags). Attributes, tag names, and the database are not changed. Turn off or delete a rule whenever you want.', 'seo-campaign-hub' ); ?></p>

				<form method="post" action="<?php echo esc_url( admin_url( 'admin-post.php' ) ); ?>">
					<input type="hidden" name="action" value="sch_text_replace_rule_add">
					<?php wp_nonce_field( 'sch_text_replace_rule_add' ); ?>
					<table class="form-table" role="presentation">
						<tr>
							<th scope="row"><label for="sch-live-from"><?php esc_html_e( 'Find text', 'seo-campaign-hub' ); ?></label></th>
							<td>
								<input type="text" class="large-text" id="sch-live-from" name="find_text" placeholder="<?php echo esc_attr__( 'Buy now', 'seo-campaign-hub' ); ?>" required minlength="3" />
							</td>
						</tr>
						<tr>
							<th scope="row"><label for="sch-live-to"><?php esc_html_e( 'Replace with', 'seo-campaign-hub' ); ?></label></th>
							<td>
								<input type="text" class="large-text" id="sch-live-to" name="replace_text" placeholder="<?php echo esc_attr__( 'Shop now', 'seo-campaign-hub' ); ?>" />
								<p class="description"><?php esc_html_e( 'Leave empty to remove the find text from the public page.', 'seo-campaign-hub' ); ?></p>
							</td>
						</tr>
					</table>
					<?php submit_button( __( 'Add live rule', 'seo-campaign-hub' ), 'primary', 'submit', false ); ?>
				</form>

				<?php if ( $rules !== [] ) : ?>
					<table class="widefat striped" style="margin-top:16px">
						<thead>
							<tr>
								<th><?php esc_html_e( 'Find', 'seo-campaign-hub' ); ?></th>
								<th><?php esc_html_e( 'Replace', 'seo-campaign-hub' ); ?></th>
								<th><?php esc_html_e( 'Status', 'seo-campaign-hub' ); ?></th>
								<th><?php esc_html_e( 'Actions', 'seo-campaign-hub' ); ?></th>
							</tr>
						</thead>
						<tbody>
							<?php foreach ( $rules as $rule ) : ?>
								<tr>
									<td><code><?php echo esc_html( $rule['from'] ); ?></code></td>
									<td><code><?php echo esc_html( $rule['to'] ); ?></code></td>
									<td>
										<?php echo $rule['enabled'] === '1' ? esc_html__( 'On', 'seo-campaign-hub' ) : esc_html__( 'Off', 'seo-campaign-hub' ); ?>
									</td>
									<td>
										<a href="<?php echo esc_url( wp_nonce_url( admin_url( 'admin-post.php?action=sch_text_replace_rule_toggle&rule_id=' . rawurlencode( $rule['id'] ) ), 'sch_text_replace_rule_toggle' ) ); ?>">
											<?php echo $rule['enabled'] === '1' ? esc_html__( 'Turn off', 'seo-campaign-hub' ) : esc_html__( 'Turn on', 'seo-campaign-hub' ); ?>
										</a>
										|
										<a href="<?php echo esc_url( wp_nonce_url( admin_url( 'admin-post.php?action=sch_text_replace_rule_delete&rule_id=' . rawurlencode( $rule['id'] ) ), 'sch_text_replace_rule_delete' ) ); ?>" onclick="return confirm('<?php echo esc_js( __( 'Delete this rule?', 'seo-campaign-hub' ) ); ?>');">
											<?php esc_html_e( 'Delete', 'seo-campaign-hub' ); ?>
										</a>
									</td>
								</tr>
							<?php endforeach; ?>
						</tbody>
					</table>
				<?php endif; ?>
			</section>

			<section class="sch-ie-panel">
				<h2><?php esc_html_e( 'Database replace (permanent)', 'seo-campaign-hub' ); ?></h2>
				<p><?php esc_html_e( 'Rewrites text stored in post titles/content, Elementor/page meta, options, comments, and short-link targeting. Always click Dry run first. Backup (Cloud Backup or Import/Export) before Apply.', 'seo-campaign-hub' ); ?></p>

				<form method="post" action="<?php echo esc_url( admin_url( 'admin-post.php' ) ); ?>">
					<input type="hidden" name="action" value="sch_text_replace_db">
					<?php wp_nonce_field( 'sch_text_replace_db' ); ?>
					<table class="form-table" role="presentation">
						<tr>
							<th scope="row"><label for="sch-db-from"><?php esc_html_e( 'Find text', 'seo-campaign-hub' ); ?></label></th>
							<td><input type="text" class="large-text" id="sch-db-from" name="find_text" required minlength="3" /></td>
						</tr>
						<tr>
							<th scope="row"><label for="sch-db-to"><?php esc_html_e( 'Replace with', 'seo-campaign-hub' ); ?></label></th>
							<td>
								<input type="text" class="large-text" id="sch-db-to" name="replace_text" />
								<p class="description"><?php esc_html_e( 'Leave empty to delete the find text from stored content.', 'seo-campaign-hub' ); ?></p>
							</td>
						</tr>
						<tr>
							<th scope="row"><?php esc_html_e( 'Confirm apply', 'seo-campaign-hub' ); ?></th>
							<td>
								<label>
									<input type="checkbox" name="sch_confirm" value="1" />
									<?php esc_html_e( 'I understand Apply cannot be easily undone (required for Apply only).', 'seo-campaign-hub' ); ?>
								</label>
							</td>
						</tr>
					</table>
					<?php submit_button( __( 'Dry run (count only)', 'seo-campaign-hub' ), 'secondary', 'sch_dry_run', false ); ?>
					<?php submit_button( __( 'Apply to database', 'seo-campaign-hub' ), 'delete', 'sch_apply', false ); ?>
				</form>

				<?php if ( $counts !== [] ) : ?>
					<h3><?php esc_html_e( 'Last run breakdown', 'seo-campaign-hub' ); ?></h3>
					<ul>
						<li><?php printf( esc_html__( 'Posts: %d', 'seo-campaign-hub' ), (int) ( $counts['posts'] ?? 0 ) ); ?></li>
						<li><?php printf( esc_html__( 'Post meta: %d', 'seo-campaign-hub' ), (int) ( $counts['postmeta'] ?? 0 ) ); ?></li>
						<li><?php printf( esc_html__( 'Options: %d', 'seo-campaign-hub' ), (int) ( $counts['options'] ?? 0 ) ); ?></li>
						<li><?php printf( esc_html__( 'Comments: %d', 'seo-campaign-hub' ), (int) ( $counts['comments'] ?? 0 ) ); ?></li>
						<li><?php printf( esc_html__( 'Short links: %d', 'seo-campaign-hub' ), (int) ( $counts['sch_links'] ?? 0 ) ); ?></li>
					</ul>
				<?php endif; ?>
			</section>

		</div>
	</div>
</div>
