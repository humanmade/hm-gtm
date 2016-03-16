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
		add_action( 'wp_head', array( $this, 'user_report' ) );

	}

	public function action_admin_init() {

		// add settings section
		add_settings_section( 'hm_gtm', esc_html__( 'Google Tag Manager', 'hm_gtm' ), array( $this, 'settings_section' ), 'general' );

		// add settings field
		add_settings_field( 'hm_gtm_id_field', esc_html__( 'Container ID', 'hm_gtm' ), array( $this, 'text_settings_field' ), 'general', 'hm_gtm', array(
			'value'       => get_option( 'hm_gtm_id', '' ),
			'name'        => 'hm_gtm_id',
			'description' => esc_html__( 'Enter your container ID eg. GTM-123ABC', 'hm_gtm' )
		) );

		register_setting( 'general', 'hm_gtm_id', 'sanitize_text_field' );

		if ( SITE_ID_CURRENT_SITE === get_current_blog_id() ) {

			// add settings section
			add_settings_section( 'hm_urm', esc_html__( 'User Report Manager', 'hm_gtm' ), array( $this, 'settings_section' ), 'general' );

			register_setting( 'general', 'hm_user_report_id', 'sanitize_text_field' );

			add_settings_field( 'hm_user_report_id_field', esc_html__( 'User report ID', 'hm_urm' ), array(
				$this,
				'text_settings_field'
			), 'general', 'hm_urm', array(
				'value'       => get_option( 'hm_user_report_id', '' ),
				'name'        => 'hm_user_report_id',
				'description' => esc_html__( 'Enter your User Report ID eg. 2fe19bec-4466-4294-ad6a-5db210106d47', 'hm_gtm' ),
				'class'       => 'regular-text'
			) );
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
			'class'       => ''
		) );

		printf( '<input type="text" id="%1$s" class="%4$s" name="%1$s" value="%2$s" />%3$s',
			esc_attr( $args['name'] ),
			esc_attr( $args['value'] ),
			$args['description'] ? '<br /> <span class="description">' . esc_html( $args['description'] ) . '</span>' : '',
			esc_attr( $args['class'] )
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

		$data = apply_filters( 'hm_gtm_data_layer', $data );

		if ( ! empty( $data ) ) {
			return sprintf( '<script>var dataLayer = %s;</script>',
				json_encode( array( $data ) )
			);
		}

		return '';
	}

	/**
	 * Outputs the user_report javascript with site-wide option
	 *
	 *
	 * @return string
	 */
	public function user_report() {

		$id = get_blog_option( SITE_ID_CURRENT_SITE, 'hm_user_report_id', false );

		if ( ! $id ) {
			return '';
		}

		$tag = sprintf( '
			<script type="text/javascript">
			var _urq = _urq || [];
			_urq.push([\'initSite\', \'%1$s\']);
			(function() {
			var ur = document.createElement(\'script\'); ur.type = \'text/javascript\'; ur.async = true;
			ur.src = (\'https:\' == document.location.protocol ? \'https://cdn.userreport.com/userreport.js\' : \'http://cdn.userreport.com/userreport.js\');
			var s = document.getElementsByTagName(\'script\')[0]; s.parentNode.insertBefore(ur, s);
			})();
			</script>
			',
			esc_attr( $id )
		);

		echo $tag;
	}

}