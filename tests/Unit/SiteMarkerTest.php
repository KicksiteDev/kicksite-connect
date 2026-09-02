<?php

use PHPUnit\Framework\TestCase;

/**
 * Tests for includes/features/marker/class-site-marker.php
 *
 * The site marker is a contract with a separate repository: Kicksite's
 * website monitoring tool reads this tag to decide whether a customer domain
 * is still running the Kicksite plugin.
 *
 * Because the consumer lives elsewhere, these tests deliberately assert
 * hard-coded literals rather than referencing the constants. Comparing a
 * constant to itself would pass no matter what anyone changed it to — the
 * whole point is that changing it must fail loudly here.
 */
class SiteMarkerTest extends TestCase
{
  /** The exact string the monitoring tool searches for. */
  private const EXPECTED_TAG = '<meta name="kicksite-site-marker" content="kicksite">';

  public function test_marker_tag_matches_the_monitoring_contract(): void
  {
    $this->assertSame(
      self::EXPECTED_TAG,
      Kicksite_Site_Marker::get_marker_tag(),
      'The site marker tag is a contract with the website monitoring repo. '
      . 'If this assertion fails, monitoring will stop recognising our sites.'
    );
  }

  public function test_marker_name_constant_is_unchanged(): void
  {
    $this->assertSame( 'kicksite-site-marker', KICKSITE_MARKER_NAME );
  }

  public function test_marker_value_constant_is_unchanged(): void
  {
    $this->assertSame( 'kicksite', KICKSITE_MARKER_VALUE );
  }

  public function test_render_marker_prints_the_tag(): void
  {
    ob_start();
    ( new Kicksite_Site_Marker() )->render_marker();
    $output = ob_get_clean();

    $this->assertSame( self::EXPECTED_TAG . "\n", $output );
  }

  public function test_marker_is_registered_on_wp_head(): void
  {
    $wp_head_callbacks = array_filter(
      $GLOBALS['kicksite_test_hooks'],
      fn( $registered ) => $registered['hook'] === 'wp_head'
    );

    $marker_hooks = array_filter(
      $wp_head_callbacks,
      fn( $registered ) => is_array( $registered['callback'] )
        && $registered['callback'][0] instanceof Kicksite_Site_Marker
        && $registered['callback'][1] === 'render_marker'
    );

    $this->assertCount(
      1,
      $marker_hooks,
      'The marker must be printed into the document head on every front-end page.'
    );
  }

  /**
   * The marker is public, on every page, to anyone. Nothing derived from the
   * site's Kicksite credentials may ever appear in it.
   */
  public function test_marker_leaks_no_configuration_values(): void
  {
    $tag = Kicksite_Site_Marker::get_marker_tag();

    $option_keys = [
      KICKSITE_SECRET_OPTION,
      KICKSITE_SUBDOMAIN_OPTION,
      KICKSITE_TOKEN_OPTION,
      KICKSITE_APP_ID_OPTION,
      KICKSITE_SECRET_KEY_OPTION,
    ];

    foreach ( $option_keys as $option_key ) {
      $this->assertStringNotContainsString( $option_key, $tag );
    }
  }

  /**
   * The marker must not depend on the Kicksite Integration Settings being
   * filled in. A site part-way through onboarding is still a Kicksite site,
   * and gating the marker on configuration would report every new build we
   * are working on as a customer who left.
   */
  public function test_marker_does_not_read_any_options(): void
  {
    $source = file_get_contents(
      __DIR__ . '/../../includes/features/marker/class-site-marker.php'
    );

    $this->assertStringNotContainsString( 'get_option', $source );
  }
}
