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

  // Enquque custom stylesheet
  public function enqueue_styles( $hook ) {
    if ( $hook !== 'settings_page_kicksite-connect' ) return;
    wp_enqueue_style(
      'kicksite-admin',
      KICKSITE_URL . 'assets/style.css'
    );
  }

  // Save input settings and attempt registration with Kicksite
  public function save_settings() {
    check_admin_referer( 'kicksite_save_settings' );

    $subdomain = sanitize_text_field( $_POST['kicksite_subdomain'] ?? '' );
    $token = sanitize_text_field( $_POST['kicksite_bearer_token'] ?? '' );

    update_option( KICKSITE_SUBDOMAIN_OPTION, $subdomain );
    update_option( KICKSITE_TOKEN_OPTION, $token );

    $secret = get_option( KICKSITE_SECRET_OPTION );

    if ( !$secret ) {
      // Creates a new 64 character hex string if one doesn't exist yet and update option value
      $secret = bin2hex( random_bytes(32) );
      update_option( KICKSITE_SECRET_OPTION, $secret );
    }

    $api = new Kicksite_Api_Client();
    // Send the request to Kicksite with the secret hex key
    $result = $api->register( get_site_url(), $secret );

    // Check if $result returns a WP_Error, if so, store the error message 
    // in the wp options table under the key KICKSITE_ERROR_OPTION
    if ( is_wp_error( $result ) ) {
      update_option( KICKSITE_ERROR_OPTION, $result->get_error_message() );
    } else {
      delete_option( KICKSITE_ERROR_OPTION );
    }

    wp_redirect( admin_url( 'options-general.php?page=kicksite-connect' ) );
    exit;
  }

  // Renders the settings page showing one of three states:
  // successful registration, an error with the registration process, or not yet connected with instructions to enter settings.
  public function render_page() {
    $registered = (bool) get_option( KICKSITE_SECRET_OPTION );
    $error  = get_option( KICKSITE_ERROR_OPTION );
    $slug = KICKSITE_SLUG;
    $safe_url = esc_url( admin_url( 'admin-post.php' ) );
    $safe_subdomain = esc_attr( get_option( KICKSITE_SUBDOMAIN_OPTION, '' ) );
    $safe_bearer_token = esc_attr( get_option( KICKSITE_TOKEN_OPTION, '' ) );

    echo "<div class='{$slug}-title'><h1>Kicksite</h1></div>";

    if ( $error ) {
      $status = "connection-error";
      $status_message = "<p><i class='fa-duotone fa-solid fa-square-x' style='color: red;'></i> Registration failed (error:" . esc_html( $error ) . ").</p>";
    } else if ( $registered ) {
      $status = "connected";
      $status_message = "<p><i class='fa-duotone fa-solid fa-square-check' style='color: green;'></i> Connected.</p>";
    } else {
      $status = "no-connection";
      $status_message = "<p><i class='fa-sharp-duotone fa-light fa-robot' style='color: gray;'></i> No key registered yet. Enter your subdomain and token below and click Save & Continue.</p>";
    }

    // Form
    echo "<div class='{$slug}-form-container kicksite-card'>";
    echo "<h3 class='mb-4'>Kicksite Website Integration Settings</h3>";
    echo "<div class='{$slug}-status {$status}'>{$status_message}</div>";

    echo "<form method='post' action='{$safe_url}'>";

    // CSRF protection
    wp_nonce_field( 'kicksite_save_settings' );

    // Tells WordPress which admin_post_ hook to fire
    echo "<input type='hidden' name='action' value='kicksite_save_settings'>";

    // Subdomain field
    echo "<div class='mb-3'>";
    echo "<label for='kicksite_subdomain' class='form-label'>Kicksite Subdomain</label>";
    echo "<input type='text' id='kicksite_subdomain' name='kicksite_subdomain' value='{$safe_subdomain}' class='form-control'>";
    echo "</div>";

    // Bearer token field
    echo "<div class='mb-3'>";
    echo "<label for='kicksite_bearer_token' class='form-label'>Kicksite Bearer Token</label>";
    echo "<input type='text' id='kicksite_bearer_token' name='kicksite_bearer_token' value='{$safe_bearer_token}' class='form-control'>";
    echo "</div>";

    submit_button( 'Save & Continue' );

    echo "</form>";

    echo "</div>";
  }
}