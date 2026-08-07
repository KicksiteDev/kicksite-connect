<?php

class Kicksite_Schedule_Api
{
  public function fetch_schedule() {
    // $kicksite_access_token = get_transient( 'kicksite_access_token' );
    $kicksite_access_token = ( new Kicksite_Api_Client() )->get_access_token();
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
    set_transient( 'kicksite_schedule_data', $data, HOUR_IN_SECONDS );
  }
}