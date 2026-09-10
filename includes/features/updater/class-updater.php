<?php

/**
 * Lets an installed copy of this plugin update itself from GitHub Releases.
 *
 * Nothing pushes updates to customer sites. Twice a day WordPress runs its own
 * update check, and because the plugin header carries an Update URI on
 * github.com, WordPress asks THIS class instead of asking wordpress.org. The
 * class fetches the latest release, compares its version to the one in the
 * plugin header, and hands back a download URL when the release is newer.
 *
 * Two consequences worth knowing before changing anything here:
 *
 *   - The Update URI header is also what stops wordpress.org being consulted
 *     about the "kicksite-connect" slug. Remove it and any wordpress.org plugin
 *     claiming that slug could be installed over this one on every customer site.
 *   - WordPress only ever offers a HIGHER version, so a bad release is rolled
 *     back by publishing the old code under a new, higher version number.
 */
class Kicksite_Updater
{
  // How long a successful lookup is reused. WordPress checks twice a day, so
  // this is roughly "ask GitHub once per day per site".
  const CACHE_TTL = 43200; // 12 hours

  // How long to wait after a failed lookup before trying again. Unauthenticated
  // GitHub API calls are rate limited per IP, and sites on shared hosting share
  // an outbound IP, so failures must not turn into a retry loop.
  const BACKOFF_TTL = 3600; // 1 hour

  const CACHE_KEY = 'kicksite_latest_release';
  const BACKOFF_KEY = 'kicksite_update_backoff';

  /**
   * Answers WordPress's "is there a newer version of this plugin?" question.
   *
   * The hook this is attached to is named after the Update URI host, so it is
   * shared with every other plugin on the site that also updates from
   * github.com. The first thing to do is confirm WordPress is asking about us.
   */
  public function check( $update, $plugin_data, $plugin_file ) {
    if ( $plugin_file !== KICKSITE_PLUGIN_BASENAME ) return $update;

    $release = $this->get_latest_release();
    if ( ! $release ) return $update;

    // Compare against the header of the copy actually running on this site,
    // not KICKSITE_VERSION, so a site somehow running an older file still
    // gets offered the update.
    $installed = $plugin_data['Version'] ?? KICKSITE_VERSION;
    if ( ! version_compare( $release['version'], $installed, '>' ) ) return $update;

    return [
      'id' => KICKSITE_UPDATE_REPO,
      'slug' => KICKSITE_SLUG,
      'plugin' => KICKSITE_PLUGIN_BASENAME,
      'version' => $release['version'],
      'url' => $release['url'],
      'package' => $release['package'],
      'requires_php' => '8.1',
    ];
  }

  /**
   * Turns automatic updating on for this plugin and takes the choice away from
   * the site.
   *
   * Customers are not expected to manage this plugin, and a customer site left
   * on an old version is one we cannot fix remotely when something breaks.
   */
  public function force_auto_update( $update, $item ) {
    if ( isset( $item->plugin ) && $item->plugin === KICKSITE_PLUGIN_BASENAME ) return true;

    return $update;
  }

  /**
   * Runs anything that has to happen once after the plugin is updated.
   *
   * register_activation_hook() does NOT fire on update, only on activation, so
   * Kicksite_Activator never runs for a site that upgrades. Per-version work
   * belongs here, branching on the version recorded in KICKSITE_VERSION_OPTION.
   */
  public function maybe_run_migrations() {
    $installed = get_option( KICKSITE_VERSION_OPTION );
    if ( $installed === KICKSITE_VERSION ) return;

    // No migrations yet. When one is needed, branch on $installed here — it is
    // false on a site that has never recorded a version, and the previous
    // version string on a site that has.

    update_option( KICKSITE_VERSION_OPTION, KICKSITE_VERSION );
  }

  // The cached lookup describes a release that may have just been installed, so
  // it is dropped after any upgrade rather than left to expire.
  public function clear_cache() {
    delete_transient( self::CACHE_KEY );
    delete_transient( self::BACKOFF_KEY );
  }

  private function get_latest_release() {
    $cached = get_transient( self::CACHE_KEY );
    if ( is_array( $cached ) ) return $cached;

    // A recent lookup failed and is still cooling off. Skip the request rather
    // than making every admin page load wait on an endpoint we know is down.
    if ( get_transient( self::BACKOFF_KEY ) ) return null;

    $response = wp_remote_get(
      'https://api.github.com/repos/' . KICKSITE_UPDATE_REPO . '/releases/latest',
      [
        'timeout' => 10,
        'headers' => [ 'Accept' => 'application/vnd.github+json' ],
      ]
    );

    if ( is_wp_error( $response ) || wp_remote_retrieve_response_code( $response ) !== 200 ) {
      set_transient( self::BACKOFF_KEY, true, self::BACKOFF_TTL );
      return null;
    }

    $release = self::parse_release( wp_remote_retrieve_body( $response ) );
    if ( ! $release ) {
      set_transient( self::BACKOFF_KEY, true, self::BACKOFF_TTL );
      return null;
    }

    set_transient( self::CACHE_KEY, $release, self::CACHE_TTL );
    return $release;
  }

  /**
   * Pulls the version and download URL out of a GitHub release payload.
   *
   * The download must come from a .zip asset attached to the release, never
   * from GitHub's auto-generated source archive. That archive unpacks to a
   * directory named after the tag rather than the plugin slug, which would
   * install the plugin a second time alongside itself, and it carries the
   * tests and composer files that bin/build-zip.sh deliberately strips out.
   *
   * Public so it can be tested directly against real GitHub payloads.
   */
  public static function parse_release( $body ) {
    $data = json_decode( $body, true );
    if ( ! is_array( $data ) || empty( $data['tag_name'] ) ) return null;

    // Releases are tagged v1.2.3; plugin headers carry 1.2.3.
    $version = ltrim( $data['tag_name'], 'v' );
    if ( $version === '' ) return null;

    $assets = isset( $data['assets'] ) && is_array( $data['assets'] ) ? $data['assets'] : [];
    foreach ( $assets as $asset ) {
      $name = $asset['name'] ?? '';
      $url = $asset['browser_download_url'] ?? '';
      if ( $url === '' || ! str_ends_with( $name, '.zip' ) ) continue;

      return [
        'version' => $version,
        'package' => $url,
        'url' => $data['html_url'] ?? '',
      ];
    }

    // A release with no attached zip is a release nobody can install.
    return null;
  }
}
