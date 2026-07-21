<?php

class Kicksite_Schedule_Api
{
  public function fetch_token() {
    $client_subdomain = get_option( KICKSITE_SUBDOMAIN_OPTION );
    $client_application_id = get_option( KICKSITE_APP_ID_OPTION );
    $secret_key = get_option( KICKSITE_SECRET_KEY_OPTION );
    $get_access_token_url = "https://{$client_subdomain}.kicksite.net/oauth/token";

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
      set_transient('kicksite_oauth_backoff', true, 3600);
      $error_data = [
        'status_code' => 0,
        'error_message' => $response->get_error_message(),
        'site_url' => get_bloginfo( 'url' ),
      ];
      set_transient( 'kicksite_connection_error_message', $error_data, 3600 );
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
      set_transient('kicksite_oauth_backoff', true, 3600);
      return;
    }

    // If there are no errors with the request, delete the error message and backoff transients
    delete_transient( 'kicksite_connection_error_message' );
    delete_transient( 'kicksite_oauth_backoff' );

    // Decode the body of the post response and create a new transient to store the access/bearer token
    $body = json_decode( wp_remote_retrieve_body( $response ), true );
    set_transient( 'kicksite_access_token', $body['access_token'], $body['expires_in'] );
  }

  public function fetch_schedule() {
    $kicksite_access_token = get_transient( 'kicksite_access_token' );
    $client_subdomain = trim( get_option( KICKSITE_SUBDOMAIN_OPTION ) );

    // If the access/bearer token or client subdomain aren't found, return early
    if ( ! $kicksite_access_token || ! $client_subdomain ) return;
    // If the kicksite schedule transient exists already, return early
    if ( false !== get_transient( 'kicksite_schedule_data' ) ) return;

    // Set the start and end dates for the schedule based on the Monday and Sunday dates 
    // from the current week
    $monday = date( 'Y-m-d', strtotime( 'monday this week' ) );
    $sunday = date( 'Y-m-d', strtotime( 'sunday this week' ) );
    // Build the query url using the start and end dates
    $url = add_query_arg(
      [ 'start_date' => $monday, 'end_date' => $sunday ],
      "https://{$client_subdomain}.kicksite.net/v1/recurring_classes"
    );

    // Create the new get request to retrieve the weekly class schedule
    $response = wp_remote_get( $url, [
      'headers' => [
        'Content-Type' => 'application/json',
        'Authorization' => "Bearer {$kicksite_access_token}",
      ],
      'timeout' => 10,
    ] );

    // If there's an error with the request, create a new Sentry error and return
    if ( is_wp_error( $response ) ) {
      if ( class_exists( '\Sentry\SentrySdk' ) ) {
        \Sentry\captureMessage( 'Kicksite schedule fetch failed: ' . $response->get_error_message(), \Sentry\Severity::error() );
      }
      return;
    }

    // Store the schedule data in a transient for one hour
    $data = json_decode( wp_remote_retrieve_body( $response ), true );
    set_transient( 'kicksite_schedule_data', $data, 60 * 60 );
  }
}