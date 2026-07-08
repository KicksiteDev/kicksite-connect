<?php

class Kicksite_Admin
{
  // Creates a new options page that appears under the "Settings" section of the left-hand sidebar
  public function add_settings_page() {
    add_options_page(
      __( 'Kicksite', 'kicksite-connect' ),
      __( 'Kicksite', 'kicksite-connect' ),
      'manage_options',
      'kicksite-connect',
      [ $this, 'render_page' ]
    );
  }

  // Renders the settings page showing one of three states:
  // successful registration, an error with the registration process, or no key yet with reactivation instructions.
  public function render_page() {
    $registered = (bool) get_option( KICKSITE_SECRET_OPTION );
    $error  = get_option( KICKSITE_ERROR_OPTION );

    echo "<div class='wrap'><h1>Kicksite</h1>";

    if ( $error ) {
      echo "<p>Registration failed (error:" . esc_html( $error ) . ").</p>";
    } else if ( $registered ) {
      echo "<p>Secret key registered with Kicksite.</p>";
    } else {
      echo "No key registered yet. Deactivate and reactivate the plugin to retry.";
    }
    
    echo "</div>";
  }
}