<?php

/*
Plugin Name: Google Tag Manager tools
Description: Provides basic GTM integration by providing a template tag <code>HM_GTM\tag()</code> to place after <code>&lt;body&gt;</code> and a filterable dataLayer object
Author: Human Made Limited
Version: 1.0
Author URI: http://hmn.md
*/

namespace HM_GTM;

require_once 'class-plugin.php';

add_action( 'plugins_loaded', array( 'HM_GTM\Plugin', 'get_instance' ) );

/**
 * Output the gtm tag, place this immediately after the opening <body> tag
 *
 * @param bool $echo
 * @param bool $network True to include network wide Google Tag Manager
 *
 * @return string
 */
function tag( $echo = true ) {

	$id = get_option( 'hm_gtm_id', false );

	$tag = '';

	$data_layer = Plugin::data_layer();

	if ( $id ) {
		$tag = sprintf( '
			%2$s
			<!-- Google Tag Manager -->
			<noscript><iframe src="//www.googletagmanager.com/ns.html?id=%1$s" height="0" width="0" style="display:none;visibility:hidden"></iframe></noscript>
			<script>(function(w,d,s,l,i){w[l]=w[l]||[];w[l].push({\'gtm.start\': new Date().getTime(),event:\'gtm.js\'});
				var f=d.getElementsByTagName(s)[0], j=d.createElement(s), dl=l!=\'dataLayer\'?\'&l=\'+l:\'\';
				j.async=true;j.src=\'//www.googletagmanager.com/gtm.js?id=\'+i+dl;f.parentNode.insertBefore(j,f);
			})(window,document,\'script\',\'dataLayer\',\'%1$s\');</script>
			<!-- End Google Tag Manager -->
			',
			esc_attr( $id ),
			$data_layer
		);
		$data_layer = '';
	}

	if ( is_multisite() ) {

		$id = get_site_option( 'hm_network_gtm_id' );

		if ( $id ) {
			$tag .= sprintf( '
				%2$s
				<!-- Google Tag Manager - Network Wide -->
				<noscript><iframe src="//www.googletagmanager.com/ns.html?id=%1$s" height="0" width="0" style="display:none;visibility:hidden"></iframe></noscript>
				<script>(function(w,d,s,l,i){w[l]=w[l]||[];w[l].push({\'gtm.start\': new Date().getTime(),event:\'gtm.js\'});
					var f=d.getElementsByTagName(s)[0], j=d.createElement(s), dl=l!=\'dataLayer\'?\'&l=\'+l:\'\';
					j.async=true;j.src=\'//www.googletagmanager.com/gtm.js?id=\'+i+dl;f.parentNode.insertBefore(j,f);
				})(window,document,\'script\',\'dataLayer\',\'%1$s\');</script>
				<!-- End Google Tag Manager -->
				',
				esc_attr( $id ),
				$data_layer
			);
		}
	}

	if ( $echo ) {
		echo $tag;
	}

	return $tag;
}
