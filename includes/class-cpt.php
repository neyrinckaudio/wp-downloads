<?php
if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

class PN_Downloads_CPT {

    public static function init() {
        add_action( 'init', [ __CLASS__, 'register_post_type' ] );
        add_action( 'add_meta_boxes', [ __CLASS__, 'add_meta_boxes' ] );
        add_action( 'save_post_pn_download', [ __CLASS__, 'save_meta' ], 10, 2 );
        add_filter( 'manage_pn_download_posts_columns', [ __CLASS__, 'admin_columns' ] );
        add_action( 'manage_pn_download_posts_custom_column', [ __CLASS__, 'admin_column_content' ], 10, 2 );
        add_filter( 'is_protected_meta', [ __CLASS__, 'expose_meta_to_divi' ], 10, 2 );
    }

    /**
     * Un-hide custom fields so they appear in Divi 5's Dynamic Content dropdowns.
     */
    public static function expose_meta_to_divi( $protected, $meta_key ) {
        $exposed = [
            '_pn_dl_mac_version', '_pn_dl_mac_version_exact', '_pn_dl_mac_url',
            '_pn_dl_win_version', '_pn_dl_win_version_exact', '_pn_dl_win_url',
        ];
        if ( in_array( $meta_key, $exposed, true ) ) {
            return false;
        }
        return $protected;
    }

    public static function register_post_type() {
        register_post_type( 'pn_download', [
            'labels' => [
                'name'               => 'Downloads',
                'singular_name'      => 'Download',
                'add_new'            => 'Add New Product',
                'add_new_item'       => 'Add New Product',
                'edit_item'          => 'Edit Product',
                'new_item'           => 'New Product',
                'view_item'          => 'View Product',
                'search_items'       => 'Search Products',
                'not_found'          => 'No products found',
                'not_found_in_trash' => 'No products found in Trash',
                'menu_name'          => 'Downloads',
            ],
            'public'       => true,
            'has_archive'  => false,
            'show_in_rest' => true,
            'menu_icon'    => 'dashicons-download',
            'supports'     => [ 'title', 'editor', 'thumbnail', 'custom-fields' ],
            'rewrite'      => [ 'slug' => 'download' ],
        ] );

        $meta_fields = [
            '_pn_dl_mac_version', '_pn_dl_mac_version_exact', '_pn_dl_mac_url',
            '_pn_dl_win_version', '_pn_dl_win_version_exact', '_pn_dl_win_url',
        ];
        foreach ( $meta_fields as $key ) {
            register_post_meta( 'pn_download', $key, [
                'show_in_rest' => true,
                'single'       => true,
                'type'         => 'string',
            ] );
        }
    }

    public static function add_meta_boxes() {
        add_meta_box(
            'pn_download_details',
            'Download Details',
            [ __CLASS__, 'render_meta_box' ],
            'pn_download',
            'normal',
            'high'
        );
    }

    public static function render_meta_box( $post ) {
        wp_nonce_field( 'pn_download_save', 'pn_download_nonce' );

        $mac_version       = get_post_meta( $post->ID, '_pn_dl_mac_version', true );
        $mac_version_exact = get_post_meta( $post->ID, '_pn_dl_mac_version_exact', true );
        $mac_url           = get_post_meta( $post->ID, '_pn_dl_mac_url', true );
        $win_version       = get_post_meta( $post->ID, '_pn_dl_win_version', true );
        $win_version_exact = get_post_meta( $post->ID, '_pn_dl_win_version_exact', true );
        $win_url           = get_post_meta( $post->ID, '_pn_dl_win_url', true );
        $legacy            = get_post_meta( $post->ID, '_pn_dl_legacy', true );
        $mac_count         = (int) get_post_meta( $post->ID, '_pn_dl_mac_count', true );
        $win_count         = (int) get_post_meta( $post->ID, '_pn_dl_win_count', true );

        if ( ! is_array( $legacy ) ) {
            $legacy = [];
        }
        ?>
        <div class="pn-download-meta">
            <h3>macOS</h3>
            <table class="form-table">
                <tr>
                    <th><label for="pn_dl_mac_version">Version</label></th>
                    <td><input type="text" id="pn_dl_mac_version" name="pn_dl_mac_version"
                               value="<?php echo esc_attr( $mac_version ); ?>" class="regular-text"
                               placeholder="e.g. 3.2.1"></td>
                </tr>
                <tr>
                    <th><label for="pn_dl_mac_version_exact">Version Exact</label></th>
                    <td><input type="text" id="pn_dl_mac_version_exact" name="pn_dl_mac_version_exact"
                               value="<?php echo esc_attr( $mac_version_exact ); ?>" class="regular-text"
                               placeholder="e.g. 3.2.1.18"></td>
                </tr>
                <tr>
                    <th><label for="pn_dl_mac_url">URL</label></th>
                    <td><input type="url" id="pn_dl_mac_url" name="pn_dl_mac_url"
                               value="<?php echo esc_url( $mac_url ); ?>" class="large-text"
                               placeholder="https://example.com/installer.pkg"></td>
                </tr>
            </table>

            <h3>Windows</h3>
            <table class="form-table">
                <tr>
                    <th><label for="pn_dl_win_version">Version</label></th>
                    <td><input type="text" id="pn_dl_win_version" name="pn_dl_win_version"
                               value="<?php echo esc_attr( $win_version ); ?>" class="regular-text"
                               placeholder="e.g. 3.2.1"></td>
                </tr>
                <tr>
                    <th><label for="pn_dl_win_version_exact">Version Exact</label></th>
                    <td><input type="text" id="pn_dl_win_version_exact" name="pn_dl_win_version_exact"
                               value="<?php echo esc_attr( $win_version_exact ); ?>" class="regular-text"
                               placeholder="e.g. 3.2.1.18"></td>
                </tr>
                <tr>
                    <th><label for="pn_dl_win_url">URL</label></th>
                    <td><input type="url" id="pn_dl_win_url" name="pn_dl_win_url"
                               value="<?php echo esc_url( $win_url ); ?>" class="large-text"
                               placeholder="https://example.com/installer.exe"></td>
                </tr>
            </table>

            <h3>Download Counts</h3>
            <table class="form-table">
                <tr>
                    <th>macOS Downloads</th>
                    <td><strong><?php echo number_format( $mac_count ); ?></strong></td>
                </tr>
                <tr>
                    <th>Windows Downloads</th>
                    <td><strong><?php echo number_format( $win_count ); ?></strong></td>
                </tr>
            </table>

            <h3>Legacy Versions</h3>
            <div id="pn-legacy-entries">
                <?php foreach ( $legacy as $i => $entry ) : ?>
                    <div class="pn-legacy-entry" data-index="<?php echo $i; ?>">
                        <table class="form-table">
                            <tr>
                                <th>Version</th>
                                <td>
                                    <input type="text" name="pn_dl_legacy[<?php echo $i; ?>][version]"
                                           value="<?php echo esc_attr( $entry['version'] ?? '' ); ?>" class="regular-text">
                                    <button type="button" class="button pn-remove-legacy">Remove</button>
                                </td>
                            </tr>
                            <tr>
                                <th>macOS URL</th>
                                <td><input type="url" name="pn_dl_legacy[<?php echo $i; ?>][mac_url]"
                                           value="<?php echo esc_url( $entry['mac_url'] ?? '' ); ?>" class="large-text"></td>
                            </tr>
                            <tr>
                                <th>Windows URL</th>
                                <td><input type="url" name="pn_dl_legacy[<?php echo $i; ?>][win_url]"
                                           value="<?php echo esc_url( $entry['win_url'] ?? '' ); ?>" class="large-text"></td>
                            </tr>
                        </table>
                        <hr>
                    </div>
                <?php endforeach; ?>
            </div>
            <button type="button" class="button button-secondary" id="pn-add-legacy">+ Add Legacy Version</button>
        </div>
        <?php
    }

    public static function save_meta( $post_id, $post ) {
        if ( ! isset( $_POST['pn_download_nonce'] ) ||
             ! wp_verify_nonce( $_POST['pn_download_nonce'], 'pn_download_save' ) ) {
            return;
        }

        if ( defined( 'DOING_AUTOSAVE' ) && DOING_AUTOSAVE ) {
            return;
        }

        if ( ! current_user_can( 'edit_post', $post_id ) ) {
            return;
        }

        $text_fields = [ 'pn_dl_mac_version', 'pn_dl_mac_version_exact', 'pn_dl_win_version', 'pn_dl_win_version_exact' ];
        foreach ( $text_fields as $field ) {
            if ( isset( $_POST[ $field ] ) ) {
                update_post_meta( $post_id, '_' . $field, sanitize_text_field( $_POST[ $field ] ) );
            }
        }

        $url_fields = [ 'pn_dl_mac_url', 'pn_dl_win_url' ];
        foreach ( $url_fields as $field ) {
            if ( isset( $_POST[ $field ] ) ) {
                update_post_meta( $post_id, '_' . $field, esc_url_raw( $_POST[ $field ] ) );
            }
        }

        $legacy = [];
        if ( isset( $_POST['pn_dl_legacy'] ) && is_array( $_POST['pn_dl_legacy'] ) ) {
            foreach ( $_POST['pn_dl_legacy'] as $entry ) {
                $version = sanitize_text_field( $entry['version'] ?? '' );
                if ( empty( $version ) ) {
                    continue;
                }
                $legacy[] = [
                    'version' => $version,
                    'mac_url' => esc_url_raw( $entry['mac_url'] ?? '' ),
                    'win_url' => esc_url_raw( $entry['win_url'] ?? '' ),
                ];
            }
        }
        update_post_meta( $post_id, '_pn_dl_legacy', $legacy );
    }

    public static function admin_columns( $columns ) {
        $new = [];
        foreach ( $columns as $key => $label ) {
            $new[ $key ] = $label;
            if ( $key === 'title' ) {
                $new['pn_version']    = 'Version';
                $new['pn_platforms']  = 'Platforms';
                $new['pn_downloads']  = 'Downloads';
            }
        }
        return $new;
    }

    public static function admin_column_content( $column, $post_id ) {
        switch ( $column ) {
            case 'pn_version':
                $parts = [];
                $mac_v = get_post_meta( $post_id, '_pn_dl_mac_version', true );
                $win_v = get_post_meta( $post_id, '_pn_dl_win_version', true );
                if ( $mac_v ) { $parts[] = 'Mac: ' . $mac_v; }
                if ( $win_v ) { $parts[] = 'Win: ' . $win_v; }
                echo esc_html( implode( ' | ', $parts ) ?: '—' );
                break;
            case 'pn_platforms':
                $platforms = [];
                if ( get_post_meta( $post_id, '_pn_dl_mac_url', true ) ) {
                    $platforms[] = 'macOS';
                }
                if ( get_post_meta( $post_id, '_pn_dl_win_url', true ) ) {
                    $platforms[] = 'Windows';
                }
                echo esc_html( implode( ', ', $platforms ) ?: '—' );
                break;
            case 'pn_downloads':
                $mac = (int) get_post_meta( $post_id, '_pn_dl_mac_count', true );
                $win = (int) get_post_meta( $post_id, '_pn_dl_win_count', true );
                $parts = [];
                if ( $mac ) {
                    $parts[] = 'Mac: ' . number_format( $mac );
                }
                if ( $win ) {
                    $parts[] = 'Win: ' . number_format( $win );
                }
                echo esc_html( implode( ' | ', $parts ) ?: '0' );
                break;
        }
    }
}
