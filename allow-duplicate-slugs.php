<?php
/*
Plugin Name: Allow Duplicate Slugs
Description: Allow duplicate slugs across different post types
Version:     1.0
Author:      John Blackbourn
Author URI:  https://johnblackbourn.com/
License:     GPL v2 or later

Copyright © 2014 John Blackbourn

This program is free software; you can redistribute it and/or modify
it under the terms of the GNU General Public License as published by
the Free Software Foundation; either version 2 of the License, or
(at your option) any later version.

This program is distributed in the hope that it will be useful,
but WITHOUT ANY WARRANTY; without even the implied warranty of
MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
GNU General Public License for more details.

--- Notes ---

This has not been tested with WPML.

See:
http://core.trac.wordpress.org/ticket/18962
http://core.trac.wordpress.org/ticket/20480 - fixed in 3.5

*/

add_filter( 'wp_unique_post_slug', 'allow_duplicate_slugs', 10, 6 );

function allow_duplicate_slugs( $slug, $post_id, $post_status, $post_type, $post_parent, $original_slug ) {

        global $wpdb, $wp_rewrite;

	$pto = get_post_type_object( $post_type );

	# If our post type isn't hierarchical, we don't need to worry about it:
	if ( !$pto->hierarchical )
		return $slug;

	# If our slug doesn't end with a number, we don't need to worry about it:
	if ( !preg_match( '|[0-9]$|', $slug ) )
		return $slug;

	# Most of this code is pulled straight from wp_unique_post_slug(). Just the post type check has changed.

	$feeds = $wp_rewrite->feeds;
	if ( ! is_array( $feeds ) )
		$feeds = array();

	$check_sql = "
		SELECT post_name
		FROM $wpdb->posts
		WHERE post_name = %s
		AND post_type = %s
		AND ID != %d
		AND post_parent = %d
		LIMIT 1
	";
	$post_name_check = $wpdb->get_var( $wpdb->prepare( $check_sql, $original_slug, $post_type, $post_id, $post_parent ) );

	if ( $post_name_check || in_array( $original_slug, $feeds ) || preg_match( "@^($wp_rewrite->pagination_base)?\d+$@", $original_slug )  || apply_filters( 'wp_unique_post_slug_is_bad_hierarchical_slug', false, $original_slug, $post_type, $post_parent ) ) {
		$suffix = 2;
		do {
			$alt_post_name = substr( $original_slug, 0, 200 - ( strlen( $suffix ) + 1 ) ) . "-$suffix";
			$post_name_check = $wpdb->get_var( $wpdb->prepare( $check_sql, $alt_post_name, $post_type, $post_id, $post_parent ) );
			$suffix++;
		} while ( $post_name_check );
		$slug = $alt_post_name;
	} else {
		$slug = $original_slug;
	}

	return $slug;

}
