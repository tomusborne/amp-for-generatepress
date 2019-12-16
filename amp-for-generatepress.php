<?php
/*
Plugin Name: AMP for GeneratePress
Plugin URI: https://github.com/tomusborne/amp-for-generatepress
Description: Enable AMP features in GeneratePress.
Version: 0.1
Author: Tom Usborne
Author URI: https://generatepress.com
License: GNU General Public License v2 or later
License URI: http://www.gnu.org/licenses/gpl-2.0.html
*/

define( 'AMPGP_VERSION', 0.1 );

/**
 * Check if AMP is active.
 *
 * @since 0.1
 */
function ampgp_is_amp() {
	return function_exists( 'is_amp_endpoint' ) && is_amp_endpoint();
}

add_action( 'wp_enqueue_scripts', 'ampgp_do_scripts', 500 );
/**
 * Remove scripts not compatible with AMP.
 *
 * @since 0.1
 */
function ampgp_do_scripts() {
	if ( ampgp_is_amp() ) {
		wp_dequeue_script( 'generate-menu' );
		wp_dequeue_script( 'generate-a11y' );
		wp_dequeue_script( 'generate-classlist' );
		wp_dequeue_script( 'generate-sticky' );
		wp_dequeue_script( 'generate-offside' );
		wp_dequeue_script( 'generate-blog' );

		wp_dequeue_style( 'generate-sticky' );
		wp_dequeue_style( 'generate-offside' );

		wp_enqueue_style( 'gpamp', plugin_dir_url( __FILE__ ) . 'amp.css', array(), AMPGP_VERSION );
		wp_add_inline_style( 'generate-navigation-branding', ampgp_navigation_logo() );
	}
}

/**
 * Some settings will have to change if AMP is active.
 *
 * @since 0.1
 */
function ampgp_do_generate_settings( $settings ) {
	$settings['nav_dropdown_type'] = 'hover';
	$settings['back_to_top'] = '';
	$settings['nav_search'] = 'disable';

	return $settings;
}

/**
 * Change some GPP Blog settings if AMP is active.
 *
 * @since 0.1
 */
function ampgp_do_generate_blog_settings( $settings ) {
	$settings['masonry'] = false;
	$settings['infinite_scroll'] = false;

	return $settings;
}

add_action( 'wp', 'ampgp_do_setup' );
/**
 * Do things that need to run once ampgp_is_amp() is available.
 *
 * @since 0.1
 */
function ampgp_do_setup() {
	if ( ! ampgp_is_amp() ) {
		return;
	}

	add_filter( 'option_generate_settings', 	 'ampgp_do_generate_settings' );
	add_filter( 'option_generate_blog_settings', 'ampgp_do_generate_blog_settings' );
}

/**
 * Add a fixed width to our navigation logo.
 *
 * @since 0.1
 */
function ampgp_navigation_logo() {
	if ( ! class_exists( 'GeneratePress_CSS' ) ) {
		return;
	}

	if ( ! function_exists( 'generate_menu_plus_get_defaults' ) ) {
		return;
	}

	$menu_plus_settings = wp_parse_args(
		get_option( 'generate_menu_plus_settings', array() ),
		generate_menu_plus_get_defaults()
	);

	$css = new GeneratePress_CSS;

	if ( isset( $menu_plus_settings['navigation_as_header'] ) && $menu_plus_settings['navigation_as_header'] ) {
		if ( get_theme_mod( 'custom_logo' ) ) {
			$data = wp_get_attachment_metadata( get_theme_mod( 'custom_logo' ) );
			$width = false;
			$height = false;

			if ( ! empty( $data ) ) {
				if ( isset( $data['width'] ) ) {
					$width = $data['width'];
				}

				if ( isset( $data['height'] ) ) {
					$height = $data['height'];
				}
			}

			if ( $height && $width ) {
				$navigation_height = 60;
				$mobile_navigation_height = '';

				if ( function_exists( 'generate_spacing_get_defaults' ) ) {
					$spacing_settings = wp_parse_args(
						get_option( 'generate_spacing_settings', array() ),
						generate_spacing_get_defaults()
					);

					$navigation_height = $spacing_settings['menu_item_height'];

					if ( isset( $spacing_settings['mobile_menu_item_height'] ) ) {
						$mobile_navigation_height = $spacing_settings['mobile_menu_item_height'];
					}
				}

				// Find our aspect ratio.
				$scale = $width / $height;

				$navigation_height = $navigation_height - 20;
				$width = $navigation_height * $scale;

				$css->set_selector( '.navigation-branding img' );
				$css->add_property( 'height', $navigation_height, false, 'px' );
				$css->add_property( 'width', $width, false, 'px' );
				$css->add_property( 'padding', '0px' );

				$mobile_menu_query = apply_filters( 'generate_mobile_menu_media_query', '(max-width: 768px)' );

				if ( is_int( $mobile_navigation_height ) ) {
					$mobile_navigation_height = $mobile_navigation_height - 20;
					$mobile_width = $mobile_navigation_height * $scale;

					$css->start_media_query( $mobile_menu_query );
						$css->set_selector( '.navigation-branding img' );
						$css->add_property( 'height', $mobile_navigation_height, false, 'px' );
						$css->add_property( 'width', $mobile_width, false, 'px' );
					$css->stop_media_query();
				}
			}
		}
	}

	if ( 'enable' === $menu_plus_settings['mobile_header'] && '' !== $menu_plus_settings['mobile_header_logo'] ) {
		$image_id = false;

		if ( function_exists( 'attachment_url_to_postid' ) ) {
			$image_id = attachment_url_to_postid( $menu_plus_settings['mobile_header_logo'] );
		}

		if ( $image_id ) {
			$data = wp_get_attachment_metadata( $image_id );
			$width = false;
			$height = false;

			if ( ! empty( $data ) ) {
				if ( isset( $data['width'] ) ) {
					$width = $data['width'];
				}

				if ( isset( $data['height'] ) ) {
					$height = $data['height'];
				}
			}

			if ( $height && $width ) {
				$navigation_height = 60;
				$mobile_navigation_height = '';

				if ( function_exists( 'generate_spacing_get_defaults' ) ) {
					$spacing_settings = wp_parse_args(
						get_option( 'generate_spacing_settings', array() ),
						generate_spacing_get_defaults()
					);

					$navigation_height = $spacing_settings['menu_item_height'];

					if ( isset( $spacing_settings['mobile_menu_item_height'] ) ) {
						$mobile_navigation_height = $spacing_settings['mobile_menu_item_height'];
					}
				}

				// Find our aspect ratio.
				$scale = $width / $height;

				$navigation_height = $navigation_height - 20;
				$width = $navigation_height * $scale;

				$css->set_selector( '.mobile-header-navigation .site-logo.mobile-header-logo img' );
				$css->add_property( 'height', $navigation_height, false, 'px' );
				$css->add_property( 'width', $width, false, 'px' );
				$css->add_property( 'padding', '0px' );

				$mobile_menu_query = apply_filters( 'generate_mobile_menu_media_query', '(max-width: 768px)' );

				if ( is_int( $mobile_navigation_height ) ) {
					$mobile_navigation_height = $mobile_navigation_height - 20;
					$mobile_width = $mobile_navigation_height * $scale;

					$css->start_media_query( $mobile_menu_query );
						$css->set_selector( '.mobile-header-navigation .site-logo.mobile-header-logo img' );
						$css->add_property( 'height', $mobile_navigation_height, false, 'px' );
						$css->add_property( 'width', $mobile_width, false, 'px' );
					$css->stop_media_query();
				}
			}
		}
	}

	return $css->css_output();
}

add_action( 'generate_after_footer','ampgp_do_toggled_nav' );
/**
 * Without the .toggled element on the page, AMP won't include the necessary CSS.
 *
 * @since 0.1
 */
function ampgp_do_toggled_nav() {
	if ( ! ampgp_is_amp() ) {
		return;
	}

	echo '<div class="main-navigation toggled amp-tree-shaking-help" style="display: none;"></div>';
}

add_action( 'generate_inside_navigation', 'ampgp_do_menu_toggle' );
add_action( 'generate_inside_mobile_header', 'ampgp_do_menu_toggle' );
/**
 * Insert our AMP-specific menu toggle.
 *
 * @since 0.1
 */
function ampgp_do_menu_toggle() {
	if ( ! ampgp_is_amp() ) {
		return;
	}

	$navigation = 'site-navigation';

	if ( 'generate_inside_mobile_header' === current_action() ) {
		$navigation = 'mobile-header';
	}
	?>
		<button
			class="menu-toggle amp-menu-toggle"
			on="tap:<?php echo $navigation; ?>.toggleClass( 'class' = 'toggled' ),AMP.setState( { navMenuExpanded: ! navMenuExpanded } )"
			aria-expanded="false"
			[aria-expanded]="navMenuExpanded ? 'true' : 'false'"
		>
			<?php
			do_action( 'generate_inside_mobile_menu' );

			if ( function_exists( 'generate_do_svg_icon' ) ) {
				generate_do_svg_icon( 'menu-bars', true );
			}

			$mobile_menu_label = apply_filters( 'generate_mobile_menu_label', __( 'Menu', 'gp-amp' ) );

			if ( $mobile_menu_label ) {
				printf(
					'<span class="mobile-menu">%s</span>',
					$mobile_menu_label
				);
			} else {
				printf(
					'<span class="screen-reader-text">%s</span>',
					__( 'Menu', 'gp-amp' )
				);
			}
			?>
		</button>
	<?php
}

add_filter( 'generate_navigation_microdata', 'ampgp_do_navigation_data' );
/**
 * Add our AMP data to the navigation.
 *
 * @since 0.1
 */
function ampgp_do_navigation_data( $data ) {
	if ( ! ampgp_is_amp() ) {
		return $data;
	}

	return sprintf(
		'[aria-expanded]="%s"',
		"navMenuExpanded ? 'true' : 'false'"
	);
}

add_filter( 'walker_nav_menu_start_el', 'gpamp_add_sub_menu_dropdown_toggles', 10, 4 );
/**
 * Filter the HTML output of a nav menu item to add the AMP dropdown button to reveal the sub-menu.
 *
 * This is only used for AMP since in JS it is added via initMainNavigation() in navigation.js.
 *
 * @param string $item_output   Nav menu item HTML.
 * @param object $item          Nav menu item.
 * @return string Modified nav menu item HTML.
 */
function gpamp_add_sub_menu_dropdown_toggles( $item_output, $item, $depth, $args ) {
	// Only add the buttons in AMP responses.
	if ( ! ampgp_is_amp() ) {
		return $item_output;
	}

	if ( 'main-nav' !== $args->container_class ) {
		return $item_output;
	}

	// Skip when the item has no sub-menu.
	if ( ! in_array( 'menu-item-has-children', $item->classes, true ) ) {
		return $item_output;
	}

	// Obtain the initial expanded state.
	$expanded = in_array( 'current-menu-ancestor', $item->classes, true );

	// Generate a unique state ID.
	static $nav_menu_item_number = 0;
	$nav_menu_item_number++;
	$expanded_state_id = 'navMenuItemExpanded' . $nav_menu_item_number;

	// Create new state for managing storing the whether the sub-menu is expanded.
	$item_output .= sprintf(
		'<amp-state id="%s"><script type="application/json">%s</script></amp-state>',
		esc_attr( $expanded_state_id ),
		wp_json_encode( $expanded )
	);

	/*
	 * Create the toggle button which mutates the state and which has class and
	 * aria-expanded attributes which react to the state changes.
	 */
	$dropdown_button  = '<button';
	$dropdown_class   = 'dropdown-menu-toggle';
	$toggled_class    = 'toggled-on';
	$dropdown_button .= sprintf(
		' class="%s" [class]="%s"',
		esc_attr( $dropdown_class ),
		esc_attr( sprintf( "%s + ( $expanded_state_id ? %s : '' )", wp_json_encode( $dropdown_class ), wp_json_encode( " $toggled_class" ) ) )
	);

	$dropdown_button .= sprintf(
		' aria-expanded="%s" [aria-expanded]="%s"',
		esc_attr( wp_json_encode( $expanded ) ),
		esc_attr( "$expanded_state_id ? 'true' : 'false'" )
	);

	$dropdown_button .= sprintf(
		' on="%s"',
		esc_attr( "tap:AMP.setState( { $expanded_state_id: ! $expanded_state_id } )" )
	);

	$dropdown_button .= '>';

	if ( function_exists( 'generate_get_svg_icon' ) ) {
		$dropdown_button .= generate_get_svg_icon( 'arrow' );
	}

	// Let the screen reader text in the button also update based on the expanded state.
	$dropdown_button .= sprintf(
		'<span class="screen-reader-text" [text]="%s">%s</span>',
		esc_attr( sprintf( "$expanded_state_id ? %s : %s", wp_json_encode( __( 'collapse child menu', 'gp-amp' ) ), wp_json_encode( __( 'expand child menu', 'gp-amp' ) ) ) ),
		esc_html( $expanded ? __( 'collapse child menu', 'example' ) : __( 'expand child menu', 'gp-amp' ) )
	);

	$dropdown_button .= '</button>';

	$item_output .= $dropdown_button;
	return $item_output;
}
