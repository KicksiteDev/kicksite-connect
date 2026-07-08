<?php

class Kicksite_Auth_Handler
{
  public function handle() {
    // Check for an incoming Kicksite token in the URL query string
    if ( empty( $_GET[KICKSITE_TOKEN_PARAM] ) ) return;
    $token = ( sanitize_text_field( $_GET[KICKSITE_TOKEN_PARAM] ) );

    // Retrieve the shared secret from the database — if missing, the plugin isn't registered so bail
    $secret = get_option( KICKSITE_SECRET_OPTION );
    if ( empty( $secret ) ) {
      $this->redirect_cleanup();
      return;
    } 

    // If validation fails the token is forged or expired, so bail
    $payload = (new Kicksite_Token_Validator())->validate( $token, $secret );
    if ( is_null( $payload ) ) {
      $this->redirect_cleanup();
      return;
    }

    // Check for the user by email or create a new user if it doesn't exist yet. 
    // If there is an error, remove the token param
    $email = $payload['email'];
    $user = (new Kicksite_User_Provisioner())->find_or_create( $email );
    if ( is_wp_error( $user ) ) {
      $this->redirect_cleanup();
      return;
    }

    // Log the user in by setting auth cookies, then redirect to strip the token from the URL
    wp_set_auth_cookies( $user->ID );
    $this->redirect_cleanup();
  }

  private function redirect_cleanup() {
    wp_safe_redirect( remove_query_arg( KICKSITE_TOKEN_PARAM ) );
    exit;
  }
}