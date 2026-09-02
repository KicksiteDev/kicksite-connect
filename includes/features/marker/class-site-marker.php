<?php

class Kicksite_Site_Marker
{
  // Builds the hidden site identity marker tag.
  public static function get_marker_tag() {
    return sprintf(
      '<meta name="%s" content="%s">',
      esc_attr( KICKSITE_MARKER_NAME ),
      esc_attr( KICKSITE_MARKER_VALUE )
    );
  }

  // Prints the marker into the document head on every front-end page.
  public function render_marker() {
    echo self::get_marker_tag() . "\n";
  }
}
