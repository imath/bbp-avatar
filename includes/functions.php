<?php

// Exit if accessed directly
if ( !defined( 'ABSPATH' ) ) exit;

/**
 * Gets the plugin's version
 *
 * @since bbp avatar (1.0-beta1)
 *
 * @uses  bbpress() to get plugin's global
 * @return string the version
 */
function bbpavatar_get_version() {
	return apply_filters( 'bbpavatar_get_version', bbpress()->extend->bbpavatar->version );
}

/**
 * Gets the plugin's url to the images folder
 *
 * @since bbp avatar (1.0-beta1)
 *
 * @uses  bbpress() to get plugin's global
 * @return string the url
 */
function bbpavatar_get_images_url() {
	return apply_filters( 'bbpavatar_get_images_url', bbpress()->extend->bbpavatar->images_url );
}

/**
 * Gets the plugin's url to the js folder
 *
 * @since bbp avatar (1.0-beta1)
 *
 * @uses  bbpress() to get plugin's global
 * @return string the url
 */
function bbpavatar_get_js_url() {
	return apply_filters( 'bbpavatar_get_js_url', bbpress()->extend->bbpavatar->js_url );
}

/**
 * Maybe updates the db version of the plugin
 * For a first install creates the bbpavatar holder draft post
 *
 * @since bbp avatar (1.0-beta1)
 *
 * @uses bbpavatar_get_version() to get plugin's version
 * @uses get_option() to get a blog's preference
 * @uses bbpavatar_get_attachments_holder() to check for the draft post
 * @uses bbpavatar_set_attachments_holder() to eventually create the draft post
 * @uses update_option() to save the blog's preference
 */
function bbpavatar_maybe_update() {
	if( bbpavatar_get_version() != get_option( 'bbp_avatar_version' ) ) {

		// Do we have our draft post to rely on for the users avatars ?
		$avatars_holder = bbpavatar_get_attachments_holder();

		// No then let's set it !
		if( empty( $avatars_holder ) )
			bbpavatar_set_attachments_holder();

		// Let's update the db version
		update_option( 'bbp_avatar_version', bbpavatar_get_version() );
	}
}

/**
 * Do we have the bbpavatar post to rely on ?
 *
 * @since bbp avatar (1.0-beta1)
 *
 * @uses get_option() to get a blog's preference
 * @return interger 0| the post_id
 */
function bbpavatar_get_attachments_holder() {
	$attachement_holder = get_option( 'bbp_avatar_holder', 0 );

	return intval( $attachement_holder );
}

/**
 * Creates a draft post to attach the avatars to
 *
 * @since bbp avatar (1.0-beta1)
 * 
 * @uses wp_insert_post() to create the avatars holder post
 * @uses update_option() to save a blog's preference
 */
function bbpavatar_set_attachments_holder() {

	$bbpavatar_page_content = '<p>' . __( 'bbPress Avatar uses this draft post to attach the avatars of your bbPress users, please leave it as is. It will not show on your blog.', 'bbp-avatar' ) .'</p>';
	$bbpavatar_page_content .= '<p>' . __( 'The images you will attach to this post will be available for users as avatar suggestions', 'bbp-avatar' ) .'</p>';

	$bbpavatar_args = array(
		'comment_status' => 'closed', 
		'ping_status'    => 'closed', 
		'post_title'     => __( 'bbPress Avatar utility - Do not delete', 'bbp-avatar' ),
		'post_content'   => $bbpavatar_page_content,
		'post_status'    => 'draft', 
		'post_type'      => 'post'
	);

	$bbpavatar_post_id = wp_insert_post( $bbpavatar_args );

	if( !empty( $bbpavatar_post_id ) )
		update_option( 'bbp_avatar_holder', $bbpavatar_post_id );
}

/**
 * What is the prefered max width ?
 *
 * @since bbp avatar (1.0-beta1)
 *
 * @uses get_option() to get a blog's preference
 * @return string the max width
 */
function bbpavatar_full_width() {
	$fullwidth = get_option( '_bbpavatar_fullwidth_avatar', 150 );
	return $fullwidth. 'px';
}

/**
 * What is the prefered max height ?
 *
 * @since bbp avatar (1.0-beta1)
 *
 * @uses get_option() to get a blog's preference
 * @return string the max height
 */
function bbpavatar_full_height() {
	$fullheight = get_option( '_bbpavatar_fullheight_avatar', 150 );
	return $fullheight. 'px';
}

/**
 * Should we use gravatar ?
 *
 * @since bbp avatar (1.0-beta1)
 *
 * @uses get_option() to get a blog's preference
 * @return integer 1|0
 */
function bbpavatar_no_gravatar() {
	$gravatar = get_option( '_bbpavatar_no_gravatar', 0 );
	return $gravatar;
}

/**
 * Returns the avatar or the gravatar for the user
 *
 * inpired by BuddyPress bp_core_fetch_avatar()
 *
 * @since bbp avatar (1.0-beta1)
 *
 * @param  array $args the avatar settings
 * @uses apply_filters() to let theme or plugin to customize some values
 * @uses bbpavatar_get_images_url() to get plugin's images directory url
 * @uses wp_parse_args() to merge user defined arguments into defaults array.
 * @uses bbp_get_displayed_user_id() to get displayed user id
 * @uses bbp_get_user_nicename() to get user's nice name
 * @uses esc_attr() to sanitize data
 * @uses bbpavatar_full_width() to get the full width settings
 * @uses bbpavatar_full_height() to get the full height settings
 * @uses get_user_meta() to get user's avatar preferences
 * @uses wp_upload_dir() to get infos about the upload dir (baseurl & basedir)
 * @uses wp_get_attachment_image_src() to retrieve an image to represent an attachment.
 * @uses delete_user_meta() to eventually delete the preferences
 * @uses bbpavatar_no_gravatar() to check for gravatar option (should we use it or not?)
 * @uses get_option() to get a blog preference setting
 * @return string url of the avatar|img html output
 */
function bbpavatar_get_avatar( $args = '' ) {

	// Set a few default variables
	$def_class  = 'avatar';
	$gravatar = false;
	$default_avatar = apply_filters( 'bbpavatar_get_avatar_default', bbpavatar_get_images_url() . 'avatar.png' );

	// Set the default variables array
	$params = wp_parse_args( $args, array(
		'user_id'    => false,
		'width'      => false,       // Custom width (int)
		'height'     => false,       // Custom height (int)
		'class'      => $def_class,  // Custom <img> class (string)
		'alt'        => '',    	     // Custom <img> alt (string)
		'email'      => false,       // Pass the user email (for gravatar) to prevent querying the DB for it
		'html'       => true,        // Wrap the return img URL in <img />
		'title'      => ''           // Custom <img> title (string)
	) );
	extract( $params, EXTR_SKIP );

	/** Set item_id ***********************************************************/

	if ( empty( $user_id ) ) {

		$user_id = bbp_get_displayed_user_id();

		if ( empty( $user_id ) ) 
			$gravatar = apply_filters( "bbpavatar_get_avatar_empty", bbpavatar_get_images_url() . 'avatar.png', $params );
	}

	/** <img> alt *************************************************************/

	if ( false !== strpos( $alt, '%s' ) || false !== strpos( $alt, '%1$s' ) ) {

		$user_name = bbp_get_user_nicename( $user_id );
		$user_name = apply_filters( 'bbpavatar_get_avatar_alt', $user_name, $user_id, $params );
		$alt       = sprintf( $alt, $item_name );
	}

	/** Sanity Checks *********************************************************/

	// Get a fallback for the 'alt' parameter
	if ( empty( $alt ) )
		$alt = __( 'Avatar Image', 'bbp-avatar' );

	$html_alt = ' alt="' . esc_attr( $alt ) . '"';

	// Set title tag, if it's been provided
	if ( !empty( $title ) ) {
		$title = " title='" . esc_attr( apply_filters( 'bbpavatar_get_avatar_title', $title, $user_id, $params ) ) . "'";
	}

	// Set image width
	if ( false !== $width ) {
		$html_width = ' width="' . $width . '"';
	} else {
		$html_width = ' width="' . bbpavatar_full_width() . '"';
	}

	// Set image height
	if ( false !== $height ) {
		$html_height = ' height="' . $height . '"';
	} else {
		$html_height = ' height="' . bbpavatar_full_height() . '"';
	}

	
	$bbpavatar_user_data = get_user_meta( $user_id, '_bbpavatar_user_data', true );

	if( !empty( $bbpavatar_user_data ) && is_object( $bbpavatar_user_data ) ) {

		$upload_datas = wp_upload_dir();
		$avatar_file = trailingslashit( $upload_datas["basedir"] ) . $bbpavatar_user_data->path;
		$avatar_url = trailingslashit( $upload_datas["baseurl"] ) . $bbpavatar_user_data->path;

		if( file_exists( $avatar_file ) )
			$gravatar = apply_filters( "bbpavatar_get_avatar_found", $avatar_url, $params );
		else {
			$attachment = wp_get_attachment_image_src( $bbpavatar_user_data->id, 'thumbnail' );

			if ( !empty( $attachment ) && is_array( $attachment ) ) {
				$gravatar = apply_filters( "bbpavatar_get_avatar_found", $attachment[0], $params );
			}

		}
	}

	if( empty( $gravatar ) && bbpavatar_no_gravatar() ){
		$gravatar = $default_avatar;
	} 

	if( empty( $gravatar ) ) {

		// if we don't need to simply get url, default to get_avatar()
		if ( true === $html )
			return false;

		// Set gravatar type
		$default = get_option( 'avatar_default' );
		
		if( !empty( $default ) )
			$default_avatar = $default; 

		// Set gravatar object
		if ( empty( $email ) ) {
			$user  = get_userdata( $user_id );
			$email = !empty( $user->user_email ) ? $user->user_email : '';
		}

		if( empty( $width ) )
			$width = 50;	

		// Set host based on if using ssl
		$host = 'http://gravatar.com/avatar/';
		if ( is_ssl() ) {
			$host = 'https://secure.gravatar.com/avatar/';
		}

		// Filter gravatar vars
		$email    = apply_filters( 'bbpavatar_get_avatar_email', $email, $user_id );
		$gravatar = apply_filters( 'bbpavatar_get_avatar_url', $host ) . md5( strtolower( $email ) ) . '?d=' . $default_avatar . '&amp;s=' . $width;

		// Gravatar rating; http://bit.ly/89QxZA
		$rating = get_option( 'avatar_rating' );
		if ( ! empty( $rating ) ) {
			$gravatar .= "&amp;r={$rating}";
		}
	}

	if ( true === $html ) {
		return apply_filters( 'bbpavatar_get_avatar', '<img src="' . $gravatar . '" class="' . esc_attr( $class ) . '"' . $html_width . $html_height . $html_alt . $title . ' />', $params, $user_id, $html_width, $html_height );
	} else {
		return apply_filters( 'bbpavatar_get_avatar_url', $gravatar );
	}
}

/**
 * Appends avatar fields to the user's edit form (front)
 *
 * @since bbp avatar (1.0-beta1)
 *
 * @uses bbp_get_displayed_user_id() to get displayed user id
 * @uses get_user_meta() to get user's avatar preferences
 * @return string html part for the fields/button
 */
function bbpavatar_load_form() {
	$bbpavatar_user_data = get_user_meta( bbp_get_displayed_user_id(), '_bbpavatar_user_data', true );
	?>
	<div class="stag-metabox-table" style="text-align:center">
			
		<input type="hidden" name="bbpavatar[_avatar_id]" id="_bbpavatar" />
		<input type="hidden" name="bbpavatar[_avatar_path]" id="_bbpavatar_thumbnail" />

			<?php if( empty( $bbpavatar_user_data ) || !is_object( $bbpavatar_user_data ) ):?>
		 	
		 		<p><input type="button" class="button submit user-submit" id="_bbpavatar_button" value="<?php esc_attr_e( 'Upload your Avatar', 'bbp-avatar');?>"/></p>
		  
		  		<div id="preview"></div>

		  	<?php else:?>

		  		<p><input type="button" class="button submit user-submit" id="_bbpavatar_button" value="<?php esc_attr_e( 'Change your Avatar', 'bbp-avatar');?>"/></p>
		  		
		  		<div id="preview">
		  			<input type="checkbox" name="bbpavatar[_avatar_delete]" value="1" class="checkbox">
		  			<?php _e( 'Delete your Avatar', 'bbp-avatar' );?>
		  			
		  		</div>

		  	<?php endif;?>

	</div>
	<?php

}

/**
 * Hooks bbPress profile actions to save user's avatar preferences
 *
 * @since bbp avatar (1.0-beta1)
 * 
 * @param  integer $user_id the id of the user being edited
 * @uses bbp_get_displayed_user_id() to get displayed user id
 * @uses get_user_meta() to get user's avatar preferences
 * @uses delete_user_meta() to eventually delete the preferences
 * @uses get_post_field() to check for attachment (avatar) author before deleting it
 * @uses wp_delete_attachment() to delete the attachment (avatar)
 * @uses sanitize_text_field() to sanitize the path
 * @uses wp_upload_dir() to get infos about the upload dir (baseurl)
 * @uses trailingslashit() to append a trailing slash.
 * @uses update_user_meta() to save user's preferences
 */
function bbpavatar_handle_profile_update( $user_id = 0 ) {

	if( empty( $user_id ) )
		$user_id = bbp_get_displayed_user_id();

	if( empty( $user_id ) )
		return;

	$previous_user_data = get_user_meta( $user_id, '_bbpavatar_user_data', true );

	if( !empty( $_POST['bbpavatar']['_avatar_delete'] ) ) {
		// we need to delete the avatar !
		delete_user_meta( $user_id, '_bbpavatar_user_data' );

		if( !empty( $previous_user_data->id ) && $user_id == get_post_field( 'post_author', $previous_user_data->id ) )
			wp_delete_attachment( $previous_user_data->id, true );

		return;
	}

	$attachment_id = !empty( $_POST['bbpavatar']['_avatar_id'] ) ? intval( $_POST['bbpavatar']['_avatar_id'] ) : 0;
	$attachment_path = !empty( $_POST['bbpavatar']['_avatar_path'] ) ? sanitize_text_field( $_POST['bbpavatar']['_avatar_path'] ) : '';

	if( empty( $attachment_id ) || empty( $attachment_path ) )
		return;

	$upload_datas = wp_upload_dir();
	$base_url = trailingslashit( $upload_datas['baseurl'] );

	$attachment_path = str_replace( $base_url, '', $attachment_path );

	$avatar_object = new stdClass();
	$avatar_object->id = $attachment_id;
	$avatar_object->path = $attachment_path;

	update_user_meta( $user_id, '_bbpavatar_user_data', $avatar_object );

	if( !empty( $previous_user_data->id ) && $previous_user_data->id != $avatar_object->id  && $user_id == get_post_field( 'post_author', $previous_user_data->id ) )
		wp_delete_attachment( $previous_user_data->id, true );
}


/**
 * Handles avatar uploads and replace built in media function (wp_ajax_upload_attachment)
 *
 * @since bbp avatar (1.0-beta1)
 *
 * @uses check_ajax_referer() security check
 * @uses wp_check_filetype_and_ext() to determine the real file type of a file
 * @uses wp_match_mime_types() to check a MIME-Type is image
 * @uses media_handle_upload() handles the file upload POST itself, creating the attachment post.
 * @uses is_wp_error() checks whether variable is a WordPress Error.
 * @uses wp_die() to kill WordPress execution
 * @uses wp_prepare_attachment_for_js() prepares an attachment post object for JS (json)
 * @return string result as a json array
 */
function bbpavatar_handle_upload() {

	check_ajax_referer( 'media-form' );

	if ( isset( $_REQUEST['post_id'] ) ) {
		$post_id = $_REQUEST['post_id'];
	} else {
		$post_id = null;
	}

	$post_data = isset( $_REQUEST['post_data'] ) ? $_REQUEST['post_data'] : array();

	// make sure the uploaded file is an image.
	
	$wp_filetype = wp_check_filetype_and_ext( $_FILES['bbpavatar']['tmp_name'], $_FILES['bbpavatar']['name'], false );

	if ( ! wp_match_mime_types( 'image', $wp_filetype['type'] ) ) {
		echo json_encode( array(
			'success' => false,
			'data'    => array(
				'message'  => __( 'The uploaded file is not a valid image. Please try again.' ),
				'filename' => $_FILES['bbpavatar']['name'],
			)
		) );

		wp_die();
	}

	$attachment_id = media_handle_upload( 'bbpavatar', $post_id, $post_data );

	if ( is_wp_error( $attachment_id ) ) {
		echo json_encode( array(
			'success' => false,
			'data'    => array(
				'message'  => $attachment_id->get_error_message(),
				'filename' => $_FILES['bbpavatar']['name'],
			)
		) );

		wp_die();
	}

	if ( ! $attachment = wp_prepare_attachment_for_js( $attachment_id ) )
		wp_die();

	echo json_encode( array(
		'success' => true,
		'data'    => $attachment,
	) );

	wp_die();
}

/**
 * Checks if the query is an avatar one
 *
 * @since bbp avatar (1.0-beta1)
 *
 * @uses bbpavatar_get_attachments_holder() to get the draft post avatar suggestions are attached to
 * @return boolean true|false
 */
function bbpavatar_is_avatar_query() {
	$retval = true;

	// are we querying attachments ?
	if( empty( $_POST['action'] ) || $_POST['action'] != 'query-attachments' )
		$retval = false;

	// are we querying attachments for the bbpavatar post ?
	if( empty( $_POST['post_id'] ) || $_POST['post_id'] != bbpavatar_get_attachments_holder() )
		$retval = false;

	return $retval;
}

/**
 * Modifies the WordPress query to only display avatar uploaded by the user
 * and eventually the suggestions of avatars attached to the draft post
 *
 * @since bbp avatar (1.0-beta1)
 * 
 * @param  object $wp_query the WordPress query
 * @uses current_user_can() to check for user's capabilities
 * @uses bbpavatar_is_avatar_query() to check the ajax query is an avatar one
 * @uses bbpavatar_get_attachments_holder() to get the draft post avatar suggestions are attached to
 * @uses get_post_field() to get the author id of the draft post
 * @uses bbp_get_current_user_id() to get logged in user's id
 * @uses get_user_meta() to get the attachment id of draft post author's to exclude it from search
 */
function bbpavatar_user_files_only( $wp_query ) {

	// editor
	if( current_user_can( 'edit_others_posts' ) )
		return;

	if( !bbpavatar_is_avatar_query() )
		return;

	$avatars_holder = bbpavatar_get_attachments_holder();

	if( !empty( $avatars_holder ) ) {

		$wp_query->set( 'post_parent', $avatars_holder );

		// using this allows the author of the avatars holder post to attach avatar suggestions
		$author_parent_id = get_post_field( 'post_author', $avatars_holder );

		$wp_query->set( 'author', bbp_get_current_user_id() .','. $author_parent_id );
		$bbpavatar_user_data = get_user_meta( $author_parent_id, '_bbpavatar_user_data', true );

		if( !empty( $bbpavatar_user_data ) && is_object( $bbpavatar_user_data ) )
			$wp_query->set( 'post__not_in', array( $bbpavatar_user_data->id ) );

	} else {
		$wp_query->set( 'author', bbp_get_current_user_id() );
	}
	
}
