<?php
/**
 * HM Google Tag Manager.
 *
 * @package hm-gtm
 */

namespace HM\GTM;

use WP_Admin_Bar;
use WP_HTML_Tag_Processor;
use WP_REST_Request;

/**
 * Set up actions and filters.
 *
 * @return void
 */
function bootstrap() {
	// Tag output.
	add_action( 'wp_head', __NAMESPACE__ . '\\output_tag', 1, 0 );
	add_action( 'wp_body_open', __NAMESPACE__ . '\\output_tag', 1, 0 );
	add_action( 'after_body', __NAMESPACE__ . '\\output_tag', 1, 0 ); // Deprecated.

	// Admin features.
	add_action( 'admin_init', __NAMESPACE__ . '\\add_site_settings' );
	add_action( 'wpmu_options', __NAMESPACE__ . '\\add_network_settings' );
	add_action( 'update_wpmu_options', __NAMESPACE__ . '\\save_network_settings' );

	// Block features.
	add_action( 'enqueue_block_editor_assets', __NAMESPACE__ . '\\block_editor_enqueue_scripts' );
	add_filter( 'render_block', __NAMESPACE__ . '\\filter_render_block', 10, 3 );

	// UUID cookie service.
	add_action( 'rest_api_init', __NAMESPACE__ . '\\uuid_cookie_endpoint' );

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
	$show_data_layer_ui = apply_filters( 'hm_gtm_show_data_layer_ui', true );

	if ( $show_data_layer_ui ) {
		add_action( 'admin_bar_menu', __NAMESPACE__ . '\\admin_bar_data_layer_ui', 100 );
		add_filter( 'map_meta_cap', __NAMESPACE__ . '\\view_data_layer_cap', 10, 3 );
	}
}

/**
 * Outputs the gtm tag, place this immediately after the opening <body> tag
 */
function output_tag() {
	if ( doing_action( 'after_body' ) ) {
		_deprecated_hook( 'after_body', '3.0.0', 'wp_body_open', 'From version 3.0.0 of the HM GTM plugin you should use the `wp_open_body()` function instead of `do_action(\'after_body\')` for classic themes.' );
	}

	/**
	 * Filter the hm_gtm_id variable to support other methods of setting this value.
	 *
	 * @param string $site_container_id The GTM container ID.
	 */
	$site_container_id = apply_filters( 'hm_gtm_id', get_option( 'hm_gtm_id', false ) );

	$site_container_url = get_option( 'hm_gtm_url', '' );
	$site_container_snippet = get_option( 'hm_gtm_snippet', '' );
	$site_container_snippet_iframe = get_option( 'hm_gtm_snippet_iframe', '' );

	/**
	 * Filter the hm_gtm_network_id variable to support other methods of setting this value.
	 *
	 * @param string $network_container_id The network GTM container ID.
	 */
	$network_container_id = apply_filters( 'hm_gtm_network_id', get_site_option( 'hm_gtm_network_id', false ) );

	$network_container_url = get_site_option( 'hm_gtm_network_url', '' );
	$network_container_snippet = get_site_option( 'hm_gtm_network_snippet', '' );
	$network_container_snippet_iframe = get_site_option( 'hm_gtm_network_snippet_iframe', '' );

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
			gtm_tag( $network_container_id, get_gtm_data_layer(), $data_layer_var, $network_container_url, $network_container_snippet );
		}

		if ( $site_container_id ) {
			gtm_tag( $site_container_id, $network_container_id ? [] : get_gtm_data_layer(), $data_layer_var, $site_container_url, $site_container_snippet );
		}
	} else {
		// If the tag is called directly or on another action output the noscript fallback.
		// This gives us back compat and noscript support in one go.
		if ( $network_container_id ) {
			gtm_tag_iframe( $network_container_id, $network_container_url, $network_container_snippet_iframe );
		}

		if ( $site_container_id ) {
			gtm_tag_iframe( $site_container_id, $site_container_url, $site_container_snippet_iframe );
		}
	}
}

/**
 * Add admin settings.
 */
function add_site_settings() {
	add_settings_section(
		'hm_gtm',
		esc_html__( 'Google Tag Manager', 'hm_gtm' ),
		__NAMESPACE__ . '\\settings_section',
		'general'
	);

	// Fields.
	add_settings_field(
		'hm_gtm_id_field',
		esc_html__( 'Container ID (Required)', 'hm_gtm' ),
		__NAMESPACE__ . '\\text_settings_field',
		'general',
		'hm_gtm',
		[
			'value'       => get_option( 'hm_gtm_id', '' ),
			'name'        => 'hm_gtm_id',
			'description' => esc_html__( 'Enter your container ID eg. GTM-123ABC', 'hm_gtm' ),
		]
	);

	register_setting(
		'general',
		'hm_gtm_id',
		[
			'type' => 'string',
			'description' => esc_html__( 'Google Tag Manager Container ID', 'hm_gtm' ),
			'sanitize_callback' => 'sanitize_text_field',
			'default' => '',
		]
	);

	add_settings_field(
		'hm_gtm_url_field',
		esc_html__( 'Container URL', 'hm_gtm' ),
		__NAMESPACE__ . '\\text_settings_field',
		'general',
		'hm_gtm',
		[
			'value'       => get_option( 'hm_gtm_url', '' ),
			'name'        => 'hm_gtm_url',
			'description' => esc_html__( 'If you are using server side Tag Manager, enter the server container URL here, typically just the custom domain, or path if using a reverse proxy.', 'hm_gtm' ),
		]
	);

	register_setting(
		'general',
		'hm_gtm_url',
		[
			'type' => 'string',
			'description' => esc_html__( 'Google Tag Manager Container URL', 'hm_gtm' ),
			'sanitize_callback' => function ( $value ) {
				if ( empty( $value ) ) {
					return $value;
				}

				// If a relative path is provided then make it absolute.
				if ( strpos( $value, 'http' ) === false ) {
					$value = home_url( $value );
				}

				return sanitize_url( $value );
			},
			'default' => '',
		]
	);

	add_settings_field(
		'hm_gtm_cookie_field',
		esc_html__( 'Cookie restoration / master cookie name', 'hm_gtm' ),
		__NAMESPACE__ . '\\text_settings_field',
		'general',
		'hm_gtm',
		[
			'value'       => get_uuid_cookie_name(),
			'name'        => 'hm_gtm_cookie',
			'description' => esc_html__( 'Some server side Tag Manager providers support restoring expired cookies from a master cookie value, you can set the name of the cookie here.', 'hm_gtm' ),
		]
	);

	register_setting(
		'general',
		'hm_gtm_cookie',
		[
			'type' => 'string',
			'description' => esc_html__( 'Google Tag Manager cookie restoration / master cookie name', 'hm_gtm' ),
			'sanitize_callback' => 'sanitize_key',
			'default' => '',
		]
	);

	// Extra permissions check for super admin privilege on multisite to enter custom JS code.
	if ( ( ! is_multisite() && current_user_can( 'manage_options' ) ) || is_super_admin() ) {
		add_settings_field(
			'hm_gtm_snippet_field',
			esc_html__( 'Custom code snippet', 'hm_gtm' ),
			__NAMESPACE__ . '\\textarea_settings_field',
			'general',
			'hm_gtm',
			[
				'value'       => get_option( 'hm_gtm_snippet', '' ),
				'name'        => 'hm_gtm_snippet',
				'description' => esc_html__( 'Some server side Tag Manager providers use a code snippet that is different to the standard one. Paste it here if you are provided with one or if you are unsure.', 'hm_gtm' ),
			]
		);

		register_setting(
			'general',
			'hm_gtm_snippet',
			[
				'type' => 'string',
				'description' => esc_html__( 'Google Tag Manager Container Snippet', 'hm_gtm' ),
				'sanitize_callback' => function ( $value ) {
					return trim( $value );
				},
				'default' => '',
			]
		);

		add_settings_field(
			'hm_gtm_snippet_iframe_field',
			esc_html__( 'Custom iframe code snippet', 'hm_gtm' ),
			__NAMESPACE__ . '\\textarea_settings_field',
			'general',
			'hm_gtm',
			[
				'value'       => get_option( 'hm_gtm_snippet_iframe', '' ),
				'name'        => 'hm_gtm_snippet_iframe',
				'description' => esc_html__( 'Some server side Tag Manager providers use a code snippet that is different to the standard one. Usually it is enough to provide the custom container URL value above.', 'hm_gtm' ),
			]
		);

		register_setting(
			'general',
			'hm_gtm_snippet_iframe',
			[
				'type' => 'string',
				'description' => esc_html__( 'Google Tag Manager Iframe Container Snippet', 'hm_gtm' ),
				'sanitize_callback' => function ( $value ) {
					return trim( $value );
				},
				'default' => '',
			]
		);
	}

	add_settings_field(
		'hm_gtm_show_datalayer_field',
		esc_html__( 'Show data layer in admin bar', 'hm_gtm' ),
		__NAMESPACE__ . '\\checkbox_settings_field',
		'general',
		'hm_gtm',
		[
			'value'       => get_option( 'hm_gtm_show_datalayer', false ),
			'name'        => 'hm_gtm_show_datalayer',
			'description' => esc_html__( 'Show the available data layer values for the current page in the admin bar', 'hm_gtm' ),
		]
	);

	register_setting(
		'general',
		'hm_gtm_show_datalayer',
		[
			'type' => 'string',
			'description' => esc_html__( 'Show Google Tag Manager Data Layer', 'hm_gtm' ),
			'sanitize_callback' => 'absint',
			'default' => '',
		]
	);
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
				<label for="hm_gtm_network_id"><?php esc_html_e( 'Container ID (Required)', 'hm_gtm' ); ?></label>
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
			<th scope="row">
				<label for="hm_gtm_network_url"><?php esc_html_e( 'Container URL', 'hm_gtm' ); ?></label>
			</th>
			<td>
				<?php
					text_settings_field( [
						'name'       => 'hm_gtm_network_url',
						'value'      => get_site_option( 'hm_gtm_network_url' ),
						'decription' => esc_html__( 'If using server side Tag Manager, enter your network container URL', 'hm_gtm' ),
					] );
				?>
			</td>
			<th scope="row">
				<label for="hm_gtm_network_snippet"><?php esc_html_e( 'Custom Container Snippet', 'hm_gtm' ); ?></label>
			</th>
			<td>
				<?php
					textarea_settings_field( [
						'name'       => 'hm_gtm_network_snippet',
						'value'      => get_site_option( 'hm_gtm_network_snippet' ),
						'decription' => esc_html__( 'If using server side Tag Manager, you may be given a customised code snippet. Enter it here.', 'hm_gtm' ),
					] );
				?>
			</td>
			<th scope="row">
				<label for="hm_gtm_network_snippet_iframe"><?php esc_html_e( 'Custom Container Iframe Snippet', 'hm_gtm' ); ?></label>
			</th>
			<td>
				<?php
					textarea_settings_field( [
						'name'       => 'hm_gtm_network_snippet_iframe',
						'value'      => get_site_option( 'hm_gtm_network_snippet_iframe' ),
						'decription' => esc_html__( 'If your server side container provider gives you a custom iframe snippet, enter it here.', 'hm_gtm' ),
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
		update_site_option( 'hm_gtm_network_id', sanitize_text_field( wp_unslash( $_POST['hm_gtm_network_id'] ) ) );
	}
	if ( isset( $_POST['hm_gtm_network_url'] ) ) {
		update_site_option( 'hm_gtm_network_url', sanitize_url( wp_unslash( $_POST['hm_gtm_network_url'] ) ) );
	}
	if ( isset( $_POST['hm_gtm_network_snippet'] ) ) {
		update_site_option( 'hm_gtm_network_snippet', sanitize_textarea_field( wp_unslash( $_POST['hm_gtm_network_snippet'] ) ) );
	}
	if ( isset( $_POST['hm_gtm_network_snippet_iframe'] ) ) {
		update_site_option( 'hm_gtm_network_snippet_iframe', sanitize_textarea_field( wp_unslash( $_POST['hm_gtm_network_snippet_iframe'] ) ) );
	}
}

/**
 * Settings section callback.
 */
function settings_section() {
	if ( is_multisite() && ! is_super_admin() ) {
		printf( '<p>%s</p>', esc_html__( 'If you need to enter a custom code snippet for server side Tag Manager, please contact a network super admin to add this for you.', 'hm_gtm' ) );
	}
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

	printf( '<input type="text" id="%1$s" name="%1$s" value="%2$s" class="regular-text ltr" %3$s/>%4$s',
		esc_attr( $args['name'] ),
		esc_attr( $args['value'] ),
		$args['description'] ? 'aria-describedby="' . esc_attr( $args['name'] ) . '-description" ' : '',
		$args['description'] ? '<p class="description" id="' . esc_attr( $args['name'] ) . '-description">' . esc_html( $args['description'] ) . '</p>' : ''
	);
}

/**
 * Textarea field for GTM container IDs.
 *
 * @param array $args The field settings.
 */
function textarea_settings_field( array $args ) {
	$args = wp_parse_args( $args, [
		'name'        => '',
		'value'       => '',
		'description' => '',
	] );

	printf( '<textarea id="%1$s" name="%1$s" rows="6" cols="100%%" %3$s>%2$s</textarea>%4$s',
		esc_attr( $args['name'] ),
		esc_textarea( $args['value'] ),
		$args['description'] ? 'aria-describedby="' . esc_attr( $args['name'] ) . '-description" ' : '',
		$args['description'] ? '<p class="description" id="' . esc_attr( $args['name'] ) . '-description">' . esc_html( $args['description'] ) . '</p>' : ''
	);
}

/**
 * Checkbox field for GTM container IDs.
 *
 * @param array $args The field settings.
 */
function checkbox_settings_field( array $args ) {
	$args = wp_parse_args( $args, [
		'name'        => '',
		'value'       => '',
		'description' => '',
	] );

	printf( '<label for="%1$s"><input type="checkbox" id="%1$s" name="%1$s" value="1" %2$s /> %3$s</label>',
		esc_attr( $args['name'] ),
		checked( true, (bool) get_option( 'hm_gtm_show_datalayer', false ), false ),
		$args['description'] ? esc_html( $args['description'] ) : ''
	);
}

/**
 * Retrieve the service UUID cookie name for cookie restoration.
 *
 * @return string
 */
function get_uuid_cookie_name() : string {
	return (string) get_option( 'hm_gtm_cookie', '' );
}

/**
 * Fires when preparing to serve a REST API request.
 */
function uuid_cookie_endpoint() : void {
	register_rest_route(
		'service/v1',
		'id',
		[
			'methods' => 'GET',
			'callback' => function ( WP_REST_Request $request ) {
				$cookie_name = get_uuid_cookie_name();

				// Short-circuit early if we're not using this feature.
				if ( empty( $cookie_name ) ) {
					return rest_ensure_response( null );
				}

				$cookie_value = $_COOKIE[ $cookie_name ] ?? '';

				// Generate or get param from localStorage if defined.
				if ( ! wp_is_uuid( $cookie_value ) ) {
					$restored_value = $request->get_param( 'id' );
					$cookie_value = wp_is_uuid( $restored_value ) ? $restored_value : wp_generate_uuid4();
				}

				// Preserve UUID for logged in users.
				if ( is_user_logged_in() ) {
					$uuid = get_user_meta( get_current_user_id(), '_hm_gtm_uuid', true );
					if ( ! wp_is_uuid( $uuid ) ) {
						update_user_meta( get_current_user_id(), '_hm_gtm_uuid', $cookie_value );
					} else {
						$cookie_value = $uuid;
					}
				}

				setcookie( $cookie_name, $cookie_value, [
					'expires' => time() + ( YEAR_IN_SECONDS * 2 ),
					'path'     => '/',
					'domain'   => '.' . wp_parse_url( home_url(), PHP_URL_HOST ),
					'secure'   => true,
					'httponly' => false,
					'samesite' => 'lax',
				] );

				// Endpoint cannot be cached by CDN or batcache.
				nocache_headers();

				return rest_ensure_response( [ 'id' => $cookie_value ] );
			},
			'permission_callback' => '__return_true',
		]
	);
}

/**
 * Enqueue data attribute tracking script.
 */
function enqueue_scripts() {
	wp_enqueue_script( 'hm-gtm', plugins_url( '/assets/events.js', dirname( __FILE__ ) ), [], VERSION, [
		'in_footer' => false,
		'strategy' => 'defer',
	] );
}

/**
 * Enqueue block editor settings panel.
 */
function block_editor_enqueue_scripts() {
	wp_enqueue_script( 'hm-gtm-blocks', plugins_url( '/assets/blocks.js', dirname( __FILE__ ) ), [
		'wp-block-editor',
		'wp-hooks',
		'wp-components',
	], VERSION, [
		'in_footer' => false,
		'strategy' => 'defer',
	] );
}

/**
 * Filters the content of a single block.
 *
 * @param string    $block_content The block content.
 * @param array     $block         The full block, including name and attributes.
 * @param \WP_Block $instance      The block instance.
 * @return string The block content.
 */
function filter_render_block( string $block_content, array $block, \WP_Block $instance ) : string {

	// Check minimum requirements.
	if ( empty( $block['attrs']['gtm'] ) || empty( $block['attrs']['gtm']['event'] ) ) {
		return $block_content;
	}

	$attributes = [];
	$attributes['data-gtm-on'] = $block['attrs']['gtm']['trigger'] ?? 'click';

	foreach ( [ 'event', 'action', 'category', 'label', 'value' ] as $key ) {
		if ( ! empty( $block['attrs']['gtm'][ $key ] ) ) {
			$attributes["data-gtm-{$key}"] = $block['attrs']['gtm'][ $key ];
		}
	}

	$block = new WP_HTML_Tag_Processor( $block_content );
	$block->set_bookmark( 'root' );

	$query = null;

	switch ( $attributes['data-gtm-on'] ) {
		case 'click':
			$query = [ 'tag_name' => 'a' ];
			if ( ! $block->next_tag( [ 'tag_name' => $query ] ) ) {
				$query['tag_name'] = 'button';
				$block->seek( 'root' );
			}
			if ( ! $block->next_tag( [ 'tag_name' => $query ] ) ) {
				$query = null;
			}
			$block->seek( 'root' );
			$block->next_tag( $query );
			break;
		case 'submit':
			$block->next_tag( [ 'tag_name' => 'form' ] );
			break;
		default:
			$block->next_tag();
	}

	foreach ( $attributes as $name => $value ) {
		$block->set_attribute( $name, $value );
	}

	return (string) $block;
}

/**
 * Modify the admin bar to show dataLayer variables.
 *
 * @param WP_Admin_Bar $admin_bar
 */
function admin_bar_data_layer_ui( WP_Admin_Bar $admin_bar ) {
	// Front end only.
	if ( is_admin() ) {
		return;
	}

	// Settings check.
	if ( ! ( (bool) get_option( 'hm_gtm_show_datalayer', false ) ) ) {
		return;
	}

	// Capability check.
	if ( ! current_user_can( 'hm_gtm_data_layer' ) ) {
		return;
	}

	// Get the data layer object.
	$data_layer = get_gtm_data_layer();
	$data_layer = flatten_array( $data_layer );

	$admin_bar->add_menu( [
		'id' => 'hm-gtm',
		'title' => '
			<span class="ab-icon dashicons-filter"></span>
			<span class="ab-label">' . __( 'GTM Data Layer', 'hm-gtm' ) . '</span>',
		'meta' => [
			'html' => 'html here',
		],
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
		$caps[] = 'hm_gtm_data_layer';
	}

	return $caps;
}
