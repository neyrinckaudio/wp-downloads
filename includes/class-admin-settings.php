<?php
if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

class PN_Downloads_Admin_Settings {

    public static function init() {
        add_action( 'admin_menu', [ __CLASS__, 'add_settings_page' ] );
        add_action( 'admin_init', [ __CLASS__, 'register_settings' ] );
        add_action( 'admin_enqueue_scripts', [ __CLASS__, 'enqueue_assets' ] );
    }

    public static function add_settings_page() {
        add_options_page(
            'PN Downloads Settings',
            'PN Downloads',
            'manage_options',
            'pn-downloads',
            [ __CLASS__, 'render_settings_page' ]
        );
    }

    public static function register_settings() {
        register_setting( 'pn_downloads_settings', 'pn_downloads_api_key', [
            'sanitize_callback' => 'sanitize_text_field',
        ] );
        register_setting( 'pn_downloads_settings', 'pn_downloads_ip_allowlist', [
            'sanitize_callback' => 'sanitize_textarea_field',
        ] );
    }

    public static function render_settings_page() {
        $api_key      = get_option( 'pn_downloads_api_key', '' );
        $ip_allowlist = get_option( 'pn_downloads_ip_allowlist', '' );
        $site_url     = get_rest_url( null, 'pn-downloads/v1/' );
        ?>
        <div class="wrap">
            <h1>PN Downloads Settings</h1>
            <form method="post" action="options.php">
                <?php settings_fields( 'pn_downloads_settings' ); ?>

                <table class="form-table">
                    <tr>
                        <th><label for="pn_downloads_api_key">API Key</label></th>
                        <td>
                            <input type="text" id="pn_downloads_api_key" name="pn_downloads_api_key"
                                   value="<?php echo esc_attr( $api_key ); ?>" class="large-text" readonly>
                            <p class="description">
                                Use this key in the <code>X-PN-API-Key</code> header for REST API requests.
                                <br><button type="button" class="button button-secondary" id="pn-regenerate-key">Regenerate Key</button>
                            </p>
                        </td>
                    </tr>
                    <tr>
                        <th><label for="pn_downloads_ip_allowlist">IP Allowlist</label></th>
                        <td>
                            <textarea id="pn_downloads_ip_allowlist" name="pn_downloads_ip_allowlist"
                                      rows="5" class="large-text"><?php echo esc_textarea( $ip_allowlist ); ?></textarea>
                            <p class="description">
                                One IP address per line. Leave empty to allow all IPs (useful when traveling).
                            </p>
                        </td>
                    </tr>
                </table>

                <h2>API Usage</h2>
                <p>Base URL: <code><?php echo esc_html( $site_url ); ?></code></p>

                <h3>Update a product</h3>
                <pre><code>curl -X POST "<?php echo esc_html( $site_url ); ?>products/{slug}" \
  -H "X-PN-API-Key: <?php echo esc_html( $api_key ); ?>" \
  -H "Content-Type: application/json" \
  -d '{
    "version": "3.2.1",
    "mac_url": "https://example.com/installer.pkg",
    "win_url": "https://example.com/installer.exe"
  }'</code></pre>

                <h3>Optional: archive current version to legacy before updating</h3>
                <pre><code>curl -X POST "<?php echo esc_html( $site_url ); ?>products/{slug}" \
  -H "X-PN-API-Key: <?php echo esc_html( $api_key ); ?>" \
  -H "Content-Type: application/json" \
  -d '{
    "version": "3.2.1",
    "mac_url": "https://example.com/installer.pkg",
    "archive_current": true
  }'</code></pre>

                <?php submit_button(); ?>
            </form>
        </div>
        <?php
    }

    public static function enqueue_assets( $hook ) {
        if ( $hook === 'settings_page_pn-downloads' || get_post_type() === 'pn_download' ) {
            wp_enqueue_style( 'pn-downloads-admin', PN_DOWNLOADS_URL . 'assets/admin.css', [], PN_DOWNLOADS_VERSION );
        }
        if ( $hook === 'settings_page_pn-downloads' ) {
            wp_enqueue_script( 'pn-downloads-settings', PN_DOWNLOADS_URL . 'assets/admin.js', [ 'jquery' ], PN_DOWNLOADS_VERSION, true );
            wp_localize_script( 'pn-downloads-settings', 'pnDownloads', [
                'ajaxUrl' => admin_url( 'admin-ajax.php' ),
                'nonce'   => wp_create_nonce( 'pn_regenerate_key' ),
            ] );
        }
        if ( get_post_type() === 'pn_download' ) {
            wp_enqueue_script( 'pn-downloads-admin', PN_DOWNLOADS_URL . 'assets/admin.js', [ 'jquery' ], PN_DOWNLOADS_VERSION, true );
        }
    }
}
