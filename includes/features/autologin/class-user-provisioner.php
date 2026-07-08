<?php

class Kicksite_User_Provisioner
{
  public function find_or_create( string $email ) {
    // Check whether there is already wp user in the system with the supplied email. If so return them.
    $user = get_user_by( 'email', $email );
    if ( $user ) return $user;

    // If no user is found, create a new username by pulling everything before the email @,
    // then attempt to create an new wp user with the derived username
    $username = sanitize_user( substr( $email, 0, strpos( $email, "@" ) ) );
    $user_id = wp_create_user( $username, wp_generate_password( 24 ), $email );

    // Check If the username is already taken, retry with a random suffix
    if ( is_wp_error( $user_id ) && $user_id->get_error_code() === 'existing_user_login' ) {
      // If it fails, try a new username with random 4 digits appended to the end. 
      $username = $username . '_' . rand( 0, 9999 );
      $user_id = wp_create_user( $username, wp_generate_password(24), $email );
    } 
    
    // If the second user creation for any reason, return the WP_Error
    if ( is_wp_error( $user_id ) ) return $user_id;

    // Fetch the newly created user object, assign the Kicksite role, and return it.
    $user_id = get_user_by( 'ID', $user_id );
    $user_id->set_role( KICKSITE_WP_ROLE );
    return $user_id;
  }
}