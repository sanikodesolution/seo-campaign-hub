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
$enabled_languages = isset( $enabled_languages ) && is_array( $enabled_languages ) ? $enabled_languages : [ 'en' ];
$default_priority  = isset( $default_priority ) ? (string) $default_priority : 'language';
$smart_redirects_enabled = ! isset( $smart_redirects_enabled ) || $smart_redirects_enabled;

$language_labels = [
    'en' => 'English',
    'es' => 'Spanish',
    'pt' => 'Portuguese',
    'fr' => 'French',
    'de' => 'German',
    'it' => 'Italian',
    'nl' => 'Dutch',
    'pl' => 'Polish',
    'ru' => 'Russian',
    'ar' => 'Arabic',
    'he' => 'Hebrew',
    'tr' => 'Turkish',
    'fa' => 'Persian',
];

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

                <details class="sch-rules-details" <?php echo $smart_redirects_enabled ? 'open' : ''; ?>>
                    <summary><?php esc_html_e( 'Smart redirect rules (language / country)', 'seo-campaign-hub' ); ?></summary>
                    <?php if ( ! $smart_redirects_enabled ) : ?>
                        <p class="description">
                            <?php esc_html_e( 'Smart redirects are disabled in Settings → Localization. Enable them to use these rules.', 'seo-campaign-hub' ); ?>
                        </p>
                    <?php endif; ?>
                    <table class="form-table" role="presentation">
                        <tr>
                            <th scope="row">
                                <label for="sch-redirect-priority"><?php esc_html_e( 'Match priority', 'seo-campaign-hub' ); ?></label>
                            </th>
                            <td>
                                <select id="sch-redirect-priority" name="redirect_priority">
                                    <option value="language" <?php selected( $default_priority, 'language' ); ?>>
                                        <?php esc_html_e( 'Language first', 'seo-campaign-hub' ); ?>
                                    </option>
                                    <option value="country" <?php selected( $default_priority, 'country' ); ?>>
                                        <?php esc_html_e( 'Country first', 'seo-campaign-hub' ); ?>
                                    </option>
                                </select>
                                <p class="description">
                                    <?php esc_html_e( 'Destination URL above is the fallback when no rule matches.', 'seo-campaign-hub' ); ?>
                                </p>
                            </td>
                        </tr>
                    </table>

                    <table class="widefat striped sch-rules-table" id="sch-targeting-rules">
                        <thead>
                            <tr>
                                <th style="width:140px"><?php esc_html_e( 'Type', 'seo-campaign-hub' ); ?></th>
                                <th style="width:180px"><?php esc_html_e( 'Match', 'seo-campaign-hub' ); ?></th>
                                <th><?php esc_html_e( 'Destination URL', 'seo-campaign-hub' ); ?></th>
                                <th style="width:90px"><?php esc_html_e( 'Remove', 'seo-campaign-hub' ); ?></th>
                            </tr>
                        </thead>
                        <tbody></tbody>
                    </table>
                    <p>
                        <button type="button" class="button" id="sch-add-rule">
                            <?php esc_html_e( 'Add rule', 'seo-campaign-hub' ); ?>
                        </button>
                    </p>
                </details>

                <?php submit_button( __( 'Create Short Link', 'seo-campaign-hub' ) ); ?>
            </form>

            <template id="sch-rule-row-template">
                <tr class="sch-rule-row">
                    <td>
                        <select name="targeting_rules[__INDEX__][type]" class="sch-rule-type">
                            <option value="language"><?php esc_html_e( 'Language', 'seo-campaign-hub' ); ?></option>
                            <option value="country"><?php esc_html_e( 'Country', 'seo-campaign-hub' ); ?></option>
                        </select>
                    </td>
                    <td class="sch-rule-match-cell">
                        <select name="targeting_rules[__INDEX__][match]" class="sch-rule-match-language">
                            <?php foreach ( $enabled_languages as $code ) : ?>
                                <option value="<?php echo esc_attr( $code ); ?>">
                                    <?php
                                    $label = isset( $language_labels[ $code ] ) ? $language_labels[ $code ] : strtoupper( $code );
                                    echo esc_html( $label . ' (' . $code . ')' );
                                    ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                        <input type="text" name="targeting_rules[__INDEX__][match]" class="sch-rule-match-country regular-text"
                               maxlength="2" placeholder="US" style="display:none;max-width:80px" disabled>
                    </td>
                    <td>
                        <input type="url" name="targeting_rules[__INDEX__][url]" class="regular-text"
                               placeholder="https://example.com/ar/deal/">
                    </td>
                    <td>
                        <button type="button" class="button button-small sch-remove-rule"><?php esc_html_e( 'Remove', 'seo-campaign-hub' ); ?></button>
                    </td>
                </tr>
            </template>

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
    .sch-utm-details,
    .sch-rules-details { margin:10px 0 20px; }
    .sch-utm-details summary,
    .sch-rules-details summary { cursor:pointer; font-weight:600; padding:8px 0; }
    .sch-rules-table { margin:12px 0; }
    .sch-status { display:inline-block; padding:2px 10px; border-radius:12px; font-size:12px; font-weight:600; }
    .sch-status-active   { background:#d4edda; color:#155724; }
    .sch-status-inactive { background:#f8d7da; color:#721c24; }
    .sch-delete { color:#b32d2e; }
</style>

<script>
    (function () {
        var tableBody = document.querySelector( '#sch-targeting-rules tbody' );
        var template = document.getElementById( 'sch-rule-row-template' );
        var addBtn = document.getElementById( 'sch-add-rule' );
        var ruleIndex = 0;

        function syncMatchControls( row ) {
            var typeSelect = row.querySelector( '.sch-rule-type' );
            var lang = row.querySelector( '.sch-rule-match-language' );
            var country = row.querySelector( '.sch-rule-match-country' );
            if ( ! typeSelect || ! lang || ! country ) {
                return;
            }
            var isCountry = typeSelect.value === 'country';
            lang.style.display = isCountry ? 'none' : '';
            lang.disabled = isCountry;
            country.style.display = isCountry ? '' : 'none';
            country.disabled = ! isCountry;
        }

        function addRule() {
            if ( ! tableBody || ! template ) {
                return;
            }
            var html = template.innerHTML.replace( /__INDEX__/g, String( ruleIndex++ ) );
            var wrap = document.createElement( 'tbody' );
            wrap.innerHTML = html.trim();
            var row = wrap.firstElementChild;
            tableBody.appendChild( row );
            syncMatchControls( row );
        }

        if ( addBtn ) {
            addBtn.addEventListener( 'click', function ( e ) {
                e.preventDefault();
                addRule();
            } );
        }

        document.addEventListener( 'change', function ( e ) {
            if ( e.target && e.target.classList.contains( 'sch-rule-type' ) ) {
                syncMatchControls( e.target.closest( 'tr' ) );
            }
        } );

        document.addEventListener( 'click', function ( e ) {
            var removeBtn = e.target.closest( '.sch-remove-rule' );
            if ( removeBtn ) {
                e.preventDefault();
                var row = removeBtn.closest( 'tr' );
                if ( row ) {
                    row.remove();
                }
            }

            var copyBtn = e.target.closest( '.sch-copy' );
            if ( ! copyBtn ) {
                return;
            }
            navigator.clipboard.writeText( copyBtn.dataset.url ).then( function () {
                var original = copyBtn.textContent;
                copyBtn.textContent = '<?php echo esc_js( __( 'Copied!', 'seo-campaign-hub' ) ); ?>';
                setTimeout( function () { copyBtn.textContent = original; }, 1500 );
            } );
        } );
    })();
</script>
