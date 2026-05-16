<?php

use PHPUnit\Framework\TestCase;

class CB_Safe_User_Deletion_Spy extends CB_Safe_User_Deletion {
  public $delete_future_bookings_called = false;
  public $anonymize_user_account_called = false;

  function delete_future_bookings($user_id) {
    $this->delete_future_bookings_called = true;
  }

  function anonymize_user_account($user_id) {
    $this->anonymize_user_account_called = true;
  }
}

final class CBSafeUserDeletionTest extends TestCase {
  private $plugin;
  private $settings;

  protected function setUp(): void {
    cb_sud_reset_test_state();
    $GLOBALS['cb_sud_test_state']['options']['cb_safe_user_deletion_options'] = [
      'check_booking_days_in_past' => 14,
    ];

    $this->plugin = new CB_Safe_User_Deletion();
    $this->settings = new CB_Safe_User_Deletion_Settings();
  }

  public function test_validate_options_accepts_valid_values_and_defaults_invalid_ones(): void {
    $this->assertSame(
      ['check_booking_days_in_past' => 30],
      $this->settings->validate_options(['check_booking_days_in_past' => 30])
    );

    $this->assertSame(
      ['check_booking_days_in_past' => CB_Safe_User_Deletion::CHECK_BOOKING_DAYS_DEFAULT],
      $this->settings->validate_options(['check_booking_days_in_past' => 0])
    );

    $this->assertSame(
      ['check_booking_days_in_past' => CB_Safe_User_Deletion::CHECK_BOOKING_DAYS_DEFAULT],
      $this->settings->validate_options(['check_booking_days_in_past' => 91])
    );
  }

  public function test_user_without_bookings_does_not_need_anonymization(): void {
    $user_id = $this->createUser('-60 days');

    $this->assertFalse($this->plugin->is_user_anonymization_needed($user_id, $this->getUserRegisteredAt($user_id)));
  }

  public function test_old_confirmed_booking_requires_anonymization_and_is_ready(): void {
    $user_id = $this->createUser('-60 days');
    $this->createBooking($user_id, '-30 days', '-29 days', 'confirmed');

    $this->assertTrue($this->plugin->is_user_anonymization_needed($user_id, $this->getUserRegisteredAt($user_id)));
    $this->assertTrue($this->plugin->check_user_anonymization_readiness($user_id));
  }

  public function test_recent_confirmed_booking_blocks_readiness(): void {
    $user_id = $this->createUser('-60 days');
    $this->createBooking($user_id, '-7 days', '-6 days', 'confirmed');

    $this->assertTrue($this->plugin->is_user_anonymization_needed($user_id, $this->getUserRegisteredAt($user_id)));
    $this->assertFalse($this->plugin->check_user_anonymization_readiness($user_id));
  }

  public function test_old_archived_confirmed_booking_requires_anonymization_and_is_ready(): void {
    $user_id = $this->createUser('-60 days');
    $this->createArchivedBooking($user_id, '-30 days', 'confirmed');

    $this->assertTrue($this->plugin->is_user_anonymization_needed($user_id, $this->getUserRegisteredAt($user_id)));
    $this->assertTrue($this->plugin->check_user_anonymization_readiness($user_id));
  }

  public function test_recent_archived_confirmed_booking_blocks_readiness(): void {
    $user_id = $this->createUser('-60 days');
    $this->createArchivedBooking($user_id, '-7 days', 'confirmed');

    $this->assertTrue($this->plugin->is_user_anonymization_needed($user_id, $this->getUserRegisteredAt($user_id)));
    $this->assertFalse($this->plugin->check_user_anonymization_readiness($user_id));
  }

  public function test_recent_archived_canceled_booking_after_start_blocks_readiness(): void {
    $user_id = $this->createUser('-60 days');
    $this->createArchivedBooking($user_id, '-7 days', 'canceled', '-5 days');

    $this->assertTrue($this->plugin->is_user_anonymization_needed($user_id, $this->getUserRegisteredAt($user_id)));
    $this->assertFalse($this->plugin->check_user_anonymization_readiness($user_id));
  }

  public function test_recent_canceled_booking_with_recent_cancellation_blocks_readiness(): void {
    $user_id = $this->createUser('-60 days');
    $this->createBooking($user_id, '-7 days', '-6 days', 'canceled', true, [
      'cancellation_time' => strtotime('-5 days'),
    ]);

    $this->assertTrue($this->plugin->is_user_anonymization_needed($user_id, $this->getUserRegisteredAt($user_id)));
    $this->assertFalse($this->plugin->check_user_anonymization_readiness($user_id));
  }

  public function test_canceled_booking_before_period_outside_threshold_does_not_require_anonymization(): void {
    $user_id = $this->createUser('-60 days');
    $this->createBooking($user_id, '-30 days', '-29 days', 'canceled', true, [
      'cancellation_time' => strtotime('-45 days'),
    ]);

    $this->assertTrue($this->plugin->is_user_anonymization_needed($user_id, $this->getUserRegisteredAt($user_id)));
    $this->assertTrue($this->plugin->check_user_anonymization_readiness($user_id));
  }

  public function test_canceled_booking_before_period_inside_threshold_does_not_require_anonymization(): void {
    $user_id = $this->createUser('-60 days');
    $this->createBooking($user_id, '-7 days', '-6 days', 'canceled', true, [
      'cancellation_time' => strtotime('-30 days'),
    ]);

    $this->assertTrue($this->plugin->is_user_anonymization_needed($user_id, $this->getUserRegisteredAt($user_id)));
    $this->assertTrue($this->plugin->check_user_anonymization_readiness($user_id));
  }

  public function test_recent_canceled_booking_without_cancellation_timestamp_does_not_block_readiness(): void {
    $user_id = $this->createUser('-60 days');
    $this->createBooking($user_id, '-7 days', '-6 days', 'canceled');

    $this->assertTrue($this->plugin->is_user_anonymization_needed($user_id, $this->getUserRegisteredAt($user_id)));
    $this->assertTrue($this->plugin->check_user_anonymization_readiness($user_id));
  }

  public function test_mixed_history_deletes_only_future_bookings_for_the_user(): void {
    $user_id = $this->createUser('-60 days');
    $other_user_id = $this->createUser('-60 days', 'other-user', 'other-user@example.com');

    $past_booking = $this->createBooking($user_id, '-30 days', '-29 days', 'confirmed');
    $future_booking = $this->createBooking($user_id, '+5 days', '+6 days', 'confirmed');
    $other_future_booking = $this->createBooking($other_user_id, '+5 days', '+6 days', 'confirmed');

    $this->plugin->delete_future_bookings($user_id);

    $this->assertSame([$future_booking->ID], $GLOBALS['cb_sud_test_state']['deleted_posts']);
    $this->assertArrayHasKey($past_booking->ID, $GLOBALS['cb_sud_test_state']['posts']);
    $this->assertArrayHasKey($other_future_booking->ID, $GLOBALS['cb_sud_test_state']['posts']);
  }

  public function test_booking_start_before_threshold_does_not_count_as_recent_booking(): void {
    $user_id = $this->createUser('-60 days');
    $this->createBooking($user_id, '-20 days', '-10 days', 'confirmed');

    $this->assertTrue($this->plugin->check_user_anonymization_readiness($user_id));
  }

  public function test_exact_threshold_boundary_is_included(): void {
    $user_id = $this->createUser('-60 days');
    $reference_date = strtotime('-14 days');

    $this->createBooking($user_id, $reference_date, $reference_date + 86400, 'confirmed');

    $bookings = $this->plugin->find_recent_user_bookings($user_id, $reference_date, true);

    $this->assertCount(1, $bookings);
    $this->assertFalse($this->plugin->check_user_anonymization_readiness($user_id));
  }

  public function test_fallback_meta_dates_are_used_when_booking_methods_are_missing(): void {
    $user_id = $this->createUser('-60 days');
    $this->createBooking($user_id, '-7 days', '-6 days', 'confirmed', false);

    $bookings = $this->plugin->find_recent_user_bookings($user_id, strtotime('-14 days'), false);

    $this->assertCount(1, $bookings);
  }

  public function test_anonymize_user_data_updates_user_fields_and_meta(): void {
    $user_id = $this->createUser('-60 days');
    $date = new DateTime('2026-05-01 12:34:56');

    $new_user_login = $this->plugin->anonymize_user_data($user_id, $date);

    $this->assertSame('deleted-user-' . $user_id . '-' . $date->getTimestamp(), $new_user_login);
    $this->assertSame($new_user_login, $GLOBALS['cb_sud_test_state']['users'][$user_id]['user_login']);
    $this->assertSame('', $GLOBALS['cb_sud_test_state']['users'][$user_id]['user_email']);
    $this->assertSame('Deleted User', $GLOBALS['cb_sud_test_state']['users'][$user_id]['user_nicename']);
    $this->assertSame('Deleted User', $GLOBALS['cb_sud_test_state']['users'][$user_id]['display_name']);
    $this->assertStringStartsWith('hashed:', $GLOBALS['cb_sud_test_state']['users'][$user_id]['user_pass']);
    $this->assertSame('0000', get_user_meta($user_id, 'phone', true));
    $this->assertSame('unknown', get_user_meta($user_id, 'address', true));
    $this->assertSame($new_user_login, get_user_meta($user_id, 'nickname', true));
    $this->assertSame('gelöscht/anonymisiert am:', get_user_meta($user_id, 'first_name', true));
    $this->assertSame('2026-05-01 12:34:56', get_user_meta($user_id, 'last_name', true));
  }

  public function test_handle_delete_user_calls_deletion_and_anonymization_when_user_is_ready(): void {
    $user_id = $this->createUser('-60 days');
    $this->createBooking($user_id, '-30 days', '-29 days', 'confirmed');

    $plugin = new CB_Safe_User_Deletion_Spy();

    $plugin->handle_delete_user($user_id);

    $this->assertTrue($plugin->delete_future_bookings_called);
    $this->assertTrue($plugin->anonymize_user_account_called);
  }

  public function test_handle_delete_user_skips_follow_up_actions_without_booking_history(): void {
    $user_id = $this->createUser('-60 days');
    $plugin = new CB_Safe_User_Deletion_Spy();

    $plugin->handle_delete_user($user_id);

    $this->assertFalse($plugin->delete_future_bookings_called);
    $this->assertFalse($plugin->anonymize_user_account_called);
  }

  private function createUser($registered_at, $login = 'test-user', $email = 'test-user@example.com'): int {
    $user_id = $GLOBALS['cb_sud_test_state']['next_user_id']++;
    $registered_at = is_int($registered_at) ? date('Y-m-d H:i:s', $registered_at) : date('Y-m-d H:i:s', strtotime($registered_at));

    $GLOBALS['cb_sud_test_state']['users'][$user_id] = [
      'ID' => $user_id,
      'user_login' => $login,
      'user_email' => $email,
      'user_registered' => $registered_at,
      'user_nicename' => $login,
      'display_name' => $login,
      'user_pass' => 'initial-password',
    ];

    return $user_id;
  }

  private function createBooking($user_id, $start_at, $end_at, $status, $with_methods = true, array $meta = []) {
    $booking_id = $GLOBALS['cb_sud_test_state']['next_post_id']++;
    $start_timestamp = is_int($start_at) ? $start_at : strtotime($start_at);
    $end_timestamp = is_int($end_at) ? $end_at : strtotime($end_at);

    if($with_methods) {
      $booking = new CB_SUD_Test_Booking($booking_id, $user_id, $status, $start_timestamp, $end_timestamp);
    }
    else {
      $booking = (object) [
        'ID' => $booking_id,
        'post_author' => $user_id,
        'post_status' => $status,
      ];
    }

    $GLOBALS['cb_sud_test_state']['posts'][$booking_id] = [
      'post_status' => $status,
    ];
    $GLOBALS['cb_sud_test_state']['timeframes'][] = $booking;
    $GLOBALS['cb_sud_test_state']['post_meta'][$booking_id]['repetition-start'] = date('Y-m-d H:i:s', $start_timestamp);
    $GLOBALS['cb_sud_test_state']['post_meta'][$booking_id]['repetition-end'] = date('Y-m-d H:i:s', $end_timestamp);

    foreach($meta as $key => $value) {
      $GLOBALS['cb_sud_test_state']['post_meta'][$booking_id][$key] = $value;
    }

    return $booking;
  }

  private function createArchivedBooking($user_id, $start_at, $status, $cancellation_time = null): object {
    $start_timestamp = is_int($start_at) ? $start_at : strtotime($start_at);

    $booking = (object) [
      'user_id' => $user_id,
      'date_start' => date('Y-m-d', $start_timestamp),
      'status' => $status,
      'cancellation_time' => $cancellation_time === null
        ? null
        : (is_int($cancellation_time) ? date('Y-m-d', $cancellation_time) : date('Y-m-d', strtotime($cancellation_time))),
    ];

    $GLOBALS['cb_sud_test_state']['archived_bookings'][] = $booking;

    return $booking;
  }

  private function getUserRegisteredAt($user_id): string {
    return $GLOBALS['cb_sud_test_state']['users'][$user_id]['user_registered'];
  }
}