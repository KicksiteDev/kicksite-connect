<?php

class Kicksite_Api_Client
{
  public function register(string $url, string $secret, string $subdomain, string $token) {
    // $endpoint_url = "https://{$subdomain}.kicksite.net/v1/wordpress/registrations";
    $endpoint_url = "https://{$subdomain}.kicksite-staging.net/v1/wordpress/registrations"; // kicksite-staging testing
    // $endpoint_url = "http://{$subdomain}.kicksite.test:3000/v1/wordpress/registrations"; // local wp environment testing

    // Post payload body containing the site's url and secret hex key
    $body = [
      'url' => $url,
      'secret' => $secret,
    ];

    // Turn the body into JSON
    $body = wp_json_encode( $body );

    // Assemble the arguments for the POST request with headers and a timeout of 10 seconds
    $args = [
      'body' => $body,
      'headers' => [
        'Content-Type' => 'application/json',
        'Authorization' => "Bearer {$token}",
      ],
      'sslverify' => false, // used to allow requests from http to https on local environments
      'timeout' => 10,
    ];

    // Perform the post request
    $response = wp_remote_post( $endpoint_url, $args );

    // If there is an error/failure during the HTTP request,
    // bubble up the WP_Error
    if ( is_wp_error( $response ) ) return $response;

    // Get the response code for the post request response
    $code = wp_remote_retrieve_response_code( $response );

    // If the code isn't a successful 200, then create a WP_Error with the
    // code and message so save_settings can store it.
    if ( $code !== 200 ) {
      $body = wp_remote_retrieve_body( $response );
      return new WP_Error( $code, "Code: {$code} — {$body}" );
    }

    return true;
  }

  /**
   * @return string|false
   */
  public function get_access_token() {
    $kicksite_access_token = get_transient( 'kicksite_access_token' );
    if ( !$kicksite_access_token ) {
      $this->fetch_token();
      $kicksite_access_token = get_transient( 'kicksite_access_token' );
    }
    return $kicksite_access_token ?: false;
  }

  private function fetch_token() {
    $client_subdomain = get_option( KICKSITE_SUBDOMAIN_OPTION );
    $client_application_id = get_option( KICKSITE_APP_ID_OPTION );
    $secret_key = get_option( KICKSITE_SECRET_KEY_OPTION );
    // $get_access_token_url = "https://{$client_subdomain}.kicksite.net/oauth/token"; // production endpoint
    $get_access_token_url = "https://{$client_subdomain}.kicksite-staging.net/oauth/token"; // staging endpoint for local development and testing

    // If any of the options don't exist, return early
    if ( ! $client_subdomain || ! $client_application_id || ! $secret_key ) return;
    // If the access token or backoff transient already exist, return early
    if ( get_transient( 'kicksite_access_token' ) ) return;
    if ( get_transient( 'kicksite_oauth_backoff' ) ) return;

    // Request an Oauth access token from Kicksite using the client's application credentials
    $response = wp_remote_post( $get_access_token_url, [
      'body' => wp_json_encode( [
          "grant_type" => "client_credentials",
          "client_id" => $client_application_id,
          "client_secret" => $secret_key
        ] ),
      'headers' => [ 'Content-Type' => 'application/json', ],
      'timeout' => 10,
    ] );

    // If there is any error with the request, create a one-hour long back off before trying
    // the request again.
    // Construct the new error message and assign it to a transient.
    // Create a new Sentry error
    if ( is_wp_error( $response ) ) {
      set_transient('kicksite_oauth_backoff', true, HOUR_IN_SECONDS);
      $error_data = [
        'status_code' => 0,
        'error_message' => $response->get_error_message(),
        'site_url' => get_bloginfo( 'url' ),
      ];
      set_transient( 'kicksite_connection_error_message', $error_data, HOUR_IN_SECONDS );
      if ( class_exists( '\Sentry\SentrySdk' ) ) {
        \Sentry\captureMessage( 'Kicksite token fetch failed: ' . $response->get_error_message(), \Sentry\Severity::error() );
      }
      return;
    }

    // Get the HTTP response code
    $code = wp_remote_retrieve_response_code( $response );

    // If the status code returns anything except 200, create a new one-hour backoff
    // before trying the request again.
    if ( $code !== 200 ) {
      set_transient( 'kicksite_oauth_backoff', true, HOUR_IN_SECONDS );
      return;
    }

    // If there are no errors with the request, delete the error message and backoff transients
    delete_transient( 'kicksite_connection_error_message' );
    delete_transient( 'kicksite_oauth_backoff' );

    // Decode the body of the post response and create a new transient to store the access/bearer token
    $body = json_decode( wp_remote_retrieve_body( $response ), true );
    set_transient( 'kicksite_access_token', $body['access_token'], $body['expires_in'] );
  }
}