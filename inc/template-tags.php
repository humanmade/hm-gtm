<?php
/**
 * HM GTM Template tags.
 *
 * @package hm-gtm
 */

use function HM\GTM\get_uuid_cookie_name;

/**
 * Return the tag manager container JavaScript.
 *
 * @param string $container_id The container's ID eg. GTM-XXXXXXX.
 * @param array $data_layer Array of data to set as the initial dataLayer variable value.
 * @param string $data_layer_var Optional alternative name for the dataLayer variable.
 * @param string $container_url Optional container URL for server side tag manager.
 * @param string $snippet Optional custom code snippet. Some server side providers have very different approaches.
 * @return string
 */
function get_gtm_tag( string $container_id, array $data_layer = [], string $data_layer_var = 'dataLayer', string $container_url = '', string $snippet = '' ) : string {
	$tag = '';
	$data_layer_var = preg_replace( '/[^a-z0-9_]/i', '', $data_layer_var );

	// Add UUID cookie getter.
	if ( ! empty( get_uuid_cookie_name() ) ) {
		$tag .= sprintf(
			'<script>(function(d,f,l){!d.cookie.match("%1$s=")&&f&&f("%2$s?id="+(l&&l.getItem("%1$s"))).then(function(r){return r.json()}).then(function(d){l&&l.setItem("%1$s",d.id)})})(document,window.fetch,window.localStorage)</script>',
			esc_js( get_uuid_cookie_name() ),
			esc_js( rest_url( 'service/v1/id' ) )
		);
	}

	if ( ! empty( $data_layer ) ) {
		$tag .= sprintf(
			'<script>window.%1$s = window.%1$s || []; window.%1$s.push( %2$s );</script>',
			$data_layer_var,
			wp_json_encode( $data_layer )
		);
	}

	$snippet = $snippet ?: '
		<!-- Google Tag Manager -->
		<script>(function(w,d,s,l,i){w[l]=w[l]||[];w[l].push({\'gtm.start\':
		new Date().getTime(),event:\'gtm.js\'});var f=d.getElementsByTagName(s)[0],
		j=d.createElement(s),dl=l!=\'dataLayer\'?\'&l=\'+l:\'\';j.async=true;j.src=
		\'%2$s/gtm.js?id=\'+i+dl;f.parentNode.insertBefore(j,f);
		})(window,document,\'script\',\'%3$s\',\'%1$s\');</script>
		<!-- End Google Tag Manager -->
		';

	// Ensure requested data layer var name is used.
	$snippet = str_replace( '\'script\',\'dataLayer\'', "'script','$data_layer_var'", $snippet );

	$tag .= sprintf(
		$snippet,
		esc_attr( $container_id ),
		esc_js( untrailingslashit( $container_url ?: 'https://www.googletagmanager.com' ) ),
		$data_layer_var
	);

	/**
	 * Filter the tag.
	 *
	 * @param string $tag HTML script tag(s) for gtag and datalayer.
	 * @param string $container_id The container's ID eg. GTM-XXXXXXX.
	 * @param array $data_layer Array of data to set as the initial dataLayer variable value.
	 * @param string $data_layer_var Optional alternative name for the dataLayer variable.
	 */
	return apply_filters( 'hm_gtm_script_tag', $tag, $container_id, $data_layer, $data_layer_var );
}

/**
 * Output the tag manager container JavaScript.
 *
 * @param string $container_id The container's ID eg. GTM-XXXXXXX.
 * @param array $data_layer Array of data to set as the initial dataLayer variable value.
 * @param string $data_layer_var Optional alternative name for the dataLayer variable.
 * @param string $container_url Optional container URL for server side tag manager.
 * @param string $snippet Optional custom code snippet.
 */
function gtm_tag( string $container_id, array $data_layer = [], string $data_layer_var = 'dataLayer', string $container_url = '', string $snippet = '' ) {
	echo get_gtm_tag( ...func_get_args() );
}

/**
 * Return the tag manager container iframe.
 *
 * @param string $container_id The container's ID eg. GTM-XXXXXXX.
 * @param string $container_url Optional container URL for server side tag manager.
 * @param string $snippet Optional custom code snippet to use.
 * @return string
 */
function get_gtm_tag_iframe( string $container_id, string $container_url = '', string $snippet = '' ) : string {
	$snippet = $snippet ?: '
		<!-- Google Tag Manager (noscript) -->
		<noscript><iframe src="%2$s/ns.html?id=%1$s"
		height="0" width="0" style="display:none;visibility:hidden"></iframe></noscript>
		<!-- End Google Tag Manager (noscript) -->
		';

	return sprintf(
		$snippet,
		esc_attr( $container_id ),
		esc_attr( untrailingslashit( $container_url ?: 'https://www.googletagmanager.com' ) ),
	);
}

/**
 * Output the tag manager container JavaScript.
 *
 * @param string $container_id The container's ID eg. GTM-XXXXXXX.
 * @param string $container_url Optional container URL for server side tag manager.
 * @param string $snippet Optional custom code snippet to use.
 */
function gtm_tag_iframe( string $container_id, string $container_url = '', string $snippet = '' ) {
	echo get_gtm_tag_iframe( ...func_get_args() );
}

/**
 * Get contextual data for the dataLayer.
 *
 * @return array
 */
function get_gtm_data_layer() {
	/**
	 * Default data.
	 */
	$data = [
		'type' => 'default',
		'subtype' => 'default',
		'context' => [
			'is_front_page' => is_front_page(),
			'is_singular' => is_singular(),
			'is_archive' => is_archive(),
			'is_home' => is_home(),
			'is_search' => is_search(),
			'is_404' => is_404(),
			'is_post_type_archive' => is_post_type_archive(),
			'is_tax' => is_tax(),
		],
		'user' => [
			'logged_in' => false,
		],
		'blog' => [
			'url' => home_url(),
			'id' => is_multisite() ? get_current_blog_id() : 0,
		],
	];

	/**
	 * Multisite specific options.
	 */
	if ( is_multisite() ) {
		$data['network'] = [
			'url' => get_site_url( get_main_site_id() ),
			'id' => get_main_site_id(),
		];
	}

	/**
	 * Logged in user data.
	 */
	if ( is_user_logged_in() ) {
		$user = wp_get_current_user();
		$data['user']['logged_in'] = true;
		$data['user']['id'] = get_current_user_id();
		$data['user']['role'] = array_keys( $user->caps );
	}

	/**
	 * Single post view data.
	 */
	if ( is_singular() ) {
		$post = get_queried_object();

		$data['type'] = 'post';
		$data['subtype'] = $post->post_type;
		$data['post'] = [
			'id' => $post->ID,
			'slug' => $post->post_name,
			'published' => $post->post_date_gmt,
			'modified' => $post->post_modified_gmt,
			'template' => get_page_template_slug( $post->ID ),
			'thumbnail' => get_the_post_thumbnail_url( $post->ID, 'full' ),
		];

		if ( post_type_supports( $post->post_type, 'author' ) ) {
			// Support Authorship plugin out of the box.
			if ( function_exists( '\\Authorship\\get_authors' ) ) {
				$authors = \Authorship\get_authors( $post );
				if ( ! empty( $authors ) ) {
					$data['post']['author_id'] = $authors[0]->ID;
					$data['post']['author_slug'] = $authors[0]->user_nicename;
					$data['post']['author_ids'] = implode( ',', wp_list_pluck( $authors, 'ID' ) );
					$data['post']['author_slugs'] = implode( ',', wp_list_pluck( $authors, 'user_nicename' ) );
				}
			} else {
				$user = get_user_by( 'id', $post->post_author );

				if ( is_a( $user, 'WP_User' ) ) {
					$data['post']['author_id'] = $user->ID;
					$data['post']['author_slug'] = $user->user_nicename;
				}
			}
		}


		foreach ( get_object_taxonomies( $post->post_type, 'objects' ) as $taxonomy ) {
			if ( ! $taxonomy->public ) {
				continue;
			}

			$terms = get_the_terms( $post->ID, $taxonomy->name );

			if ( $terms && ! is_wp_error( $terms ) ) {
				$data['post'][ $taxonomy->name ] = wp_list_pluck( $terms, 'slug' );
				$data['post'][ $taxonomy->name . '_flattened' ] = implode( ',', wp_list_pluck( $terms, 'slug' ) );
			}
		}
	}

	/**
	 * Add archive type data.
	 */
	if ( is_archive() ) {
		$data['type'] = 'archive';

		if ( is_date() ) {
			$data['subtype'] = 'date';
			$data['date']    = get_the_date();
		}

		if ( is_search() ) {
			$data['subtype'] = 'search';
			$data['search_term']  = get_search_query();
		}

		if ( is_post_type_archive() ) {
			$data['subtype'] = get_post_type();
		}

		if ( is_tag() || is_category() || is_tax() ) {
			$term = get_queried_object();

			$data['type'] = 'term';
			$data['subtype'] = $term->taxonomy;
			$data['term']    = [
				'id' => $term->term_id,
				'slug' => $term->slug,
			];
		}

		if ( is_author() ) {
			$user = get_queried_object();

			$data['subtype'] = 'author';
			$data['author']  = [
				'slug' => $user->user_nicename,
				'name' => $user->display_name,
			];
		}
	}

	// Special case for 'home' page.
	if ( is_home() ) {
		$data['type'] = 'archive';
		$data['subtype'] = 'post';
	}

	/**
	 * Filter the dataLayer array.
	 *
	 * @param array $data An array of data to pass to Tag Manager.
	 */
	$data = apply_filters( 'hm_gtm_data_layer', $data );

	return $data;
}

/**
 * Helper for outputting tag manager data attributes.
 *
 * @param string $event The custom event name.
 * @param string $on The JS event listener, default to 'click'. Supports
 *                   any value that can be passed to addEventListener.
 * @param string $category Optional event category.
 * @param string $label Optional event label.
 * @param float $value Optional numeric event value.
 * @param array $fields Optional array of custom data.
 * @param string $var Optionally override the dataLayer variable name for this event.
 * @return string
 */
function get_gtm_data_attributes( string $event, string $on = 'click', string $category = '', string $label = '', ?float $value = null, array $fields = [], string $var = '' ) : string {
	$attrs = [
		'data-gtm-on' => $on,
		'data-gtm-event' => $event,
	];

	if ( ! empty( $category ) ) {
		$attrs['data-gtm-category'] = $category;
	}

	if ( ! empty( $label ) ) {
		$attrs['data-gtm-label'] = $label;
	}

	if ( ! empty( $value ) ) {
		$attrs['data-gtm-value'] = $value;
	}

	if ( ! empty( $fields ) ) {
		$attrs['data-gtm-fields'] = wp_json_encode( $fields );
	}

	if ( ! empty( $var ) ) {
		$attrs['data-gtm-var'] = preg_replace( '/[^a-z0-9_\-]/i', '', $var );
	}

	return array_reduce( array_keys( $attrs ), function ( $key ) use ( $attrs ) : string {
		return sprintf( '%s="%s" ', sanitize_key( $key ), esc_attr( $attrs[ $key ] ) );
	}, ' ' );
}
