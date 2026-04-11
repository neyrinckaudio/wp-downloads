<?php
if ( ! defined( 'WP_UNINSTALL_PLUGIN' ) ) {
    exit;
}

// Delete all pn_download posts and their meta.
$posts = get_posts( [
    'post_type'      => 'pn_download',
    'posts_per_page' => -1,
    'post_status'    => 'any',
    'fields'         => 'ids',
] );

foreach ( $posts as $post_id ) {
    wp_delete_post( $post_id, true );
}

// Delete plugin options.
delete_option( 'pn_downloads_api_key' );
delete_option( 'pn_downloads_ip_allowlist' );
