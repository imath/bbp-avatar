<?php
/* 
Plugin Name: bbPress Avatar
Plugin URI: http://imathi.eu/tag/bbp-avatar/
Description: Extends bbPress to manage Avatars locally (upload/get)
Version: 1.0-beta1
Author: imath
Author URI: http://imathi.eu
License: GPLv2
Text Domain: bbp-avatar
Domain Path: /languages/
*/

// Exit if accessed directly
if ( !defined( 'ABSPATH' ) ) exit;

if ( !class_exists( 'bbPress_Avatar_Component' ) ) :
/**
 * Main bbPress_Avatar_Component Class
 *
 * "Je m'accroche à bbpress()->extend...
 * .. car je suis une extension bbPress !"
 *
 * @since bbp avatar (1.0-beta1)
 */
class bbPress_Avatar_Component {

	/**
	 * @var string version of the plugin
	 */
	public $version = '';

	/**
	 * @var string Path to the plugin's directory
	 */
	public $plugin_dir = '';

	/**
	 * @var string dir to the plugin's includes directory
	 */
	public $includes_dir = '';

	/** URLs ******************************************************************/

	/**
	 * @var string URL to the plugin's directory
	 */
	public $plugin_url = '';

	/**
	 * @var string URL to the plugin's js images directory
	 */
	public $images_url = '';

	/**
	 * @var string URL to the plugin's js directory
	 */
	public $js_url = '';

	/** Translation domain *****************************************************/

	/**
	 * @var string text domain of the plugin
	 */
	public $domain = '';

	/**
	 * The constructor
	 *
	 * @since bbp avatar (1.0-beta1)
	 */
	function __construct() {
		$this->setup_globals();
		$this->includes();
		$this->setup_actions();
		$this->setup_filters();
	}

	/**
	 * Set plugin's globals.
	 *
	 * @since bbp avatar (1.0-beta1)
	 * @access private
	 * 
	 * @uses plugin_dir_path() To generate bbp avatar plugin path
	 * @uses plugin_dir_url() To generate bbp avatar plugin url
	 */
	private function setup_globals() {
		$this->version      = '1.0-beta1';
		$this->plugin_dir   = plugin_dir_path( __FILE__ );
		$this->plugin_url   = plugin_dir_url ( __FILE__ );
		$this->includes_dir = trailingslashit( $this->plugin_dir . 'includes' );
		$this->images_url   = trailingslashit( $this->plugin_url . 'images'   );
		$this->js_url       = trailingslashit( $this->plugin_url . 'js'       );
		$this->domain       = 'bbp-avatar';
	}

	/**
	 * Include required files
	 *
	 * @since bbp avatar (1.0-beta1)
	 * @access private
	 */
	private function includes() {
		require( $this->includes_dir . 'functions.php' );
		require( $this->includes_dir . 'filters.php'   );

		if( is_admin() )
			require( $this->includes_dir . 'admin.php' );
	}

	/**
	 * Setup the admin hooks, actions and filters
	 *
	 * @since bbp avatar (1.0-beta1)
	 * @access private
	 *
	 * @uses add_action() To add various actions
	 */
	private function setup_actions() {

		// Bail to prevent interfering with the deactivation process
		if ( bbp_is_deactivation() )
			return;

		// stores the db version and eventually creates the avatars holder post
		add_action( 'bbp_admin_menu',             'bbpavatar_maybe_update'                 );

		// prints a css rule to highlight the avatars holder post
		add_action( 'admin_print_styles-edit.php', array( $this, 'print_css' )             );

		// enqueues the new media uploader js scripts and our custom one
		add_action( 'bbp_enqueue_scripts',         array( $this, 'enqueue_scripts' )       );

		// add some html to the bbPress user edit form
		add_action( 'bbp_user_edit_after_name',    'bbpavatar_load_form'                    );

		// Saves the Avatar infos from the user edit form
		add_action( 'personal_options_update',     'bbpavatar_handle_profile_update', 10, 1 );
		add_action( 'edit_user_profile_update',    'bbpavatar_handle_profile_update', 10, 1 );

		// Catches the Ajax media submission specific to bbp avatar
		add_action( 'wp_ajax_bbpavatar_upload',    'bbpavatar_handle_upload'                );

		/* Customize the query in order to get avatars uploaded by current user
		   and the eventual avatar suggestions */
		add_action( 'parse_query',                 'bbpavatar_user_files_only',       10, 1 );

		// Loads the translation
		add_action( 'bbp_init', 			       array( $this, 'load_textdomain'),   7    );

	}

	/**
	 * Setup the filters
	 *
	 * @since bbp avatar (1.0-beta1)
	 * @access private
	 *
	 * @uses add_filter() To add various filters
	 */
	private function setup_filters() {

		// On front changes the profile edit url (Admin Bar) for the bbPress one 
		add_filter( 'edit_profile_url',                'bbpavatar_edit_profile_url',       10, 2 );

		// Controls wether to load internal Avatars or Gravatars
		add_filter( 'get_avatar',                      'bbpavatar_get_avatar_filter',      10, 5 );

		// Customize the default settings of WordPress built in uploading system
		add_filter( 'plupload_default_settings',       'bbpavatar_new_plupload_settings',  10, 1 );

		// Replaces the ajax action for the uploaded avatars
		add_filter( 'plupload_default_params',         'bbpavatar_new_plupload_params',    10, 1 );

		// Maps some key capabilities to avoid role creation or temp capabilities
		add_filter( 'map_meta_cap',                    'bbpavatar_map_meta_cap',           10, 4 );

		// Registers a new section in the bbPress settings
		add_filter( 'bbp_admin_get_settings_sections', 'bbpavatar_admin_settings_section', 10, 1 );

		// Registers the fields for the registered bbPress settings section
		add_filter( 'bbp_admin_get_settings_fields',   'bbpavatar_get_settings_fields',    10, 1 );
	}

	/**
	 * Prints a css rule to highlight the Avatars holder draft post
	 *
	 * @since bbp avatar (1.0-beta1)
	 * @access public
	 * 
	 * @uses get_current_screen() to be sure we're on the list of posts
	 * @uses bbpavatar_get_attachments_holder() to get the draft post id
	 * @return string a css rule to highlight the draft post
	 */
	public function print_css() {
		if ( !isset( get_current_screen()->post_type ) || ( 'post' != get_current_screen()->post_type ) )
			return false;

		$avatars_holder = bbpavatar_get_attachments_holder();

		if( !empty( $avatars_holder ) ) : ?>

			<style type="text/css" media="screen">
			/*<![CDATA[*/
				tr#post-<?php echo $avatars_holder;?> {
					background-color: #ffffe0;
				}
			/*]]>*/
			</style>

		<?php endif;
	}

	/**
	 * Loads the needed javascripts for avatar uploads
	 *
	 * @since bbp avatar (1.0-beta1)
	 * @access public
	 * 
	 * @uses bbp_is_single_user_edit() to check we're on the edit front part of the user's profile
	 * @uses wp_enqueue_media() to enqueue all scripts, styles, settings, and templates necessary to use all media JS APIs.
	 * @uses wp_enqueue_script() to register the script and enqueues.
	 * @uses bbpavatar_get_js_url() to get the url to the plugin's js folder
	 * @uses bbpavatar_get_version() to get plugin's version
	 * @uses wp_localize_script() to localize a script, here we'll use it to send the avatars holder post id
	 * @uses bbpavatar_get_attachments_holder() to get the draft post id
	 */
	public function enqueue_scripts() {
		if( bbp_is_single_user_edit() ) {
			wp_enqueue_media();

			wp_enqueue_script( 'bbp-avatar-js', bbpavatar_get_js_url() . 'bbp-avatar.js', array( 'media-editor' ), true, bbpavatar_get_version() );
			wp_localize_script( 'bbp-avatar-js', 'bbpavatar_vars', array( 'post_id' => bbpavatar_get_attachments_holder() ) );
		}
	}

	/**
	 * Loads the translation files
	 *
	 * @since bbp avatar (1.0-beta1)
	 * @access public
	 * 
	 * @uses get_locale() to get the language of WordPress config
	 * @uses load_texdomain() to load the translation if any is available for the language
	 */
	public function load_textdomain() {
		// try to get locale
		$locale = apply_filters( 'bbpavatar_load_textdomain_get_locale', get_locale() );

		// if we found a locale, try to load .mo file
		if ( !empty( $locale ) ) {
			// default .mo file path
			$mofile_default = sprintf( '%s/languages/%s-%s.mo', $this->plugin_dir, $this->domain, $locale );
			// final filtered file path
			$mofile = apply_filters( 'bbpavatar_textdomain_mofile', $mofile_default );
			// make sure file exists, and load it
			if ( file_exists( $mofile ) ) {
				load_textdomain( $this->domain, $mofile );
			}
		}
	}
}

/**
 * Waits for bbPress to be ready before extending it
 * with a local Avatar management system.
 *
 * @since bbp avatar (1.0-beta1)
 * 
 * @uses bbpress() to register the plugin into the extend part of bbPress
 */
function bbpavatar() {
	bbpress()->extend->bbpavatar = new bbPress_Avatar_Component();
}

add_action( 'bbp_ready', 'bbpavatar' );

endif;
