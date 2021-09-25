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
	protected $_instance;

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

		$wp_rest_server = new \WP_REST_Server();

		$this->server = $wp_rest_server;

		do_action( 'rest_api_init' );

		$this->_instance = new Comment_Preview();

		update_option( 'jetpack_active_modules', array( 'markdown' ) );
	}

	/**
	 * Call protected/private method of a class.
	 *
	 * @param object &$object     Instantiated object that we will run method on.
	 * @param string $method_name Method name to call
	 * @param array  $parameters  Array of parameters to pass into method.
	 *
	 * @throws \ReflectionException
	 *
	 * @return mixed Method return.
	 */
	public function invoke_hidden_method( &$object, $method_name, array $parameters = array() ) {

		$reflection = new \ReflectionClass( get_class( $object ) );

		$method = $reflection->getMethod( $method_name );

		$method->setAccessible( true );

		return $method->invokeArgs( $object, $parameters );
	}

	/**
	 * @covers ::_setup_hooks
	 * @covers ::__construct
	 */
	public function test_setup_hooks() {

		$this->invoke_hidden_method( $this->_instance, '_setup_hooks' );

		$hooks = array(
			array(
				'type'     => 'action',
				'name'     => 'wp_enqueue_scripts',
				'priority' => 10,
				'listener' => 'enqueue_scripts',
			),
			array(
				'type'     => 'filter',
				'name'     => 'comment_form_fields',
				'priority' => 20,
				'listener' => 'comment_form_fields',
			),
			array(
				'type'     => 'filter',
				'name'     => 'comment_form_field_comment',
				'priority' => 20,
				'listener' => 'append_markdown_option',
			),
			array(
				'type'     => 'filter',
				'name'     => 'comment_form_submit_button',
				'priority' => 20,
				'listener' => 'append_preview_button',
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
				call_user_func( sprintf( 'has_%s', $hook['type'] ), $hook['name'], array( $this->_instance, $hook['listener'] ) ),
				sprintf( '\Deadline\Inc\Rest_Api::_setup_hooks() failed to register %1$s "%2$s" to %3$s()', $hook['type'], $hook['name'], $hook['listener'] )
			);
		}
	}

	/**
	 * @covers ::enqueue_scripts
	 */
	public function test_enqueue_scripts_with_page_type() {

		$new_page = $this->factory->post->create(
			array(
				'post_type' => 'page',
			)
		);

		$this->go_to( get_permalink( $new_page ) );

		do_action( 'wp_enqueue_scripts' );

		$this->assertFalse( wp_script_is( 'wp-comment-preview' ) );
	}

	/**
	 * @covers ::enqueue_scripts
	 */
	public function test_enqueue_scripts() {

		$new_post = $this->factory->post->create(
			array(
				'post_type' => 'post',
			)
		);

		$this->go_to( get_permalink( $new_post ) );

		do_action( 'wp_enqueue_scripts' );

		$this->assertTrue( wp_script_is( 'wp-comment-preview' ) );
	}

	/**
	 * @covers ::comment_form_fields
	 */
	public function test_comment_form_fields() {

		$comment_fields = array(
			'comment' => '',
		);

		$output_fields = apply_filters( 'comment_form_fields', $comment_fields );

		ob_start();

		include dirname( __FILE__ ) . '/templates/comment-preview.php';

		// Save output and stop output buffering.
		$comment_preview_field = ob_get_clean();

		$this->assertContains( '<div id="preview-wrapper"></div>', $output_fields['comment'] );

		$this->assertContains( $comment_preview_field, $output_fields['comment'] );
	}

	/**
	 * @covers ::append_markdown_option
	 */
	public function test_append_markdown_option() {

		$field = '<input type="text" name="test" />';

		$output_fields = apply_filters( 'comment_form_field_comment', $field );

		ob_start();

		include dirname( __FILE__ ) . '/templates/markdown-option.php';

		// Save output and stop output buffering.
		$comment_preview_field = ob_get_clean();

		$this->assertContains( $comment_preview_field, $output_fields );
	}

	/**
	 * @covers ::append_preview_button
	 */
	public function test_append_preview_button() {

		$button = '<input type="submit" name="comment_submit" />';

		$output_buttons = apply_filters( 'comment_form_submit_button', $button );

		$preview_button = sprintf(
			'<input name="preview" type="button" id="preview" class="submit" value="%1$s">',
			esc_html__( 'Preview', 'comment-preview' )
		);

		$this->assertContains( $preview_button, $output_buttons );
	}

	/**
	 * @covers ::register_rest_route
	 */
	public function test_register_rest_route() {

		$this->invoke_hidden_method( $this->_instance, '_setup_hooks' );

		do_action( 'rest_api_init' );

		$routes = $this->server->get_routes();

		$custom_route = '/wp_comment_preview/v1/preview';

		$this->assertArrayHasKey( $custom_route, $routes );

		$endpoint = $routes[ $custom_route ][0];

		$this->assertTrue( is_callable( $endpoint['callback'] ) );

		$this->assertEquals( array( $this->_instance, 'generate_preview' ), $endpoint['callback'] );
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
		$user = $this->factory->user->create_and_get( array( 'role' => 'editor' ) );

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
