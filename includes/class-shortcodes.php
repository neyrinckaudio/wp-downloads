<?php
if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

class PN_Downloads_Shortcodes {

    public static function init() {
        add_shortcode( 'pn_download_version', [ __CLASS__, 'version' ] );
        add_shortcode( 'pn_download_version_exact', [ __CLASS__, 'version_exact' ] );
        add_shortcode( 'pn_download_url', [ __CLASS__, 'url' ] );
    }

    /**
     * [pn_download_version platform="mac"]
     * [pn_download_version slug="pt-peek" platform="win"]
     */
    public static function version( $atts ) {
        $atts = shortcode_atts( [ 'slug' => '', 'platform' => 'mac' ], $atts );
        $post = self::resolve_post( $atts );
        if ( ! $post ) {
            return '';
        }
        $platform = in_array( $atts['platform'], [ 'mac', 'win' ], true ) ? $atts['platform'] : 'mac';
        return esc_html( get_post_meta( $post->ID, "_pn_dl_{$platform}_version", true ) );
    }

    /**
     * [pn_download_version_exact platform="mac"]
     * [pn_download_version_exact slug="pt-peek" platform="win"]
     */
    public static function version_exact( $atts ) {
        $atts = shortcode_atts( [ 'slug' => '', 'platform' => 'mac' ], $atts );
        $post = self::resolve_post( $atts );
        if ( ! $post ) {
            return '';
        }
        $platform = in_array( $atts['platform'], [ 'mac', 'win' ], true ) ? $atts['platform'] : 'mac';
        return esc_html( get_post_meta( $post->ID, "_pn_dl_{$platform}_version_exact", true ) );
    }

    /**
     * [pn_download_url platform="mac"]
     * [pn_download_url slug="pt-peek" platform="win"]
     */
    public static function url( $atts ) {
        $atts = shortcode_atts( [ 'slug' => '', 'platform' => 'mac' ], $atts );
        $post = self::resolve_post( $atts );
        if ( ! $post ) {
            return '';
        }
        $platform = in_array( $atts['platform'], [ 'mac', 'win' ], true ) ? $atts['platform'] : 'mac';
        return esc_url( rest_url( "pn-downloads/v1/download/{$post->post_name}/{$platform}" ) );
    }

    private static function resolve_post( $atts ) {
        if ( ! empty( $atts['slug'] ) ) {
            $posts = get_posts( [
                'post_type'      => 'pn_download',
                'name'           => $atts['slug'],
                'posts_per_page' => 1,
                'post_status'    => 'publish',
            ] );
            return $posts[0] ?? null;
        }

        $post = get_post();
        if ( $post && $post->post_type === 'pn_download' ) {
            return $post;
        }

        return null;
    }
}
