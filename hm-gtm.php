<?php

/*
Plugin Name: Google Tag Manager tools
Description: Provides basic GTM integration by providing a template tag <code>HM_GTM\tag()</code> to place after <code>&lt;body&gt;</code> and a filterable dataLayer object
Author: Human Made Limited
Version: 1.0.1
Author URI: http://hmn.md
*/

namespace HM_GTM;

require_once __DIR__ . '/class-plugin.php';

add_action( 'plugins_loaded', array( 'HM_GTM\Plugin', 'get_instance' ) );
add_action( 'wp_head', __NAMESPACE__ . '\tag', 1, 0 );

/**
 * Output the gtm tag, place this immediately after the opening <body> tag
 *
 * @param bool $echo
 *
 * @return string
 */
function tag( $echo = true ) {
	// Back compat for tag calls in the body of a theme.
	if ( ! doing_action( 'wp_head' ) ) {
		return '';
	}

	$output = '';

	$tag = '
			<!-- Google Tag Manager -->
			<script>(function(w,d,s,l,i){w[l]=w[l]||[];w[l].push({\'gtm.start\':
			new Date().getTime(),event:\'gtm.js\'});var f=d.getElementsByTagName(s)[0],
			j=d.createElement(s),dl=l!=\'dataLayer\'?\'&l=\'+l:\'\';j.async=true;j.src=
			\'https://www.googletagmanager.com/gtm.js?id=\'+i+dl;f.parentNode.insertBefore(j,f);
			})(window,document,\'script\',\'dataLayer\',\'%1$s\');</script>
			<!-- End Google Tag Manager -->
			';

	echo Plugin::data_layer();

	$id = get_option( 'hm_gtm_id', false );
	if ( $id ) {
		$output .= sprintf( $tag, esc_attr( $id ) );
	}

	if ( is_multisite() ) {
		$network_id = get_site_option( 'hm_gtm_network_id' );
		if ( $network_id ) {
			$output .= sprintf( $tag, esc_attr( $network_id ) );
		}
	}

	if ( $echo ) {
		echo $output;
	}

	return $output;
}
