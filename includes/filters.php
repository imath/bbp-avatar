<?php

// Exit if accessed directly
if ( !defined( 'ABSPATH' ) ) exit;

/**
 * Filters the WordPress user's profile link to eventually replace it with bbPress one
 *
 * @since bbp avatar (1.0-beta1)
 * 
 * @param  string  $url    the link to WordPress user's profile
 * @param  integer $user   the user id
 * @uses is_admin() to check if we're in backend
 * @uses get_option() to get blog's preference
 * @uses bbp_get_user_profile_edit_url() to get the bbPress profile url for the user
 * @return string  the link to bbPress user's profile or WordPress one
 */
function bbpavatar_edit_profile_url( $url = '', $user = 0 ) {
	
	if( is_admin() )
		return $url;

	$use_bbpress_front_profile = get_option( '_bbpavatar_bbpress_profile', 0 );

	$url = !empty( $use_bbpress_front_profile ) ? bbp_get_user_profile_edit_url( $user ) : $url ;

	return $url;
}

/**
 * Filters get_avatar to eventually replace gravatar
 *
 * inspired by BuddyPress bp_core_fetch_avatar_filter() function
 *
 * @since bbp avatar (1.0-beta1)
 * 
 * @param  string $avatar  html part for the gravatar (img)
 * @param  object|integer|string $user user, id or email
 * @param  string $size    the size of the avatar
 * @param  string $default the default avatar to fall back to
 * @param  string $alt     alternative text
 * @uses get_user_by() to retrieve user info by a given field
 * @uses bbp_get_user_nicename() to his user nicename
 * @uses bbpavatar_get_avatar() to build the avatar
 * @return string html for the avatar or simply the link
 */
function bbpavatar_get_avatar_filter( $avatar, $user, $size, $default, $alt = '' ) {
	global $pagenow;

	// Do not filter if inside WordPress options page
	if ( 'options-discussion.php' == $pagenow )
		return $avatar;

	// If passed an object, assume $user->user_id
	if ( is_object( $user ) ) {
		$id = $user->user_id;

	// If passed a number, assume it was a $user_id
	} else if ( is_numeric( $user ) ) {
		$id = $user;

	// If passed a string and that string returns a user, get the $id
	} elseif ( is_string( $user ) && ( $user_by_email = get_user_by( 'email', $user ) ) ) {
		$id = $user_by_email->ID;
	}

	// If somehow $id hasn't been assigned, return the result of get_avatar
	if ( empty( $id ) ) {
		return !empty( $avatar ) ? $avatar : $default;
	}

	// Image alt tag
	if ( empty( $alt ) ) {
		$alt = sprintf( __( 'Avatar of %s', 'buddypress' ), bbp_get_user_nicename( $id ) );
	}

	// Let BuddyPress handle the fetching of the avatar
	$bbpavatar = bbpavatar_get_avatar( array( 'user_id' => $id, 'width' => $size, 'height' => $size, 'alt' => $alt ) );

	// If BuddyPress found an avatar, use it. If not, use the result of get_avatar
	return ( !$bbpavatar ) ? $avatar : $bbpavatar;
}


/**
 * Filters the plupload settings for our values
 *
 * @since bbp avatar (1.0-beta1)
 * 
 * @param  array  $plupload_settings the plupload settings
 * @uses bbp_is_single_user_edit() to check we're on the edit front part of the user's profile
 * @uses admin_url() to build the url to ajax url
 * @return array  the settings customized by ours
 */
function bbpavatar_new_plupload_settings( $plupload_settings = array() ) {

	if( !bbp_is_single_user_edit() )
		return $plupload_settings;

	$plupload_settings['file_data_name']   = 'bbpavatar';
	$plupload_settings['url']              = admin_url( 'admin-ajax.php', 'relative' );
	$plupload_settings['filters']          = array( array('title' => __( 'Allowed Files' ), 'extensions' => 'jpeg,jpg,gif,png') );
	$plupload_settings['multi_selection']  = false;

	return $plupload_settings;
}

/**
 * Filters the plupload params to change the ajax action
 *
 * @since bbp avatar (1.0-beta1)
 * 
 * @param  array  $plupload_params the plupload params
 * @uses bbp_is_single_user_edit() to check we're on the edit front part of the user's profile
 * @return array  the params customized by ours
 */
function bbpavatar_new_plupload_params( $plupload_params ) {
	if( !bbp_is_single_user_edit() )
		return $plupload_params;

	$plupload_params = array(
		'action' => 'bbpavatar_upload'
	);

	return $plupload_params;
}

/**
 * Maps capabilities to temporarly allow regular users to manage their avatar
 *
 * @since bbp avatar (1.0-beta1)
 * 
 * @param array $caps Capabilities for meta capability
 * @param string $cap Capability name
 * @param integer $user_id User id
 * @param mixed $args Arguments
 * @uses bbpavatar_is_avatar_query() to check we're in an Avatar query
 * @uses get_post() to retrieve attachment data to check author id
 * @return array the filtered capabilities
 */
function bbpavatar_map_meta_cap( $caps = array(), $cap = '', $user_id = 0, $args = array() ) {

	if( $cap == 'bbpavatar_settings' )
		$caps = array( 'manage_options' );

	if( $cap == 'upload_files' ) {

		if( bbpavatar_is_avatar_query() ) {
			$caps = array( 'participate' );
		}

	}

	if( in_array( $cap, array( 'delete_post', 'edit_post' ) ) ) {
		$_post = get_post( $args[0] );

		if ( !empty( $_post ) ) {

			// Get caps for post type object
			if( $_post->post_type != 'attachment' )
				return $caps;

			// we have an attachment author
			if ( (int) $user_id === (int) $_post->post_author )
				$caps = array( 'participate' );

		}
	}

	return $caps;
}