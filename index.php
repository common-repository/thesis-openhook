<?php
/*
 * Plugin Name: OpenHook
 * Plugin URI: https://rickbeckman.org/openhook/
 * Description: OpenHook opens the door to future-proof customization of WordPress and every hook-enabled theme or plugin by storing your custom actions safely in your database. OpenHook also provides a handful of useful shortcodes for even more customization possibility!
 * Version: 4.3.1
 * Author: Rick Beckman
 * Author URI: https://rickbeckman.org/
 * License: GNU General Public License v3.0 (or later)
 * License URI: http://www.opensource.org/licenses/gpl-license.php
 * Text Domain: thesis-openhook
 * Domain Path: /languages/
*/

# Prevent direct access to this file
if ( 1 == count( get_included_files() ) ) {
	header( 'HTTP/1.1 403 Forbidden' );
	return;
}

/**
 * Define OpenHook constants
 */
define( 'OPENHOOK_VERSION', '4.3.1' );
define( 'OPENHOOK_DB_VERSION', 9 ); # Dev version (used for internal feature tracking)
define( 'OPENHOOK_PLUGIN_DIR', plugin_dir_path( __FILE__ ) );
define( 'OPENHOOK_PLUGIN_URL', plugin_dir_url( __FILE__ ) );

class OpenHook {
	/**
	 * Get the authorization level required to use OpenHook's features
	 *
	 * @since 4.0
	 */
	public function get_auth_level() {
		return 'edit_themes';
	}

	/**
	 * Initializes the OpenHook class
	 *
	 * Runs through several actions, such as activating our i18n text domain, required to get us started
	 *
	 * @since 3.4
	 */
	public function __construct() {
		register_activation_hook( __FILE__, array( $this, 'activate' ) );

		add_action( 'init', array( $this, 'upgrade' ) ); # Upgrade, if needed
		add_action( 'init', array( $this, 'i18n' ) ); # Internationalization
		add_action( 'init', array( $this, 'execute_hooks' ) ); # Attach custom actions to hooks
		add_action( 'init', array( $this, 'register_shortcodes' ) ); # Turn on chosen shortcodes
		add_filter( 'plugin_action_links', array( $this, 'edit_files_compatibility_notice' ), 10, 2 ); # Add compatibility notice if editing files is disabled

		# The meat & potatoes of OpenHook processes on the frontend only
		if ( ! is_admin() ) {
			# Handle hook visualization
			$options = get_option( 'openhook_general' );

			if ( isset( $options['visualize_hooks'] ) && $options['visualize_hooks'] ) {
				add_action( 'init', array( $this, 'hook_visualization_setup' ) );
			}
		} else {
			# This stuff processes on the admin-side only
			include_once( OPENHOOK_PLUGIN_DIR . 'inc/admin.php' );

			$openhook_admin = new OpenHook_Admin();

			add_action( 'admin_init', array( $openhook_admin, 'initiate_options' ) );
			add_action( 'admin_menu', array( $openhook_admin, 'add_admin_menu_links' ), 99 );
		}
	}

	/**
	 * Display a notice on the plugin screen if file editing is disabled
	 *
	 * @since 4.3.1
	 * @return array
	 */
	public function edit_files_compatibility_notice( $links, $file ) {
		$disable_file_editing = false;

		if ( defined( 'DISALLOW_FILE_EDIT' ) ) {
			$disable_file_editing = DISALLOW_FILE_EDIT;
		}

		if ( plugin_basename(__FILE__) === $file && true == $disable_file_editing ) {
			$notice = sprintf( __( 'Due to how much OpenHook allows you to do, it requires %s to be disabled (set to false). Please review your %s file. OpenHook will not function while file editing is disabled.', 'thesis-openhook' ), '<code>DISALLOW_FILE_EDIT</code>', '<code>wp-config.php</code>' );

			array_unshift( $links, '<span style="color: red; font-weight: bold">' . $notice . '</span>' );
		}

		return $links;
	}

	/**
	 * We don't need to do much on initial install... but what little we need, we do
	 *
	 * @since 3.4
	 */
	public function activate( $network_wide ) {
		# Install OpenBox
		$this->install_openbox();
	}

	/**
	 * Handle anything that needs upgraded aside from standard plugin files
	 *
	 * @since 3.4
	 */
	public function upgrade() {
		# Update to DB version 8
		if ( version_compare( get_option( 'openhook_version' ), 8, '<' ) ) {
			# Ensure OpenBox stays upgraded
			$this->install_openbox();

			update_option( 'openhook_version', 8 );
		}

		# Update to DB version 9
		if ( version_compare( get_option( 'openhook_version' ), 9, '<' ) ) {
			# Active theme conversion from checkbox to radio
			$options = get_option( 'openhook_general' );
			$active_groups = isset( $options['active_actions'] ) ? $options['active_actions'] : array();
			$radio_check = false;
			# Find the first active theme and set it as the only active theme
			foreach( $active_groups as $group => $bool ) {
				if ( isset( $group ) && $group && false === $radio_check && ! in_array( $group, array( 'openhook_tha', 'openhook_wordpress' ) ) ) {
					$theme = str_replace( 'openhook_', '', $group );
					$options['active_theme'] = $theme;
					$radio_check = true;
				}
			}

			# Simplify option names
			$options['active_actions']['tha'] = ( isset( $options['active_actions']['openhook_tha'] ) && $options['active_actions']['openhook_tha'] ) ? 1 : 0;
			unset( $options['active_actions']['openhook_tha'] );
			$options['active_actions']['wordpress'] = ( isset( $options['active_actions']['openhook_wordpress'] ) && $options['active_actions']['openhook_wordpress'] ) ? 1 : 0;
			unset( $options['active_actions']['openhook_wordpress'] );

			# Cleanup options
			unset( $options['active_actions']['openhook_astra'] );
			unset( $options['active_actions']['openhook_flat'] );
			unset( $options['active_actions']['openhook_headway'] );
			unset( $options['active_actions']['openhook_thesis'] );

			update_option( 'openhook_general', $options );
			update_option( 'openhook_version', 9 );
		}

		# Set final version in database
		update_option( 'openhook_version', OPENHOOK_DB_VERSION );
	}

	/**
	 * Loads the plugin text domain for translation
	 *
	 * @since 3.4
	 */
	public function i18n() {
		load_plugin_textdomain( 'thesis-openhook', false, dirname( plugin_basename( __FILE__ ) ) . '/languages' );
	}

	/**
	 * Registers OpenHook's various shortcode customizations
	 *
	 * @since 4.0
	 */
	public function register_shortcodes() {
		$options = get_option( 'openhook_shortcodes' );

		# If shortcodes are disabled, nuke them all using WordPress' function
		if ( isset( $options['disable_all'] ) && $options['disable_all'] ) {
			remove_all_shortcodes();
		} else {
			# Include our shortcode functions
			include_once( OPENHOOK_PLUGIN_DIR . 'inc/shortcodes.php' );

			# Set up our shortcodes object
			$shortcodes = new OpenHook_ShortCodes();

			# Register each of our shortcodes, if applicable
			if ( isset( $options['email'] ) && $options['email'] ) {
				add_shortcode( 'email', array( $shortcodes, 'email' ) );
			}

			if ( isset( $options['global_enabled'] ) && $options['global_enabled'] ) {
				add_shortcode( 'global', array( $shortcodes, 'globals' ) );
			}

			if ( isset( $options['php'] ) && $options['php'] ) {
				add_shortcode( 'php', array( $shortcodes, 'php' ) );
			}

			if ( isset ( $options['snap'] ) && $options['snap'] ) {
				add_shortcode( 'snap', array( $shortcodes, 'snap' ) );
			}
		}
	}

	/**
	 * Setup hook visualizations
	 *
	 * @since 3.3
	 */
	public function hook_visualization_setup() {
		# Prevent hook visualizations from appearing to users which can't edit the hooks to begin with
		if ( current_user_can( $this->get_auth_level() ) ) {
			# Get a list of all OpenHook hooks
			$hooks = $this->get_active_hooks();

			# Register the style needed to highlight hook locations
			wp_enqueue_style( 'openhook-visualization', OPENHOOK_PLUGIN_URL . 'inc/css/visualization.min.css' );

			# Cycle through each of the hooks, adding the visualization code to it
			foreach ( $hooks as $hook => $info ) {
				add_action( $hook, array( $this, 'do_hook_visualization' ), 1 );
			}
		}
	}

	/**
	 * Output hook name/visualization
	 *
	 * @since 3.3
	 */
	public function do_hook_visualization() {
		# Get the current hook to be highlighted
		$current_action = current_filter();

		# These actions fire outside of the visible part of the page
		$invisible_actions = array(
			'astra_html_before',
			'astra_head_top',
			'astra_head_bottom',
			'flat_html_before',
			'tha_html_before',
			'wp_head',
		);

		# Highlight each hook, except for the invisible ones
		if ( ! in_array( $current_action, $invisible_actions ) ) {
			echo '<span class="openhook">' . $current_action . '</span>';
		} else {
			echo "<!-- $current_action -->";
		}
	}

	/**
	 * Determine which actions to take for each of our hooks
	 *
	 * @since 3.0
	 */
	public function execute_hooks() {
		$active_hooks = $this->get_active_hooks();
		$all_actions = $this->get_active_actions();

		# Go through each of our hooks, doing stuff if needed
		foreach ( $active_hooks as $hook => $info ) {
			# Add actions to all required hooks
			if ( isset( $all_actions[ $hook ]['action'] ) && ! isset ( $all_actions[ $hook ]['disable'] ) ) {
				$priority = ( isset( $all_actions[ $hook ]['priority'] ) && is_int( (int) $all_actions[ $hook ]['priority'] ) ) ? $all_actions[ $hook ]['priority'] : 10;

				add_action( $hook, array( $this, 'execute_action' ), $priority );
			}

			# Unhook actions as needed
			if ( isset( $info['unhook'] ) ) {
				foreach ( $info['unhook'] as $action => $actinfo ) {
					if ( isset( $all_actions[ $hook ]['unhook'][ $action ] ) ) {
						$priority = isset( $actinfo['priority'] ) ? $actinfo['priority'] : false;

						# Actions hooked with a priority need a priority to be unhooked
						if ( $priority ) {
							remove_action( $hook, $action, $priority );
						} else {
							remove_action( $hook, $action );
						}
					}
				}
			}
		}
	}

	/**
	 * Process an action
	 *
	 * @since 3.0
	 */
	public function execute_action() {
		# Determine the current hook/filter we're acting upon & get our options to act
		$hook = current_filter();
		$options = $this->get_active_actions();
		$args = func_get_args();

		# Bail out if we have neither a hook nor options to work with
		if( ! $hook || ! $options ) {
			return;
		}

		# Nice names for our options
		$action = $options[ $hook ]['action'];
		$php = isset( $options[ $hook ]['php'] ) ? 1 : 0;
		$shortcodes = isset( $options[ $hook ]['shortcodes'] ) ? 1 : 0;

		# Process shortcodes if needed
		$value = $shortcodes ? do_shortcode( $action ) : $action;

		# Output our action, with or w/o PHP as needed
		if ( $php ) {
			eval( "?>$value<?php " );
		} else {
			echo $value;
		}
	}

	/**
	 * Install OpenBox to allow custom code in Thesis 2
	 *
	 * @since 3.4
	 */
	private function install_openbox() {
		include_once( ABSPATH . '/wp-admin/includes/file.php' );

		if ( 'direct' === get_filesystem_method() ) {
			# Use the WordPress file management system to manage file manipulations
			WP_Filesystem();

			$f = $GLOBALS['wp_filesystem'];

			# Create files needed to install OpenHook
			if ( ! is_dir( WP_CONTENT_DIR . '/thesis/boxes/openbox' ) ) {
				$directories = array( 'thesis/', 'thesis/boxes/', 'thesis/boxes/openbox/' );

				# Loop the file structure, creating directories which do not exist
				foreach ( $directories as $dir ) {
					$f->mkdir( $f->wp_content_dir() . $dir );
				}
			}

			# This installs the OpenBox file itself
			$f->put_contents( $f->wp_content_dir() . 'thesis/boxes/openbox/box.php', file_get_contents( OPENHOOK_PLUGIN_DIR . 'inc/openbox.inc' ) );
		}
	}

	/**
	 * Remove OpenBox
	 *
	 * Thus far, this is an unused function. Deleting boxes removes them from Thesis' system,
	 * resulting in the saved customizations being lost with them. That's bad.
	 * This means that OpenBox persists, even after OpenHook is uninstalled. This function
	 * persists as a reminder that a workaround should be found someday. Someday.
	 *
	 * @since 3.4
	 */
	private function uninstall_openbox() {
		include_once( ABSPATH . '/wp-admin/includes/file.php' );

		if ( 'direct' === get_filesystem_method() ) {
			# Use the WordPress file management system to manage file manipulations
			WP_Filesystem();

			$f = $GLOBALS['wp_filesystem'];

			$f->rmdir( $f->wp_content_dir() . 'thesis/boxes/openbox/' );
		}
	}

	/**
	 * Return all active hooks as an array
	 *
	 * @return array Concatenated list of all active hooks
	 * @since 4.0
	 */
	private function get_active_hooks() {
		$options = get_option( 'openhook_general' );
		$active_theme = isset( $options['active_theme'] ) ? $options['active_theme'] : 'none';
		$theme_hooks = array();

		if ( 'none' != $active_theme ) {
			$func = "{$active_theme}_hooks";
			$theme_hooks = $this->$func();
		}

		$active_groups = isset( $options['active_actions'] ) ? $options['active_actions'] : '';
		$custom_hooks = ( isset( $active_groups['custom'] ) && $active_groups['custom'] ) ? $this->custom_hooks() : array();
		$tha_hooks = ( isset( $active_groups['tha'] ) && $active_groups['tha'] ) ? $this->tha_hooks() : array();
		$wordpress_hooks = ( isset( $active_groups['wordpress'] ) && $active_groups['wordpress'] ) ? $this->wordpress_hooks() : array();
		$hooks = array_merge(
			(array) $theme_hooks,
			(array) $custom_hooks,
			(array) $tha_hooks,
			(array) $wordpress_hooks
		);

		return $hooks;
	}

	/**
	 * Return all actions to be processed
	 *
	 * @return array Concatenated list of all relevant options
	 * @since 3.0
	 */
	private function get_active_actions() {
		$options = get_option( 'openhook_general' );
		$active_groups = isset( $options['active_actions'] ) ? $options['active_actions'] : array();
		$active_actions = array();
		$groups = array(
			'custom',
			'tha',
			'wordpress',
		);

		# Add actions for general hook groups
		foreach ( $groups as $group ) {
			if ( isset( $active_groups[ $group ] ) && $active_groups[ $group ] ) {
				$group_actions = get_option( "openhook_$group" );
				$active_actions = is_array( $group_actions ) ? array_merge( $active_actions, $group_actions ) : $active_actions;
			}
		}

		# Add actions for active theme
		if ( isset( $options['active_theme'] ) && 'none' != $options['active_theme'] ) {
			$group_actions = get_option( "openhook_{$options['active_theme']}" );
			$active_actions = is_array( $group_actions ) ? array_merge( $active_actions, $group_actions ) : $active_actions;
		}

		return $active_actions;
	}

	/**
	 * An array of available Astra hooks
	 *
	 * @link https://developers.wpastra.com/theme-visual-hooks/
	 * @since 4.3.0
	 */
	public function astra_hooks() {
		$hooks = array(
			'astra_html_before' => array(),
			'astra_head_top' => array(),
			'astra_head_bottom' => array(),
			'astra_body_top' => array(),
			'astra_header_before' => array(),
			'astra_masthead_top' => array(),
			'astra_main_header_bar_top' => array(),
			'astra_masthead_content' => array(),
			'astra_main_header_bar_bottom' => array(),
			'astra_masthead_bottom' => array(),
			'astra_header_after' => array(),
			'astra_content_before' => array(),
			'astra_content_top' => array(),
			'astra_primary_content_top' => array(),
			'astra_entry_before' => array(),
			'astra_entry_top' => array(),
			'astra_entry_content_before' => array(),
			'astra_entry_content_after' => array(),
			'astra_entry_bottom' => array(),
			'astra_entry_after' => array(),
			'astra_primary_content_bottom' => array(),
			'astra_content_bottom' => array(),
			'astra_sidebars_before' => array(),
			'astra_sidebars_after' => array(),
			'astra_content_after' => array(),
			'astra_footer_before' => array(),
			'astra_footer_content_top' => array(),
			'astra_footer_content_bottom' => array(),
			'astra_footer_after' => array(),
			'astra_body_bottom' => array(),
		);
	
		return $hooks;
	}

	/**
	 * An array of available Flat hooks
	 *
	 * @link https://github.com/yoarts/flat
	 * @since 4.10
	 */
	public function flat_hooks() {
		$hooks = array(
			'flat_html_before' => array(
				'unhook' => array(
					'flat_doctype' => array(
						'desc' => __( 'The default <abbr title="Hypertext Markup Language">HTML</abbr> doctype', 'thesis-openhook' ),
					),
				),
			),
			'flat_head_top' => array(),
			'flat_head_bottom' => array(),
			'flat_header_before' => array(),
			'flat_header_top' => array(),
			'flat_header_bottom' => array(),
			'flat_header_after' => array(),
			'flat_body_top' => array(),
			'flat_body_bottom' => array(),
			'flat_content_before' => array(),
			'flat_content_after' => array(),
			'flat_content_top' => array(),
			'flat_content_bottom' => array(),
			'flat_entry_before' => array(),
			'flat_entry_after' => array(),
			'flat_entry_top' => array(),
			'flat_entry_bottom' => array(),
			'flat_page_before' => array(),
			'flat_page_after' => array(),
			'flat_page_top' => array(),
			'flat_page_bottom' => array(),
			'flat_index_before' => array(),
			'flat_index_after' => array(),
			'flat_index_top' => array(),
			'flat_index_bottom' => array(),
			'flat_archive_before' => array(),
			'flat_archive_after' => array(),
			'flat_archive_top' => array(),
			'flat_archive_bottom' => array(),
			'flat_search_before' => array(),
			'flat_search_after' => array(),
			'flat_search_top' => array(),
			'flat_search_bottom' => array(),
			'flat_comments_before' => array(),
			'flat_comments_after' => array(),
			'flat_comments_top' => array(),
			'flat_comments_bottom' => array(),
			'flat_sidebar_before' => array(),
			'flat_sidebar_after' => array(),
			'flat_sidebar_top' => array(),
			'flat_sidebar_bottom' => array(),
			'flat_404_content' => array(
				'unhook' => array(
					'flat_output_404_content' => array(
						'desc' => __( 'The default 404 page content', 'thesis-openhook' ),
					),
				),
			),
			'flat_footer_before' => array(),
			'flat_footer_after' => array(),
			'flat_footer_top' => array(),
			'flat_footer_bottom' => array(),
		);

		return $hooks;
	}

	/**
	 * An array of available Headway hooks
	 *
	 * @link http://docs.headwaythemes.com/article/81-hook-reference
	 * @since 4.0
	 */
	public function headway_hooks() {
		$hooks = array(
			'headway_after_block' => array(),
			'headway_after_entry' => array(),
			'headway_after_entry_comments' => array(),
			'headway_after_entry_content' => array(),
			'headway_after_entry_title' => array(),
			'headway_after_footer' => array(),
			'headway_after_header_link' => array(),
			'headway_after_tagline' => array(),
			'headway_after_wrapper' => array(),
			'headway_before_block' => array(),
			'headway_before_entry' => array(),
			'headway_before_entry_comments' => array(),
			'headway_before_entry_content' => array(),
			'headway_before_entry_title' => array(),
			'headway_before_footer' => array(),
			'headway_before_header_link' => array(),
			'headway_before_wrapper' => array(),
			'headway_block_close' => array(),
			'headway_block_content_close' => array(),
			'headway_block_content_open' => array(),
			'headway_block_open' => array(),
			'headway_body_close' => array(),
			'headway_body_open' => array(),
			'headway_entry_close' => array(),
			'headway_entry_open' => array(),
			'headway_footer_close' => array(),
			'headway_footer_open' => array(),
			'headway_head_extras' => array(),
			'headway_html_close' => array(),
			'headway_html_open' => array(),
			'headway_page_start' => array(),
			'headway_register_elements' => array(),
			'headway_scripts' => array(),
			'headway_seo_meta' => array(),
			'headway_setup' => array(),
			'headway_setup_child_theme' => array(),
			'headway_stylesheets' => array(),
			'headway_whitewrap_close' => array(),
			'headway_whitewrap_open' => array(),
			'headway_wrapper_close' => array(),
			'headway_wrapper_open' => array(),
		);

		return $hooks;
	}

	/**
	 *  An array of available K2 hooks
	 *
	 * @since 4.2.0
	 */
	public function k2_hooks() {
		$hooks = array(
			'template_body_top' => array(
				'desc' => __( 'Just after the opening <code>body</code>', 'thesis-openhook' ),
			),
			'template_before_header' => array(
				'desc' => __( 'Just after the opening <code>div#page</code>', 'thesis-openhook' ),
			),
			'template_header' => array(
				'desc' => __( 'Just before the close of <code>div#header</code>', 'thesis-openhook' ),
			),
			'template_header_menu' => array(
				'desc' => __( 'Within <code>ul.menu</code>, just after the homepage list item and just before the other page links are listed. Using <code>li</code> tags to wrap your output is highly recommended.', 'thesis-openhook' ),
			),
			'template_primary_begin' => array(
				'desc' => __( 'Within <code>div#primary</code> just after <code>div#notices</code>', 'thesis-openhook' ),
			),
			'template_primary_end' => array(
				'desc' => __( 'Just before the close of <code>div#primary</code>', 'thesis-openhook' ),
			),
			'template_before_content' => array(
				'desc' => __( 'Within <code>div#page</code> just after the first <code>hr</code>', 'thesis-openhook' ),
			),
			'template_after_content' => array(
				'desc' => __( 'Within <code>div#page</code> just before <code>div.clear</code>', 'thesis-openhook' ),
			),
			'template_entry_head' => array(
				'desc' => __( 'Just before the close of <code>div.entry-head</code>', 'thesis-openhook' ),
			),
			'template_entry_foot' => array(
				'desc' => __( 'Just before the close of <code>div.entry-foot</code>', 'thesis-openhook' ),
			),
			'template_before_footer' => array(
				'desc' => __( 'Just before <code>div#footer</code>', 'thesis-openhook' ),
			),
			'template_footer' => array(
				'desc' => __( 'Just before the close of <code>div#footer</code>', 'thesis-openhook' ),
			),
			'k2_image_meta' => array(
				'desc' => __( 'Just before the close of <code>ul.image-meta</code>. Using <code>li></code> tags to wrap your output is highly recommended.', 'thesis-openhook' ),
				'args' => array(
					'$args[0]' => __( 'The ID number of the image currently being viewed', 'thesis-openhook' ),
				),
			),
		);

		return $hooks;
	}

	/**
	 * An array of available WordPress hooks
	 *
	 * @since 3.0
	 */
	public function thesis_hooks() {
		$hooks = array(
			'thesis_hook_before_html' => array(),
			'thesis_hook_after_html' => array(),
			'thesis_hook_before_header' => array(
				'unhook' => array(
					'thesis_nav_menu' => array(
						'desc' => __( 'The default navigation menu', 'thesis-openhook' ),
					),
				),
			),
			'thesis_hook_after_header' => array(),
			'thesis_hook_header' => array(
				'unhook' => array(
					'thesis_default_header' => array(
						'desc' => __( 'Default header', 'thesis-openhook' ),
					),
				),
			),
			'thesis_hook_before_title' => array(),
			'thesis_hook_after_title' => array(),
			'thesis_hook_first_nav_item' => array(),
			'thesis_hook_last_nav_item' => array(),
			'thesis_hook_before_content_box' => array(),
			'thesis_hook_after_content_box' => array(),
			'thesis_hook_content_box_top' => array(),
			'thesis_hook_content_box_bottom' => array(),
			'thesis_hook_before_content' => array(),
			'thesis_hook_after_content' => array(
				'unhook' => array(
					'thesis_post_navigation' => array(
						'desc' => __( 'Displays links to the next or previous post', 'thesis-openhook' ),
					),
					'thesis_prev_next_posts' => array(
						'desc' => __( 'Displays links to the next or previous pages of an archive listing', 'thesis-openhook' ),
					),
				),
			),
			'thesis_hook_before_content_area' => array(),
			'thesis_hook_after_content_area' => array(),
			'thesis_hook_feature_box' => array(),
			'thesis_hook_before_post_box' => array(),
			'thesis_hook_after_post_box' => array(),
			'thesis_hook_after_content_box' => array(),
			'thesis_hook_post_box_top' => array(),
			'thesis_hook_post_box_bottom' => array(),
			'thesis_hook_before_teasers_box' => array(),
			'thesis_hook_after_teasers_box' => array(),
			'thesis_hook_before_post' => array(),
			'thesis_hook_after_post' => array(
				'unhook' => array(
					'thesis_post_tags' => array(
						'desc' => __( 'Displays a list of tags applied to the post', 'thesis-openhook' ),
					),
					'thesis_comments_link' => array(
						'desc' => __( 'Displays a link to the comments of a post', 'thesis-openhook' ),
					),
				),
			),
			'thesis_hook_before_teaser_box' => array(),
			'thesis_hook_after_teaser_box' => array(),
			'thesis_hook_before_teaser' => array(),
			'thesis_hook_after_teaser' => array(),
			'thesis_hook_before_headline' => array(),
			'thesis_hook_after_headline' => array(),
			'thesis_hook_before_teaser_headline' => array(),
			'thesis_hook_after_teaser_headline' => array(),
			'thesis_hook_byline_item' => array(),
			'thesis_hook_before_comment_meta' => array(),
			'thesis_hook_after_comment_meta' => array(),
			'thesis_hook_after_comment' => array(),
			'thesis_hook_after_comments' => array(),
			'thesis_hook_comment_form_top' => array(),
			'thesis_hook_comment_field' => array(),
			'thesis_hook_after_comment_box' => array(
				'unhook' => array(
					'show_subscription_checkbox' => array(
						'desc' => __( 'The Subscribe to Comments plugin subscription checkbox', 'thesis-openhook' ),
					),
				),
			),
			'thesis_hook_comment_form_bottom' => array(),
			'thesis_hook_archives_template' => array(
				'unhook' => array(
					'thesis_archives_template' => array(
						'desc' => __( 'Default output for the archives template', 'thesis-openhook' ),
					),
				),
			),
			'thesis_hook_custom_template' => array(
				'unhook' => array(
					'thesis_custom_template_sample' => array(
						'desc' => __( 'Default sample output for the custom template', 'thesis-openhook' ),
					),
				),
			),
			'thesis_hook_faux_admin' => array(),
			'thesis_hook_404_title' => array(
				'unhook' => array(
					'thesis_404_title' => array(
						'desc' => __( 'Displays default 404 error page title', 'thesis-openhook' ),
					),
				),
			),
			'thesis_hook_404_content' => array(
				'unhook' => array(
					'thesis_404_content' => array(
						'desc' => __( 'Displays default 404 error page content', 'thesis-openhook' ),
					),
				),
			),
			'thesis_hook_before_sidebars' => array(),
			'thesis_hook_after_sidebars' => array(),
			'thesis_hook_multimedia_box' => array(),
			'thesis_hook_after_multimedia_box' => array(),
			'thesis_hook_before_sidebar_1' => array(),
			'thesis_hook_after_sidebar_1' => array(),
			'thesis_hook_before_sidebar_2' => array(),
			'thesis_hook_after_sidebar_2' => array(),
			'thesis_hook_before_footer' => array(),
			'thesis_hook_after_footer' => array(),
			'thesis_hook_footer' => array(
				'unhook' => array(
					'thesis_attribution' => array(
						'desc' => __( 'Displays DIYthemes attribution &amp; link', 'thesis-openhook' ),
					),
				),
			),
		);

		return $hooks;
	}

	/**
	 * An array of available Thesis 2 hooks
	 *
	 * @since 4.2.0
	 */
	/*public function thesis2_hooks() {
		$hooks = array(
			'hook_head' => array(),
			'hook_before_html' => array(),
			'hook_after_html' => array(),
		);

		return $hooks;
	}*/

	/**
	 * An array of available THA hooks
	 *
	 * @link https://github.com/zamoose/themehookalliance/
	 * @since 4.1
	 */
	public function tha_hooks() {
		$hooks = array(
			'tha_html_before' => array(),
			'tha_body_top' => array(),
			'tha_body_bottom' => array(),
			'tha_head_top' => array(),
			'tha_head_bottom' => array(),
			'tha_header_before' => array(),
			'tha_header_after' => array(),
			'tha_header_top' => array(),
			'tha_header_bottom' => array(),
			'tha_content_before' => array(),
			'tha_content_after' => array(),
			'tha_content_top' => array(),
			'tha_content_bottom' => array(),
			'tha_entry_before' => array(),
			'tha_entry_after' => array(),
			'tha_entry_top' => array(),
			'tha_entry_bottom' => array(),
			'tha_comments_before' => array(),
			'tha_comments_after' => array(),
			'tha_sidebars_before' => array(),
			'tha_sidebars_after' => array(),
			'tha_sidebar_top' => array(),
			'tha_sidebar_bottom' => array(),
			'tha_footer_before' => array(),
			'tha_footer_after' => array(),
			'tha_footer_top' => array(),
			'tha_footer_bottom' => array(),
		);

		return $hooks;
	}

	/**
	 * An array of available WordPress hooks
	 *
	 * @since 3.0
	 */
	public function wordpress_hooks() {
		$hooks = array(
			'comment_form' => array(
				'desc' => __( 'Executed at the bottom of the comment form (as rendered by <code>comment_form()</code>, just before the closing <code>form</code> tag.', 'thesis-openhook' ),
				'args' => array(
					'$args[0]' => __( 'The ID number of the post the comment form is associated with.', 'thesis-openhook' ),
				)
			),
			'wp_footer' => array(
				'desc' => __( 'Executed either within the footer section (<code>div#footer</code> or <code>footer</code>) or immediately before the closing <code>html</code> tag, this hook is commonly used to output stats tracking scripts or other asyncronously loaded JavaScript.', 'thesis-openhook' ),
				'unhook' => array(
					'wp_print_footer_scripts' => array(
						'desc' => __( 'Is used to queue &amp; output barious scripts. Disabling this prevents most scripts from appearing in your footer, allowing you to manage them all manually, if you so choose.', 'thesis-openhook' ),
						'priority' => 20,
						'advanced' => 1,
					),
				),
			),
			'wp_head' => array(
				'desc' => __( 'Executes within your site&rsquo;s <code>head</code> section. Useful for outputting, for example, meta tags.', 'thesis-openhook' ),
				'unhook' => array(
					'feed_links' => array(
						'desc' => __( 'Many themes support the automatic addition of <code>link</code> tags pointing to your site&rsquo;s primary &amp; comments feeds. Disable to prevent this behavior.', 'thesis-openhook' ),
						'priority' => 2,
					),
					'feed_links_extra' => array(
						'desc' => __( 'Similar to <code>feed_links</code>, except the <code>link</code> tags output by this hook point to secondary feeds, such as category feeds.', 'thesis-openhook' ),
						'priority' => 3,
					),
					'wp_enqueue_scripts' => array(
						'desc' => __( 'Is used to queue &amp; output various scripts. Disabling this prevents most scripts from appearing in your header, allowing you to manage them all manually, if you so choose.', 'thesis-openhook' ),
						'priority' => 1,
						'advanced' => 1,
					),
					'wp_generator' => array(
						'desc' => sprintf( __( 'Is used to output a <code>meta</code> tag crediting WordPress as the software powering your site. Removing this is a form of <a href="%s">security through obscurity</a>.', 'thesis-openhook' ), 'http://en.wikipedia.org/wiki/Security_through_obscurity' ),
					), 
				),
			),
			'wp_meta' => array(
				'desc' => __( 'Executed just before the closing <code>ul</code> tag in the meta widget.', 'thesis-openhook' ),
			)
		);

		return $hooks;
	}

	/**
	 * Get custom hooks that have been defined
	 *
	 * @since 4.2.0
	 */
	public function custom_hooks() {
		$hooks = get_option( 'openhook_custom' );

		return $hooks;
	}
}

# Processes OpenHook in the global domain
$openhook = new OpenHook();