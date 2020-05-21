<?php
/**
 * HM GTM Template tags.
 *
 * @package hm-gtm
 */

/**
 * Return the tag manager container JavaScript.
 *
 * @param string $container_id The container's ID eg. GTM-XXXXXXX.
 * @param array $data_layer Array of data to set as the initial dataLayer variable value.
 * @param string $data_layer_var Optional alternative name for the dataLayer variable.
 * @return string
 */
function get_gtm_tag( string $container_id, array $data_layer = [], string $data_layer_var = 'dataLayer' ) : string {
	$tag = '';
	$data_layer_var = preg_replace( '/[^a-z0-9_\-]/i', '', $data_layer_var );

	if ( ! empty( $data_layer ) ) {
		$tag .= sprintf(
			'<script>var %1$s = [ %2$s ];</script>',
			$data_layer_var,
			wp_json_encode( $data_layer )
		);
	}

	$tag .= sprintf( '
		<!-- Google Tag Manager -->
		<script>(function(w,d,s,l,i){w[l]=w[l]||[];w[l].push({\'gtm.start\':
		new Date().getTime(),event:\'gtm.js\'});var f=d.getElementsByTagName(s)[0],
		j=d.createElement(s),dl=l!=\'dataLayer\'?\'&l=\'+l:\'\';j.async=true;j.src=
		\'https://www.googletagmanager.com/gtm.js?id=\'+i+dl;f.parentNode.insertBefore(j,f);
		})(window,document,\'script\',\'%2$s\',\'%1$s\');</script>
		<!-- End Google Tag Manager -->
		',
		esc_attr( $container_id ),
		$data_layer_var
	);

	return $tag;
}

/**
 * Output the tag manager container JavaScript.
 *
 * @param string $container_id The container's ID eg. GTM-XXXXXXX.
 * @param array $data_layer Array of data to set as the initial dataLayer variable value.
 * @param string $data_layer_var Optional alternative name for the dataLayer variable.
 */
function gtm_tag( string $container_id, array $data_layer = [], string $data_layer_var = 'dataLayer' ) {
	echo get_gtm_tag( $container_id, $data_layer, $data_layer_var );
}

/**
 * Return the tag manager container iframe.
 *
 * @param string $container_id The container's ID eg. GTM-XXXXXXX.
 * @return string
 */
function get_gtm_tag_iframe( string $container_id ) : string {
	return sprintf( '
		<!-- Google Tag Manager (noscript) -->
		<noscript><iframe src="https://www.googletagmanager.com/ns.html?id=%1$s"
		height="0" width="0" style="display:none;visibility:hidden"></iframe></noscript>
		<!-- End Google Tag Manager (noscript) -->
		',
		esc_attr( $container_id )
	);
}

/**
 * Output the tag manager container JavaScript.
 *
 * @param string $container_id The container's ID eg. GTM-XXXXXXX.
 */
function gtm_tag_iframe( string $container_id ) {
	echo get_gtm_tag_iframe( $container_id );
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
			'role' => [],
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
			'ID' => $post->ID,
			'slug' => $post->post_name,
			'published' => $post->post_date_gmt,
			'modified' => $post->post_modified_gmt,
			'comments' => get_comment_count( $post->ID )['approved'],
			'template' => get_page_template_slug( $post->ID ),
			'thumbnail' => get_the_post_thumbnail_url( $post->ID, 'full' ),
		];

		if ( post_type_supports( $post->post_type, 'author' ) ) {
			$data['post']['author_ID'] = $post->post_author;
			$data['post']['author_slug'] = get_user_by( 'id', $post->post_author )->get( 'user_nicename' );
		}

		foreach ( get_object_taxonomies( $post->post_type, 'objects' ) as $taxonomy ) {
			if ( ! $taxonomy->public ) {
				continue;
			}

			$terms = get_the_terms( $post->ID, $taxonomy->name );

			if ( $terms && ! is_wp_error( $terms ) ) {
				$data['post'][ $taxonomy->name ] = wp_list_pluck( $terms, 'slug' );
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
				'ID' => $term->term_id,
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
 * @param numeric $value Optional numeric event value.
 * @param array $fields Optional array of custom data.
 * @param strin $var Optionally override the dataLayer variable name for this event.
 * @return string
 */
function get_gtm_data_attributes( string $event, string $on = 'click', string $category = '', string $label = '', ?numeric $value = null, array $fields = [], string $var = '' ) : string {
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
