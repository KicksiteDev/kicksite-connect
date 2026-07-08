<?php

class Kicksite_Token_Validator
{
  public function validate( string $token, string $secret ) : ?array {
    // Split the JWT into its three parts: header, payload, and signature
    $payload_arr = explode( ".", $token ); 
    if ( count( $payload_arr ) !== 3 ) return NULL;

    $header_b64 = $payload_arr[0];
    $payload_b64 = $payload_arr[1];
    $payload_sig_b64 = $payload_arr[2];
    // Combine the header and payload sections separated by a dot to be combined for HMAC_SHA256
    $data = $header_b64 . '.' . $payload_b64;
  
    // Recreate the expected signature by HMAC-SHA256 signing the header and payload, then base64url-encoding the result
    $expected = $this->base64url_encode( hash_hmac( 'sha256', $data , $secret ) );

    // Check whether the locally computed signature matches the one from the token
    if ( !hash_equals( $expected, $payload_sig_b64 ) ) return NULL;

    // Decode the payload into a json array
    $payload = json_decode( $this->base64url_decode( $payload_b64 ), true );

    // Return null if the payload is invalid or the token's expiration time has passed
    if ( !$payload || $payload['exp'] < time() ) return NULL;

    return $payload;
  }

  private function base64url_encode( $data ) {
    return rtrim( strtr( base64_encode( $data ), '+/', '-_' ), '=' );
  }

  private function base64url_decode( $data ) {
    $b64 = strtr( $data, '-_', '+/' );
    return base64_decode( $b64 );
  }
}