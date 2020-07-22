<?php
/**
 * Primary plugin file.
 *
 * @package   Block_Lab
 * @copyright Copyright(c) 2020, Block Lab
 * @license   http://opensource.org/licenses/GPL-2.0 GNU General Public License, version 2 (GPL-2.0)
 */

namespace Block_Lab;

/**
 * Class Plugin
 */
class Plugin extends Plugin_Abstract {

	/**
	 * Utility methods.
	 *
	 * @var Util
	 */
	protected $util;

	/**
	 * WP Admin resources.
	 *
	 * @var Admin\Admin
	 */
	public $admin;

	/**
	 * Block loader.
	 *
	 * @var Blocks\Loader
	 */
	public $loader;

	/**
	 * The slug of the post type that stores the blocks.
	 *
	 * @since 1.3.5
	 * @var string
	 */
	public $post_type_slug = 'block_lab';

	/**
	 * Execute this as early as possible.
	 */
	public function init() {
		$this->util = new Util();
		$this->register_component( $this->util );
		$this->register_component( new Post_Types\Block_Post() );

		$this->loader = new Blocks\Loader();
		$this->register_component( $this->loader );

		register_activation_hook(
			$this->get_file(),
			function() {
				$onboarding = new Admin\Onboarding();
				$onboarding->plugin_activation();
			}
		);
	}

	/**
	 * Execute this once plugins are loaded. (not the best place for all hooks)
	 */
	public function plugin_loaded() {
		$this->admin = new Admin\Admin();
		$this->register_component( $this->admin );

		if ( $this->is_plugin_conflict() ) {
			add_action( 'admin_notices', [ $this, 'plugin_conflict_notice' ] );
		} else {
			$this->require_helpers();
		}
	}

	/**
	 * Require the helper function files.
	 */
	public function require_helpers() {
		require_once __DIR__ . '/helpers.php';
		require_once __DIR__ . '/deprecated.php';
	}

	/**
	 * Gets whether there is a conflict from another plugin having the same functions.
	 *
	 * @return bool Whether there is a conflict.
	 */
	public function is_plugin_conflict() {
		return function_exists( 'block_field' ) && function_exists( 'block_value' );
	}

	/**
	 * An admin notice for another plugin being active.
	 *
	 * Only display this if the user can deactivate plugins,
	 * and if this is on a Block Lab or plugins page.
	 */
	public function plugin_conflict_notice() {
		if ( ! current_user_can( 'deactivate_plugins' ) ) {
			return;
		}

		$screen                = get_current_screen();
		$should_display_notice = (
			( isset( $screen->base, $screen->post_type ) && 'edit' === $screen->base && $this->post_type_slug === $screen->post_type )
			||
			( isset( $screen->base ) && in_array( $screen->base, [ 'plugins', 'block_lab_page_block-lab-settings' ], true ) )
		);

		if ( $should_display_notice ) {
			printf(
				'<div class="notice notice-error"><p>%1$s</p></div>',
				esc_html__( 'It looks like Block Lab is active. Please deactivate it or migrate, as it will not work while Genesis Custom Blocks is active.', 'block-lab' )
			);
		}
	}
}
