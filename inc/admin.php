<?php
/**
 * Contains admin functions
 */

# Prevent direct access to this file
if ( 1 == count( get_included_files() ) ) {
	header( 'HTTP/1.1 403 Forbidden' );
	return;
}

class OpenHook_Admin {
	/**
	 * Register our settings with WordPress, enabling saving them to database w/o fuss
	 *
	 * @since 3.0
	 * @todo Data sanitization
	 */
	public function initiate_options() {
		register_setting( 'openhook_settings_general', 'openhook_general' );
		register_setting( 'openhook_settings_shortcodes', 'openhook_shortcodes' );

		register_setting( 'openhook_settings_astra', 'openhook_astra' );
		register_setting( 'openhook_settings_flat', 'openhook_flat' );
		register_setting( 'openhook_settings_headway', 'openhook_headway' );
		register_setting( 'openhook_settings_k2', 'openhook_k2' );
		register_setting( 'openhook_settings_thesis', 'openhook_thesis' );
#		register_setting( 'openhook_settings_thesis2', 'openhook_thesis2' );

		register_setting( 'openhook_settings_custom', 'openhook_custom', array( $this, 'validate_custom_actions' ) );
		register_setting( 'openhook_settings_tha', 'openhook_tha' );
		register_setting( 'openhook_settings_wordpress', 'openhook_wordpress' );
	}

	/**
	 * Add OpenHook to admin menus
	 *
	 * @global object $openhook Main OpenHook class object
	 * @global array $submenu An array of the current menu items
	 * @since 3.0
	 */
	public function add_admin_menu_links() {
		global $openhook, $submenu;

		$auth = $openhook->get_auth_level();

		# Sneak shortcuts to various hook menus into appropriate places in the existing WordPress & theme menus
		$submenu['themes.php'][500] = array( __( 'WordPress Hooks', 'thesis-openhook' ), $auth, 'options-general.php?page=openhook&tab=wordpress', __( 'WordPress Hooks', 'thesis-openhook' ) );
		$submenu['tools.php'][500] = array( 'Server Info', $auth, 'options-general.php?page=openhook&tab=phpinfo', __( 'Server Info' ) );

		if ( array_key_exists( 'thesis-options', $submenu ) ) {
			$submenu['thesis-options'][500] = array( __( 'Thesis Hooks', 'thesis-openhook' ), $auth, 'options-general.php?page=openhook&tab=thesis', __( 'Thesis Hooks', 'thesis-openhook' ) );
		}

		# Add our primary options page; set it to a variable for use in targeting our admin style
		$page = add_options_page( __( 'OpenHook', 'thesis-openhook' ), __( 'OpenHook', 'thesis-openhook' ), $auth, 'openhook', array( $this, 'set_up_admin_page' ) );

		# Add our admin styles only to our page
		add_action( 'admin_print_styles-' . $page, array( $this, 'do_admin_css' ) );
	}

	/**
	 * Set up our admin styling
	 *
	 * @since 3.0
	 */
	public function do_admin_css() {
		wp_enqueue_style( 'openhook-admin-css', OPENHOOK_PLUGIN_URL . 'inc/css/admin.min.css' );
	}

	/**
	 * Create and echo OpenHook panel navigation tabs
	 *
	 * @since 3.0
	 */
	private function set_up_admin_tabs( ) {
		$links = array();

		# IDs & names of tabs
		$groups = array(
			'custom' => __( 'Custom', 'thesis-openhook' ),
			'astra' => _x( 'Astra', 'theme name', 'thesis-openhook' ),
			'flat' => _x( 'Flat', 'theme name', 'thesis-openhook' ),
			'headway' => _x( 'Headway', 'theme name', 'thesis-openhook' ),
			'k2' => _x( 'K2', 'theme name', 'thesis-openhook' ),
			'tha' => __( 'Theme Hook Alliance', 'thesis-openhook' ),
			'thesis' => _x( 'Thesis 1.8.x', 'theme name', 'thesis-openhook' ),
			'wordpress' => __( 'WordPress', 'thesis-openhook' ),
		);
		$tabs = array(
			'general' => __( 'General', 'thesis-openhook' ),
			'shortcodes' => __( 'Shortcodes', 'thesis-openhook' ),
			'phpinfo' => __( 'Server Info', 'thesis-openhook' ),
		);

		# Get active theme group
		$options = get_option( 'openhook_general' );
		$active_theme = ( isset( $options['active_theme'] ) ) ? $options['active_theme'] : 'none';

		if ( 'none' != $active_theme ) {
			$tabs[ $active_theme ] = $groups[ $active_theme ];

			unset( $groups[ $active_theme ] );
		}

		# Get other active action groups
		$active_groups = ( isset( $options['active_actions'] ) && (bool) $options['active_actions'] ) ? $options['active_actions'] : false;

		if ( $active_groups ) {
			foreach ( $active_groups as $active_group => $bool ) {
				if ( (bool) $bool ) {
					$tabs[ $active_group ] = $groups[ $active_group ];

					unset( $groups[ $active_group ] );
				}
			}
		}

		# Determine which tab is currently being viewed...
		$current = ( isset( $_GET['tab'] ) && array_key_exists( $_GET['tab'], $tabs ) ) ? $_GET['tab'] : false;

		# Construct tab HTML
		foreach( $tabs as $tab => $name ) {
			$url = admin_url( 'options-general.php?page=openhook&tab=' . $tab );

			$links[] = ( $current === $tab ) ? "<a class='nav-tab nav-tab-active' href='$url'>$name</a>" : "<a class='nav-tab' href='$url'>$name</a>";
		}

		# Construct inactive link HTML
		foreach( $groups as $theme => $name ) {
			$url = admin_url( 'options-general.php?page=openhook&tab=' . $theme );
			$inactive_links[] = ( isset( $_GET['tab'] ) && $_GET['tab'] === $theme ) ? "<a href='$url' class='nav-active'>$name</a>" : "<a href='$url'>$name</a>";
		}

		# Echo the tabs
		echo '<div id="nav"><h3>' . implode( '', $links ) . '</h3></div>';
		echo '<div id="inactive_theme_links">' . __( 'Customize inactive hook groups: ', 'thesis-openhook' ) . implode( ' | ', $inactive_links ) . '</div>';
	}

	/**
	 * Output the OpenHook Customization Manager
	 *
	 * @global object $openhook Main OpenHook class object 
	 * @since 3.0
	 */
	public function set_up_admin_page() {
		global $openhook;

		# Get user authorization level
		$auth = $openhook->get_auth_level();

		# Protect against unauthorized users
		if ( ! current_user_can( $auth ) ) {
			wp_die( __( 'Sorry, but you do not have the appropriate permissions to view this page.', 'thesis-openhook' ) );
		} else {
			# Determine which page is being viewed
			$tab = isset( $_GET['tab'] ) ? $_GET['tab'] : 'general';
		?>
<div class="wrap">
	<h2><?php _e( 'OpenHook', 'thesis-openhook' ); ?></h2>
	<?php $this->set_up_admin_tabs(); ?>
	<div class="main <?php echo esc_attr( $tab ); ?>">
		<?php
			# Display the appropriate page
			switch( $tab ) {
				case 'contact':
					$this->generate_contact_page();

					break;
				case 'phpinfo':
					$this->generate_phpinfo_page();

					break;
				case 'shortcodes':
					$this->generate_shortcodes_page();

					break;
				case 'custom':
				case 'astra':
				case 'flat':
				case 'headway':
				case 'k2':
				case 'tha':
				case 'thesis':
				#case 'thesis2':
				case 'wordpress':
					$this->generate_hook_page( $tab );

					break;
				case 'general':
				default:
					$this->generate_general_page();

					break;
			}
		?>
	</div>
	<?php
		if ( 'phpinfo' != $tab ) {
			$this->do_admin_sidebar();
		}
	?>
	<p class="footer"><?php printf( __( '&copy; Copyright 2008&ndash;%1$s <a href="%3$s" rel="noopener noreferrer" target="_blank">Rick Beckman</a>. <a href="%2$s" rel="noopener noreferrer" target="_blank">Open source software</a>. | Dedicated to Anya Marie &amp; Annie Kay.', 'thesis-openhook' ), date( 'Y' ), 'http://www.gnu.org/licenses/gpl-3.0.html', 'https://www.rickbeckman.org/' ); ?></p>
</div>
		<?php
		}
	}

	/**
	 * Output JavaScript for hook panels
	 *
	 * @since 3.0
	 */
	private function do_hook_panel_js( $hooks ) {
	?>
<script type="text/javascript">
jQuery(document).ready(function(){
	jQuery('#hook_selector').change(function(){
		var fieldsetName = jQuery(this).val();
		jQuery('fieldset.hook').hide().filter('#' + fieldsetName).fadeIn();
		jQuery('.button-primary').fadeIn();
	});
	jQuery('fieldset.hook').hide();
	jQuery('.button-primary').hide();
});
</script>
	<?php
	}

	/**
	 * Output a hook selector to allow swapping between hook options
	 *
	 * @param array $hooks The list of hooks from which we're selecting
	 * @param array $options Currently saved options
	 * @since 3.0
	 */
	private function build_hook_select_selector( $hooks, $options ) {
		echo '<select id="hook_selector"><option value="0">-- ' . __( 'Select a hook to customize', 'thesis-openhook' ) . ' --</option>';

		# Cycle through each hook for display
		foreach ( $hooks as $hook => $info ) {
			$has_customization = false;

			$actions = ( (bool) $options[ $hook ] ) ? $options[ $hook ] : array();

			unset( $actions['active'] );

			foreach ( $actions as $customization ) {
				if ( (bool) $customization ) {
					$has_customization = true;
				}
			}

			$asterisk = ( $has_customization ) ? '&diams; ' : '';

			# Build appropriate HTML
			echo '<option value="hook_' . $hook . '">' . $asterisk . $hook . '</option>';
		}

		echo '</select>';
	}

	/**
	 * Output the OpenHook Customization Manager's sidebar
	 *
	 * @since 3.0
	 */
	private function do_admin_sidebar() {
	?>
<div class="sidebar">
	<h3><?php _e( 'OpenHook', 'thesis-openhook' ); ?></h3>
	<p><?php _e( 'Like OpenHook on Facebook and share and discuss customizations with fellow users!', 'thesis-openhook' ); ?></p>
	<p><a href="https://wordpress.org/plugins/thesis-openhook/" rel="noopener noreferrer" target="_blank"><?php _e( 'Plugin Repository Listing', 'thesis-openhook' ); ?></a><br>
		<!--<a href="<?php echo admin_url( 'options-general.php?page=openhook&tab=contact' ); ?>"><?php _e( 'Official Support', 'thesis-openhook' ); ?></a> | --><a href="https://wordpress.org/support/plugin/thesis-openhook/" rel="noopener noreferrer" target="_blank"><?php _e( 'Community Support', 'thesis-openhook' ); ?></a></p>
	<h3><?php _e( 'Support Development', 'thesis-openhook' ); ?></h3>
	<div id="donate-button-container">
		<div id="donate-button"></div>
		<script src="https://www.paypalobjects.com/donate/sdk/donate-sdk.js" charset="UTF-8"></script>
		<script>
			PayPal.Donation.Button({
				env:'production',
				hosted_button_id:'DXYAEEXMSK3DG',
				image: {
					src:'https://www.paypalobjects.com/en_US/i/btn/btn_donateCC_LG.gif',
					alt:'Donate with PayPal button',
					title:'PayPal - The safer, easier way to pay online!',
				}
			}).render('#donate-button');
		</script>
	</div>
	<p><?php printf( __( 'Donating money not your thing? <a href="%s" target="_blank" rel="noopener referrer">Buy me a book instead</a>.', 'thesis-openhook' ), 'https://www.amazon.com/gp/registry/wishlist/1GZRG9D7NI272/' ); ?>
	<p><?php _e( 'Developing OpenHook is a labor of love. Your donation, of any size, is greatly appreciated.', 'thesis-openhook' ); ?></p>
	<p><?php printf( __( 'Has OpenHook helped you? <a href="%s" rel="noopener noreferrer" target="_blank">Leave it a good review!</a>', 'thesis-openhook' ), 'https://wordpress.org/support/plugin/thesis-openhook/reviews/' ); ?></p>
	<h3><?php _e( 'Credits', 'thesis-openhook' ); ?></h3>
	<ul>
		<li><?php printf( __( 'Created by <a href="%s" target="_blank" rel="noopener noreferrer">Rick Beckman</a>', 'thesis-openhook' ), 'https://www.rickbeckman.org/' ); ?></li>
		<li><?php printf( __( 'Inspired by <a href="%s" target="_blank" rel="noopener noreferrer">K2 Hook Up</a>', 'thesis-openhook' ), 'https://wordpress.org/plugins/k2-hook-up/' ); ?></li>
	</ul>
</div>
	<?php
	}

	/**
	 * Generate general options
	 *
	 * @since 3.0
	 */
	private function generate_general_page() {
		if ( isset( $_POST ) ) {
			# Delete legacy options
			if ( ! empty( $_REQUEST['cleanup_openhook'] ) ) {
				$this->delete_options( 'legacy' );
			}

			# Upgrade OpenHook
			if ( ! empty( $_REQUEST['upgrade_openhook'] ) ) {
				$this->import_old_options();
			}

			# Uninstall OpenHook
			if ( ! empty( $_REQUEST['uninstall_openhook'] ) ) {
				$this->delete_options( 'current' );
			}

			# Import from K2 Hook Up
			if ( ! empty( $_REQUEST['k2_hook_up_import'] ) ) {
				$this->import_k2_hook_up_options();
			}
		}

		$options = get_option( 'openhook_general' );
		$active_theme = ( isset( $options['active_theme'] ) && (bool) $options['active_theme'] ) ? $options['active_theme'] : 'none';
		$custom_active = ( isset( $options['active_actions']['custom'] ) && $options['active_actions']['custom'] ) ? 1 : 0;
		$tha_active = ( isset( $options['active_actions']['tha'] ) && $options['active_actions']['tha'] ) ? 1 : 0;
		$wordpress_active = ( isset( $options['active_actions']['wordpress'] ) && $options['active_actions']['wordpress'] ) ? 1 : 0;
		$visualize_hooks = ( isset( $options['visualize_hooks'] ) && $options ['visualize_hooks'] ) ? 1 : 0;
		$hook_page_layout = ( isset( $options['hook_page_layout'] ) ) ? $options['hook_page_layout'] : 'single';
		?>
<script>
function confirmUpgrade()
{
    return confirm("<?php echo esc_js( sprintf( __( 'Are you sure you want to migrate your OpenHook 2 settings to OpenHook %s? This will overwrite all current OpenHook 3 options.', 'thesis-openhook' ), OPENHOOK_VERSION ) ); ?>");
}
function confirmCleanUp()
{
	return confirm("<?php echo esc_js( __( 'Are you sure you want to delete all old OpenHook 2 settings? This action is irreversible!', 'thesis-openhook' ) ); ?>");
}
function confirmUninstall()
{
	return confirm("<?php echo esc_js( sprintf( __( 'Are you sure you want to delete all OpenHook %s settings? This action is irreversible!', 'thesis-openhook' ), OPENHOOK_VERSION ) ); ?>");
}
function confirmK2Import()
{
	return confirm("<?php echo esc_js( __( 'Are you sure you want to import options from K2 Hook Up? This will overwrite any K2 actions already created using OpenHook.', 'thesis-openhook' ) ); ?>");
}
</script>
<form method="post" action="options.php">
	<p><?php _e( 'Here are just a few settings to tailor your OpenHook experience. Note that no custom actions will be processed unless you enable them below.', 'thesis-openhook' ); ?></p>
	<?php settings_fields( 'openhook_settings_general' ); ?>
	<p class="submit"><input type="submit" class="button-primary" value="<?php _e( 'Save Changes', 'thesis-openhook' ); ?>"></p>
	<table class="form-table">
		<tr>
			<th scope="row"><?php _e( 'Active hook groups', 'thesis-openhook' ); ?></th>
			<td>
				<p class="description"><?php _e( 'OpenHook allows customizing several different themes&apos; hooks. To save on processing power, it will only process the hooks you specify here by enabling them on the groups of your choice. Disabling the hook groups using these options does not delete your saved customizations.', 'thesis-openhook' ); ?></p>
				<h4><?php _e( 'Choose a Theme', 'thesis-openhook' ); ?></h4>
				<p>
					<label><input type="radio" name="openhook_general[active_theme]" value="astra"<?php checked( 'astra', $active_theme ); ?>> <?php _ex( 'Astra', 'theme name', 'thesis-openhook' ); ?></label><br>
					<label><input type="radio" name="openhook_general[active_theme]" value="flat"<?php checked( 'flat', $active_theme ); ?>> <?php _ex( 'Flat', 'theme name', 'thesis-openhook' ); ?></label><br>
					<label><input type="radio" name="openhook_general[active_theme]" value="headway"<?php checked( 'headway', $active_theme ); ?>> <?php _ex( 'Headway', 'theme name', 'thesis-openhook' ); ?></label><br>
					<label><input type="radio" name="openhook_general[active_theme]" value="k2"<?php checked( 'k2', $active_theme ); ?>> <?php _ex( 'K2', 'theme name', 'thesis-openhook' ); ?></label><br>
					<label><input type="radio" name="openhook_general[active_theme]" value="thesis"<?php checked( 'thesis', $active_theme ); ?>> <?php _e( 'Thesis 1.8.x', 'thesis-openhook' ); ?></label><br>
					<label><input type="radio" name="openhook_general[active_theme]" value="none"<?php checked( 'none', $active_theme ); ?>> <?php _e( 'none', 'thesis-openhook' ); ?></label>
				</p>
				<h4><?php _e( 'Choose Other Hook Groups', 'thesis-openhook' ); ?></h4>
				<p>
					<label><input type="checkbox" name="openhook_general[active_actions][tha]" value="1"<?php checked( 1, $tha_active ); ?>> <a href="https://github.com/zamoose/themehookalliance/"><?php _e( 'Theme Hook Alliance', 'thesis-openhook' ); ?></a></label><br>
					<label><input type="checkbox" name="openhook_general[active_actions][wordpress]" value="1"<?php checked( 1, $wordpress_active ); ?>> <?php _e( 'WordPress', 'thesis-openhook' ); ?></label><br>
					<label><input type="checkbox" name="openhook_general[active_actions][custom]" value="1"<?php checked( 1, $custom_active ); ?>> <?php _e( 'Custom', 'thesis-openhook' ); ?></label>
				</p>
			</td>
		</tr>
		<tr>
			<th scope="row"><?php _e( 'Visualize hooks', 'thesis-openhook' ); ?></th>
			<td>
				<p><label><input type="checkbox" name="openhook_general[visualize_hooks]" value="1"<?php checked( 1, $visualize_hooks ); ?>> <?php _e( 'Enable', 'thesis-openhook' ); ?></label></p>
				<p class="description"><?php _e( 'Turning on hook visualization will output the name of each hook in use on your site in a colored box, allowing you to get a better idea of where a hook will execute at. Hook visualizations will only appear to logged in users who have the ability to access OpenHook settings. Some hooks are only used in the <code>HEAD</code> portion of your page and will appear in the page source as an <abbr title="Hypertext Markup Language">HTML</abbr> comment.', 'thesis-openhook' ); ?></p>
			</td>
		</tr>
		<tr>
			<th scope="row"><?php _e( 'Hook page display', 'thesis-openhook' ); ?></th>
			<td>
				<p><label><input type="radio" name="openhook_general[hook_page_layout]" value="all"<?php checked( 'all', $hook_page_layout ); ?>> <?php _e( 'View all hooks at once', 'thesis-openhook' ); ?></label><br>
					<label><input type="radio" name="openhook_general[hook_page_layout]" value="single"<?php checked( 'single', $hook_page_layout ); ?>> <?php _e( 'View one hook at a time', 'thesis-openhook' ); ?></label></p>
			</td>
		</tr>
	</table>
	<p class="submit"><input type="submit" class="button-primary" value="<?php _e( 'Save Changes', 'thesis-openhook' ); ?>"></p>
</form>
<h3><?php _e( 'Theme Notes', 'thesis-openhook' ); ?></h3>
<h4><?php _e( 'Headway', 'thesis-openhook' ); ?></h4>
<p><?php _e( 'Headway, in addition to the static hooks explicitly included in OpenHook, provides a number of dynamically generated hooks. To add customization to them via OpenHook, you will need to identify the name of the hook and add it to the Custom panel of OpenHook.', 'thesis-openhook' ); ?></p>
<h4><?php _e( 'Thesis 2', 'thesis-openhook' ); ?></h4>
<p><?php printf( __( 'Enable OpenBox in Thesis 2&apos;s boxes management screen, and it will be freely usable within the Thesis skin editor! Once you have added an OpenBox to your skin, save the skin, then edit the contents of the OpenBox instance in the Thesis skin content panel.', 'thesis-openhook' ), 'http://j.mp/GetThesis2' ); ?></p>

<h3><?php _e( 'Manage Options', 'thesis-openhook' ); ?></h3>
<form method="post" action="options-general.php?page=openhook&tab=general">

	<p><?php _e( 'Have you used previous versions of OpenHook? You have the option of importing your customizations into the current OpenHook system; this will not remove your old options, allowing you to downgrade if desired. Note that upgrading from OpenHook 2 will overwrite your WordPress &amp; Thesis hook customizations made with the current version of OpenHook!', 'thesis-openhook' ); ?></p>
	<p><input class="button-secondary" onclick="return confirmUpgrade();" type="submit" name="upgrade_openhook" value="<?php esc_attr_e( 'Upgrade from OpenHook 2', 'thesis-openhook' ); ?>"></p>

	<p><?php _e( 'If you&rsquo;re satisfied with OpenHook, you may remove any legacy OpenHook options found in the database as well. Note that this is irreversible, and you should consider making a backup of your database (at the very least, a backup of your options table) prior to removing the legacy options.', 'thesis-openhook' ); ?></p>
	<p><input class="button-secondary" onclick="return confirmCleanUp();" type="submit" name="cleanup_openhook" value="<?php esc_attr_e( 'Remove legacy options', 'thesis-openhook' ); ?>"></p>

	<p><?php _e( 'Want to start fresh with your customizations or wish to uninstall OpenHook? You can easily remove all of OpenHook&rsquo;s options with the following button. You are strongly encouraged to have a database backup available as this action is not reversible! (Legacy options must be removed separately, if desired.)', 'thesis-openhook' ); ?></p>
	<p><input class="button-secondary" onclick="return confirmUninstall();" type="submit" name="uninstall_openhook" value="<?php esc_attr_e( 'Remove all OpenHook options', 'thesis-openhook' ); ?>"></p>

	<p><?php _e( 'If you have used K2 Hook Up to customize the K2 theme, you can import its settings into OpenHook to take advantage of OpenHook&apos;s more powerful environment. This will not remove the old K2 Hook Up settings from your database.', 'thesis-openhook' ); ?></p>
	<p><input class="button-secondary" onclick="return confirmK2Import();" type="submit" name="k2_hook_up_import" value="<?php esc_attr_e( 'Import settings from K2 Hook Up', 'thesis-openhook' ); ?>"></p>

</form>
	<?php
	}

	/**
	 * Generate Shortcodes options
	 *
	 * @since 4.0
	 */
	private function generate_shortcodes_page() {
		$options = get_option( 'openhook_shortcodes' );
		$disable_all = ( isset( $options['disable_all'] ) && $options['disable_all'] ) ? 1 : 0;
		$email = ( isset( $options['email'] ) && $options['email'] ) ? 1 : 0;
		$global_enabled = ( isset( $options['global_enabled'] ) && $options['global_enabled'] ) ? 1 : 0;
		$global_source = ( isset( $options['global_source'] ) && $options['global_source'] ) ? $options['global_source'] : false;
		$php = ( isset( $options['php'] ) && $options['php'] ) ? 1 : 0;
		?>
<form method="post" action="options.php">
	<p><?php _e( 'In order to be a full-service customization machine, OpenHook not only provides you with access to your theme&apos;s hooks but also with the means to customize your post content itself via shortcodes.', 'thesis-openhook' ); ?></p>
	<?php settings_fields( 'openhook_settings_shortcodes' ); ?>
	<p class="submit"><input type="submit" class="button-primary" value="<?php _e( 'Save Changes', 'thesis-openhook' ); ?>"></p>
	<table class="form-table">
		<tr>
			<th scope="row">[snap url="https://example.com/" alt="Website Snapshot" w=400 h=300 class=""]</th>
			<td>
				<p><label><input type="checkbox" name="openhook_shortcodes[snap]" value="1"<?php checked( 1, $snap ); ?>> <?php _e( 'Enable', 'thesis-openhook' ); ?></label></p>
				<p class="description"><?php printf( __( 'Quickly and easily insert a periodically updated website screenshot into your post. <a href="%s" rel="noopener noreferrer" target="_blank">Examples and an explanation for how to fully use this shortcode</a> are available online.', 'thesis_openhook' ), 'https://www.rickbeckman.org/log/website-snapshot-shortcode/' ); ?></p>
		</tr>
		<tr>
			<th scope="row">[email] [/email]</th>
			<td>
				<p><label><input type="checkbox" name="openhook_shortcodes[email]" value="1"<?php checked( 1, $email ); ?>> <?php _e( 'Enable', 'thesis-openhook' ); ?></label></p>
				<p class="description"><?php  printf( __( 'If you&apos;re using email addresses within your post content, you can disguise them from the more basic email harvesters using this shortcode. It takes an email address (e.g., &ldquo;%1$s&rdquo;) and outputs an encoded version of it, which looks ordinary to human visitors but email harvesters and other robots will see an encoded form of it (e.g., <code>%2$s</code>).', 'thesis-openhook' ), 'wizard@oz.com', htmlspecialchars( antispambot( 'wizard@oz.com' ) ) ); ?></p>
			</td>
		</tr>
		<tr>
			<th scope="row">[global key=""]</th>
			<td>
				<p><label><input type="checkbox" name="openhook_shortcodes[global_enabled]" value="1"<?php checked( 1, $global_enabled ); ?>> <?php _e( 'Enable', 'thesis-openhook' ); ?></label><br>
				<label><select name="openhook_shortcodes[global_source]">
					<option value=""><?php echo esc_attr( __( 'Select page', 'thesis-openhook' ) ); ?></option> 
<?php
$pages = get_pages( array( 'post_status' => 'publish,private,draft' ) ); 
foreach ( $pages as $page ) {
	$opt = '<option value="' .  $page->ID . '"' . selected( $page->ID, $global_source, false ) . '>';
	$opt .= $page->post_title;
	$opt .= '</option>';
	echo $opt;
}
 ?>
				</select> <?php _e( 'Source page', 'thesis-openhook' ); ?></p>
				<p class="description"><?php _e( 'The <code>[global]</code> tag provides you a means of creating a collection of reusable snippets of text which can then be quickly used in posts without fuss. Examples: ad code for use in posts, affiliate codes, or standard disclaimers.', 'thesis-openhook' ); ?></p>
				<p class="description"><?php printf( __( 'You&apos;ll first need to create a page to serve as a repository of your snippets; you don&apos;t have to publish this page, so feel free to leave it as a draft. Use the <a href="%1$s">custom fields</a> of that page to add as many snippets as you want.', 'thesis-openhook' ), 'http://codex.wordpress.org/Custom_Fields#Usage' ); ?></p>
				<p class="description"><?php _e( 'The &ldquo;Name&rdquo; of the custom field will be how you access your global string; it is the &ldquo;key&rdquo; of this shortcode. The &ldquo;Value&rdquo; of the custom field will be your global string itself. So for example, if you have an affiliate code to AwesomeStore.com, you can create a new custom field with a name of &ldquo;awesomestore&rdquo; and a value of &ldquo;http://awesomestore.com/?ref=DA-AAK1939375713LKAJDKLAKDKDKDK,&rdquo; which you can then access in any of your posts like this: <code>[global=awesomestore]</code>.', 'thesis-openhook' ); ?></p>
			</td>
		</tr>
		<tr>
			<th scope="row">[php] [/php]</th>
			<td>
				<p><label><input type="checkbox" name="openhook_shortcodes[php]" value="1"<?php checked( 1, $php ); ?>> <?php _e( 'Enable', 'thesis-openhook' ); ?></label></p>
				<p class="description"><?php _e( 'Think of the <code>[php]</code> shortcode as an in-post instance of OpenBox: You can use any markup or <abbr title="PHP: Hypertext Preprocessor">PHP</abbr> code you&apos;d like. Note that code should be properly wrapped, i.e., <code>&lt;?php code(); ?&gt;</code>, and for best results, use &ldquo;Text&rdquo; view rather than the &ldquo;Visual&rdquo; view when using the classic editor or an "HTML" block when using the newer Gutenberg editor, when editing posts with this shortcode. Only administrators may utilize <code>[php]</code>.', 'thesis-openhook' ); ?></p>
			</td>
		</tr>
		<tr>
			<th scope="row"><label for="openhook_shortcodes[disable_all]"><?php _e( 'Disable all shortcodes', 'thesis-openhook' ); ?></label></th>
			<td>
				<p><input type="checkbox" name="openhook_shortcodes[disable_all]" value="1"<?php checked( 1, $disable_all ); ?>></p>
				<p class="description"><?php _e( 'If you do not use shortcodes, you can disable them here to save a bit of processor time. All shortcodes (including WordPress default shortcodes, shortcodes added by other plugins or themes, and shortcodes enabled above) will be disabled. Note that if you have used shortcodes in your posts already, the shortcodes themselves (e.g., &ldquo;[gallery]&rdquo;) will appear in your post content when shortcodes are disabled.', 'thesis-openhook' ); ?></p>
			</td>
		</tr>
	</table>
</form>
	<?php
	}

	/**
	 * Generate phpinfo() display page
	 *
	 * @since 3.0
	 */
	private function generate_phpinfo_page() {
		echo '<h3>' . __( 'Your Server Details', 'thesis-openhook' ) . '</h3>';
		echo '<p>' . sprintf( __( 'When deciding what customizations to make to your site, it can be useful to know what your server supports. The below information describes your server environment. If your server is missing a feature you require, upgrade to a more powerful host, such as <a href="%1$s" rel="noopener noreferrer" target="_blank">DreamHost</a>! Otherwise, consider contacting your own host to see if they can enable the feature(s) you want.', 'thesis-openhook' ), 'http://www.dreamhost.com/' ) . '</p>';

		$matches = array();

		# Begin content buffering to catch the output of phpinfo() for styling
		ob_start();

		# This is what we're here for
		phpinfo();

		# Capture the styling information & the body of phpinfo()
		preg_match( '%<style type="text/css">(.*?)</style>.*?<body>(.*)</body>%s', ob_get_clean(), $matches );

		# Wrap the output of phpinfo() in a DIV with a little bit of styling
		echo '<div class="phpinfo_display"><style type="text/css">.phpinfo_display { max-width: 100%; overflow: auto; } .phpinfo_display .e {
background-color: rgb(204, 204, 255) !important; } ' . join( "\n", array_map( function( $i ) { return ".phpinfo_display " . preg_replace( "/,/", ",.phpinfodisplay ", $i ); }, preg_split( '/\n/', $matches[ 1 ] ) ) ) . '</style>', $matches[2], '</div>';
}

	/**
	 * Generate contact page
	 *
	 * @since 3.0
	 */
	private function generate_contact_page() {
	?>
<p><?php _e( 'Feature request? Support request? Bug report? You&rsquo;re invited to get in touch with me!', 'thesis-openhook' ); ?></p>
<p><?php printf( __( 'Prior to submitting a bug report, remember the immortal advice: Don&rsquo;t panic! Help me help you by submitting <a href="%s">the best possible bug reports</a> you can.', 'thesis-openhook' ), 'http://www.chiark.greenend.org.uk/~sgtatham/bugs.html' ); ?>
<ul>
	<li>Twitter: <a href="http://twitter.com/OnSixes">@OnSixes</a></li>
</ul>
<script>
	id = 126606;
</script>
<script src="http://kontactr.com/wp.js"></script>
	<?php
	}

	/**
	 * Generate fieldset for our hook options
	 *
	 * @since 3.0
	 * @todo Move JavaScript to header or an external file
	 */
	private function generate_hook_page( $context ) {
		global $openhook;

		# Get the hooks we'll be dealing with
		$func = "{$context}_hooks";
		$hooks = $openhook->$func();

		# Get general options
		$general_options = get_option( 'openhook_general' );
		$layout = isset( $general_options['hook_page_layout'] ) ? $general_options['hook_page_layout'] : 'single';
		$active_theme = ( isset( $general_options['active_theme'] ) && $general_options['active_theme'] && 'none' != $general_options['active_theme'] ) ? $general_options['active_theme'] : 'none'; 
		$actions_active = ( ( isset( $general_options['active_actions'][ $context ] ) && (bool) $general_options['active_actions'][ $context ] ) || $context == $active_theme ) ? 1 : 0;

		# Output necessary JavaScript
		if ( 'single' === $layout ) {
			$this->do_hook_panel_js( $hooks );
		}

		if ( ! $actions_active ) { ?>
<div id='openhook-warning' class='updated fade'><p><?php printf( __( '%1$s actions are currently <strong>disabled</strong>. When you are ready for your customizations to appear on your site, please enable the %1$s action groups in the <a href="%2$s">general OpenHook settings</a>.', 'thesis-openhook' ), str_replace( 'Wordpress', 'WordPress', ucfirst( $context ) ), admin_url( 'options-general.php?page=openhook' ) ); ?></p></div>
		<?php
		}
		?>
<form method="post" action="options.php">
	<?php settings_fields( "openhook_settings_$context" ); ?>
	<?php $options = get_option( "openhook_$context" ); ?>
	<?php if ( 'custom' === $context ) : ?><p><?php _e( 'Many great WordPress themes utilize hooks, and whether OpenHook has explicit support for them or not, you can define any hook that you want to below, enabling you to customize all of those themes, including those, such as Headway, which create dynamic hook names.', 'thesis-openhook' ); ?></p>
	<p>
		<label><?php _e( 'Add a custom hook: ', 'thesis-openhook' ); ?><input type="text" placeholder="<?php esc_attr_e( 'custom_hook_example', 'thesis-openhook' ); ?>" name="openhook_custom[new_hook]"></label> <input type="submit" class="button-secondary" value="<?php _e( 'Save Changes', 'thesis-openhook' ); ?>">
	</p><?php endif; ?>
	<?php if ( 'single' === $layout ) : ?><p><?php _e( 'You can freely swap between hooks on this page without losing your customizations. When you are finished customizing, the save button will save all hook customizations at once.', 'thesis-openhook' ); ?></p>
	<p>
		<input type="submit" class="button-primary" value="<?php _e( 'Save Changes', 'thesis-openhook' ); ?>" style="float: left;">
		<label style="float: right; text-align: right;"><?php _e( 'Hook selection:', 'thesis-openhook' ); ?> <?php $this->build_hook_select_selector( $hooks, $options ); ?></label>
	</p><?php endif; ?>
	<div class="clear"></div>
		<?php
		if ( is_array( $hooks ) ) {
			foreach ( $hooks as $hook => $info) {
				$this->build_hook_forms( $hook, $info, $options, $context, $layout );
			}
		}
		?>
	<p class="submit"><input type="submit" class="button-primary" value="<?php _e( 'Save Changes', 'thesis-openhook' ); ?>"></p>
</form>	
	<?php
	}

	/**
	 * Outputs the form for hook customization
	 *
	 * @param $hook string Name of the hook in question
	 * @param $info array Information about the hook to be customized
	 * @param $options array Current customizations for display in the form
	 * @param $context string Which hook group are we working with
	 * @since 3.0
	 */
	private function build_hook_forms( $hook, $info, $options, $context, $layout ) {
		$action = ( isset( $options[ $hook ]['action'] ) && '' != $options[ $hook ]['action'] ) ? $options[ $hook ]['action'] : '';
		$priority = ( isset( $options[ $hook ]['priority'] ) && is_int( (int) $options[ $hook ]['priority'] ) ) ? $options[ $hook ]['priority'] : '';
		$php = ( isset( $options[ $hook ]['php'] ) && $options[ $hook ]['php'] ) ? 1 : 0;
		$shortcodes = ( isset( $options[ $hook ]['shortcodes'] ) && $options[ $hook ]['shortcodes'] ) ? 1 : 0;
		$disable = ( isset( $options[ $hook ]['disable'] ) && $options[ $hook ]['disable'] ) ? 1 : 0;
		$unhook = 0;
	?>
	<fieldset class="hook" id="hook_<?php echo $hook; ?>">
		<h3><?php echo $hook; ?><?php if ( 'all' === $layout ) : ?><input style="margin-left: 2em;" type="submit" class="button-primary" value="<?php _e( 'Save all changes', 'thesis-openhook' ); ?>"><?php endif; ?></h3>
		<?php if ( isset( $info['desc'] ) ) : echo '<p class="description">' . $info['desc'] . '</p>'; endif; ?>
		<textarea placeholder="<?php esc_attr_e( 'Enter your custom action here&hellip;', 'thesis-openhook'); ?>" rows="10" cols="50" name="openhook_<?php echo $context; ?>[<?php echo $hook; ?>][action]"><?php echo esc_textarea( $action ); ?></textarea>
		<?php if ( isset( $info['args'] ) ) : ?>
		<p class="description"><?php echo _n( 'This action is passed the following variable for use within <abbr title="PHP: Hypertext Preprocessor">PHP</abbr>, if desired:', 'This action is passed the following variables for use within <abbr title="PHP: Hypertext Preprocessor">PHP</abbr>, if desired:', count( $info['args'] ), 'thesis-openhook' ); ?></p>
		<ul class="action_args">
			<?php foreach ( $info['args'] as $arg => $arginfo ) : ?>
			<li><?php printf( __( '%1$s — %2$s', 'thesis-openhook' ), "<code>$arg</code>", $arginfo ); ?></li>
			<?php endforeach; ?>
		</ul>
		<?php endif; ?>
		<p>
			<label><input type="number" name="openhook_<?php echo $context; ?>[<?php echo $hook; ?>][priority]" value="<?php echo $priority; ?>" placeholder="10" min="1" max="<?php echo PHP_INT_MAX; ?>" step="1"> <?php _e( 'Priority (default: 10); can be any positive integer', 'thesis-openhook' ); ?></label><br>
			<label><input type="checkbox" name="openhook_<?php echo $context; ?>[<?php echo $hook; ?>][php]" value="1"<?php checked( '1', $php ); ?>> <?php _e( 'Process <abbr title="PHP: Hypertext Preprocessor">PHP</abbr> on this hook? (Your code must be wrapped in <code>&lt;?php ?&gt;</code> tags)', 'thesis-openhook'); ?></label><br>
			<label><input type="checkbox" name="openhook_<?php echo $context; ?>[<?php echo $hook; ?>][shortcodes]" value="1"<?php checked( '1', $shortcodes ); ?>> <?php _e( 'Process shortcodes on this hook?', 'thesis-openhook'); ?></label><br>
			<label><input type="checkbox" name="openhook_<?php echo $context; ?>[<?php echo $hook; ?>][disable]" value="1"<?php checked( '1', $disable ); ?>> <?php _e( 'Disable this action? This will keep your action saved, but will prevent it from being processed.', 'thesis-openhook' ); ?></label>
		<?php if ( 'custom' === $context ) : ?>
			<label><input type="checkbox" name="openhook_<?php echo $context; ?>[<?php echo $hook; ?>][delete]" value="1"> <?php _e( 'Delete this custom hook? This hook and any custom action saved to it will be deleted.', 'thesis-openhook' ); ?></label>
		<?php endif; ?>
		</p>
		<?php if ( isset( $info['unhook'] ) ) : ?>
		<h4><?php _e( 'Remove Default Actions', 'thesis-openhook' ); ?></h4>
		<p class="description"><?php _e( 'The following actions may be attached to this hook; selecting them below will remove them from this hook so that you may use them elsewhere, if you so choose. They may have already been disabled via other means, such as by a plugin. The following settings apply even if you have disabled the custom action using the above setting.', 'thesis-openhook' ); ?></p>
<?php
			foreach ( $info['unhook'] as $action => $actinfo ) {
				$unhook = ( isset( $options[ $hook ]['unhook'][ $action ] ) && $options[ $hook ]['unhook'][ $action ] ) ? 1 : 0;
			?>
			<p>
				<label><input type="checkbox" name="openhook_<?php echo $context; ?>[<?php echo $hook; ?>][unhook][<?php echo $action; ?>]" value="1"<?php checked( '1', $unhook ); ?>> <?php echo $action; ?>()</label><br>
				<span class="description"><?php echo $actinfo['desc']; ?></span>
			</p>
		<?php
				}
		endif;
		?>
		<?php if ( 'custom' === $context ) : ?><input type="hidden" name="active" value="1"><?php endif; ?>
	</fieldset>
	<?php
	}

	/**
	 * Handle validating custom options
	 *
	 * @param $input The unvalidated actions
	 * @return array The validated actions
	 * @since 4.2.0
	 */
	public function validate_custom_actions( $input ) {
		$current = get_option( 'openhook_custom' );

		# Handle creation of a new hook
		if ( isset( $input['new_hook'] ) && '' != $input['new_hook'] ) {
			$new_hook = preg_replace( '/[^\p{L}\p{N}\-\_]/u', '', $input['new_hook'] );

			# Alert users to duplicated hook names
			if ( isset( $current[ $new_hook ] ) ) {
				add_settings_error( 'openhook_custom', '', __( 'The custom hook you have attempted to create already exists. Any other changes you made to the actions on this page have been saved.', 'thesis-openhook' ) );
			} else {
				# If the hook isn't a duplicate, give it a proper place in the input array
				$input[ $new_hook ]['active'] = 1;
			}
		}

		# Unneeded beyond this point
		unset( $input['new_hook'] );

		# Handle hook deletion
		if ( is_array( $input ) ) {
			foreach ( $input as $hook => $details ) {
				if ( isset( $details['delete'] ) && 1 == $details['delete'] ) {
					unset( $input[ $hook ] );
				} 
			}
		}

		return $input;
	}

	/**
	 * Import K2 Hook up options into OpenHook
	 *
	 * @global object $openhook Main OpenHook class object
	 * @since 4.2.0
	 */
	private function import_k2_hook_up_options() {
		global $openhook;

		$auth = $openhook->get_auth_level();

		if ( current_user_can( $auth ) ) {
			$k2 = array();
			$k2['template_body_top']['action'] = stripslashes( get_option( 'k2hookup_template_body_top' ) );
			$k2['template_body_top']['php'] = get_option( 'k2hookup_template_body_top' );
			$k2['template_before_header']['action'] = stripslashes( get_option( 'k2hookup_template_before_header' ) );
			$k2['template_before_header']['php'] = get_option( 'k2hookup_template_before_header_top' );
			$k2['template_header']['action'] = stripslashes( get_option( 'k2hookup_template_header' ) );
			$k2['template_header']['php'] = get_option( 'k2hookup_template_header_php' );
			$k2['template_header_menu']['action'] = stripslashes( get_option( 'k2hookup_template_header_menu' ) );
			$k2['template_header_menu']['php'] = get_option( 'k2hookup_template_header_menu_php' );
			$k2['template_primary_begin']['action'] = stripslashes( get_option( 'k2hookup_template_primary_begin' ) );
			$k2['template_primary_begin']['php'] = get_option( 'k2hookup_template_primary_begin_php' );
			$k2['template_primary_end']['action'] = stripslashes( get_option( 'k2hookup_template_primary_end' ) );
			$k2['template_primary_end']['php'] = get_option( 'k2hookup_template_primary_end_php' );
			$k2['template_before_content']['action'] = stripslashes( get_option( 'k2hookup_template_before_content' ) );
			$k2['template_before_content']['php'] = get_option( 'k2hookup_template_before_content_php' );
			$k2['template_after_content']['action'] = stripslashes( get_option( 'k2hookup_template_after_content' ) );
			$k2['template_after_content']['php'] = get_option( 'k2hookup_template_after_content_php' );
			$k2['template_entry_head']['action'] = stripslashes( get_option( 'k2hookup_template_entry_head' ) );
			$k2['template_entry_head']['php'] = get_option( 'k2hookup_template_entry_head_php' );
			$k2['template_entry_foot']['action'] = stripslashes( get_option( 'k2hookup_template_entry_foot' ) );
			$k2['template_entry_foot']['php'] = get_option( 'k2hookup_template_entry_foot_php' );
			$k2['template_before_footer']['action'] = stripslashes( get_option( 'k2hookup_template_before_footer' ) );
			$k2['template_before_footer']['php'] = get_option( 'k2hookup_template_before_footer' );
			$k2['template_footer']['action'] = stripslashes( get_option( 'k2hookup_template_footer' ) );
			$k2['template_footer']['php'] = get_option( 'k2hookup_template_footer_php' );

			update_option( 'openhook_k2', $k2 );

			echo '<div id="setting-error-settings_updated" class="updated settings-error"><p><strong>' . __( 'You have successfully imported the customizations made in K2 Hook Up to OpenHook.', 'thesis-openhook' ) . '</strong></p></div>';
		} else {
			wp_die( __( 'You do not have permission to do this.', 'thesis-openhook' ) );
		}
	}

	/**
	 * Import OpenHook 2 options into current OpenHook
	 *
	 * @global object $openhook Main OpenHook class object
	 * @since 3.0
	 */
	private function import_old_options() {
		global $openhook;

		$auth = $openhook->get_auth_level();

		if ( current_user_can( $auth ) ) {
			$wordpress = array();
			$thesis = array();

			$wordpress['wp_head']['action'] = stripslashes( get_option( 'openhook_wp_head' ) );
			$wordpress['wp_head']['php'] = get_option( 'openhook_wp_head_php' );
			$wordpress['wp_footer']['action'] = stripslashes( get_option( 'openhook_wp_footer' ) );
			$wordpress['wp_footer']['php'] = get_option( 'openhook_wp_footer_php' );

			update_option( 'openhook_wordpress', $wordpress );

			$thesis['thesis_hook_before_html']['action'] = stripslashes( get_option( 'openhook_before_html' ) );
			$thesis['thesis_hook_before_html']['php'] = get_option( 'openhook_before_html_php' );
			$thesis['thesis_hook_after_html']['action'] = stripslashes( get_option( 'openhook_after_html' ) );
			$thesis['thesis_hook_before_html']['php'] = get_option( 'openhook_after_html_php' );
			$thesis['thesis_hook_before_header']['action'] = stripslashes( get_option( 'openhook_before_header' ) );
			$thesis['thesis_hook_before_header']['php'] = get_option( 'openhook_before_header_php' );
			$thesis['thesis_hook_before_header']['unhook']['thesis_nav_menu'] = get_option( 'openhook_before_header_nav_menu' );
			$thesis['thesis_hook_after_header']['action'] = stripslashes( get_option( 'openhook_after_header' ) );
			$thesis['thesis_hook_after_header']['php'] = get_option( 'openhook_after_header_php' );
			$thesis['thesis_hook_header']['action'] = stripslashes( get_option( 'openhook_header' ) );
			$thesis['thesis_hook_header']['php'] = get_option( 'openhook_header_php' );
			$thesis['thesis_hook_header']['unhook']['thesis_default_header'] = get_option( 'openhook_header_default_header' );
			$thesis['thesis_hook_before_title']['action'] = stripslashes( get_option( 'openhook_before_title' ) );
			$thesis['thesis_hook_before_title']['php'] = get_option( 'openhook_before_title_php' );
			$thesis['thesis_hook_after_title']['action'] = stripslashes( get_option( 'openhook_after_title' ) );
			$thesis['thesis_hook_after_title']['php'] = get_option( 'openhook_after_title_php' );
			$thesis['thesis_hook_before_content_box']['action'] = stripslashes( get_option( 'openhook_before_content_box' ) );
			$thesis['thesis_hook_before_content_box']['php'] = get_option( 'openhook_before_content_box_php' );
			$thesis['thesis_hook_after_content_box']['action'] = stripslashes( get_option( 'openhook_after_content_box' ) );
			$thesis['thesis_hook_after_content_box']['php'] = get_option( 'openhook_after_content_box_php' );
			$thesis['thesis_hook_before_content']['action'] = stripslashes( get_option( 'openhook_before_content' ) );
			$thesis['thesis_hook_before_content']['php'] = get_option( 'openhook_before_content_php' );
			$thesis['thesis_hook_after_content']['action'] = stripslashes( get_option( 'openhook_after_content' ) );
			$thesis['thesis_hook_after_content']['php'] = get_option( 'openhook_after_content_php' );
			$thesis['thesis_hook_before_content_area']['action'] = stripslashes( get_option( 'openhook_before_content_area' ) );
			$thesis['thesis_hook_before_content_area']['php'] = get_option( 'openhook_before_content_area_php' );
			$thesis['thesis_hook_after_content_area']['action'] = stripslashes( get_option( 'openhook_after_content_area' ) );
			$thesis['thesis_hook_after_content_area']['php'] = get_option( 'openhook_after_content_area_php' );
			$thesis['thesis_hook_after_content_area']['unhook']['thesis_post_navigation'] = get_option( 'openhook_after_content_post_navigation' );
			$thesis['thesis_hook_after_content_area']['unhook']['thesis_prev_next_posts'] = get_option( 'openhook_after_content_prev_next_posts' );
			$thesis['thesis_hook_post_box_top']['action'] = stripslashes( get_option( 'openhook_post_box_top' ) );
			$thesis['thesis_hook_post_box_top']['php'] = get_option( 'openhook_post_box_top_php' );
			$thesis['thesis_hook_post_box_bottom']['action'] = stripslashes( get_option( 'openhook_post_box_bottom' ) );
			$thesis['thesis_hook_post_box_bottom']['php'] = get_option( 'openhook_post_box_bottom_php' );
			$thesis['thesis_hook_content_box_top']['action'] = stripslashes( get_option( 'openhook_content_box_top' ) );
			$thesis['thesis_hook_content_box_top']['php'] = get_option( 'openhook_content_box_top_php' );
			$thesis['thesis_hook_content_box_bottom']['action'] = stripslashes( get_option( 'openhook_content_box_bottom' ) );
			$thesis['thesis_hook_content_box_bottom']['php'] = get_option( 'openhook_content_box_bottom_php' );
			$thesis['thesis_hook_feature_box']['action'] = stripslashes( get_option( 'openhook_feature_box' ) );
			$thesis['thesis_hook_feature_box']['php'] = get_option( 'openhook_feature_box_php' );
			$thesis['thesis_hook_before_post_box']['action'] = stripslashes( get_option( 'openhook_before_post_box' ) );
			$thesis['thesis_hook_before_post_box']['php'] = get_option( 'openhook_before_post_box_php' );
			$thesis['thesis_hook_after_post_box']['action'] = get_option( 'openhook_after_post_box' );
			$thesis['thesis_hook_after_post_box']['php'] = get_option( 'openhook_after_post_box_php' );
			$thesis['thesis_hook_before_teasers_box']['action'] = stripslashes( get_option( 'openhook_before_teasers_box' ) );
			$thesis['thesis_hook_before_teasers_box']['php'] = get_option( 'openhook_before_teasers_box_php' );
			$thesis['thesis_hook_after_teasers_box']['action'] = stripslashes( get_option( 'openhook_after_teasers_box' ) );
			$thesis['thesis_hook_after_teasers_box']['php'] = get_option( 'openhook_after_teasers_box_php' );
			$thesis['thesis_hook_before_post']['action'] = stripslashes( get_option( 'openhook_before_post' ) );
			$thesis['thesis_hook_before_post']['php'] = get_option( 'openhook_before_post_php' );
			$thesis['thesis_hook_after_post']['action'] = stripslashes( get_option( 'openhook_after_post' ) );
			$thesis['thesis_hook_after_post']['php'] = get_option( 'openhook_after_post_php' );
			$thesis['thesis_hook_after_post']['unhook']['thesis_post_tags'] = get_option( 'openhook_after_post_post_tags' );
			$thesis['thesis_hook_after_post']['unhook']['thesis_comments_link'] = get_option( 'openhook_after_post_comments_link' );
			$thesis['thesis_hook_before_teaser_box']['action'] = stripslashes( get_option( 'openhook_before_teaser_box' ) );
			$thesis['thesis_hook_before_teaser_box']['php'] = get_option( 'openhook_before_teaser_box_php' );
			$thesis['thesis_hook_after_teaser_box']['action'] = stripslashes( get_option( 'openhook_after_teaser_box' ) );
			$thesis['thesis_hook_after_teaser_box']['php'] = get_option( 'openhook_after_teaser_box_php' );
			$thesis['thesis_hook_before_teaser']['action'] = stripslashes( get_option( 'openhook_before_teaser' ) );
			$thesis['thesis_hook_before_teaser']['php'] = get_option( 'openhook_before_teaser_php' );
			$thesis['thesis_hook_after_teaser']['action'] = stripslashes( get_option( 'openhook_after_teaser' ) );
			$thesis['thesis_hook_after_teaser']['php'] = get_option( 'openhook_after_teaser_php' );
			$thesis['thesis_hook_before_headline']['action'] = stripslashes( get_option( 'openhook_before_headline' ) );
			$thesis['thesis_hook_before_headline']['php'] = get_option( 'openhook_before_headline_php' );
			$thesis['thesis_hook_after_headline']['action'] = stripslashes( get_option( 'openhook_after_headline' ) );
			$thesis['thesis_hook_after_headline']['php'] = get_option( 'openhook_after_headline_php' );
			$thesis['thesis_hook_before_teaser_headline']['action'] = stripslashes( get_option( 'openhook_before_teaser_headline' ) );
			$thesis['thesis_hook_before_teaser_headline']['php'] = get_option( 'openhook_before_teaser_headline_php' );
			$thesis['thesis_hook_after_teaser_headline']['action'] = stripslashes( get_option( 'openhook_after_teaser_headline' ) );
			$thesis['thesis_hook_after_teaser_headline']['php'] = get_option( 'openhook_after_teaser_headline_php' );
			$thesis['thesis_hook_byline_item']['action'] = stripslashes( get_option( 'openhook_byline_item' ) );
			$thesis['thesis_hook_byline_item']['php'] = get_option( 'openhook_byline_item_php' );
			$thesis['thesis_hook_before_comment_meta']['action'] = stripslashes( get_option( 'openhook_before_comment_meta' ) );
			$thesis['thesis_hook_before_comment_meta']['php'] = get_option( 'openhook_before_comment_meta_php' );
			$thesis['thesis_hook_after_comment_meta']['action'] = stripslashes( get_option( 'openhook_after_comment_meta' ) );
			$thesis['thesis_hook_after_comment_meta']['php'] = get_option( 'openhook_after_comment_meta_php' );
			$thesis['thesis_hook_comment_field']['action'] = stripslashes( get_option( 'openhook_comment_field' ) );
			$thesis['thesis_hook_comment_field']['php'] = get_option( 'openhook_comment_field_php' );
			$thesis['thesis_hook_comment_form']['action'] = stripslashes( get_option( 'openhook_comment_form' ) );
			$thesis['thesis_hook_comment_form']['php'] = get_option( 'openhook_comment_form_php' );
			$thesis['thesis_hook_comment_form']['unhook']['show_subscription_checkbox'] = get_option( 'openhook_comment_form_show_subscription_checkbox' );
			$thesis['thesis_hook_archives_template']['action'] = stripslashes( get_option( 'openhook_archives_template' ) );
			$thesis['thesis_hook_archives_template']['php'] = get_option( 'openhook_archives_template_php' );
			$thesis['thesis_hook_archives_template']['unhook']['thesis_archives_template'] = get_option( 'openhook_archives_template_archives_template' );
			$thesis['thesis_hook_custom_template']['action'] = stripslashes( get_option( 'openhook_custom_template' ) );
			$thesis['thesis_hook_custom_template']['php'] = get_option( 'openhook_custom_template_php' );
			$thesis['thesis_hook_custom_template']['unhook']['thesis_custom_template_sample'] = get_option( 'openhook_custom_template_custom_template_sample' );
			$thesis['thesis_hook_faux_admin']['action'] = stripslashes( get_option( 'openhook_faux_admin' ) );
			$thesis['thesis_hook_faux_admin']['php'] = get_option( 'openhook_faux_admin_php' );
			$thesis['thesis_hook_404_title']['action'] = stripslashes( get_option( 'openhook_404_title' ) );
			$thesis['thesis_hook_404_title']['php'] = get_option( 'openhook_404_title_php' );
			$thesis['thesis_hook_404_title']['unhook']['thesis_404_title'] = get_option( 'openhook_404_title_404_title' );
			$thesis['thesis_hook_404_content']['action'] = stripslashes( get_option( 'openhook_404_content' ) );
			$thesis['thesis_hook_404_content']['php'] = get_option( 'openhook_404_content_php' );
			$thesis['thesis_hook_404_content']['unhook']['thesis_404_content'] = get_option( 'openhook_404_content_404_content' );
			$thesis['thesis_hook_before_sidebars']['action'] = stripslashes( get_option( 'openhook_before_sidebars' ) );
			$thesis['thesis_hook_before_sidebars']['php'] = get_option( 'openhook_before_sidebars_php' );
			$thesis['thesis_hook_after_sidebars']['action'] = stripslashes( get_option( 'openhook_after_sidebars' ) );
			$thesis['thesis_hook_after_sidebars']['php'] = get_option( 'openhook_after_sidebars_php' );
			$thesis['thesis_hook_after_multimedia_box']['action'] = stripslashes( get_option( 'openhook_after_multimedia_box' ) );
			$thesis['thesis_hook_after_multimedia_box']['php'] = get_option( 'openhook_after_multimedia_box_php' );
			$thesis['thesis_hook_multimedia_box']['action'] = stripslashes( get_option( 'openhook_multimedia_box' ) );
			$thesis['thesis_hook_multimedia_box']['php'] = get_option( 'openhook_multimedia_box_php' );
			$thesis['thesis_hook_before_sidebar_1']['action'] = stripslashes( get_option( 'openhook_before_sidebar_1' ) );
			$thesis['thesis_hook_before_sidebar_1']['php'] = get_option( 'openhook_before_sidebar_1_php' );
			$thesis['thesis_hook_after_sidebar_1']['action'] = stripslashes( get_option( 'openhook_after_sidebar_1' ) );
			$thesis['thesis_hook_after_sidebar_1']['php'] = get_option( 'openhook_after_sidebar_1_php' );
			$thesis['thesis_hook_before_sidebar_2']['action'] = stripslashes( get_option( 'openhook_before_sidebar_2' ) );
			$thesis['thesis_hook_before_sidebar_2']['php'] = get_option( 'openhook_before_sidebar_2_php' );
			$thesis['thesis_hook_after_sidebar_2']['action'] = stripslashes( get_option( 'openhook_after_sidebar_2' ) );
			$thesis['thesis_hook_after_sidebar_2']['php'] = get_option( 'openhook_after_sidebar_2_php' );
			$thesis['thesis_hook_before_footer']['action'] = stripslashes( get_option( 'openhook_before_footer' ) );
			$thesis['thesis_hook_before_footer']['php'] = get_option( 'openhook_before_footer_php' );
			$thesis['thesis_hook_after_footer']['action'] = stripslashes( get_option( 'openhook_after_footer' ) );
			$thesis['thesis_hook_after_footer']['php'] = get_option( 'openhook_after_footer_php' );
			$thesis['thesis_hook_footer']['action'] = stripslashes( get_option( 'openhook_footer' ) );
			$thesis['thesis_hook_footer']['php'] = get_option( 'openhook_footer_php' );
			$thesis['thesis_hook_footer']['unhook']['thesis_attribution'] = get_option( 'openhook_footer_thesis_attribution' );

			update_option( 'openhook_thesis', $thesis );

			echo '<div id="setting-error-settings_updated" class="updated settings-error"><p><strong>' . __( 'You have successfully upgraded OpenHook.', 'thesis-openhook' ) . '</strong></p></div>';
		} else {
			wp_die( __( 'You do not have permission to do this.', 'thesis-openhook' ) );
		}
	}

	/**
	 * Delete options
	 *
	 * @global object $openhook Main OpenHook class object
	 * @param string $scope Which set of options to delete: legacy (2.x.x) or current
	 * @since 3.0
	 */
	private function delete_options( $scope = 'legacy' ) {
		global $openhook;

		$auth = $openhook->get_auth_level();

		if ( current_user_can( $auth ) ) {
			if ( 'legacy' === $scope ) {
				delete_option( 'openhook_save_button' );
				delete_option( 'openhook_wp_head' );
				delete_option( 'openhook_wp_head_php' );
				delete_option( 'openhook_before_html' );
				delete_option( 'openhook_before_html_php' );
				delete_option( 'openhook_after_html' );
				delete_option( 'openhook_after_html_php' );
				delete_option( 'openhook_after_html_footer_scripts' );
				delete_option( 'openhook_after_html_ie_clear' );
				delete_option( 'openhook_before_header' );
				delete_option( 'openhook_before_header_php' );
				delete_option( 'openhook_before_header_nav_menu' );
				delete_option( 'openhook_after_header' );
				delete_option( 'openhook_after_header_php' );
				delete_option( 'openhook_header' );
				delete_option( 'openhook_header_php' );
				delete_option( 'openhook_header_default_header' );
				delete_option( 'openhook_before_title' );
				delete_option( 'openhook_before_title_php' );
				delete_option( 'openhook_after_title' );
				delete_option( 'openhook_after_title_php' );
				delete_option( 'openhook_before_content_box' );
				delete_option( 'openhook_before_content_box_php' );
				delete_option( 'openhook_after_content_box' );
				delete_option( 'openhook_after_content_box_php' );
				delete_option( 'openhook_before_content' );
				delete_option( 'openhook_before_content_php' );
				delete_option( 'openhook_after_content' );
				delete_option( 'openhook_after_content_php' );
				delete_option( 'openhook_before_content_area' );
				delete_option( 'openhook_before_content_area_php' );
				delete_option( 'openhook_after_content_area' );
				delete_option( 'openhook_after_content_area_php' );
				delete_option( 'openhook_after_content_post_navigation' );
				delete_option( 'openhook_after_content_prev_next_posts' );
				delete_option( 'openhook_post_box_top' );
				delete_option( 'openhook_post_box_top_php' );
				delete_option( 'openhook_post_box_bottom' );
				delete_option( 'openhook_post_box_bottom_php' );
				delete_option( 'openhook_content_box_top' );
				delete_option( 'openhook_content_box_top_php' );
				delete_option( 'openhook_content_box_bottom' );
				delete_option( 'openhook_content_box_bottom_php' );
				delete_option( 'openhook_feature_box' );
				delete_option( 'openhook_feature_box_php' );
				delete_option( 'openhook_before_post_box' );
				delete_option( 'openhook_before_post_box_php' );
				delete_option( 'openhook_before_post_box_add_post_image' );
				delete_option( 'openhook_after_post_box' );
				delete_option( 'openhook_after_post_box_php' );
				delete_option( 'openhook_before_teasers_box' );
				delete_option( 'openhook_before_teasers_box_php' );
				delete_option( 'openhook_after_teasers_box' );
				delete_option( 'openhook_after_teasers_box_php' );
				delete_option( 'openhook_before_post' );
				delete_option( 'openhook_before_post_php' );
				delete_option( 'openhook_after_post' );
				delete_option( 'openhook_after_post_php' );
				delete_option( 'openhook_after_post_trackback_rdf' );
				delete_option( 'openhook_after_post_post_tags' );
				delete_option( 'openhook_after_post_comments_link' );
				delete_option( 'openhook_before_teaser_box' );
				delete_option( 'openhook_before_teaser_box_php' );
				delete_option( 'openhook_before_teaser_box_add_thumb' );
				delete_option( 'openhook_after_teaser_box' );
				delete_option( 'openhook_after_teaser_box_php' );
				delete_option( 'openhook_before_teaser' );
				delete_option( 'openhook_before_teaser_php' );
				delete_option( 'openhook_after_teaser' );
				delete_option( 'openhook_after_teaser_php' );
				delete_option( 'openhook_before_headline' );
				delete_option( 'openhook_before_headline_php' );
				delete_option( 'openhook_after_headline' );
				delete_option( 'openhook_after_headline_php' );
				delete_option( 'openhook_before_teaser_headline' );
				delete_option( 'openhook_before_teaser_headline_php' );
				delete_option( 'openhook_after_teaser_headline' );
				delete_option( 'openhook_after_teaser_headline_php' );
				delete_option( 'openhook_byline_item' );
				delete_option( 'openhook_byline_item_php' );
				delete_option( 'openhook_before_comment_meta' );
				delete_option( 'openhook_before_comment_meta_php' );
				delete_option( 'openhook_after_comment_meta' );
				delete_option( 'openhook_after_comment_meta_php' );
				delete_option( 'openhook_comment_field' );
				delete_option( 'openhook_comment_field_php' );
				delete_option( 'openhook_comment_form' );
				delete_option( 'openhook_comment_form_php' );
				delete_option( 'openhook_comment_form_show_subscription_checkbox' );
				delete_option( 'openhook_archives_template' );
				delete_option( 'openhook_archives_template_php' );
				delete_option( 'openhook_archives_template_archives_template' );
				delete_option( 'openhook_archive_info_default_archive_info' );
				delete_option( 'openhook_custom_template' );
				delete_option( 'openhook_custom_template_php' );
				delete_option( 'openhook_custom_template_custom_template_sample' );
				delete_option( 'openhook_faux_admin' );
				delete_option( 'openhook_faux_admin_php' );
				delete_option( 'openhook_archive_info' );
				delete_option( 'openhook_archive_info_php' );
				delete_option( 'openhook_404_title' );
				delete_option( 'openhook_404_title_php' );
				delete_option( 'openhook_404_title_404_title' );
				delete_option( 'openhook_404_content' );
				delete_option( 'openhook_404_content_php' );
				delete_option( 'openhook_404_content_404_content' );
				delete_option( 'openhook_before_sidebars' );
				delete_option( 'openhook_before_sidebars_php' );
				delete_option( 'openhook_after_sidebars' );
				delete_option( 'openhook_after_sidebars_php' );
				delete_option( 'openhook_after_multimedia_box' );
				delete_option( 'openhook_after_multimedia_box_php' );
				delete_option( 'openhook_multimedia_box' );
				delete_option( 'openhook_multimedia_box_php' );
				delete_option( 'openhook_before_sidebar_1' );
				delete_option( 'openhook_before_sidebar_1_php' );
				delete_option( 'openhook_after_sidebar_1' );
				delete_option( 'openhook_after_sidebar_1_php' );
				delete_option( 'openhook_before_sidebar_2' );
				delete_option( 'openhook_before_sidebar_2_php' );
				delete_option( 'openhook_after_sidebar_2' );
				delete_option( 'openhook_after_sidebar_2_php' );
				delete_option( 'openhook_before_footer' );
				delete_option( 'openhook_before_footer_php' );
				delete_option( 'openhook_after_footer' );
				delete_option( 'openhook_after_footer_php' );
				delete_option( 'openhook_footer' );
				delete_option( 'openhook_footer_php' );
				delete_option( 'openhook_footer_thesis_attribution' );
				delete_option( 'openhook_footer_debug_info' );
				delete_option( 'openhook_footer_honeypot' );
				delete_option( 'openhook_wp_footer' );
				delete_option( 'openhook_wp_footer_php' );
				delete_option( 'openhook_footer_admin_link' );

				echo '<div id="setting-error-settings_updated" class="updated settings-error"><p><strong>' . __( 'Legacy OpenHook options have been removed.', 'thesis-openhook' ) . '</strong></p></div>';
			} elseif ( 'current' === $scope ) {
				delete_option( 'openhook_custom' );
				delete_option( 'openhook_astra' );
				delete_option( 'openhook_flat' );
				delete_option( 'openhook_general' );
				delete_option( 'openhook_headway' );
				delete_option( 'openhook_shortcodes' );
				delete_option( 'openhook_tha' );
				delete_option( 'openhook_thesis' );
				delete_option( 'openhook_wordpress' );
				delete_option( 'openhook_version' );

				echo '<div id="setting-error-settings_updated" class="updated settings-error"><p><strong>' . sprintf( __( 'OpenHook %s options have been removed.', 'thesis-openhook' ), OPENHOOK_VERSION ) . '</strong></p></div>';
			}
		} else {
			wp_die( __( 'You do not have permission to do this.', 'thesis-openhook' ) );
		}
	}
}