<?php
/**
 * HM Google Tag Manager.
 *
 * @package hm-gtm
 */

namespace HM\GTM;

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
	add_action( 'admin_init', __NAMESPACE__ . '\\action_admin_init' );
	add_action( 'wpmu_options', __NAMESPACE__ . '\\show_network_settings' );
	add_action( 'update_wpmu_options', __NAMESPACE__ . '\\save_network_settings' );
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
function action_admin_init() {
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
function show_network_settings() {
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
