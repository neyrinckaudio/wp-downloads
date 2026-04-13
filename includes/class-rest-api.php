<?php
if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

class PN_Downloads_REST_API {

    public static function init() {
        add_action( 'rest_api_init', [ __CLASS__, 'register_routes' ] );
        add_action( 'wp_ajax_pn_regenerate_key', [ __CLASS__, 'ajax_regenerate_key' ] );
    }

    public static function register_routes() {
        $namespace = 'pn-downloads/v1';

        // Public: get product info.
        register_rest_route( $namespace, '/products/(?P<slug>[a-zA-Z0-9_-]+)', [
            [
                'methods'             => 'GET',
                'callback'            => [ __CLASS__, 'get_product' ],
                'permission_callback' => '__return_true',
            ],
            [
                'methods'             => 'POST',
                'callback'            => [ __CLASS__, 'update_product' ],
                'permission_callback' => [ __CLASS__, 'check_api_key' ],
            ],
        ] );

        // Tracked download redirect.
        register_rest_route( $namespace, '/download/(?P<slug>[a-zA-Z0-9_-]+)/(?P<platform>mac|win)', [
            'methods'             => 'GET',
            'callback'            => [ __CLASS__, 'tracked_download' ],
            'permission_callback' => '__return_true',
        ] );
    }

    public static function check_api_key( $request ) {
        $provided = $request->get_header( 'X-PN-API-Key' );
        $stored   = get_option( 'pn_downloads_api_key', '' );

        if ( empty( $stored ) || empty( $provided ) || ! hash_equals( $stored, $provided ) ) {
            return new WP_Error( 'unauthorized', 'Invalid API key.', [ 'status' => 401 ] );
        }

        // IP allowlist check.
        $allowlist = get_option( 'pn_downloads_ip_allowlist', '' );
        if ( ! empty( trim( $allowlist ) ) ) {
            $allowed_ips = array_filter( array_map( 'trim', explode( "\n", $allowlist ) ) );
            $client_ip   = $_SERVER['REMOTE_ADDR'] ?? '';

            if ( ! empty( $allowed_ips ) && ! in_array( $client_ip, $allowed_ips, true ) ) {
                return new WP_Error( 'forbidden', 'IP not allowed.', [ 'status' => 403 ] );
            }
        }

        return true;
    }

    private static function find_product_by_slug( $slug ) {
        $posts = get_posts( [
            'post_type'      => 'pn_download',
            'name'           => $slug,
            'posts_per_page' => 1,
            'post_status'    => 'publish',
        ] );

        return $posts[0] ?? null;
    }

    public static function get_product( $request ) {
        $post = self::find_product_by_slug( $request['slug'] );

        if ( ! $post ) {
            return new WP_Error( 'not_found', 'Product not found.', [ 'status' => 404 ] );
        }

        return rest_ensure_response( self::format_product( $post ) );
    }

    public static function update_product( $request ) {
        $post = self::find_product_by_slug( $request['slug'] );

        if ( ! $post ) {
            return new WP_Error( 'not_found', 'Product not found.', [ 'status' => 404 ] );
        }

        $body = $request->get_json_params();

        // Optionally archive current version to legacy.
        if ( ! empty( $body['archive_current'] ) ) {
            $legacy = get_post_meta( $post->ID, 'pn_dl_legacy', true );
            $legacy = is_array( $legacy ) ? $legacy : [];
            $legacy[] = [
                'mac_version'       => get_post_meta( $post->ID, 'pn_dl_mac_version', true ),
                'mac_version_exact' => get_post_meta( $post->ID, 'pn_dl_mac_version_exact', true ),
                'mac_url'           => get_post_meta( $post->ID, 'pn_dl_mac_url', true ),
                'win_version'       => get_post_meta( $post->ID, 'pn_dl_win_version', true ),
                'win_version_exact' => get_post_meta( $post->ID, 'pn_dl_win_version_exact', true ),
                'win_url'           => get_post_meta( $post->ID, 'pn_dl_win_url', true ),
            ];
            update_post_meta( $post->ID, 'pn_dl_legacy', $legacy );
        }

        $text_fields = [
            'mac_version'       => 'pn_dl_mac_version',
            'mac_version_exact' => 'pn_dl_mac_version_exact',
            'win_version'       => 'pn_dl_win_version',
            'win_version_exact' => 'pn_dl_win_version_exact',
        ];
        foreach ( $text_fields as $body_key => $meta_key ) {
            if ( isset( $body[ $body_key ] ) ) {
                update_post_meta( $post->ID, $meta_key, sanitize_text_field( $body[ $body_key ] ) );
            }
        }

        if ( isset( $body['mac_url'] ) ) {
            update_post_meta( $post->ID, 'pn_dl_mac_url', esc_url_raw( $body['mac_url'] ) );
        }
        if ( isset( $body['win_url'] ) ) {
            update_post_meta( $post->ID, 'pn_dl_win_url', esc_url_raw( $body['win_url'] ) );
        }

        // Refetch after update.
        $post = get_post( $post->ID );

        return rest_ensure_response( [
            'updated' => true,
            'product' => self::format_product( $post ),
        ] );
    }

    public static function tracked_download( $request ) {
        $post = self::find_product_by_slug( $request['slug'] );

        if ( ! $post ) {
            return new WP_Error( 'not_found', 'Product not found.', [ 'status' => 404 ] );
        }

        $platform = $request['platform'];
        $meta_key = $platform === 'mac' ? 'pn_dl_mac_url' : 'pn_dl_win_url';
        $url      = get_post_meta( $post->ID, $meta_key, true );

        if ( empty( $url ) ) {
            return new WP_Error( 'no_url', 'No download URL for this platform.', [ 'status' => 404 ] );
        }

        // Increment count.
        PN_Downloads_Tracker::increment( $post->ID, $platform );

        // 302 redirect to the actual file.
        wp_redirect( $url, 302 );
        exit;
    }

    private static function format_product( $post ) {
        $legacy = get_post_meta( $post->ID, 'pn_dl_legacy', true );

        return [
            'slug'    => $post->post_name,
            'name'    => $post->post_title,
            'mac_version'       => get_post_meta( $post->ID, 'pn_dl_mac_version', true ),
            'mac_version_exact' => get_post_meta( $post->ID, 'pn_dl_mac_version_exact', true ),
            'mac_url'           => get_post_meta( $post->ID, 'pn_dl_mac_url', true ),
            'win_version'       => get_post_meta( $post->ID, 'pn_dl_win_version', true ),
            'win_version_exact' => get_post_meta( $post->ID, 'pn_dl_win_version_exact', true ),
            'win_url' => get_post_meta( $post->ID, 'pn_dl_win_url', true ),
            'legacy'  => is_array( $legacy ) ? $legacy : [],
        ];
    }

    public static function ajax_regenerate_key() {
        check_ajax_referer( 'pn_regenerate_key', 'nonce' );

        if ( ! current_user_can( 'manage_options' ) ) {
            wp_send_json_error( 'Permission denied.' );
        }

        $new_key = wp_generate_password( 40, false );
        update_option( 'pn_downloads_api_key', $new_key );
        wp_send_json_success( [ 'key' => $new_key ] );
    }
}
