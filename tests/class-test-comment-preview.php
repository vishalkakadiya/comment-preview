<?php
/**
 * Test class for Comment Preview.
 *
 * @package Plugin_Sample
 */

namespace CommentPreview\Tests;

use CommentPreview\Inc\Comment_Preview;

/**
 * Sample test case.
 */
class Test_Comment_Preview extends \WP_UnitTestCase {

	/**
	 * Holds the WP REST Server object
	 *
	 * @var \WP_REST_Server
	 */
	private $server;

	/**
	 * @var
	 */
	protected $instance;

	/**
	 * Jetpack plugin File Path.
	 *
	 * @var string
	 */
	protected $jetpack_plugin = WP_PLUGIN_DIR . '/jetpack/jetpack.php';

	/**
	 * @var string
	 */
	public $rest_route = '/wp_comment_preview/v1/preview';

	public function setUp() {

		parent::setUp();

		// Initiating the REST API.
		global $wp_rest_server;

		$this->server = $wp_rest_server = new \WP_REST_Server;

		do_action( 'rest_api_init' );

		$this->instance = new Comment_Preview();

		update_option( 'jetpack_active_modules', array( 'markdown' ) );
	}

	/**
	 * @covers ::_setup_hooks
	 * @covers ::__construct
	 */
	public function test_setup_hooks() {

		$this->instance->setup_hooks();

		$hooks = array(
			array(
				'type'     => 'filter',
				'name'     => 'comment_form_submit_button',
				'priority' => 10,
				'listener' => 'append_preview_button',
			),
			array(
				'type'     => 'filter',
				'name'     => 'comment_form_field_comment',
				'priority' => 20,
				'listener' => 'append_markdown_option',
			),
			array(
				'type'     => 'filter',
				'name'     => 'comment_form_fields',
				'priority' => 20,
				'listener' => 'comment_form_fields',
			),
			array(
				'type'     => 'action',
				'name'     => 'wp_enqueue_scripts',
				'priority' => 10,
				'listener' => 'enqueue_scripts',
			),
			array(
				'type'     => 'action',
				'name'     => 'rest_api_init',
				'priority' => 10,
				'listener' => 'register_rest_route',
			),
		);

		foreach ( $hooks as $hook ) {

			$this->assertEquals(
				$hook['priority'],
				call_user_func( sprintf( 'has_%s', $hook['type'] ), $hook['name'], array( $this->instance, $hook['listener'] ) ),
				sprintf( '\Deadline\Inc\Rest_Api::_setup_hooks() failed to register %1$s "%2$s" to %3$s()', $hook['type'], $hook['name'], $hook['listener'] )
			);
		}
	}

	/**
	 * @covers ::comment_form_fields
	 */
	public function test_comment_form_fields() {

		$comment_fields = array(
			'comment' => '',
		);

		// Get template file output.
		$templates_path = dirname( __FILE__ ) . '/templates/comment-preview.php';

		$output_fields = $this->instance->comment_form_fields( $comment_fields );

		ob_start();

		include $templates_path;

		// Save output and stop output buffering.
		$comment_preview_field = ob_get_clean();

		$comment_fields['comment'] = '<div id="preview-wrapper"></div>' . $comment_fields['comment'];

		$comment_fields['comment'] .= $comment_preview_field;

		$this->assertEquals( $output_fields, $comment_fields );
	}

	public function set_cpt( $post_types = array() ) {

		$post_types[] = 'video';

		return $post_types;
	}

	/**
	 * @covers ::enqueue_scripts
	 */
	public function test_enqueue_scripts_with_cpt() {

		$new_page = $this->factory->post->create(
			[
				'post_type' => 'page',
			]
		);

		do_action( 'wp_enqueue_scripts' );

		$this->go_to( get_permalink( $new_page ) );

		$this->assertFalse( wp_script_is( 'wp-comment-preview' ) );
	}

	/**
	 * @covers ::enqueue_scripts
	 */
	public function test_enqueue_scripts() {

		$new_post = $this->factory->post->create(
			[
				'post_type' => 'post',
			]
		);

		$this->go_to( get_permalink( $new_post ) );

		do_action( 'wp_enqueue_scripts' );

		$this->assertTrue( wp_script_is( 'wp-comment-preview' ) );
	}

	/**
	 * @covers ::register_rest_route
	 */
	public function test_register_rest_route() {

		$this->instance->setup_hooks();

		do_action( 'rest_api_init' );

		$routes = $this->server->get_routes();

		$custom_route = '/wp_comment_preview/v1/preview';

		$this->assertArrayHasKey( $custom_route, $routes );

		$endpoint = $routes[ $custom_route ][0];

		$this->assertTrue( is_callable( $endpoint['callback'] ) );
		$this->assertEquals( [ $this->instance, 'generate_preview' ], $endpoint['callback'] );
	}

	/**
	 * @covers ::generate_preview
	 */
	public function test_generate_preview_plain_text() {

		/**
		 * Test as guest user.
		 */
		$request = new \WP_REST_Request( 'POST', $this->rest_route );

		$comment_data = 'This is the plain text';

		$request->set_param( 'comment', $comment_data );
		$request->set_param( 'format', 'plain' );
		$request->set_param( 'author', 'Vishal Kakadiya' );

		$response = $this->server->dispatch( $request );

		$this->assertEquals( 200, $response->status );

		$this->assertEquals( $comment_data, wp_unslash( $response->data['comment'] ) );

		// Check guest author name.
		$this->assertEquals( 'Vishal Kakadiya', $response->data['author'] );

		/**
		 * Test as guest user with markdown text but without Jetpack's markdown module enabled.
		 */
		$comment_data = '## This is the Markdown';

		$request->set_param( 'comment', $comment_data );
		$request->set_param( 'format', 'markdown' );

		$response = $this->server->dispatch( $request );

		$this->assertEquals( 200, $response->status );

		$this->assertEquals( $comment_data, wp_unslash( $response->data['comment'] ) );
	}

	/**
	 * @covers ::generate_preview
	 */
	public function test_generate_preview_markdown() {

		/**
		 * Enabling jetpack plugin and markdown module.
		 */
		activate_plugin( $this->jetpack_plugin );

		\Jetpack::activate_module( 'markdown', false, false );

		update_option( 'wpcom_publish_comments_with_markdown', 1, 'yes' );

		include_once WP_PLUGIN_DIR . '/jetpack/modules/markdown/easy-markdown.php';

		\WPCom_Markdown::get_instance()->load();

		/**
		 * Testing as guest user with markdown module enabled.
		 */
		$request = new \WP_REST_Request( 'POST', $this->rest_route );

		$request->set_param( 'comment', '[hello](https://www.google.com)' );
		$request->set_param( 'format', 'markdown' );
		$request->set_param( 'author', 'Vikram Bajaj' );

		$response = $this->server->dispatch( $request );

		$this->assertEquals( 200, $response->status );

		$this->assertEquals( '<a href="https://www.google.com" rel="nofollow ugc">hello</a>', wp_unslash( $response->data['comment'] ) );

		$this->assertEquals( 'Vikram Bajaj', $response->data['author'] );

		/**
		 * Testing as login user.
		 */
		$user = $this->factory->user->create_and_get( [ 'role' => 'editor' ] );

		wp_set_current_user( $user->ID );

		$request = new \WP_REST_Request( 'POST', $this->rest_route );

		$request->set_param( 'comment', '## h2 Heading' );
		$request->set_param( 'format', 'markdown' );

		$response = $this->server->dispatch( $request );

		$this->assertEquals( 200, $response->status );

		$this->assertEquals( $user->data->display_name, $response->data['author'] );

		$avatar_url = get_avatar_url( $user->ID, array( 'size' => 50 ) );

		$this->assertEquals( $avatar_url, $response->data['gravatar'] );

		delete_option( 'wpcom_publish_comments_with_markdown' );

		\Jetpack::deactivate_module( 'markdown' );
	}

	/**
	 * Clean up.
	 */
	public function tearDown() {

		parent::tearDown();

		global $wp_rest_server;

		$wp_rest_server = null;
	}

}
