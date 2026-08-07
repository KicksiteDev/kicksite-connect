<?php

class Kicksite_Calendar_Renderer
{
  // Register Kicksite Calendar Block
  function register_block() {
    // The recommended block registration doesn't work for plugins because it uses locate_template
    // which only searches themes and child-themes. This sidesteps that to manually point ACF to
    // The right render template.
    acf_register_block_type([
      'name'            => 'kicksite-calendar',
      'title'           => 'Kicksite Calendar',
      'description'     => 'A calendar to display available classes with options to signup for a trial.',
      'render_callback' => [ $this, 'render_template' ],
      'category'        => 'formatting',
      'icon'            => 'calendar-alt',
      'mode'            => 'preview',
      'align'           => 'full',
      'keywords'        => [ "calendar", "schedule", "classes", "registration" ],
      'supports'        => [
        'align'   => true,
        'color'   => [
          'color'      => true,
          'background' => true,
          'gradients'  => true,
        ],
        'spacing' => [
          'padding' => [ 'top', 'bottom', 'right', 'left' ],
        ],
        'shadow'  => true,
      ],
    ]);

    // Whitelists plugin blocks so that they appear in the block inserter.
    // After creating a new block, add the block to the approved list by
    // adding its full block name to the $allowed_block_types array.
    add_filter( 'allowed_block_types_all', function( $allowed_block_types, $block_editor_context ) {
      if ( is_array( $allowed_block_types ) ) {
        $allowed_block_types[] = 'acf/kicksite-calendar';
      }
      return $allowed_block_types;
    }, 26, 2 );
  }

  //
  public function render_template( $block, $content, $is_preview, $post_id ) {
    include KICKSITE_PATH . 'blocks/kicksite-calendar/kicksite-calendar.php';
  }

  // ACF Load Path
  function register_acf_block_path() {
    add_filter( 'acf/settings/load_json', function( $paths ) {
      $paths[] = KICKSITE_PATH . 'acf-json';
      return $paths;
    } );

    // Only redirects saves for blocks whitelisted by the plugin-blocks var.
    // Update with block namespace whenever a new block and associated field group are created.
    add_filter( 'acf/settings/save_json', function( $path ) {
      $plugin_blocks = [ 'acf/kicksite-calendar' ];
      if ( isset( $_POST['acf_field_group']['location'] )  ){
        foreach ( $_POST['acf_field_group']['location'] as $group ) {
          foreach ( $group as $rule ) {
            if ( $rule['param'] === 'block' && in_array( $rule['value'], $plugin_blocks ) ) {
              return KICKSITE_PATH . 'acf-json';
            }
          }
        }
      }
      return $path;
    });
  }

  // Enquque custom stylesheet
  public function enqueue_public_assets() {
    wp_enqueue_style( 'kicksite-calendar', KICKSITE_URL . 'assets/css/calendar-public.css', [], KICKSITE_CONNECT_VERSION );
    wp_enqueue_script( 'kicksite-calendar', KICKSITE_URL . 'assets/js/calendar-public.js', [], KICKSITE_CONNECT_VERSION, true );
  }

  //
  public function shortcode_handler( array $atts) {

  }

  //
  public function render( array $config ) {
    $css_vars = $this->build_css_variables( $config );
    $data_attrs = $this->build_data_attrs( $config );
    $school_name = get_bloginfo();
    $month = date('F Y');

    return <<<HTML
      <section class="{$config['block_name']}" style="{$config['section_styles']}" {$data_attrs}>
        <div class="container">
          <div class="ks-calendar-heading w-50 d-flex flex-column justify-content-center p-3">
            <div class="page-section-header">
              <h3>{$config['heading']}</h3>
            </div>
            <div class="ks-calendar-content">
              {$config['content']}
            </div>
          </div>
          <div class="ks-track-wizard">
            <div class="ks-track-container">
              <div class="ks-calendar-track">
                <div class="ks-calendar-container step-1 p-3">
                  <div class="table-card p-3 border">
                    <div class="table-heading d-flex align-items-center justify-content-between mb-3">
                      <div class="calendar-month">
                        <strong>{$month}</strong>
                      </div>
                      <div class="month-nav-arrows d-flex flex-row gap-4">
                        <div class="cal-nav-left">
                          <i class="fa-solid fa-chevron-left"></i>
                        </div>
                        <div class="cal-nav-right">
                          <i class="fa-solid fa-chevron-right"></i>
                        </div>
                      </div>
                    </div>
                    <table style="table-layout: fixed; width: 100%; {$css_vars}">
                      <thead>
                        <tr class="text-center">
                          <th>Sun</th>
                          <th>Mon</th>
                          <th>Tue</th>
                          <th>Wed</th>
                          <th>Thu</th>
                          <th>Fri</th>
                          <th>Sat</th>
                        </tr>
                      </thead>
                      <tbody class="text-center">
                      </tbody>
                    </table>
                  </div>
                </div>
                <div class="ks-calendar-classes step-2 p-3">
                  <div class="ks-calendar-nav-back">
                    <i class="fa-solid fa-chevron-left"></i>
                  </div>
                  <div class="ks-cal-class-container"></div>
                </div>
                <div class="ks-calendar-form-container step-3 p-3">
                  <div class="ks-calendar-nav-back">
                    <i class="fa-solid fa-chevron-left"></i>
                  </div>
                  <div class="ks-cal-form-heading"></div>
                  <form class="ks-calendar-form">
                    <div class="ks-cal-form-name field-group">
                      <label for="ksCalName">Name</label>
                      <input type="text" id="ksCalName">
                    </div>
                    <div class="ks-cal-form-email field-group">
                      <label for="ksCalEmail">Email</label>
                      <input type="text" id="ksCalEmail">
                    </div>
                    <div class="ks-cal-form-birthdate field-group">
                      <label for="">Birthdate</label>
                      <input type="date" id="ksCalBDate">
                    </div>
                    <div class="ks-cal-form-phone field-group">
                      <label for="ksCalPhone">Phone Number</label>
                      <div class="ks-cal-form-phone-number">
                        <input type="text" id="ksCalPhone">
                        <div class="ks-cal-form-sms">
                          <label for="ksCalSMS">SMS OPT-IN</label>
                          <input type="checkbox" id="ksCalSMS">
                        </div>
                      </div>
                    </div>
                    <div class="ks-cal-sms-notification">
                      By opting in to SMS, the person agrees to receive announcements and billing alerts from {$school_name}. Standard messaging rates may apply. Messaging cadence may vary. Reply STOP to opt out.
                    </div>
                    <input type="submit" value="Submit" class="wp-element-button">
                  </form>
                </div>
              </div>
            </div>
          </div>
        </div>
      </section>
    HTML;
  }

  private function build_css_variables( array $config ) {
    $block_name = $config['block_name'];
    $vars = '';

    //
    $headers = $config['header_styles'] ?? [];
    if ( ! empty( $headers['background_color'] ) ) {
      $vars .= "--{$block_name}-headers-bg-color: {$headers['background_color']};";
    }
    if ( ! empty( $headers['active_background_color'] ) ) {
      $vars .= "--{$block_name}-headers-active-bg-color: {$headers['active_background_color']};";
    }
    if ( ! empty( $headers['select_background_color'] ) ) {
      $vars .= "--{$block_name}-headers-selected-bg-color: {$headers['select_background_color']};";
    }
    if ( ! empty( $headers['text_color'] ) ) {
      $vars .= "--{$block_name}-headers-text-color: {$headers['text_color']};";
    }
    if ( ! empty( $headers['active_text_color'] ) ) {
      $vars .= "--{$block_name}-headers-active-text-color: {$headers['active_text_color']};";
    }
    if ( ! empty( $headers['selected_text_color'] ) ) {
      $vars .= "--{$block_name}-headers-selected-text-color: {$headers['selected_text_color']};";
    }
    if ( ( $headers['border_option'] ?? 'none' ) !== 'none' && ! empty( $headers['border_color'] ) ) {
      $vars .= "--{$block_name}-headers-border-color: {$headers['border_color']};";
    }
    if ( ( $headers['border_option'] ?? 'none' ) === 'ring' && ! empty( $headers['border_radius'] ) ) {
      $vars .= "--{$block_name}-headers-border-radius: {$headers['border_radius']};";
    }

    //
    $day = $config['day_styles'] ?? [];
    if ( ! empty( $day['background_color'] ) ) {
      $vars .= "--{$block_name}-day-bg-color: {$day['background_color']};";
    }
    if ( ! empty( $day['active_background_color'] ) ) {
      $vars .= "--{$block_name}-day-active-bg-color: {$day['active_background_color']};";
    }
    if ( ! empty( $day['select_background_color'] ) ) {
      $vars .= "--{$block_name}-day-selected-bg-color: {$day['select_background_color']};";
    }
    if ( ! empty( $day['text_color'] ) ) {
      $vars .= "--{$block_name}-day-text-color: {$day['text_color']};";
    }
    if ( ! empty( $day['active_text_color'] ) ) {
      $vars .= "--{$block_name}-day-active-text-color: {$day['active_text_color']};";
    }
    if ( ! empty( $day['selected_text_color'] ) ) {
      $vars .= "--{$block_name}-day-selected-text-color: {$day['selected_text_color']};";
    }
    if ( ( $day['border_option'] ?? 'none' ) !== 'none' && ! empty( $day['border_color'] ) ) {
      $vars .= "--{$block_name}-day-border-color: {$day['border_color']};";
    }
    if ( ( $day['border_option'] ?? 'none' ) === 'ring' && ! empty( $day['border_radius'] ) ) {
      $vars .= "--{$block_name}-day-border-radius: {$day['border_radius']};";
    }

    //
    $card = $config['card_styles'] ?? [];
    if ( ! empty( $card['text'] ) ) {
      $vars .= "--{$block_name}-card-text-color: {$card['text']};";
    }
    if ( ! empty( $card['background'] ) ) {
      $vars .= "--{$block_name}-card-background-color: {$card['background']};";
    }
    if ( ! empty( $card['nav_color'] ) ) {
      $vars .= "--{$block_name}-nav-color: {$card['nav_color']};";
    }
    if ( ! empty( $card['nav_hover_color'] ) ) {
      $vars .= "--{$block_name}-nav-hover-color: {$card['nav_hover_color']};";
    }
    if ( ( $card['include_card_border'] ?? false ) && ! empty( $card['border'] ) ) {
      $vars .= "--{$block_name}-card-border-color: {$card['border']};";
    }
    if ( ( $card['include_card_border'] ?? false ) && ! empty( $card['border_size'] ) ) {
      $vars .= "--{$block_name}-card-border-radius: {$card['border_size']};";
    }
    if ( ( $card['include_card_border_radius'] ?? false ) && ! empty( $card['border_radius'] ) ) {
      $vars .= "--{$block_name}-card-border-radius: {$card['border_radius']};";
    }

    // if ( ( $card['include_card_shadow'] ?? false ) && ! empty( $card['drop_shadow'] ) ) {
    //   $vars .= "--{$block_name}-card-drop-shadow: {$card['drop_shadow']};";
    // }

    return $vars;
  }

  public function fetch_calendar_data() {

    // Check the nonce, if it's invalid, check_ajax_referer invokes wp_die
    check_ajax_referer( 'kicksite_calendar_nonce', 'nonce' );

    // Set the new year/month by either the post request or default to the current year/month
    // Sanitize the user selected program id fields
    $year = intval( $_POST['year'] ?? date( 'Y' ) );
    $month = intval( $_POST['month'] ?? date( 'n' ) );
    $program_ids = sanitize_text_field( $_POST['program_ids'] ?? '' );

    // Format the year/month/day correctly for the start and end request params
    $start_date = date( 'Y-m-d', mktime( 0, 0, 0, $month, 1, $year ) );
    $end_date = date( 'Y-m-d', mktime( 0, 0, 0, $month + 1, 0, $year ) );

    // Create a new transient key that stores the correct request params for the given date and programs
    // Check whether there is a stored transient, if so return
    $transient_key = 'kicksite_calendar_' . $year . '_' . $month . '_' . md5( $program_ids );
    $cached = get_transient( $transient_key );
    if ( $cached !== false ) {
      wp_send_json_success( $cached );
      return;
    }

    // Get the access token, if there is a failure, print out a 401 error.
    $token = ( new Kicksite_Api_Client() )->get_access_token();
    if ( ! $token ) {
      wp_send_json_error( [ 'message' => 'Unable to authenticate with Kicksite.' ], 401 );
      return;
    }

    // Get the stored subdomain, construct the endpoint with satart and end data, and
    // append the program_ids separately so it an empty program_id param
    $client_subdomain = trim( get_option( KICKSITE_SUBDOMAIN_OPTION ) );
    // $url = "https://{$client_subdomain}.kicksite.net/v1/calendar?start_date={$start_date}&end_date={$end_date}"; // production
    $url = "https://{$client_subdomain}.kicksite-staging.net/v1/calendar?start_date={$start_date}&end_date={$end_date}"; // local dev and testing
    if ( ! empty( $program_ids ) ) {
      $url .= '&program_ids=' . urlencode( $program_ids );
    }

    // Create the new get request to retrieve the weekly class schedule
    $response = wp_remote_get( $url, [
      'headers' => [
        'Authorization' => "Bearer {$token}",
        'Accept'        => 'application/json',
      ],
      'timeout' => 15,
    ] );

    // If there is an error retrieving the calendar data, create a new Sentry error
    if ( is_wp_error ( $response )) {
      if ( class_exists( '\Sentry\SentrySdk' ) ) {
        \Sentry\captureMessage( 'Kicksite calendar fetch failure on ' . get_bloginfo( 'url' ) . ': '  . $response->get_error_message(), \Sentry\Severity::error() );
      }
      wp_send_json_error( [ 'message' => $response->get_error_message() ], 500 );
      return;
    }

    // Get the response code for the request, if it's not 200 log the error
    $code = wp_remote_retrieve_response_code( $response );
    if ( $code !== 200 ) {
      wp_send_json_error( [ 'message' => "Kicksite API returned {$code}." ], $code );
      return;
    }

    // Decode the response data and create a new transient
    $data = json_decode( wp_remote_retrieve_body( $response ), true );
    set_transient( $transient_key, $data, HOUR_IN_SECONDS );
    wp_send_json_success( $data );
  }

  private function build_data_attrs( array $config ) {
    $program_ids = $config['program_ids'] ?? [];
    $programs = '';
    if ( ! empty( $program_ids ) ) {
      $programs = implode( '; ', $program_ids );
    }

    $attrs  = ' data-ajax-url="' . esc_url( admin_url( 'admin-ajax.php' ) ) . '"';
    $attrs .= ' data-nonce="' . esc_attr( wp_create_nonce( 'kicksite_calendar_nonce' ) ) . '"';
    $attrs .= ' data-program-ids="' . esc_attr( $programs ) . '"';
    $attrs .= ' data-subdomain="' . esc_attr( get_option( KICKSITE_SUBDOMAIN_OPTION, '' ) ) . '"';
    return $attrs;
  }
}
