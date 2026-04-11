<?php
if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

class PN_Downloads_Tracker {

    public static function init() {
        // Nothing to hook for now — increment is called directly by the REST API.
    }

    public static function increment( $post_id, $platform ) {
        $meta_key = $platform === 'mac' ? '_pn_dl_mac_count' : '_pn_dl_win_count';
        $current  = (int) get_post_meta( $post_id, $meta_key, true );
        update_post_meta( $post_id, $meta_key, $current + 1 );
    }

    public static function get_counts( $post_id ) {
        return [
            'mac' => (int) get_post_meta( $post_id, '_pn_dl_mac_count', true ),
            'win' => (int) get_post_meta( $post_id, '_pn_dl_win_count', true ),
        ];
    }
}
