<?php

class Kicksite_Admin
{
  // Creates a new menu page that appears in the left-hand sidebar
  public function add_settings_page() {
    add_menu_page(
      __( 'Kicksite', 'kicksite-connect' ),
      __( 'Kicksite', 'kicksite-connect' ),
      'manage_options',
      'kicksite-connect',
      [ $this, 'render_page' ],
      'dashicons-kicksite',
      17
    );
  }

  // Enquque custom stylesheet
  public function enqueue_styles( $hook ) {
    if ( $hook !== 'toplevel_page_kicksite-connect' ) return;
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
    $app_id = sanitize_text_field( $_POST['kicksite_client_app_id'] ?? '' );
    $secret_key = sanitize_text_field( $_POST['kicksite_secret_key'] ?? '' );

    update_option( KICKSITE_SUBDOMAIN_OPTION, $subdomain );
    update_option( KICKSITE_TOKEN_OPTION, $token );
    update_option( KICKSITE_APP_ID_OPTION, $app_id );
    update_option( KICKSITE_SECRET_KEY_OPTION, $secret_key );

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

    wp_redirect( admin_url( 'admin.php?page=kicksite-connect' ) );
    exit;
  }

  // Renders the settings page showing one of three states:
  // successful registration, an error with the registration process, or not yet connected with instructions to enter settings.
  public function render_page() {
    $registered = (bool) get_option( KICKSITE_SECRET_OPTION )
               && (bool) get_option( KICKSITE_SUBDOMAIN_OPTION )
               && (bool) get_option( KICKSITE_TOKEN_OPTION );
    $error  = get_option( KICKSITE_ERROR_OPTION );
    $slug = KICKSITE_SLUG;
    $safe_url = esc_url( admin_url( 'admin-post.php' ) );
    $safe_subdomain = esc_attr( get_option( KICKSITE_SUBDOMAIN_OPTION, '' ) );
    $safe_bearer_token = esc_attr( get_option( KICKSITE_TOKEN_OPTION, '' ) );
    $safe_app_id = esc_attr( get_option( KICKSITE_APP_ID_OPTION, '' ) );
    $safe_secret_key = esc_attr( get_option( KICKSITE_SECRET_KEY_OPTION, '' ) );

    echo "<div class='{$slug}-title'><h1>Kicksite</h1></div>";

    if ( $error ) {
      $status = "connection-error";
      $status_message = "<p><i class='fa-duotone fa-solid fa-square-x' style='color: red;'></i> Registration failed (error:" . esc_html( $error ) . ").</p>";
    } else if ( $registered ) {
      $status = "connected";
      $status_message = "<p><i class='fa-duotone fa-solid fa-square-check' style='color: green;'></i> Connected.</p>";
    } else {
      $status = "no-connection";
      $status_message = "<p><i class='fa-sharp-duotone fa-light fa-robot' style='color: gray;'></i> No registration found. Enter your subdomain and token below and click Save & Continue.</p>";
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
    echo "<p class='description'>The url for the client's Kicksite account. Enter just the subdomain portion — for https://gymname.kicksite.net, enter gymname</p>";
    echo "</div>";

    // Bearer token field
    echo "<div class='mb-3'>";
    echo "<label for='kicksite_bearer_token' class='form-label'>Kicksite Autologin API Token</label>";
    echo "<input type='text' id='kicksite_bearer_token' name='kicksite_bearer_token' value='{$safe_bearer_token}' class='form-control'>";
    echo "<p class='description'>Token generated from the admin or school website integration page.</p>";
    echo "</div>";

    // Client App ID field
    echo "<div class='mb-3'>";
    echo "<label for='kicksite_client_app_id' class='form-label'>Client Application ID <span class='description'>Optional</span></label>";
    echo "<input type='text' id='kicksite_client_app_id' name='kicksite_client_app_id' value='{$safe_app_id}' class='form-control'>";
    echo "<p class='description'>Found under Client Applications in your Kicksite account. Required for schedule integration.</p>";
    echo "</div>";

    // Client Secret Key field
    echo "<div class='mb-3'>";
    echo "<label for='kicksite_secret_key' class='form-label'>Client Secret Key <span class='description'>Optional</span></label>";
    echo "<input type='text' id='kicksite_secret_key' name='kicksite_secret_key' value='{$safe_secret_key}' class='form-control'>";
    echo "<p class='description'>Found alongside the Client Application ID. Required for schedule integration.</p>";
    echo "</div>";

    submit_button( 'Save & Continue' );

    echo "</form>";

    echo "</div>";

    $this->render_site_marker_card();
  }

  private function render_site_marker_card() {
    $slug = KICKSITE_SLUG;
    $safe_tag = esc_html( Kicksite_Site_Marker::get_marker_tag() );

    echo "<div class='{$slug}-marker-container kicksite-card mt-4'>";
    echo "<h3 class='mb-4'>Site Marker</h3>";

    echo "<div class='{$slug}-status connected'>";
    echo "<p><i class='fa-duotone fa-solid fa-square-check' style='color: green;'></i> Active on every page.</p>";
    echo "</div>";

    echo "<code class='{$slug}-marker-tag'>{$safe_tag}</code>";

    echo "<p class='description'>This tag is added to every page so Kicksite can confirm your site is still connected to our platform. It is not visible to visitors and contains no private information.</p>";

    echo "</div>";
  }
}
