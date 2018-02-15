<?php

/*
Plugin Name: Google Tag Manager tools
Description: Provides GTM integration per site or for an entire multisite network.
Author: Human Made Limited
Version: 1.1.0
Author URI: https://humanmade.com
*/

namespace HM_GTM;

require_once __DIR__ . '/class-plugin.php';

add_action( 'plugins_loaded', array( 'HM_GTM\Plugin', 'get_instance' ) );
add_action( 'wp_head', __NAMESPACE__ . '\tag', 1, 0 );
add_action( 'after_body', __NAMESPACE__ . '\tag', 1, 0 );

/**
 * Outputs the gtm tag, place this immediately after the opening <body> tag
 *
 * @return string
 */
function tag() {
	$output = '';
	
	/**
	 * Filter the dataLayer variable name. Tag manager allow for 
	 * custom variable names to avoid collisions and scope container events.
	 *
	 * @param string $data_layer The name to use for the dataLayer variable.
	 */
	$data_layer_var = apply_filters( 'hm_gtm_data_layer_var', 'dataLayer' );

	if ( doing_action( 'wp_head' ) ) {	
		// If it's the head action output the JS and dataLayer.
		$output .= Plugin::data_layer();
		$tag    = '
			<!-- Google Tag Manager -->
			<script>(function(w,d,s,l,i){w[l]=w[l]||[];w[l].push({\'gtm.start\':
			new Date().getTime(),event:\'gtm.js\'});var f=d.getElementsByTagName(s)[0],
			j=d.createElement(s),dl=l!=\'dataLayer\'?\'&l=\'+l:\'\';j.async=true;j.src=
			\'https://www.googletagmanager.com/gtm.js?id=\'+i+dl;f.parentNode.insertBefore(j,f);
			})(window,document,\'script\',\'%2$s\',\'%1$s\');</script>
			<!-- End Google Tag Manager -->
			';
	} else {
		// If the tag is called directly or on another action output the noscript fallback.
		// This gives us back compat and noscript support in one go.
		$tag = '
			<!-- Google Tag Manager (noscript) -->
			<noscript><iframe src="https://www.googletagmanager.com/ns.html?id=%1$s"
			height="0" width="0" style="display:none;visibility:hidden"></iframe></noscript>
			<!-- End Google Tag Manager (noscript) -->
			';
	}

	$id = get_option( 'hm_gtm_id', false );
	if ( $id ) {
		$output .= sprintf( $tag, esc_attr( $id ), sanitize_key( $data_layer_var ) );
	}

	if ( is_multisite() ) {
		$network_id = get_site_option( 'hm_gtm_network_id' );
		if ( $network_id ) {
			$output .= sprintf( $tag, esc_attr( $network_id ), sanitize_key( $data_layer_var ) );
		}
	}

	echo $output;
}
