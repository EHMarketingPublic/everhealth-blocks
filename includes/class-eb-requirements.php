<?php
/**
 * Checks for the ACF ecosystem plugins this plugin depends on / can use.
 *
 * - ACF (free) or ACF PRO: required. Either satisfies the block/field-group
 *   registration needs; PRO just unlocks Options Pages, Repeater, Flex
 *   Content, etc. if a block's field group happens to use them.
 * - ACF FontAwesome: optional. If active, blocks are free to use the
 *   "font_awesome" field type; if not, we simply skip icon-picker fields
 *   gracefully rather than fatal-erroring.
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

class EB_Requirements {

	/**
	 * True if either ACF or ACF PRO is active.
	 */
	public static function has_acf() {
		return class_exists( 'ACF' ) || function_exists( 'acf' );
	}

	/**
	 * True specifically for ACF PRO (vs. the free version).
	 */
	public static function has_acf_pro() {
		return defined( 'ACF_PRO' ) && ACF_PRO;
	}

	/**
	 * True if the ACF FontAwesome add-on field type is registered/active.
	 */
	public static function has_acf_fontawesome() {
		return class_exists( 'ACF_FontAwesome' ) || function_exists( 'acf_fontawesome' );
	}

	/**
	 * True if the site is running ACF's block.json-based registration
	 * (ACF 6.1+ "third generation" blocks, referred to here as ACF v3).
	 * We gate on the function ACF exposes for registering blocks from
	 * block.json plus a version_compare against ACF's reported version.
	 */
	public static function has_acf_block_json_support() {
		if ( ! function_exists( 'acf_get_setting' ) ) {
			return false;
		}
		$version = defined( 'ACF_VERSION' ) ? ACF_VERSION : '0';
		return version_compare( $version, '6.1', '>=' );
	}

	/**
	 * Overall gate used by the bootstrap class. Only ACF/ACF PRO is a hard
	 * requirement — FontAwesome and block.json support are feature-detected
	 * per block instead of blocking plugin activation entirely.
	 */
	public static function check() {
		return self::has_acf();
	}

	/**
	 * Friendly, non-fatal admin notice instead of a white-screen fatal
	 * when ACF is missing.
	 */
	public static function admin_notice() {
		if ( ! current_user_can( 'activate_plugins' ) ) {
			return;
		}
		?>
		<div class="notice notice-error">
			<p>
				<strong>EverHealth Blocks</strong> requires
				<a href="https://www.advancedcustomfields.com/" target="_blank" rel="noopener noreferrer">Advanced Custom Fields</a>
				(free or PRO) to be installed and active. The plugin's blocks will not
				be registered until ACF is active.
			</p>
		</div>
		<?php
	}
}
