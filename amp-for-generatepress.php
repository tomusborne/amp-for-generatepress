<?php
/*
Plugin Name: AMP for GeneratePress
Plugin URI:
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

add_action( 'wp_enqueue_scripts', 'ampgp_do_scripts', 100 );
/**
 * Remove scripts not compatible with AMP.
 *
 * @since 0.1
 */
function ampgp_do_scripts() {
	if ( ampgp_is_amp() ) {
		wp_dequeue_script( 'generate-menu' );
		wp_dequeue_script( 'generate-a11y' );
		wp_dequeue_script( 'generate-sticky' );

		wp_dequeue_style( 'generate-sticky' );
		wp_dequeue_style( 'generate-offside' );

		wp_enqueue_style( 'gpamp', plugin_dir_url( __FILE__ ) . 'amp.css', array(), AMPGP_VERSION );
	}
}

add_action( 'wp', 'ampgp_replace_navigation' );
/**
 * Replace our navigation with the AMP version.
 *
 * @since 0.1
 */
function ampgp_replace_navigation() {
	if ( ! ampgp_is_amp() ) {
		return;
	}

	remove_action( 'generate_after_header', 'generate_add_navigation_after_header', 5 );
	remove_action( 'generate_before_header', 'generate_add_navigation_before_header', 5 );
	remove_action( 'generate_after_header_content', 'generate_add_navigation_float_right', 5 );
	remove_action( 'generate_before_right_sidebar_content', 'generate_add_navigation_before_right_sidebar', 5 );
	remove_action( 'generate_before_left_sidebar_content', 'generate_add_navigation_before_left_sidebar', 5 );

	if ( ! function_exists( 'generate_get_navigation_location' ) ) {
		return;
	}

	if ( 'nav-below-header' === generate_get_navigation_location() ) {
		add_action( 'generate_after_header', 'ampgp_do_primary_navigation', 5 );
	}

	if ( 'nav-above-header' === generate_get_navigation_location() ) {
		add_action( 'generate_before_header', 'ampgp_do_primary_navigation', 5 );
	}

	if ( 'nav-float-right' === generate_get_navigation_location() || 'nav-float-left' == generate_get_navigation_location() ) {
		add_action( 'generate_after_header_content', 'ampgp_do_primary_navigation', 5 );
	}

	if ( 'nav-right-sidebar' === generate_get_navigation_location() ) {
		add_action( 'generate_before_right_sidebar_content', 'ampgp_do_primary_navigation', 5 );
	}

	if ( 'nav-left-sidebar' === generate_get_navigation_location() ) {
		add_action( 'generate_before_left_sidebar_content', 'ampgp_do_primary_navigation', 5 );
	}
}

/**
 * Do the AMP navigation.
 *
 * @since 0.1
 */
function ampgp_do_primary_navigation() {
	if ( ! function_exists( 'generate_get_element_classes' ) ) {
		return;
	}

	$navigation_classes = implode( ' ', generate_get_element_classes( 'navigation' ) );
	?>
		<amp-state id="navMenuExpanded">
			<script type="application/json">false</script>
		</amp-state>

		<?php if ( 'nav-right-sidebar' === generate_get_navigation_location() || 'nav-left-sidebar' === generate_get_navigation_location() ) : ?>
			<div class="gen-sidebar-nav">
		<?php endif; ?>

		<nav
			id="site-navigation"
			class="<?php echo $navigation_classes; ?>"
			[class]="'<?php echo $navigation_classes; ?>' + ( navMenuExpanded ? ' toggled' : '' )"
			aria-expanded="false"
			[aria-expanded]="navMenuExpanded ? 'true' : 'false'"
		>
			<div <?php generate_do_element_classes( 'inside_navigation' ); ?>>
				<?php
				/**
				 * generate_inside_navigation hook.
				 *
				 * @since 0.1
				 *
				 * @hooked generate_navigation_search - 10
				 * @hooked generate_mobile_menu_search_icon - 10
				 */
				do_action( 'generate_inside_navigation' );
				?>
				<button
					class="menu-toggle amp-menu-toggle"
					on="tap:AMP.setState( { navMenuExpanded: ! navMenuExpanded } )"
					[class]="'menu-toggle' + ( navMenuExpanded ? ' toggled-on' : '' )"
					aria-expanded="false"
					[aria-expanded]="navMenuExpanded ? 'true' : 'false'"
				>
					<?php do_action( 'generate_inside_mobile_menu' ); ?>
		        	<span class="mobile-menu"><?php echo apply_filters( 'generate_mobile_menu_label', __( 'Menu', 'gp-amp' ) ); // WPCS: XSS ok. ?></span>
				</button>
				<?php
				wp_nav_menu(
					array(
						'theme_location' => 'primary',
						'container' => 'div',
						'container_class' => 'main-nav',
						'container_id' => 'primary-menu',
						'menu_class' => '',
						'fallback_cb' => 'generate_menu_fallback',
						'items_wrap' => '<ul id="%1$s" class="%2$s ' . join( ' ', generate_get_element_classes( 'menu' ) ) . '">%3$s</ul>',
					)
				);
				?>
			</div><!-- .inside-navigation -->
		</nav><!-- #site-navigation -->

		<?php if ( 'nav-right-sidebar' === generate_get_navigation_location() || 'nav-left-sidebar' === generate_get_navigation_location() ) : ?>
			</div>
		<?php endif; ?>
	<?php
}

add_filter( 'walker_nav_menu_start_el', 'gpamp_add_sub_menu_dropdown_toggles', 10, 2 );
/**
 * Filter the HTML output of a nav menu item to add the AMP dropdown button to reveal the sub-menu.
 *
 * This is only used for AMP since in JS it is added via initMainNavigation() in navigation.js.
 *
 * @param string $item_output   Nav menu item HTML.
 * @param object $item          Nav menu item.
 * @return string Modified nav menu item HTML.
 */
function gpamp_add_sub_menu_dropdown_toggles( $item_output, $item ) {

    // Only add the buttons in AMP responses.
    if ( ! ampgp_is_amp() ) {
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
        esc_attr( $dropdown_class . ( $expanded ? " $toggled_class" : '' ) ),
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
