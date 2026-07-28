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

    // Store the user ID in a short-lived transient keyed by a random token.
    // Then respond with a self-submitting POST form to the homepage instead of a redirect.
    // Flywheel's Fastly CDN strips Set-Cookie headers from GET responses, but allows them
    // on POST responses — the POST to handle_post_login() is where the auth cookie actually lands.
    $login_token = bin2hex( random_bytes(16) );
    set_transient( 'kicksite_login_' . $login_token, $user->ID, 30 );
    $post_url = esc_url( home_url() );
    $safe_token = esc_attr( $login_token );
    nocache_headers();
    echo '<form id="ksf" method="post" action="' . $post_url . '">';
    echo '<input type="hidden" name="kicksite_login" value="' . $safe_token . '">';
    echo '</form><script>document.getElementById("ksf").submit();</script>';
    exit;
  }

  // Receives the POST form submission from handle(), verifies the one-time transient token,
  // sets the WordPress auth cookie, then navigates to the homepage via JS.
  // Using window.location.replace keeps the login flow out of the browser's back-button history.
  public function handle_post_login() {
    if ( $_SERVER['REQUEST_METHOD'] !== 'POST' || empty( $_POST['kicksite_login'] ) ) return;
    $token = sanitize_text_field( $_POST['kicksite_login'] );
    $user_id = get_transient( 'kicksite_login_' . $token );
    if ( ! $user_id ) return;
    delete_transient( 'kicksite_login_' . $token );
    wp_set_auth_cookie( (int) $user_id );
    nocache_headers();
    echo '<script>window.location.replace(' . wp_json_encode( home_url() ) . ');</script>';
    exit;
  }

  private function redirect_cleanup() {
    wp_safe_redirect( remove_query_arg( KICKSITE_TOKEN_PARAM ) );
    exit;
  }
}