<?php

class Kicksite_Activator
{
  public static function activate() {
    // Creates a new 64 character hex string
    $secret = bin2hex( random_bytes(32) );

    // Saving the hex string to the wp options table under the key KICKSITE_SECRET_OPTION
    if ( !get_option( KICKSITE_SECRET_OPTION ) ) {
      update_option( KICKSITE_SECRET_OPTION, $secret );
    }
  }
}