<?php

use PHPUnit\Framework\TestCase;

/**
 * Tests for includes/features/updater/class-updater.php
 *
 * These guard the parts of self-updating that fail SILENTLY. A broken updater
 * does not throw anything — sites simply stop hearing about new versions, and
 * nobody finds out until a release that mattered never arrived.
 */
class UpdaterTest extends TestCase
{
  private static function plugin_file_source(): string
  {
    return file_get_contents( __DIR__ . '/../../kicksite-connect.php' );
  }

  private static function header_value( string $field ): string
  {
    preg_match( '/^ \* ' . preg_quote( $field, '/' ) . ':\s*(.+)$/m', self::plugin_file_source(), $matches );

    return isset( $matches[1] ) ? trim( $matches[1] ) : '';
  }

  private static function registered_hooks(): array
  {
    return array_column( $GLOBALS['kicksite_test_hooks'], 'hook' );
  }

  /**
   * Every site compares the release version against the Version HEADER, while
   * migrations compare against the KICKSITE_VERSION constant. If the two drift
   * apart, one of those two comparisons is wrong on every site.
   */
  public function test_version_constant_matches_the_plugin_header(): void
  {
    $this->assertSame(
      self::header_value( 'Version' ),
      KICKSITE_VERSION,
      'The Version header and KICKSITE_VERSION must be bumped together.'
    );
  }

  /**
   * Without this header WordPress asks wordpress.org about the slug
   * "kicksite-connect", and anyone who publishes a plugin under that slug there
   * could have it installed over this one on every customer site.
   */
  public function test_plugin_declares_an_update_uri(): void
  {
    $this->assertNotSame( '', self::header_value( 'Update URI' ) );
  }

  /**
   * WordPress derives the filter name from the HOST of the Update URI. The
   * header lives in kicksite-connect.php and the filter name is hard-coded in
   * class-kicksite.php, so nothing but this test keeps the two in agreement.
   */
  public function test_update_check_is_registered_on_the_host_from_the_update_uri(): void
  {
    $host = parse_url( self::header_value( 'Update URI' ), PHP_URL_HOST );

    $this->assertContains(
      'update_plugins_' . $host,
      self::registered_hooks(),
      'The Update URI host and the update_plugins_* filter name have drifted apart. '
      . 'WordPress would call a filter nothing is listening on and updates would stop.'
    );
  }

  public function test_auto_updates_are_forced_on(): void
  {
    $this->assertContains( 'auto_update_plugin', self::registered_hooks() );
  }

  /**
   * Without this, "check for updates" in WP-CLI or ManageWP refreshes
   * WordPress's list but is still answered from our own cache, which can be
   * twelve hours old. Forcing a check would appear to work and change nothing.
   */
  public function test_forcing_an_update_check_clears_the_cached_lookup(): void
  {
    $this->assertContains( 'delete_site_transient_update_plugins', self::registered_hooks() );
  }

  public function test_force_auto_update_only_claims_this_plugin(): void
  {
    $updater = new Kicksite_Updater();

    $this->assertTrue(
      $updater->force_auto_update( false, (object) [ 'plugin' => KICKSITE_PLUGIN_BASENAME ] )
    );

    $this->assertFalse(
      $updater->force_auto_update( false, (object) [ 'plugin' => 'some-other-plugin/some-other-plugin.php' ] ),
      'The filter runs for every plugin on the site. It must not answer for anyone else.'
    );
  }

  /**
   * The zip attached to the release is the one bin/build-zip.sh produced, with
   * the correct folder name and no dev files. GitHub also exposes an
   * auto-generated source archive on every release; installing that one would
   * unpack to a directory named after the tag, leaving a second broken copy of
   * the plugin alongside the real one.
   */
  public function test_parse_release_prefers_the_attached_zip_over_the_source_archive(): void
  {
    $release = Kicksite_Updater::parse_release( json_encode( [
      'tag_name' => 'v1.2.0',
      'html_url' => 'https://github.com/KicksiteDev/kicksite-connect/releases/tag/v1.2.0',
      'tarball_url' => 'https://api.github.com/repos/KicksiteDev/kicksite-connect/tarball/v1.2.0',
      'zipball_url' => 'https://api.github.com/repos/KicksiteDev/kicksite-connect/zipball/v1.2.0',
      'assets' => [
        [
          'name' => 'kicksite-connect-1.2.0.zip',
          'browser_download_url' => 'https://github.com/KicksiteDev/kicksite-connect/releases/download/v1.2.0/kicksite-connect-1.2.0.zip',
        ],
      ],
    ] ) );

    $this->assertSame( '1.2.0', $release['version'], 'The leading v on the tag is not part of the version.' );
    $this->assertSame(
      'https://github.com/KicksiteDev/kicksite-connect/releases/download/v1.2.0/kicksite-connect-1.2.0.zip',
      $release['package']
    );
  }

  /**
   * A release published without the build attached cannot be installed. Better
   * to report no update at all than to hand WordPress a download it will fail
   * on, or worse, GitHub's source archive.
   */
  public function test_parse_release_reports_nothing_when_no_zip_is_attached(): void
  {
    $release = Kicksite_Updater::parse_release( json_encode( [
      'tag_name' => 'v1.2.0',
      'assets' => [],
    ] ) );

    $this->assertNull( $release );
  }

  public function test_parse_release_survives_an_unexpected_response(): void
  {
    $this->assertNull( Kicksite_Updater::parse_release( '' ) );
    $this->assertNull( Kicksite_Updater::parse_release( 'not json' ) );
    $this->assertNull( Kicksite_Updater::parse_release( json_encode( [ 'message' => 'Not Found' ] ) ) );
  }
}
