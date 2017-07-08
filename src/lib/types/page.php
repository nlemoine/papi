<?php

/**
 * Check if the page type should be displayed or not.
 *
 * @param  string|object $page_type
 *
 * @return bool
 */
function papi_display_page_type( $page_type ) {
	$post_type = papi_get_post_type();

	if ( empty( $post_type ) ) {
		return false;
	}

	if ( is_string( $page_type ) ) {
		$page_type = papi_get_entry_type_by_id( $page_type );
	}

	if ( ! is_object( $page_type ) ) {
		return false;
	}

	if ( ! in_array( $post_type, $page_type->post_type ) ) {
		return false;
	}

	$display = $page_type->display( $post_type );

	if ( ! is_bool( $display ) || $display === false ) {
		return false;
	}

	if ( preg_match( '/papi\-standard\-\w+\-type/', $page_type->get_id() ) ) {
		return true;
	}

	$parent_page_type = papi_get_entry_type_by_meta_id( papi_get_parent_post_id() );

	if ( papi_is_page_type( $parent_page_type ) ) {
		$child_types = $parent_page_type->get_child_types();

		if ( ! empty( $child_types ) ) {
			return in_array( $page_type, $parent_page_type->get_child_types() );
		}
	}

	// Run show page type filter.
	return papi_filter_settings_show_page_type( $post_type, $page_type );
}

/**
 * Get all page types based on a post type.
 *
 * @param  string $post_type
 *
 * @return array
 */
function papi_get_all_page_types( $post_type = '' ) {
	$entry_types = papi_get_all_entry_types( [
		'args'  => $post_type,
		'mode'  => 'include',
		'types' => ['attachment', 'page']
	] );

	$page_types = array_filter( $entry_types, 'papi_is_page_type' );

	if ( is_array( $page_types ) ) {
		usort( $page_types, function ( $a, $b ) {
			return strcmp( $a->name, $b->name );
		} );
	}

	return papi_sort_order( array_reverse( $page_types ) );
}

/**
 * Get the data page.
 *
 * @param  int    $id
 * @param  string $type
 *
 * @return Papi_Core_Meta_Store|null
 */
function papi_get_page( $id = 0, $type = 'post' ) {
	return papi_get_meta_store( $id, $type );
}

/**
 * Get page type id by post id.
 *
 * @param  int $post_id
 *
 * @return string
 */
function papi_get_page_type_id( $post_id = 0, $type = 'post' ) {
	return papi_get_entry_type_id( $post_id, $type );
}

/**
 * Get the page type key that is used for each post.
 *
 * @return string
 */
function papi_get_page_type_key() {
	return defined( 'PAPI_PAGE_TYPE_KEY' ) ? PAPI_PAGE_TYPE_KEY : '_papi_page_type';
}

/**
 * Get the page type name.
 *
 * @param  int $post_id
 *
 * @return string
 */
function papi_get_page_type_name( $post_id = 0 ) {
	$post_id = papi_get_post_id( $post_id );

	if ( empty( $post_id ) ) {
		return '';
	}

	$entry_type_id = papi_get_page_type_id( $post_id );

	if ( empty( $entry_type_id ) ) {
		return '';
	}

	$entry_type = papi_get_entry_type_by_id( $entry_type_id );

	if ( empty( $entry_type ) ) {
		return '';
	}

	return $entry_type->name;
}

/**
 * Get all post types Papi should work with.
 *
 * @return array
 */
function papi_get_post_types() {
	$post_types = [];
	$page_types = papi_get_all_entry_types( [
		'types' => ['attachment', 'page']
	] );

	foreach ( $page_types as $page_type ) {
		$post_types = array_merge( $post_types, papi_to_array( $page_type->post_type ) );
	}

	return array_unique( $post_types );
}

/**
 * Check if `$obj` is a instanceof `Papi_Page_Type`.
 *
 * @param  mixed $obj
 *
 * @return bool
 */
function papi_is_page_type( $obj ) {
	return $obj instanceof Papi_Page_Type;
}

/**
 * Load the entry type id on a post types.
 *
 * @param  string $entry_type_id
 *
 * @return string
 */
function papi_load_page_type_id( $entry_type_id = '' ) {
	$key       = papi_get_page_type_key();
	$post_id   = papi_get_post_id();
	$post_type = papi_get_post_type( $post_id );

	// Try to load the entry type id from only page type filter.
	if ( empty( $entry_type_id ) ) {
		$entry_type_id = papi_filter_settings_only_page_type( $post_type );
	}

	// If we have a post id we can load the entry type id from the post.
	if ( empty( $entry_type_id ) && $post_id > 0 ) {
		$meta_value    = get_post_meta( $post_id, $key, true );
		$entry_type_id = empty( $meta_value ) ? '' : $meta_value;
	}

	// Try to fetch the entry type id from `page_type` query string.
	if ( empty( $entry_type_id ) ) {
		$entry_type_id = papi_get_qs( 'page_type' );
	}

	// When using `only_page_type` filter we need to fetch the value since it
	// maybe not always saved in the database.
	if ( empty( $entry_type_id ) ) {
		$entry_type_id = papi_filter_settings_only_page_type( $post_type );
	}

	// Load right entry type from the parent post id.
	if ( empty( $entry_type_id ) ) {
		$meta_value = get_post_meta( papi_get_parent_post_id(), $key, true );
		$entry_type_id = empty( $meta_value ) ? '' : $meta_value;
	}

	// Load entry type id from the container if it exists.
	if ( empty( $entry_type_id ) ) {
		$key = sprintf( 'entry_type_id.post_type.%s', $post_type );

		if ( papi()->exists( $key )  ) {
			return papi()->make( $key );
		}
	}

	return $entry_type_id;
}

add_filter( 'papi/entry_type_id', 'papi_load_page_type_id' );

/**
 * Set page type to a post.
 *
 * @param  mixed $post_id
 * @param  string $page_type
 *
 * @return bool
 */
function papi_set_page_type_id( $post_id, $page_type ) {
	if ( papi_entry_type_exists( $page_type ) ) {
		return update_post_meta( papi_get_post_id( $post_id ), papi_get_page_type_key(), $page_type );
	}

	return false;
}

/**
 * Echo the page type name.
 *
 * @param  int $post_id
 *
 * @return string
 */
function the_papi_page_type_name( $post_id = 0 ) {
	echo papi_get_page_type_name( $post_id );
}
