<?php

namespace HM_GTM;

class Plugin {

	protected static $instance;

	public static function get_instance() {
		if ( ! self::$instance ) {
			self::$instance = new Plugin();
		}
		return self::$instance;
	}

	public function __construct() {

		add_action( 'admin_init', array( $this, 'action_admin_init' ) );
		add_action( 'wpmu_options', array( $this, 'show_network_settings' ) );
		add_action( 'update_wpmu_options', array( $this, 'save_network_settings' ) );

	}

	public function action_admin_init() {

		// add settings section
		add_settings_section( 'hm_gtm', esc_html__( 'Google Tag Manager', 'hm_gtm' ), array(
			$this,
			'settings_section',
		), 'general' );

		// add settings field
		add_settings_field( 'hm_gtm_id_field', esc_html__( 'Container ID', 'hm_gtm' ), array(
			$this,
			'text_settings_field',
		), 'general', 'hm_gtm', array(
			'value'       => get_option( 'hm_gtm_id', '' ),
			'name'        => 'hm_gtm_id',
			'description' => esc_html__( 'Enter your container ID eg. GTM-123ABC', 'hm_gtm' ),
		) );

		register_setting( 'general', 'hm_gtm_id', 'sanitize_text_field' );

	}

	/**
	 * Display Network Settings for Google Tag Manager
	 */
	public function show_network_settings() { ?>
		<h3><?php esc_html_e( 'Network Google Tag Manager', 'hm_gtm' ); ?></h3>
		<table id="menu" class="form-table">
			<tr valign="top">
				<th scope="row">
					<label for="hm_gtm_network_id"><?php esc_html_e( 'Container ID', 'hm_gtm' ); ?></label>
				</th>
				<td>
					<?php $this->text_settings_field( array(
						'name'       => 'hm_gtm_network_id',
						'value'      => get_site_option( 'hm_gtm_network_id' ),
						'decription' => esc_html__( 'Enter your network container ID eg. GTM-123ABC', 'hm_gtm' ),
					) ); ?>
				</td>
			</tr>
		</table>
		<?php
	}

	/**
	 * Save Network Settings for Google Tag Manager.
	 */
	public function save_network_settings() {

		if ( isset( $_POST['hm_gtm_network_id'] ) ) {
			update_site_option( 'hm_gtm_network_id', sanitize_text_field( $_POST['hm_gtm_network_id'] ) );
		}

	}

	public function settings_section() {
		// void
	}

	public function text_settings_field( $args ) {

		$args = wp_parse_args( $args, array(
			'name'        => '',
			'value'       => '',
			'description' => '',
		) );

		printf( '<input type="text" id="%1$s" name="%1$s" value="%2$s" />%3$s',
			esc_attr( $args['name'] ),
			esc_attr( $args['value'] ),
			$args['description'] ? '<br /> <span class="description">' . esc_html( $args['description'] ) . '</span>' : ''
		);
	}

	/**
	 * Outputs the dataLayer object
	 *
	 * Use the below to add custom values to the dataLayer
	 *
	 * add_filter( 'hm_gtm_data_layer', function( $data ) {
	 *     $data['my_var'] = 'hello';
	 *     return $data;
	 * } );
	 *
	 * @return string
	 */
	public static function data_layer() {

		$data = array();

		if ( is_user_logged_in() ) {
			$user              = wp_get_current_user();
			$data['logged_in'] = $user->get( 'user_nicename' );
			$data['role']      = implode( ',', array_keys( $user->caps ) );
		}
		if ( is_front_page() ) {
			$data['front_page'] = true;
		}
		if ( is_404() ) {
			$data['404'] = true;
		}
		if ( is_singular() ) {
			$data['post_type'] = get_post_type();
			$data['post_id']   = get_the_ID();
		}
		if ( is_archive() ) {
			$data['archive'] = true;
			if ( is_date() ) {
				$data['archive'] = 'date';
				$data['date']    = get_the_date();
			}
			if ( is_search() ) {
				$data['archive'] = 'search';
				$data['search']  = get_search_query();
			}
			if ( is_post_type_archive() ) {
				$data['archive'] = get_post_type();
			}
			if ( is_tag() || is_category() || is_tax() ) {
				$data['archive'] = get_queried_object()->taxonomy;
				$data['term']    = get_queried_object()->slug;
			}
			if ( is_author() ) {
				$data['archive'] = 'author';
				$data['author']  = get_queried_object()->user_nicename;
			}
		}
		if ( is_multisite() ) {
			$data['blog']    = home_url();
			$data['network'] = network_home_url();
		}

		$data = apply_filters( 'hm_gtm_data_layer', $data );

		if ( ! empty( $data ) ) {
			return sprintf( '<script>var dataLayer = %s;</script>',
				wp_json_encode( array( $data ) )
			);
		}

		return '';
	}

}
