<?php

class Kicksite_Admin_Notices
{
  public function display() {
    $error = get_option( KICKSITE_ERROR_OPTION );

    // If there is no error, return early
    if ( !$error ) return;
    
    // If there is an error in the registration, display the error message 
    // in a WordPress admin notice banner
    printf(
      '<div class="notice notice-error"><p>
        <strong>There was an error during registration (error: %s). Please Contact Kicksite Support.
      </p></div>',
      esc_html( $error )
    );
  }
}