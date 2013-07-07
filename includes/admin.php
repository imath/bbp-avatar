<?php

// Exit if accessed directly
if ( !defined( 'ABSPATH' ) ) exit;

/**
 * Registering a setting section in bbPress
 *
 * @since bbp avatar (1.0-beta1)
 *
 * @param array $settings_sections the bbPress settings sections
 * @return array the settings sections enrich by ours
 */
function bbpavatar_admin_settings_section( $settings_sections ) {

	$settings_sections['bbpavatar_settings'] = array(
		'title'    => __( 'Avatar Settings', 'bbp-avatar' ),
		'callback' => 'bbpavatar_admin_setting_section_callback',
		'page'     => 'bbpress',
	);

	return $settings_sections;
}

/**
 * bbp avatar settings section description
 *
 * @since bbp avatar (1.0-beta1)
 * 
 * @uses bbpavatar_get_attachments_holder() to get the draft post id
 * @uses get_post_field() to get the post author of the draft post
 * @uses bbp_get_user_nicename() to his user nicename
 */
function bbpavatar_admin_setting_section_callback() {
	$avatars_holder = bbpavatar_get_attachments_holder();
	$author_parent_name = false;

	if( !empty( $avatars_holder ) ) {
		$author_parent_id = get_post_field( 'post_author', $avatars_holder );
		$author_parent_name = bbp_get_user_nicename( $author_parent_id );
	}
	
	?>

	<p><?php _e( 'Avatar settings to customize its behavior.', 'bbp-avatar' ); ?></p>

	<?php if( !empty( $author_parent_name ) ) :?>

		<p class="description">
			<?php printf( __( 'The author of the bbpavatar draft posts is : %s, he is able to upload avatar suggestions for the forum participants', 'bbp-avatar' ), '<strong>'. $author_parent_name . '</strong>' ); ?>
		</p>

	<?php endif;
}

/**
 * Registering fields for our settings section
 *
 * @since bbp avatar (1.0-beta1)
 * 
 * @param  array $setting_fields the bbPress settings fields
 * @return array the setting fields enrich by ours
 */
function bbpavatar_get_settings_fields( $setting_fields ) {

	$setting_fields['bbpavatar_settings'] = array(

		// Disable gravatar ?
		'_bbpavatar_no_gravatar' => array(
			'title'             => __( 'Gravatar', 'bbp-avatar' ),
			'callback'          => 'bbpavatar_setting_callback_gravatar',
			'sanitize_callback' => 'intval',
			'args'              => array()
		),

		// Replace edit profile link ?
		'_bbpavatar_bbpress_profile' => array(
			'title'             => __( 'Edit Prodile link', 'bbp-avatar' ),
			'callback'          => 'bbpavatar_setting_callback_bbpress_profile',
			'sanitize_callback' => 'intval',
			'args'              => array()
		),

		// customize full width ?
		'_bbpavatar_fullwidth_avatar' => array(
			'title'             => __( 'Full width for the avatar', 'bbp-avatar' ),
			'callback'          => 'bbpavatar_setting_callback_fullwidth',
			'sanitize_callback' => 'intval',
			'args'              => array()
		),

		// customize full height ?
		'_bbpavatar_fullheight_avatar' => array(
			'title'             => __( 'Full height for the avatar', 'bbp-avatar' ),
			'callback'          => 'bbpavatar_setting_callback_fullheight',
			'sanitize_callback' => 'intval',
			'args'              => array()
		)
	);

	return $setting_fields;
}

/**
 * Allow the neutralization of Gravatar
 *
 * @since bbp avatar (1.0-beta1)
 *
 * @uses checked() To display the checked attribute
 */
function bbpavatar_setting_callback_gravatar() {
	$current = get_option( '_bbpavatar_no_gravatar', 0 );
?>

	<input id="_bbpavatar_no_gravatar" name="_bbpavatar_no_gravatar" type="checkbox" value="1" <?php checked( 1, $current );?> />
	<label for="_bbpavatar_no_gravatar"><?php _e( 'Disable.', 'bbp-avatar' ); ?></label>

<?php
}

/**
 * Allows the admin to replace WordPress edit profile link by bbPress one
 *
 * @since bbp avatar (1.0-beta1)
 *
 * @uses checked() To display the checked attribute
 */
function bbpavatar_setting_callback_bbpress_profile() {
$current = get_option( '_bbpavatar_bbpress_profile', 0 );
?>

	<input id="_bbpavatar_bbpress_profile" name="_bbpavatar_bbpress_profile" type="checkbox" value="1" <?php checked( 1, $current );?> />
	<label for="_bbpavatar_bbpress_profile"><?php _e( 'Replace WordPress edit profile link (admin) with bbPress one (front)', 'bbp-avatar' ); ?></label>

<?php
}

/**
 * Customize the fullwidth for avatars
 *
 * @since bbp avatar (1.0-beta1)
 */
function bbpavatar_setting_callback_fullwidth() {
	$current = get_option( '_bbpavatar_fullwidth_avatar', 150 );
?>

	<input name="_bbpavatar_fullwidth_avatar" type="number" min="100" step="2" id="_bbpavatar_fullwidth_avatar" value="<?php echo intval( $current ); ?>" class="small-text" />
	<label for="_bbpavatar_fullwidth_avatar"><?php _e( 'pixels', 'bbp-avatar' ); ?></label>

<?php
}

/**
 * Customize the fullwidth for avatars
 *
 * @since bbp avatar (1.0-beta1)
 */
function bbpavatar_setting_callback_fullheight() {
	$current = get_option( '_bbpavatar_fullheight_avatar', 150 );
?>

	<input name="_bbpavatar_fullheight_avatar" type="number" min="100" step="2" id="_bbpavatar_fullheight_avatar" value="<?php echo intval( $current ); ?>" class="small-text" />
	<label for="_bbpavatar_fullheight_avatar"><?php _e( 'pixels', 'bbp-avatar' ); ?></label>

<?php
}