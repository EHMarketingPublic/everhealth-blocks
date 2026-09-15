<?php
/**
 * Self-contained GitHub Releases update checker.
 *
 * No external library dependency (no plugin-update-checker vendor folder
 * to keep in sync) — this hooks the same core WP transient filters that
 * WordPress.org-hosted plugins use, just backed by the GitHub Releases
 * API instead of the plugins API.
 *
 * Expects each GitHub Release to have a version-numbered tag (e.g. "1.2.0"
 * or "v1.2.0") and a downloadable .zip asset attached to the release
 * (either a uploaded release asset, or it will fall back to GitHub's
 * auto-generated "Source code (zip)" archive).
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

class EB_GitHub_Updater {

	/** @var string Absolute path to the main plugin file. */
	private $plugin_file;

	/** @var string "owner/repo" */
	private $repo;

	/** @var string Currently installed version. */
	private $current_version;

	/** @var string Optional GitHub personal access token (private repos). */
	private $access_token;

	/** @var string plugin_basename(), e.g. everhealth-blocks/everhealth-blocks.php */
	private $basename;

	/** @var string Cache key for the GitHub API response. */
	private $cache_key;

	/** @var int How long to cache the GitHub API response, in seconds. */
	private $cache_hours = 6;

	public function __construct( $plugin_file, $repo, $current_version, $access_token = '' ) {
		$this->plugin_file     = $plugin_file;
		$this->repo            = trim( $repo, '/' );
		$this->current_version = $current_version;
		$this->access_token    = $access_token;
		$this->basename        = plugin_basename( $plugin_file );
		$this->cache_key       = 'eb_gh_updater_' . md5( $this->repo );

		add_filter( 'pre_set_site_transient_update_plugins', array( $this, 'check_for_update' ) );
		add_filter( 'plugins_api', array( $this, 'plugin_info_popup' ), 20, 3 );
		add_action( 'upgrader_process_complete', array( $this, 'clear_cache' ), 10, 2 );
		add_filter( 'upgrader_source_selection', array( $this, 'fix_source_dir' ), 10, 4 );
		add_filter( 'plugin_row_meta', array( $this, 'plugin_row_meta' ), 10, 2 );
	}

	/**
	 * Fetches the latest release from the GitHub API, cached in a
	 * transient so we're not hitting GitHub's API on every admin page
	 * load (and tripping their unauthenticated rate limit).
	 */
	private function get_latest_release() {
		$cached = get_transient( $this->cache_key );
		if ( false !== $cached ) {
			return $cached;
		}

		$url = sprintf( 'https://api.github.com/repos/%s/releases/latest', $this->repo );

		$args = array(
			'headers' => array(
				'Accept'     => 'application/vnd.github+json',
				'User-Agent' => 'EverHealth-Blocks-Updater',
			),
			'timeout' => 10,
		);

		if ( ! empty( $this->access_token ) ) {
			$args['headers']['Authorization'] = 'Bearer ' . $this->access_token;
		}

		$response = wp_remote_get( $url, $args );

		if ( is_wp_error( $response ) || 200 !== wp_remote_retrieve_response_code( $response ) ) {
			// Cache the miss briefly so a bad network/API state doesn't
			// hammer GitHub on every page load either.
			set_transient( $this->cache_key, array(), HOUR_IN_SECONDS );
			return array();
		}

		$body    = wp_remote_retrieve_body( $response );
		$release = json_decode( $body, true );

		if ( empty( $release ) || ! is_array( $release ) ) {
			set_transient( $this->cache_key, array(), HOUR_IN_SECONDS );
			return array();
		}

		set_transient( $this->cache_key, $release, $this->cache_hours * HOUR_IN_SECONDS );

		return $release;
	}

	/**
	 * Picks the best download URL for a release: a real uploaded .zip
	 * asset if one exists, otherwise GitHub's auto-generated source zip.
	 */
	private function get_download_url( $release ) {
		if ( ! empty( $release['assets'] ) && is_array( $release['assets'] ) ) {
			foreach ( $release['assets'] as $asset ) {
				if ( isset( $asset['browser_download_url'] ) && str_ends_with( $asset['browser_download_url'], '.zip' ) ) {
					return $asset['browser_download_url'];
				}
			}
		}

		return isset( $release['zipball_url'] ) ? $release['zipball_url'] : '';
	}

	/**
	 * Normalizes a GitHub tag ("v1.2.0" or "1.2.0") to a bare version
	 * string WordPress' version_compare() can work with.
	 */
	private function normalize_version( $tag ) {
		return ltrim( (string) $tag, 'vV' );
	}

	/**
	 * Core hook: adds our plugin to the update transient WordPress uses
	 * to decide whether to show an "update available" row.
	 */
	public function check_for_update( $transient ) {
		if ( empty( $transient->checked ) ) {
			return $transient;
		}

		$release = $this->get_latest_release();

		if ( empty( $release['tag_name'] ) ) {
			return $transient;
		}

		$latest_version = $this->normalize_version( $release['tag_name'] );

		if ( version_compare( $latest_version, $this->current_version, '>' ) ) {
			$item = new stdClass();

			$item->slug        = dirname( $this->basename );
			$item->plugin      = $this->basename;
			$item->new_version = $latest_version;
			$item->url         = sprintf( 'https://github.com/%s', $this->repo );
			$item->package      = $this->get_download_url( $release );
			$item->tested       = get_bloginfo( 'version' );

			$transient->response[ $this->basename ] = $item;
		}

		return $transient;
	}

	/**
	 * Powers the "View version x.x details" popup on the plugins page.
	 */
	public function plugin_info_popup( $result, $action, $args ) {
		if ( 'plugin_information' !== $action ) {
			return $result;
		}

		if ( empty( $args->slug ) || $args->slug !== dirname( $this->basename ) ) {
			return $result;
		}

		$release = $this->get_latest_release();

		if ( empty( $release ) ) {
			return $result;
		}

		$info                = new stdClass();
		$info->name           = 'EverHealth Blocks';
		$info->slug           = dirname( $this->basename );
		$info->version        = $this->normalize_version( $release['tag_name'] ?? $this->current_version );
		$info->author         = 'EverHealth';
		$info->homepage       = sprintf( 'https://github.com/%s', $this->repo );
		$info->download_link  = $this->get_download_url( $release );
		$info->sections       = array(
			'description' => 'Shared ACF Blocks plugin for EverHealth WordPress sites.',
			'changelog'   => isset( $release['body'] ) ? wpautop( wp_kses_post( $release['body'] ) ) : 'See GitHub releases for details.',
		);

		return $info;
	}

	/**
	 * GitHub zip downloads (especially the auto-generated source archive)
	 * extract into a folder named like "owner-repo-<hash>" rather than
	 * "everhealth-blocks". This renames the extracted folder so the
	 * plugin's directory slug stays stable across upgrades.
	 */
	public function fix_source_dir( $source, $remote_source, $upgrader, $hook_extra = array() ) {
		global $wp_filesystem;

		if ( empty( $hook_extra['plugin'] ) || $hook_extra['plugin'] !== $this->basename ) {
			return $source;
		}

		$desired_slug = dirname( $this->basename ); // "everhealth-blocks"
		$corrected    = trailingslashit( $remote_source ) . $desired_slug . '/';

		if ( trailingslashit( $source ) === $corrected ) {
			return $source;
		}

		if ( $wp_filesystem->move( $source, $corrected, true ) ) {
			return $corrected;
		}

		return $source;
	}

	/**
	 * Clears the cached release data right after WP finishes updating
	 * this plugin, so the "update available" nag doesn't linger.
	 */
	public function clear_cache( $upgrader, $hook_extra ) {
		if ( isset( $hook_extra['action'], $hook_extra['type'] ) && 'update' === $hook_extra['action'] && 'plugin' === $hook_extra['type'] ) {
			delete_transient( $this->cache_key );
		}
	}

	/**
	 * Adds a "View details" / GitHub link in the plugin row on the
	 * Plugins admin page.
	 */
	public function plugin_row_meta( $links, $file ) {
		if ( $file === $this->basename ) {
			$links[] = sprintf(
				'<a href="https://github.com/%s/releases" target="_blank" rel="noopener noreferrer">%s</a>',
				esc_attr( $this->repo ),
				esc_html__( 'View releases on GitHub', 'everhealth-blocks' )
			);
		}
		return $links;
	}
}
