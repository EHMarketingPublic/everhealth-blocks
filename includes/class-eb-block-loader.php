<?php
/**
 * Auto-discovers and registers ACF blocks from the /blocks directory.
 *
 * Convention per block (one folder per block, folder name = block slug):
 *
 *   blocks/
 *     example-block/
 *       block.json     <- required. Standard WP block.json, with an
 *                          "acf" key (ACF's block.json / "v3" registration
 *                          style — this is what powers ACF 6.1+ native
 *                          block.json support instead of the legacy
 *                          acf_register_block_type() PHP array).
 *       render.php      <- required if block.json's "render" points to it.
 *       fields.php      <- optional. Returns an array for
 *                          acf_add_local_field_group(); auto-loaded and
 *                          scoped to this block automatically.
 *       style.css       <- optional, enqueued if present.
 *       editor.css      <- optional, enqueued in the editor if present.
 *
 * This intentionally mirrors core WP's own block.json convention so any
 * dev on the team can drop in a new block without touching this file.
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

class EB_Block_Loader {

	/** @var string Absolute path to the /blocks directory. */
	private $blocks_dir;

	public function __construct( $blocks_dir ) {
		$this->blocks_dir = trailingslashit( $blocks_dir );
	}

	public function register() {
		add_action( 'acf/init', array( $this, 'register_field_groups' ) );
		add_action( 'init', array( $this, 'register_blocks' ) );
		add_filter( 'block_categories_all', array( $this, 'register_block_category' ) );
	}

	/**
	 * Adds a dedicated "EverHealth Blocks" category in the block inserter
	 * so these blocks aren't mixed in with core/theme blocks.
	 */
	public function register_block_category( $categories ) {
		return array_merge(
			array(
				array(
					'slug'  => 'everhealth-blocks',
					'title' => __( 'EverHealth Blocks', 'everhealth-blocks' ),
					'icon'  => 'layout',
				),
			),
			$categories
		);
	}

	/**
	 * Returns an array of block folder paths, one per discovered block.
	 */
	private function get_block_dirs() {
		if ( ! is_dir( $this->blocks_dir ) ) {
			return array();
		}

		$dirs = glob( $this->blocks_dir . '*', GLOB_ONLYDIR );
		return $dirs ? $dirs : array();
	}

	/**
	 * Registers each block with core WP's register_block_type(), pointed
	 * at its block.json. This is the ACF v3 / block.json registration
	 * path — ACF hooks into register_block_type() automatically via the
	 * "acf" key inside block.json, so no acf_register_block_type() call
	 * is needed here.
	 */
	public function register_blocks() {
		foreach ( $this->get_block_dirs() as $dir ) {
			$block_json = trailingslashit( $dir ) . 'block.json';

			if ( ! file_exists( $block_json ) ) {
				continue;
			}

			register_block_type( $dir );
		}
	}

	/**
	 * Loads each block's optional fields.php and registers its field
	 * group with ACF, if present. Keeping fields co-located with the
	 * block folder (rather than one giant fields file) is what makes
	 * this scale cleanly across the four brand sites.
	 */
	public function register_field_groups() {
		if ( ! function_exists( 'acf_add_local_field_group' ) ) {
			return;
		}

		foreach ( $this->get_block_dirs() as $dir ) {
			$fields_file = trailingslashit( $dir ) . 'fields.php';

			if ( ! file_exists( $fields_file ) ) {
				continue;
			}

			$field_group = include $fields_file;

			if ( is_array( $field_group ) ) {
				acf_add_local_field_group( $field_group );
			}
		}
	}
}
