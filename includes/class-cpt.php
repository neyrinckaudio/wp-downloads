<?php
if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

class PN_Downloads_CPT {

    public static function init() {
        add_action( 'init', [ __CLASS__, 'register_post_type' ] );
        add_action( 'init', [ __CLASS__, 'maybe_migrate_meta_keys' ] );
        add_action( 'add_meta_boxes', [ __CLASS__, 'add_meta_boxes' ] );
        add_action( 'save_post_pn_download', [ __CLASS__, 'save_meta' ], 10, 2 );
        add_filter( 'manage_pn_download_posts_columns', [ __CLASS__, 'admin_columns' ] );
        add_action( 'manage_pn_download_posts_custom_column', [ __CLASS__, 'admin_column_content' ], 10, 2 );
        add_filter( 'divi_module_dynamic_content_options', [ __CLASS__, 'relabel_divi_dynamic_fields' ], 20, 3 );
    }

    /**
     * Replace raw meta-key labels in Divi 5's Dynamic Content "Custom Field" dropdown
     * with human-readable names. Divi populates the dropdown with the raw meta key as
     * the label for standard meta; this filter rewrites those entries for our keys.
     */
    public static function relabel_divi_dynamic_fields( $options, $post_id, $context ) {
        $labels = self::meta_field_labels();

        if ( ! isset( $options['post_meta_key']['fields']['select_meta_key']['options'] ) ) {
            return $options;
        }

        $groups = &$options['post_meta_key']['fields']['select_meta_key']['options'];

        foreach ( $groups as $group_key => &$group ) {
            if ( empty( $group['options'] ) || ! is_array( $group['options'] ) ) {
                continue;
            }
            foreach ( $group['options'] as $option_key => &$option ) {
                // Divi prefixes standard meta keys with 'custom_meta_'.
                if ( strpos( $option_key, 'custom_meta_' ) !== 0 ) {
                    continue;
                }
                $meta_key = substr( $option_key, strlen( 'custom_meta_' ) );
                if ( isset( $labels[ $meta_key ] ) ) {
                    if ( is_array( $option ) ) {
                        $option['label'] = $labels[ $meta_key ];
                    } else {
                        $option = [ 'label' => $labels[ $meta_key ] ];
                    }
                }
            }
            unset( $option );
        }
        unset( $group );

        return $options;
    }

    private static function meta_field_labels() {
        return [
            'pn_dl_mac_version'       => 'PN Download: macOS Version',
            'pn_dl_mac_version_exact' => 'PN Download: macOS Version (Exact Build)',
            'pn_dl_mac_url'           => 'PN Download: macOS Installer URL',
            'pn_dl_win_version'       => 'PN Download: Windows Version',
            'pn_dl_win_version_exact' => 'PN Download: Windows Version (Exact Build)',
            'pn_dl_win_url'           => 'PN Download: Windows Installer URL',
            'pn_dl_mac_count'         => 'PN Download: macOS Download Count',
            'pn_dl_win_count'         => 'PN Download: Windows Download Count',
        ];
    }

    /**
     * One-time rename of legacy underscore-prefixed meta keys to their
     * unprefixed equivalents so Divi 5's Dynamic Content picker can see them.
     */
    public static function maybe_migrate_meta_keys() {
        if ( get_option( 'pn_downloads_meta_migrated_v2' ) ) {
            return;
        }

        global $wpdb;
        $map = [
            '_pn_dl_mac_version'       => 'pn_dl_mac_version',
            '_pn_dl_mac_version_exact' => 'pn_dl_mac_version_exact',
            '_pn_dl_mac_url'           => 'pn_dl_mac_url',
            '_pn_dl_win_version'       => 'pn_dl_win_version',
            '_pn_dl_win_version_exact' => 'pn_dl_win_version_exact',
            '_pn_dl_win_url'           => 'pn_dl_win_url',
            '_pn_dl_legacy'            => 'pn_dl_legacy',
            '_pn_dl_mac_count'         => 'pn_dl_mac_count',
            '_pn_dl_win_count'         => 'pn_dl_win_count',
        ];
        foreach ( $map as $old => $new ) {
            $wpdb->update( $wpdb->postmeta, [ 'meta_key' => $new ], [ 'meta_key' => $old ] );
        }

        update_option( 'pn_downloads_meta_migrated_v2', 1 );
    }

    public static function register_post_type() {
        register_post_type( 'pn_download', [
            'labels' => [
                'name'               => 'PN Downloads',
                'singular_name'      => 'PN Download',
                'add_new'            => 'Add New Product',
                'add_new_item'       => 'Add New Product',
                'edit_item'          => 'Edit Product',
                'new_item'           => 'New Product',
                'view_item'          => 'View Product',
                'search_items'       => 'Search Products',
                'not_found'          => 'No products found',
                'not_found_in_trash' => 'No products found in Trash',
                'menu_name'          => 'PN Downloads',
            ],
            'public'       => true,
            'has_archive'  => false,
            'show_in_rest' => true,
            'menu_icon'    => 'dashicons-download',
            'supports'     => [ 'title', 'editor', 'thumbnail', 'custom-fields' ],
            'rewrite'      => [ 'slug' => 'pn-downloads' ],
        ] );

        foreach ( array_keys( self::meta_field_labels() ) as $key ) {
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

        $mac_version       = get_post_meta( $post->ID, 'pn_dl_mac_version', true );
        $mac_version_exact = get_post_meta( $post->ID, 'pn_dl_mac_version_exact', true );
        $mac_url           = get_post_meta( $post->ID, 'pn_dl_mac_url', true );
        $win_version       = get_post_meta( $post->ID, 'pn_dl_win_version', true );
        $win_version_exact = get_post_meta( $post->ID, 'pn_dl_win_version_exact', true );
        $win_url           = get_post_meta( $post->ID, 'pn_dl_win_url', true );
        $legacy            = get_post_meta( $post->ID, 'pn_dl_legacy', true );
        $mac_count         = (int) get_post_meta( $post->ID, 'pn_dl_mac_count', true );
        $win_count         = (int) get_post_meta( $post->ID, 'pn_dl_win_count', true );

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
                update_post_meta( $post_id, $field, sanitize_text_field( $_POST[ $field ] ) );
            }
        }

        $url_fields = [ 'pn_dl_mac_url', 'pn_dl_win_url' ];
        foreach ( $url_fields as $field ) {
            if ( isset( $_POST[ $field ] ) ) {
                update_post_meta( $post_id, $field, esc_url_raw( $_POST[ $field ] ) );
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
        update_post_meta( $post_id, 'pn_dl_legacy', $legacy );
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
                $mac_v = get_post_meta( $post_id, 'pn_dl_mac_version', true );
                $win_v = get_post_meta( $post_id, 'pn_dl_win_version', true );
                if ( $mac_v ) { $parts[] = 'Mac: ' . $mac_v; }
                if ( $win_v ) { $parts[] = 'Win: ' . $win_v; }
                echo esc_html( implode( ' | ', $parts ) ?: '—' );
                break;
            case 'pn_platforms':
                $platforms = [];
                if ( get_post_meta( $post_id, 'pn_dl_mac_url', true ) ) {
                    $platforms[] = 'macOS';
                }
                if ( get_post_meta( $post_id, 'pn_dl_win_url', true ) ) {
                    $platforms[] = 'Windows';
                }
                echo esc_html( implode( ', ', $platforms ) ?: '—' );
                break;
            case 'pn_downloads':
                $mac = (int) get_post_meta( $post_id, 'pn_dl_mac_count', true );
                $win = (int) get_post_meta( $post_id, 'pn_dl_win_count', true );
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
