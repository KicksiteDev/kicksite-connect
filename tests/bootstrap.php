<?php

require_once __DIR__ . '/../vendor/autoload.php';

// The plugin exits immediately unless it believes it is running inside
// WordPress.
define( 'ABSPATH', __DIR__ . '/' );

// Every hook the plugin registers at load time is recorded here so tests can
// assert what was wired up without booting all of WordPress.
$GLOBALS['kicksite_test_hooks'] = [];

function add_action( $hook, $callback, $priority = 10, $accepted_args = 1 ) {
  $GLOBALS['kicksite_test_hooks'][] = [
    'hook' => $hook,
    'callback' => $callback,
    'priority' => $priority,
  ];
  return true;
}

function add_filter( $hook, $callback, $priority = 10, $accepted_args = 1 ) {
  $GLOBALS['kicksite_test_hooks'][] = [
    'hook' => $hook,
    'callback' => $callback,
    'priority' => $priority,
  ];
  return true;
}

function register_activation_hook( $file, $callback ) {}
function register_deactivation_hook( $file, $callback ) {}

function plugin_dir_url( $file ) {
  return 'https://example.test/wp-content/plugins/kicksite-connect/';
}

function plugin_basename( $file ) {
  return 'kicksite-connect/' . basename( $file );
}

// Minimal escaping stubs. The real WordPress versions do more, but for the
// values this plugin passes them these behave the same way.
function esc_attr( $text ) {
  return htmlspecialchars( (string) $text, ENT_QUOTES, 'UTF-8' );
}

function esc_html( $text ) {
  return htmlspecialchars( (string) $text, ENT_QUOTES, 'UTF-8' );
}

// Load the real plugin file, so tests run against the real constants rather
// than a copy in the bootstrap that could silently drift out of sync with it.
require_once __DIR__ . '/../kicksite-connect.php';
