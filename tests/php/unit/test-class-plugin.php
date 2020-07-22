<?php
/**
 * Tests for class Plugin.
 *
 * @package Block_Lab
 */

use Block_Lab\Plugin;
use function Brain\Monkey\Functions\expect;
use function Brain\Monkey\setUp;
use function Brain\Monkey\tearDown;

/**
 * Tests for class Plugin.
 */
class Test_Plugin extends \WP_UnitTestCase {

	use Testing_Helper;

	/**
	 * Instance of Plugin.
	 *
	 * @var Plugin
	 */
	public $instance;

	/**
	 * Setup.
	 *
	 * @inheritdoc
	 */
	public function setUp() {
		parent::setUp();
		setUp();
		$this->instance = new Plugin();
		$this->instance->init();
		$this->instance->plugin_loaded();
	}

	/**
	 * Teardown.
	 *
	 * @inheritdoc
	 */
	public function tearDown() {
		tearDown();
		parent::tearDown();
	}

	/**
	 * Test init.
	 *
	 * @covers \Block_Lab\Plugin::init()
	 */
	public function test_init() {
		$plugin_instance = new Plugin();
		$plugin_instance->init();
		$plugin_instance->plugin_loaded();

		$reflection_plugin = new ReflectionObject( $this->instance );
		$util_property     = $reflection_plugin->getProperty( 'util' );

		$util_property->setAccessible( true );
		$util_class = $util_property->getValue( $this->instance );

		$this->assertEquals( 'Block_Lab\Util', get_class( $util_class ) );
	}

	/**
	 * Test plugin_loaded.
	 *
	 * @covers \Block_Lab\Plugin::plugin_loaded()
	 */
	public function test_plugin_loaded() {
		$this->instance->plugin_loaded();
		$this->assertEquals( 'Block_Lab\Admin\Admin', get_class( $this->instance->admin ) );
	}

	/**
	 * Test is_pro.
	 *
	 * This is essentially the same test as in Test_Util.
	 * But this tests that the __call() magic method in Plugin works.
	 * This method, is_pro(), is called in the Plugin class.
	 * So this ensures that the magic method refers the call to the Util class.
	 *
	 * @covers \Block_Lab\Util::is_pro()
	 */
	public function test_is_pro() {
		$this->instance->admin = new Block_Lab\Admin\Admin();
		$this->instance->admin->init();
		$this->set_license_validity( true );
		$this->assertTrue( $this->instance->is_pro() );

		$this->set_license_validity( false );
		$this->assertFalse( $this->instance->is_pro() );
	}

	/**
	 * Test get_template_locations.
	 *
	 * This is also essentially the same test as in Test_Util.
	 * But this also tests that the __call() magic method in Plugin works.
	 *
	 * @covers \Block_Lab\Util::get_template_locations()
	 */
	public function test_get_template_locations() {
		$name = 'foo-baz';
		$this->assertEquals(
			[
				'blocks/foo-baz/block.php',
				'blocks/block-foo-baz.php',
				'blocks/block.php',
			],
			$this->instance->get_template_locations( $name )
		);
	}

	/**
	 * Gets the test data for test_is_plugin_conflict.
	 *
	 * @return array The test data.
	 */
	public function get_data_is_conflict() {
		return [
			'no_conflict' => [ false, false ],
			'conflict'    => [ true, true ],
		];
	}

	/**
	 * Test is_plugin_conflict.
	 *
	 * @dataProvider get_data_is_conflict
	 * @covers \Genesis\CustomBlocks\Util::get_template_locations()
	 *
	 * @param bool $function_exists Whether the function exists.
	 * @param bool $expected        The expected return value.
	 */
	public function test_is_plugin_conflict( $function_exists, $expected ) {
		expect( 'function_exists' )
			->andReturn( $function_exists );

		// This should return the cached value, without needing to call function_exists() again.
		$this->assertEquals( $expected, $this->instance->is_plugin_conflict() );
	}

	/**
	 * Test plugin_conflict_notice does not display when on the wrong page or with the wrong user.
	 *
	 * @covers \Genesis\CustomBlocks\Util::plugin_conflict_notice()
	 */
	public function test_plugin_conflict_notice_does_not_display() {
		ob_start();
		$this->instance->plugin_conflict_notice();
		$this->assertEmpty( ob_get_clean() );
	}

	/**
	 * Test plugin_conflict_notice displays when it should.
	 *
	 * @covers \Genesis\CustomBlocks\Util::plugin_conflict_notice()
	 */
	public function test_plugin_conflict_notice_displays() {
		$user_id = $this->factory()->user->create( [ 'role' => 'administrator' ] );
		wp_set_current_user( $user_id );
		if ( is_multisite() ) {
			grant_super_admin( $user_id );
		}

		$mock_current_screen       = new stdClass();
		$mock_current_screen->base = 'block_lab_page_block-lab-settings';

		expect( 'get_current_screen' )
			->once()
			->andReturn( $mock_current_screen );

		ob_start();
		$this->instance->plugin_conflict_notice();
		$this->assertContains(
			'<div class="notice notice-error"><p>It looks like Block Lab is active. Please deactivate it or migrate, as it will not work while Genesis Custom Blocks is active.</p></div>',
			ob_get_clean()
		);
	}
}
