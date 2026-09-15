<?php
/**
 * Plugin Name:       EverHealth Blocks
 * Plugin URI:         https://github.com/YOUR-ORG/everhealth-blocks
 * Description:        Shared ACF Blocks plugin for EverHealth WordPress sites (DrChrono, CollaborateMD, Updox, iSalus). Requires ACF or ACF PRO. Supports ACF FontAwesome field and ACF Blocks v3 (block.json) registration.
 * Version:            1.0.0
 * Requires at least:  6.0
 * Requires PHP:       7.4
 * Author:              EverHealth
 * License:            GPL v2 or later
 * License URI:        https://www.gnu.org/licenses/gpl-2.0.html
 * Text Domain:        everhealth-blocks
 * Domain Path:        /languages
 *
 * GitHub Plugin URI:  YOUR-ORG/everhealth-blocks
 * Primary Branch:     main
 */

// Block direct access.
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * -----------------------------------------------------------------------
 * Core constants
 * -----------------------------------------------------------------------
 * Update EB_GITHUB_REPO below to your actual "owner/repo" GitHub path.
 * This is the single place that needs to change per-environment if you
 * ever fork this plugin; the slug/name stays "everhealth-blocks" so it
 * behaves identically across every site it's installed on.
 */
define( 'EB_VERSION', '1.0.0' );
define( 'EB_PLUGIN_FILE', __FILE__ );
define( 'EB_PLUGIN_DIR', plugin_dir_path( __FILE__ ) );
define( 'EB_PLUGIN_URL', plugin_dir_url( __FILE__ ) );
define( 'EB_PLUGIN_BASENAME', plugin_basename( __FILE__ ) );
define( 'EB_BLOCKS_DIR', EB_PLUGIN_DIR . 'blocks' );
define( 'EB_GITHUB_REPO', 'YOUR-ORG/everhealth-blocks' ); // e.g. 'everhealth/everhealth-blocks'
define( 'EB_GITHUB_ACCESS_TOKEN', '' ); // Optional: set a fine-grained PAT if the repo is private, otherwise leave blank.

/**
 * -----------------------------------------------------------------------
 * Class includes
 * -----------------------------------------------------------------------
 */
require_once EB_PLUGIN_DIR . 'includes/class-eb-requirements.php';
require_once EB_PLUGIN_DIR . 'includes/class-eb-block-loader.php';
require_once EB_PLUGIN_DIR . 'includes/class-eb-github-updater.php';

/**
 * Main plugin bootstrap class. Kept intentionally thin — it just wires
 * the requirement check, the block loader, and the updater together.
 */
final class EverHealth_Blocks {

	/** @var EverHealth_Blocks|null */
	private static $instance = null;

	/** @var EB_Block_Loader|null */
	public $block_loader = null;

	public static function instance() {
		if ( null === self::$instance ) {
			self::$instance = new self();
		}
		return self::$instance;
	}

	private function __construct() {
		add_action( 'plugins_loaded', array( $this, 'init' ) );

		// The updater needs to run early (on admin_init/pre_set_site_transient)
		// regardless of whether ACF is active, so a missing ACF install doesn't
		// block sites from receiving plugin updates that might fix that.
		if ( is_admin() ) {
			new EB_GitHub_Updater(
				EB_PLUGIN_FILE,
				EB_GITHUB_REPO,
				EB_VERSION,
				EB_GITHUB_ACCESS_TOKEN
			);
		}
	}

	public function init() {
		// Bail early (with an admin notice) if ACF isn't active at all.
		if ( ! EB_Requirements::check() ) {
			add_action( 'admin_notices', array( 'EB_Requirements', 'admin_notice' ) );
			return;
		}

		load_plugin_textdomain( 'everhealth-blocks', false, dirname( EB_PLUGIN_BASENAME ) . '/languages' );

		$this->block_loader = new EB_Block_Loader( EB_BLOCKS_DIR );
		$this->block_loader->register();
	}
}

EverHealth_Blocks::instance();
