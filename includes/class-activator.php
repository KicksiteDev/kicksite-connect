<?php

class Kicksite_Activator
{
  // a static method that receives the instantiated Kicksite Api Client 
  // and assigns it to the variable $api
  public static function activate(Kicksite_Api_Client $api) {
    // Creates a new 64 character hex string
    $secret = bin2hex( random_bytes(32) );

    // Saving the hex string to the wp options table under the key KICKSITE_SECRET_OPTION
    update_option( KICKSITE_SECRET_OPTION, $secret );

    // Send the request to Kicksite with the secret hex key
    $result = $api->register( get_site_url(), $secret );

    // Check if $result returns a WP_Error, if so, store the error message 
    // in the wp options table under the key KICKSITE_ERROR_OPTION
    if ( is_wp_error( $result ) ) {
      update_option( KICKSITE_ERROR_OPTION, $result->get_error_message() );
    } else {
      delete_option( KICKSITE_ERROR_OPTION );
    }
  }
}