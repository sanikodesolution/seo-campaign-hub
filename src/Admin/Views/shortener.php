<?php
/**
 * URL Shortener View
 *
 * Receives from AdminInit::render_shortener():
 *
 * @var string $page_title Page title.
 * @var array  $links      Short link rows (objects) from ShortenerService.
 * @var string $prefix     Short URL prefix (e.g. "go").
 *
 * @package SEO_Campaign_Hub\Admin\Views
 */

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

$links  = isset( $links ) && is_array( $links ) ? $links : [];
$prefix = isset( $prefix ) ? (string) $prefix : 'go';

// phpcs:disable WordPress.Security.NonceVerification.Recommended -- read-only notice display.
$notice     = isset( $_GET['sch_notice'] ) ? sanitize_key( $_GET['sch_notice'] ) : '';
$notice_url = isset( $_GET['sch_url'] ) ? esc_url_raw( rawurldecode( wp_unslash( $_GET['sch_url'] ) ) ) : '';
// phpcs:enable

$notices = [
    'created'       => [ 'success', __( 'Short link created successfully.', 'seo-campaign-hub' ) ],
    'deleted'       => [ 'success', __( 'Short link deleted.', 'seo-campaign-hub' ) ],
    'updated'       => [ 'success', __( 'Short link updated.', 'seo-campaign-hub' ) ],
    'missing_url'   => [ 'error', __( 'Please enter a destination URL.', 'seo-campaign-hub' ) ],
    'slug_taken'    => [ 'error', __( 'That slug is already in use. Please choose another one.', 'seo-campaign-hub' ) ],
    'create_failed' => [ 'error', __( 'Could not create the short link. Check that the destination URL is valid and the shortener is enabled in Settings.', 'seo-campaign-hub' ) ],
    'delete_failed' => [ 'error', __( 'Could not delete the short link.', 'seo-campaign-hub' ) ],
    'not_found'     => [ 'error', __( 'Short link not found.', 'seo-campaign-hub' ) ],
];
?>
<div class="wrap">
    <h1><?php echo esc_html( $page_title ?? __( 'URL Shortener', 'seo-campaign-hub' ) ); ?></h1>

    <?php if ( $notice && isset( $notices[ $notice ] ) ) : ?>
        <div class="seo-campaign-hub-notice <?php echo esc_attr( $notices[ $notice ][0] ); ?>">
            <?php echo esc_html( $notices[ $notice ][1] ); ?>
            <?php if ( 'created' === $notice && $notice_url ) : ?>
                <strong>
                    <a href="<?php echo esc_url( $notice_url ); ?>" target="_blank" rel="noopener">
                        <?php echo esc_html( $notice_url ); ?>
                    </a>
                </strong>
                <button type="button" class="button button-small sch-copy" data-url="<?php echo esc_attr( $notice_url ); ?>">
                    <?php esc_html_e( 'Copy', 'seo-campaign-hub' ); ?>
                </button>
            <?php endif; ?>
        </div>
    <?php endif; ?>

    <div class="seo-campaign-hub-admin">
        <div class="seo-campaign-hub-content">

            <h2><?php esc_html_e( 'Create a short link', 'seo-campaign-hub' ); ?></h2>

            <form method="post" action="<?php echo esc_url( admin_url( 'admin-post.php' ) ); ?>">
                <input type="hidden" name="action" value="sch_shortener_create">
                <?php wp_nonce_field( 'sch_shortener_create' ); ?>

                <table class="form-table" role="presentation">
                    <tr>
                        <th scope="row">
                            <label for="sch-destination-url"><?php esc_html_e( 'Destination URL', 'seo-campaign-hub' ); ?> <span class="description">(<?php esc_html_e( 'required', 'seo-campaign-hub' ); ?>)</span></label>
                        </th>
                        <td>
                            <input type="url" id="sch-destination-url" name="destination_url" class="regular-text" required
                                   placeholder="https://example.com/my-landing-page">
                            <p class="description"><?php esc_html_e( 'The page visitors will be redirected to (campaign page, offer, or external affiliate link).', 'seo-campaign-hub' ); ?></p>
                        </td>
                    </tr>
                    <tr>
                        <th scope="row">
                            <label for="sch-slug"><?php esc_html_e( 'Custom slug', 'seo-campaign-hub' ); ?></label>
                        </th>
                        <td>
                            <code><?php echo esc_html( home_url( '/' . $prefix . '/' ) ); ?></code>
                            <input type="text" id="sch-slug" name="slug" class="regular-text" style="max-width:200px"
                                   placeholder="<?php esc_attr_e( 'auto-generated if empty', 'seo-campaign-hub' ); ?>">
                        </td>
                    </tr>
                    <tr>
                        <th scope="row">
                            <label for="sch-title"><?php esc_html_e( 'Title', 'seo-campaign-hub' ); ?></label>
                        </th>
                        <td>
                            <input type="text" id="sch-title" name="title" class="regular-text"
                                   placeholder="<?php esc_attr_e( 'Internal label (optional)', 'seo-campaign-hub' ); ?>">
                        </td>
                    </tr>
                    <tr>
                        <th scope="row">
                            <label for="sch-redirect-type"><?php esc_html_e( 'Redirect type', 'seo-campaign-hub' ); ?></label>
                        </th>
                        <td>
                            <select id="sch-redirect-type" name="redirect_type">
                                <option value=""><?php esc_html_e( 'Default (from Settings)', 'seo-campaign-hub' ); ?></option>
                                <option value="301">301 — <?php esc_html_e( 'Permanent', 'seo-campaign-hub' ); ?></option>
                                <option value="302">302 — <?php esc_html_e( 'Temporary', 'seo-campaign-hub' ); ?></option>
                                <option value="307">307 — <?php esc_html_e( 'Temporary (strict)', 'seo-campaign-hub' ); ?></option>
                            </select>
                        </td>
                    </tr>
                </table>

                <details class="sch-utm-details">
                    <summary><?php esc_html_e( 'UTM tracking parameters (optional)', 'seo-campaign-hub' ); ?></summary>
                    <table class="form-table" role="presentation">
                        <?php
                        $utm_fields = [
                            'utm_source'   => __( 'Source', 'seo-campaign-hub' ),
                            'utm_medium'   => __( 'Medium', 'seo-campaign-hub' ),
                            'utm_campaign' => __( 'Campaign', 'seo-campaign-hub' ),
                            'utm_term'     => __( 'Term', 'seo-campaign-hub' ),
                            'utm_content'  => __( 'Content', 'seo-campaign-hub' ),
                        ];
                        foreach ( $utm_fields as $field => $label ) :
                            ?>
                            <tr>
                                <th scope="row">
                                    <label for="sch-<?php echo esc_attr( $field ); ?>"><?php echo esc_html( $label ); ?></label>
                                </th>
                                <td>
                                    <input type="text" id="sch-<?php echo esc_attr( $field ); ?>"
                                           name="<?php echo esc_attr( $field ); ?>" class="regular-text"
                                           placeholder="<?php echo esc_attr( $field ); ?>">
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    </table>
                </details>

                <?php submit_button( __( 'Create Short Link', 'seo-campaign-hub' ) ); ?>
            </form>

            <hr>

            <h2><?php esc_html_e( 'Your short links', 'seo-campaign-hub' ); ?></h2>

            <?php if ( empty( $links ) ) : ?>
                <div class="seo-campaign-hub-placeholder">
                    <?php esc_html_e( 'No short links yet. Create your first one using the form above.', 'seo-campaign-hub' ); ?>
                </div>
            <?php else : ?>
                <table class="wp-list-table widefat fixed striped">
                    <thead>
                        <tr>
                            <th><?php esc_html_e( 'Short URL', 'seo-campaign-hub' ); ?></th>
                            <th><?php esc_html_e( 'Destination', 'seo-campaign-hub' ); ?></th>
                            <th style="width:80px"><?php esc_html_e( 'Clicks', 'seo-campaign-hub' ); ?></th>
                            <th style="width:90px"><?php esc_html_e( 'Status', 'seo-campaign-hub' ); ?></th>
                            <th style="width:130px"><?php esc_html_e( 'Created', 'seo-campaign-hub' ); ?></th>
                            <th style="width:220px"><?php esc_html_e( 'Actions', 'seo-campaign-hub' ); ?></th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ( $links as $link ) : ?>
                            <?php
                            $toggle_url = wp_nonce_url(
                                admin_url( 'admin-post.php?action=sch_shortener_toggle&link_id=' . (int) $link->id ),
                                'sch_shortener_toggle_' . (int) $link->id
                            );
                            $delete_url = wp_nonce_url(
                                admin_url( 'admin-post.php?action=sch_shortener_delete&link_id=' . (int) $link->id ),
                                'sch_shortener_delete_' . (int) $link->id
                            );
                            ?>
                            <tr>
                                <td>
                                    <a href="<?php echo esc_url( $link->short_url ); ?>" target="_blank" rel="noopener">
                                        <code>/<?php echo esc_html( $prefix ); ?>/<?php echo esc_html( $link->slug ); ?></code>
                                    </a>
                                    <?php if ( ! empty( $link->title ) ) : ?>
                                        <br><span class="description"><?php echo esc_html( $link->title ); ?></span>
                                    <?php endif; ?>
                                </td>
                                <td>
                                    <a href="<?php echo esc_url( $link->destination_url ); ?>" target="_blank" rel="noopener" class="sch-dest">
                                        <?php echo esc_html( wp_html_excerpt( $link->destination_url, 60, '…' ) ); ?>
                                    </a>
                                </td>
                                <td><?php echo esc_html( number_format_i18n( (int) $link->total_clicks ) ); ?></td>
                                <td>
                                    <?php if ( $link->is_active ) : ?>
                                        <span class="sch-status sch-status-active"><?php esc_html_e( 'Active', 'seo-campaign-hub' ); ?></span>
                                    <?php else : ?>
                                        <span class="sch-status sch-status-inactive"><?php esc_html_e( 'Inactive', 'seo-campaign-hub' ); ?></span>
                                    <?php endif; ?>
                                </td>
                                <td>
                                    <?php echo esc_html( mysql2date( get_option( 'date_format' ), $link->created_at ) ); ?>
                                </td>
                                <td>
                                    <button type="button" class="button button-small sch-copy" data-url="<?php echo esc_attr( $link->short_url ); ?>">
                                        <?php esc_html_e( 'Copy', 'seo-campaign-hub' ); ?>
                                    </button>
                                    <a href="<?php echo esc_url( $toggle_url ); ?>" class="button button-small">
                                        <?php $link->is_active ? esc_html_e( 'Deactivate', 'seo-campaign-hub' ) : esc_html_e( 'Activate', 'seo-campaign-hub' ); ?>
                                    </a>
                                    <a href="<?php echo esc_url( $delete_url ); ?>" class="button button-small sch-delete"
                                       onclick="return confirm('<?php echo esc_js( __( 'Delete this short link? This cannot be undone.', 'seo-campaign-hub' ) ); ?>');">
                                        <?php esc_html_e( 'Delete', 'seo-campaign-hub' ); ?>
                                    </a>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            <?php endif; ?>

            <p class="description" style="margin-top:20px">
                <?php
                printf(
                    /* translators: %s: link to Permalinks settings */
                    esc_html__( 'Short links return a 404? Flush permalinks once via %s.', 'seo-campaign-hub' ),
                    '<a href="' . esc_url( admin_url( 'options-permalink.php' ) ) . '">' . esc_html__( 'Settings → Permalinks → Save Changes', 'seo-campaign-hub' ) . '</a>'
                );
                ?>
            </p>
        </div>
    </div>
</div>

<style>
    .sch-utm-details { margin:10px 0 20px; }
    .sch-utm-details summary { cursor:pointer; font-weight:600; padding:8px 0; }
    .sch-status { display:inline-block; padding:2px 10px; border-radius:12px; font-size:12px; font-weight:600; }
    .sch-status-active   { background:#d4edda; color:#155724; }
    .sch-status-inactive { background:#f8d7da; color:#721c24; }
    .sch-delete { color:#b32d2e; }
</style>

<script>
    document.addEventListener( 'click', function ( e ) {
        var btn = e.target.closest( '.sch-copy' );
        if ( ! btn ) {
            return;
        }
        navigator.clipboard.writeText( btn.dataset.url ).then( function () {
            var original = btn.textContent;
            btn.textContent = '<?php echo esc_js( __( 'Copied!', 'seo-campaign-hub' ) ); ?>';
            setTimeout( function () { btn.textContent = original; }, 1500 );
        } );
    } );
</script>
