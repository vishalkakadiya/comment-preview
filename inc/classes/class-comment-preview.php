<?php
/**
 * Class to manage functions for comment preview.
 *
 * @package WP_Comment_Preview
 */

namespace CommentPreview\Inc;

/**
 * Class for comment preview functionality.
 *
 * @package CommentPreview\Inc
 */
class Comment_Preview {

	/**
	 * Class constructor.
	 */
	public function __construct() {

		$this->_setup_hooks();
	}

	/**
	 * Initialize actions and filters.
	 */
	protected function _setup_hooks() {

		add_action( 'wp_enqueue_scripts', array( $this, 'enqueue_scripts' ) );

		add_filter( 'comment_form_fields', array( $this, 'comment_form_fields' ), 20 );

		add_filter( 'comment_form_field_comment', array( $this, 'append_markdown_option' ), 20 );

		add_filter( 'comment_form_submit_button', array( $this, 'append_preview_button' ), 20 );

		add_action( 'rest_api_init', array( $this, 'register_rest_route' ) );
	}

	/**
	 * Enqueue JavaScript for handling comment previews.
	 */
	public function enqueue_scripts() {

		/**
		 * Filter to enable comment preview on custom post types.
		 *
		 * @param array List of post types.
		 */
		$post_types = apply_filters( 'wp_comment_preview_allowed_post_types', array( 'post' ) );

		if ( is_singular( $post_types ) ) {

			wp_register_script(
				'wp-comment-preview',
				WP_COMMENT_PREVIEW_URL . '/assets/js/comment-preview.js',
				array(),
				'1.0.0',
				true
			);

			wp_localize_script(
				'wp-comment-preview',
				'commentPreviewData',
				array(
					'apiURL' => get_rest_url( null, 'wp_comment_preview/v1/' ),
					'nonce'  => wp_create_nonce( 'wp_rest' ),
				)
			);

			wp_enqueue_script( 'wp-comment-preview' );
		}
	}

	/**
	 * Add custom markup in comment form.
	 *
	 * @param array $comment_fields Comment fields.
	 *
	 * @return mixed
	 */
	public function comment_form_fields( array $comment_fields = array() ) {

		ob_start();

		// Get template file output.
		include WP_COMMENT_PREVIEW_PATH . 'templates/comment-preview.php';

		// Save output and stop output buffering.
		$field = ob_get_clean();

		if ( ! empty( $field ) ) {

			$comment_fields['comment'] = '<div id="preview-wrapper"></div>' . $comment_fields['comment'];

			$comment_fields['comment'] .= $field;
		}

		return $comment_fields;
	}

	/**
	 * Append radio buttons to allow a commenter to format their comment in
	 * either markdown or plain text.
	 *
	 * @param string $fields HTML to output for the comment field.
	 *
	 * @return string Modified HTML.
	 */
	public function append_markdown_option( $fields ) {

		ob_start();

		// Get template file output.
		include WP_COMMENT_PREVIEW_PATH . 'templates/markdown-option.php';

		// Save output and stop output buffering.
		$markdown_option_field = ob_get_clean();

		return $fields . $markdown_option_field;
	}

	/**
	 * Append a button to allow commenters to preview their comment.
	 *
	 * @param string $submit_button HTML to output for the submit button.
	 *
	 * @return string Modified HTML
	 */
	public function append_preview_button( $submit_button = '' ) {

		$preview_button = sprintf(
			'<input name="preview" type="button" id="preview" class="submit" value="%1$s">',
			esc_html__( 'Preview', 'comment-preview' )
		);

		return $submit_button . $preview_button;
	}

	/**
	 * Register the route for generating comment previews.
	 */
	public function register_rest_route() {

		register_rest_route(
			'wp_comment_preview/v1',
			'preview',
			array(
				'methods'             => \WP_REST_Server::CREATABLE,
				'callback'            => array( $this, 'generate_preview' ),
				'permission_callback' => '__return_true',
			)
		);
	}

	/**
	 * Processes a comment for previewing.
	 *
	 * @param \WP_REST_Request $request Full details about the request.
	 *
	 * @return array Response object.
	 */
	public function generate_preview( $request ) {

		$response = array();

		if ( ! empty( $request['author'] ) ) {
			$response['author'] = esc_html( $request['author'] );
		}

		$user_id = ( ( is_user_logged_in() ) ? get_current_user_id() : 0 );

		if ( ! empty( $user_id ) && empty( $response['author'] ) ) {

			$user = get_userdata( $user_id );

			if ( $user ) {

				$response['author'] = $user->data->display_name;
			}
		}

		$response['gravatar'] = get_avatar_url( $user_id, array( 'size' => 50 ) );

		$response['date'] = current_time( get_option( 'date_format' ) . ' \a\t ' . get_option( 'time_format' ) );

		if ( ! empty( $request['comment'] ) && isset( $request['format'] ) ) {
			if ( 'text' === $request['format'] ) {
				$comment = wp_kses_data( $request['comment'] );
			} else {
				$comment = apply_filters( 'pre_comment_content', $request['comment'] );
			}
		} else {
			$comment = '';
		}

		$response['comment'] = $comment;

		return $response;
	}

}
