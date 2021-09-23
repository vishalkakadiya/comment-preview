<?php
/**
 * Test class for Comment Preview.
 *
 * @package Plugin_Sample
 */

/**
 * Sample test case.
 */
class Test_Comment_Preview extends WP_UnitTestCase {

	/**
	 * Holds the WP REST Server object
	 *
	 * @var WP_REST_Server
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

		$this->server = $wp_rest_server = new WP_REST_Server;

		do_action( 'rest_api_init' );

		$this->instance = new \CommentPreview\Inc\Comment_Preview();

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

//		/**
//		 * Registering with CPT.
//		 */
//		register_post_type( 'video' );
//
//		add_filter( 'wp_comment_preview_post_types', function( $post_types ) {
//
//			$post_types[] = 'video';
//
//			return $post_types;
//
//		}, 11 );
//
//		$video_post = $this->factory->post->create(
//			[
//				'post_type' => 'video',
//			]
//		);
//
//		// do_action( 'wp_enqueue_scripts' );
//
//		$this->go_to( get_permalink( $video_post ) );
//
//		$this->assertTrue( wp_script_is( 'wp-comment-preview' ) );
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

//	/**
//	 * @covers ::generate_preview
//	 */
//	public function test_generate_preview() {
//
//		activate_plugin( $this->jetpack_plugin );
//
//		update_option( 'jetpack_active_modules', array( 'markdown' ) );
//
//		\Jetpack::activate_module( 'markdown', false, false );
//
//		/**
//		 * Test as Annoymous user.
//		 */
//		$request = new WP_REST_Request( 'POST', $this->rest_route );
//
//		$request->set_param( 'comment', '[hello](https://www.google.com)' );
//		$request->set_param( 'format', 'markdown' );
//
//		$response = $this->server->dispatch( $request );
//
//		$this->assertEquals( 200, $response->status );
//
//		$this->assertEquals( '<a href="https://www.google.com">hello</a>', $response->data['comment'] );
//
//		/**
//		 * Testing as login user.
//		 */
//		$user_id = $this->factory->user->create( [ 'role' => 'administrator' ] );
//
//		wp_set_current_user( $user_id );
//
//		$request = new WP_REST_Request( 'POST', $this->rest_route );
//
//		$request->set_param( 'comment', '## h2 Heading' );
//		$request->set_param( 'format', 'markdown' );
//
//		$response = $this->server->dispatch( $request );
//
//		$this->assertEquals( 200, $response->status );
//
//		$this->assertEquals( $user_id, $response->data['author'] );
//
//		$avatar_url = get_avatar_url( $user_id, array( 'size' => 50 ) );
//
//		$this->assertEquals( $avatar_url, $response->data['gravatar'] );
//
//		$this->assertEquals( '<h2>h2 Heading</h2>', $response->data['comment'] );
//	}

	public function tearDown() {

		parent::tearDown();

		global $wp_rest_server;

		$wp_rest_server = null;
	}

}
