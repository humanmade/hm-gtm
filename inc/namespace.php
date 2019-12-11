<?php
/**
 * HM Google Tag Manager.
 *
 * @package hm-gtm
 */

namespace HM\GTM;

use WP_Admin_Bar;

/**
 * Set up actions and filters.
 *
 * @return void
 */
function bootstrap() {
	// Tag output.
	add_action( 'wp_head', __NAMESPACE__ . '\\output_tag', 1, 0 );
	add_action( 'after_body', __NAMESPACE__ . '\\output_tag', 1, 0 );

	// Admin features.
	add_action( 'admin_init', __NAMESPACE__ . '\\add_site_settings' );
	add_action( 'wpmu_options', __NAMESPACE__ . '\\add_network_settings' );
	add_action( 'update_wpmu_options', __NAMESPACE__ . '\\save_network_settings' );

	// Custom event tracking JS.
	/**
	 * Toggle to load the data attribute tracking script.
	 *
	 * @param $enable bool If true loads the data attribute handling script.
	 */
	$enable_event_tracking = apply_filters( 'hm_gtm_enable_event_tracking', true );

	if ( $enable_event_tracking ) {
		add_action( 'wp_enqueue_scripts', __NAMESPACE__ . '\\enqueue_scripts' );
	}

	// dataLayer display.
	/**
	 * Toggle whether to show the dataLayer UI or not.
	 *
	 * @param $show bool If true the UI is displayed.
	 */
	$show_data_layer_ui = apply_filters( 'hm_gtm_show_data_layer_ui', false );

	if ( $show_data_layer_ui ) {
		add_action( 'admin_bar_menu', __NAMESPACE__ . '\\admin_bar_data_layer_ui', 100 );
		add_filter( 'map_meta_cap', __NAMESPACE__ . '\\view_data_layer_cap', 10, 3 );
	}
}

/**
 * Outputs the gtm tag, place this immediately after the opening <body> tag
 */
function output_tag() {
	/**
	 * Filter the hm_gtm_id variable to support other methods of setting this value.
	 *
	 * @param string $site_container_id The GTM container ID.
	 */
	$site_container_id = apply_filters( 'hm_gtm_id', get_option( 'hm_gtm_id', false ) );

	/**
	 * Filter the hm_gtm_network_id variable to support other methods of setting this value.
	 *
	 * @param string $network_container_id The network GTM container ID.
	 */
	$network_container_id = apply_filters( 'hm_gtm_network_id', get_site_option( 'hm_gtm_network_id', false ) );

	/**
	 * Filter the dataLayer variable name. Tag manager allow for
	 * custom variable names to avoid collisions and scope container events.
	 *
	 * @param string $data_layer The name to use for the dataLayer variable.
	 */
	$data_layer_var = apply_filters( 'hm_gtm_data_layer_var', 'dataLayer' );

	if ( doing_action( 'wp_head' ) ) {
		// If it's the head action output the JS and dataLayer.
		if ( $network_container_id ) {
			gtm_tag( $network_container_id, get_gtm_data_layer(), $data_layer_var );
		}

		if ( $site_container_id ) {
			gtm_tag( $site_container_id, $network_container_id ? [] : get_gtm_data_layer(), $data_layer_var );
		}
	} else {
		// If the tag is called directly or on another action output the noscript fallback.
		// This gives us back compat and noscript support in one go.
		if ( $network_container_id ) {
			gtm_tag_iframe( $network_container_id );
		}

		if ( $site_container_id ) {
			gtm_tag_iframe( $site_container_id );
		}
	}
}

/**
 * Add admin settings.
 */
function add_site_settings() {
	// add settings section
	add_settings_section(
		'hm_gtm',
		esc_html__( 'Google Tag Manager', 'hm_gtm' ),
		__NAMESPACE__ . '\\settings_section',
		'general'
	);

	// add settings field
	add_settings_field(
		'hm_gtm_id_field',
		esc_html__( 'Container ID', 'hm_gtm' ),
		__NAMESPACE__ . '\\text_settings_field',
		'general',
		'hm_gtm',
		[
			'value'       => get_option( 'hm_gtm_id', '' ),
			'name'        => 'hm_gtm_id',
			'description' => esc_html__( 'Enter your container ID eg. GTM-123ABC', 'hm_gtm' ),
		]
	);

	register_setting( 'general', 'hm_gtm_id', 'sanitize_text_field' );
}

/**
 * Display Network Settings for Google Tag Manager
 */
function add_network_settings() {
	?>
	<h3><?php esc_html_e( 'Network Google Tag Manager', 'hm_gtm' ); ?></h3>
	<table id="menu" class="form-table">
		<tr valign="top">
			<th scope="row">
				<label for="hm_gtm_network_id"><?php esc_html_e( 'Container ID', 'hm_gtm' ); ?></label>
			</th>
			<td>
				<?php
					text_settings_field( [
						'name'       => 'hm_gtm_network_id',
						'value'      => get_site_option( 'hm_gtm_network_id' ),
						'decription' => esc_html__( 'Enter your network container ID eg. GTM-123ABC', 'hm_gtm' ),
					] );
				?>
			</td>
		</tr>
	</table>
	<?php
}

/**
 * Save Network Settings for Google Tag Manager.
 */
function save_network_settings() {
	if ( isset( $_POST['hm_gtm_network_id'] ) ) {
		update_site_option( 'hm_gtm_network_id', sanitize_text_field( $_POST['hm_gtm_network_id'] ) );
	}
}

/**
 * Noop settings section callback.
 */
function settings_section() {
	// void
}

/**
 * Text field for GTM container IDs.
 *
 * @param array $args The field settings.
 */
function text_settings_field( array $args ) {
	$args = wp_parse_args( $args, [
		'name'        => '',
		'value'       => '',
		'description' => '',
	 ] );

	printf( '<input type="text" id="%1$s" name="%1$s" value="%2$s" />%3$s',
		esc_attr( $args['name'] ),
		esc_attr( $args['value'] ),
		$args['description'] ? '<br /> <span class="description">' . esc_html( $args['description'] ) . '</span>' : ''
	);
}

/**
 * Enqueue data attribute tracking script.
 */
function enqueue_scripts() {
	wp_enqueue_script( 'hm-gtm', plugins_url( '/assets/events.js', dirname( __FILE__ ) ), [], '2.0.2', true );
}

/**
 * Modify the admin bar to show dataLayer variables.
 *
 * @param WP_Admin_Bar $admin_bar
 */
function admin_bar_data_layer_ui( WP_Admin_Bar $admin_bar ) {
	// Capability check.
	if ( ! current_user_can( 'hm_gtm_data_layer' ) ) {
		return;
	}

	// Front end only.
	if ( is_admin() ) {
		return;
	}

	// Get the data layer object.
	$data_layer = get_gtm_data_layer();
	$data_layer = flatten_array( $data_layer );

	$admin_bar->add_menu( [
		'id' => 'hm-gtm',
		'title' => '
			<span class="ab-icon dashicons-filter"></span>
			<span class="ab-label">' . __( 'Data Layer', 'hm-gtm' ) . '</span>',
	] );

	foreach ( $data_layer as $key => $value ) {
		$admin_bar->add_node( [
			'id' => sanitize_key( "hm-gtm-$key" ),
			'title' => "<strong style=\"font-weight:bold;\">{$key}:</strong> " . wp_unslash( $value ),
			'parent' => 'hm-gtm',
		] );
	}
}

/**
 * Flattens an array recursively and sets the keys for nested values
 * as a dot separated path.
 *
 * @param array $data The array to flatten.
 * @param string $prefix The current key prefix.
 * @return array
 */
function flatten_array( array $data, string $prefix = '' ) : array {
	$flattened = [];
	$prefix = ! empty( $prefix ) ? "{$prefix}." : '';

	foreach ( $data as $key => $value ) {
		if ( is_array( $value ) ) {
			$flattened = array_merge( $flattened, flatten_array( $value, "{$prefix}{$key}" ) );
		} else {
			$flattened[ "{$prefix}{$key}" ] = trim( json_encode( $value ), '"' );
		}
	}

	return $flattened;
}

/**
 * Allow site admins to view the dataLayer by default.
 *
 * @param array $caps Required capabilities for current check.
 * @param string $cap Capability being checked.
 * @return array
 */
function view_data_layer_cap( array $caps, string $cap, int $user_id ) {
	if ( $cap === 'hm_gtm_data_layer' && user_can( $user_id, 'manage_options' ) ) {
		$caps[] = 'manage_options';
	}

	return $caps;
}
