<?php

/**
 * Registers new REST API endpoints.
 *
 * @since 3.0.0
 */
class RegenerateThumbnails_REST_Controller extends WP_REST_Controller {
	/**
	 * The namespace for the REST API routes.
	 *
	 * @since 3.0.0
	 *
	 * @var string
	 */
	public $namespace = 'regenerate-thumbnails/v1';

	/**
	 * The base prefix for the routes that this class adds.
	 *
	 * @since 3.0.0
	 *
	 * @var string
	 */
	public $rest_base = 'regenerate';

	/**
	 * Register the new routes and endpoints.
	 *
	 * @since 3.0.0
	 */
	public function register_routes() {
		register_rest_route( $this->namespace, '/' . $this->rest_base . '/(?P<id>[\d]+)', array(
			array(
				'methods'             => WP_REST_Server::EDITABLE,
				'callback'            => array( $this, 'regenerate_item' ),
				'permission_callback' => array( $this, 'regenerate_item_permissions_check' ),
				'args'                => array(
					'regeneration_args'           => array(
						'default'           => array(),
						'validate_callback' => array( $this, 'is_array' ),
					),
					'update_usages_in_posts'      => array(
						'default' => true,
					),
					'update_usages_in_posts_args' => array(
						'default'           => array(),
						'validate_callback' => array( $this, 'is_array' ),
					),
				),
			),
		) );
	}

	/**
	 * Regenerate the thumbnails for a specific media item.
	 *
	 * @since 3.0.0
	 *
	 * @param WP_REST_Request $request Full data about the request.
	 *
	 * @return true|WP_Error True on success, otherwise a WP_Error object.
	 */
	public function regenerate_item( $request ) {
		$regenerator = RegenerateThumbnails_Regenerator::get_instance( $request->get_param( 'id' ) );

		if ( is_wp_error( $regenerator ) ) {
			return $regenerator;
		}

		$result = $regenerator->regenerate( $request->get_param( 'regeneration_args' ) );

		if ( is_wp_error( $result ) ) {
			return $result;
		}

		if ( $request->get_param( 'update_usages_in_posts' ) ) {
			$posts_updated = $regenerator->update_usages_in_posts( $request->get_param( 'update_usages_in_posts_args' ) );

			// If wp_update_post() failed for any posts, return that error
			foreach ( $posts_updated as $post_updated_id => $result ) {
				if ( is_wp_error( $result ) ) {
					return $result;
				}
			}
		}

		return true;
	}

	/**
	 * Check to see if the current user is allowed to use this endpoint.
	 *
	 * @since 3.0.0
	 *
	 * @param WP_REST_Request $request Full data about the request.
	 *
	 * @return bool Whether the current user has permission to regenerate thumbnails.
	 */
	public function regenerate_item_permissions_check( $request ) {
		return current_user_can( RegenerateThumbnails()->capability );
	}

	/**
	 * Returns whether a variable is an array or not. This is needed because 3 arguments are
	 * passed to validation callbacks but is_array() only accepts one argument.
	 *
	 * @see https://core.trac.wordpress.org/ticket/34659
	 *
	 * @param mixed           $param   The parameter value to validate.
	 * @param WP_REST_Request $request The REST request.
	 * @param string          $key     The parameter name.
	 *
	 * @return bool Whether the parameter is an array or not.
	 */
	public function is_array( $param, $request, $key ) {
		return is_array( $param );
	}
}