<?php
/**
 * Contains shortcode functions
 *
 * @since 4.0
 */

# Prevent direct access to this file
if ( 1 == count( get_included_files() ) ) {
	header( 'HTTP/1.1 403 Forbidden' );
	return;
}

class OpenHook_ShortCodes {
	/**
	 * PHP shortcode to process PHP in posts
	 *
	 * @global object $openhook Main OpenHook class object
	 */
	public function php( $atts, $content = null ) {
		global $openhook;

		# Prevent access to the shortcode via Ajax
		if ( defined( 'DOING_AJAX' ) && DOING_AJAX ) {
			return;
		}

		# Only process this shortcode if the author of the post has the authority
		if ( author_can( get_the_ID(), $openhook->get_auth_level() ) ) {
			# Buffer the output of the PHP as we don't want to echo anything here
			ob_start();

			eval( "?>$content<?php " );
		
			return ob_get_clean();
		} else {
			return;
		}
	}

	/**
	 * Obfuscates a given email address to provide additional protection
	 * against email harvesters
	 */
	public function email( $atts , $content = null ) {
		return antispambot( $content );
	}

	/**
	 * Global custom fields, adapted from
	 * http://digwp.com/2009/09/global-custom-fields/
	 */
	public function globals($atts) {
		# Get the desired key
		extract( shortcode_atts( array( 'key' => false ), $atts ) );

		# Determine the source of our global values
		$options = get_option( 'openhook_shortcodes' );
		$source = ( isset( $options[ 'global_source' ] ) && $options[ 'global_source' ] ) ? $options[ 'global_source' ] : false;

		# Only attempt to pull a global if both a key & source page are set
		if ( (string) $key && $source ) {
			return get_post_meta( $source, $key, true );
		} else {
			return;
		}
	}

	/**
	 * [snap] - Website snapshot shortcode
	 *
	 * @via https://www.rickbeckman.org/
	 * @inspiredby http://www.geekeries.fr/snippet/creer-automatiquement-miniatures-sites-wordpress/
	 */
	public function snap( $atts, $content = null ) {
		# Default values
		$defaults = [
			'url' => 'https://www.example.com/', # URL to be snapshotted
			'alt' => __( 'Website Snapshot', 'thesis-openhook' ), # Alt text for snapshot image
			'w' => 400, # Width of snapshot
			'h' => 300, # Height of snapshot
			'class' => '', # CSS class(es), space separated
		];

		# Parse attributes
		$atts = shortcode_atts( $defaults, $atts, 'snap' ); # @filter: shortcode_atts_snap

		# Sanity checks to ensure proper variables
		$url = urlencode( wp_http_validate_url( $atts['url'] ) ?: $defaults['url'] );
		$alt = esc_attr( $atts['alt'] );
		$w = absint( $atts['w'] ) ?: $defaults['w'];
		$h = absint( $atts['h'] ) ?: $defaults['h'];
		$class = ! empty( $atts['class'] ) ? esc_attr( $atts['class'] ) . ' website_snapshot' : 'website_snapshot';

		# Put together our IMG tag to be output, with final data sanitation
		$img = '<img src="https://s.wordpress.com/mshots/v1/' . $url . '?w=' . $w . '&h=' . $h . '" alt="' . $alt . '" class="' . $class . '">';

		return $img;
	}
}