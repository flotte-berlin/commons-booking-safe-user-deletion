<?php

namespace {

class CB_SUD_Test_WPDB {
  public $prefix = 'wp_';

  public function query($sql) {
    if(!preg_match('/WHERE ID = (\d+)/', $sql, $id_matches)) {
      return false;
    }

    if(!preg_match("/SET user_login = '([^']*)',\s*user_email = '([^']*)',\s*user_nicename = '([^']*)',\s*display_name = '([^']*)',\s*user_pass = '([^']*)'/s", $sql, $value_matches)) {
      return false;
    }

    $user_id = (int) $id_matches[1];

    if(!isset($GLOBALS['cb_sud_test_state']['users'][$user_id])) {
      return false;
    }

    $GLOBALS['cb_sud_test_state']['users'][$user_id]['user_login'] = $value_matches[1];
    $GLOBALS['cb_sud_test_state']['users'][$user_id]['user_email'] = $value_matches[2];
    $GLOBALS['cb_sud_test_state']['users'][$user_id]['user_nicename'] = $value_matches[3];
    $GLOBALS['cb_sud_test_state']['users'][$user_id]['display_name'] = $value_matches[4];
    $GLOBALS['cb_sud_test_state']['users'][$user_id]['user_pass'] = $value_matches[5];

    return 1;
  }
}

class CB_SUD_Test_Booking {
  public $ID;
  public $post_author;
  public $post_status;

  private $start_date;
  private $end_date;

  public function __construct($id, $post_author, $post_status, $start_date, $end_date) {
    $this->ID = $id;
    $this->post_author = $post_author;
    $this->post_status = $post_status;
    $this->start_date = $start_date;
    $this->end_date = $end_date;
  }

  public function getStartDate() {
    return $this->start_date;
  }

  public function getTimeframeEndDate() {
    return $this->end_date;
  }
}

function cb_sud_reset_test_state() {
  $GLOBALS['cb_sud_test_state'] = [
    'options' => [],
    'users' => [],
    'user_meta' => [],
    'posts' => [],
    'post_meta' => [],
    'timeframes' => [],
    'deleted_posts' => [],
    'redirects' => [],
    'logged_out' => false,
    'current_user_id' => 0,
    'next_user_id' => 1,
    'next_post_id' => 1,
  ];

  $GLOBALS['wpdb'] = new CB_SUD_Test_WPDB();
}

function cb_sud_test_get_booking_start_timestamp($booking) {
  if(method_exists($booking, 'getStartDate')) {
    return $booking->getStartDate();
  }

  return strtotime($GLOBALS['cb_sud_test_state']['post_meta'][$booking->ID]['repetition-start'] ?? '');
}

function cb_sud_test_get_booking_end_timestamp($booking) {
  if(method_exists($booking, 'getTimeframeEndDate')) {
    return $booking->getTimeframeEndDate();
  }

  return strtotime($GLOBALS['cb_sud_test_state']['post_meta'][$booking->ID]['repetition-end'] ?? '');
}

function cb_sud_test_filter_timeframes($date_from, $date_until, array $statuses) {
  $bookings = [];

  foreach($GLOBALS['cb_sud_test_state']['timeframes'] as $booking) {
    $status = $booking->post_status ?? get_post_status($booking->ID);
    if(!in_array($status, $statuses, true)) {
      continue;
    }

    $booking_start = cb_sud_test_get_booking_start_timestamp($booking);
    $booking_end = cb_sud_test_get_booking_end_timestamp($booking);

    if($date_until !== null && $booking_start > $date_until) {
      continue;
    }

    if($date_from && $booking_end && $booking_end < $date_from) {
      continue;
    }

    if($date_from && !$booking_end && $booking_start < $date_from) {
      continue;
    }

    $bookings[] = $booking;
  }

  return $bookings;
}

function __($text, $domain = 'default') {
  return $text;
}

function esc_attr($value) {
  return $value;
}

function esc_html($value) {
  return $value;
}

function add_action() {
}

function add_filter() {
}

function add_options_page() {
}

function register_setting() {
}

function get_option($key, $default = false) {
  return $GLOBALS['cb_sud_test_state']['options'][$key] ?? $default;
}

function get_post_meta($post_id, $key, $single = true) {
  return $GLOBALS['cb_sud_test_state']['post_meta'][$post_id][$key] ?? '';
}

function get_post_status($post_id) {
  return $GLOBALS['cb_sud_test_state']['posts'][$post_id]['post_status'] ?? false;
}

function get_userdata($user_id) {
  if(!isset($GLOBALS['cb_sud_test_state']['users'][$user_id])) {
    return false;
  }

  $user = (object) $GLOBALS['cb_sud_test_state']['users'][$user_id];
  $user->data = (object) $GLOBALS['cb_sud_test_state']['users'][$user_id];

  return $user;
}

function update_user_meta($user_id, $key, $value) {
  $GLOBALS['cb_sud_test_state']['user_meta'][$user_id][$key] = $value;

  return true;
}

function get_user_meta($user_id, $key, $single = true) {
  return $GLOBALS['cb_sud_test_state']['user_meta'][$user_id][$key] ?? '';
}

function wp_delete_post($post_id, $force_delete = false) {
  $GLOBALS['cb_sud_test_state']['deleted_posts'][] = $post_id;
  unset($GLOBALS['cb_sud_test_state']['posts'][$post_id]);

  return true;
}

function wp_get_current_user() {
  $current_user_id = $GLOBALS['cb_sud_test_state']['current_user_id'];

  if(!$current_user_id || !isset($GLOBALS['cb_sud_test_state']['users'][$current_user_id])) {
    return (object) ['ID' => 0];
  }

  return get_userdata($current_user_id);
}

function wp_hash_password($password) {
  return 'hashed:' . $password;
}

function wp_logout() {
  $GLOBALS['cb_sud_test_state']['logged_out'] = true;
}

function wp_redirect($location) {
  $GLOBALS['cb_sud_test_state']['redirects'][] = $location;

  return true;
}

cb_sud_reset_test_state();

require_once __DIR__ . '/../functions/translate.php';
require_once __DIR__ . '/../classes/class-cb-safe-user-deletion.php';
require_once __DIR__ . '/../classes/class-cb-safe-user-deletion-settings.php';
}

namespace CommonsBooking\Wordpress\CustomPostType {

class Timeframe {
  const BOOKING_ID = 'booking';
}
}

namespace CommonsBooking\Repository {

class Timeframe {
  public static function getInRange($date_from, $date_until, $locations, $items, $types, $return_as_model, $statuses) {
    return \cb_sud_test_filter_timeframes($date_from, $date_until, $statuses);
  }
}
}