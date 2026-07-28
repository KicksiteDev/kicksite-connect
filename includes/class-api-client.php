<?php

class Kicksite_Api_Client
{
  public function register(string $url, string $secret) {
    $subdomain = get_option( KICKSITE_SUBDOMAIN_OPTION );
    $token = get_option( KICKSITE_TOKEN_OPTION );
    $endpoint_url = "https://{$subdomain}.kicksite.net/v1/wordpress/registrations";

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
}