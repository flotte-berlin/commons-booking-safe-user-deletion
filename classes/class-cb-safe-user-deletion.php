<?php

class CB_Safe_User_Deletion {

  public $options;

  const CHECK_BOOKING_DAYS_DEFAULT = 14;
  const CHECK_BOOKING_DAYS_MIN = 1;
  const CHECK_BOOKING_DAYS_MAX = 90;

  public function __construct() {
    $this->options = get_option('cb_safe_user_deletion_options', array());
  }

  /**
  * option getter
  **/
  public function get_option($key, $default = false) {
    return isset($this->options[$key]) ? $this->options[$key] : $default;
  }

  /**
  * show error message related to handling of user deletion
  */
  function show_user_delete_error_message() {

    if ( !session_id() ) {
      session_start();
    }

    if(array_key_exists( 'user_delete_error', $_SESSION )) {
      $class = 'notice notice-error';
      $message = $_SESSION['user_delete_error'];

      printf( '<div class="%1$s"><p>%2$s</p></div>', esc_attr( $class ), esc_html( $message ) );
    }

    unset( $_SESSION['user_delete_error'] );
  }

  /**
  * anonymize data of a user supposed to be deleted
  */
  function handle_delete_user($user_id) {

    $user_data = get_userdata( $user_id );

    $anonymization_needed = $this->is_user_anonymization_needed($user_id, $user_data->data->user_registered);

    if($anonymization_needed) {

      //check if user can be deleted (no recent bookings)
      $user_ready_for_deletion = $this->check_user_deletion_readiness($user_id);

      //handle error - no user to reassign
      if(!$user_ready_for_deletion) {
        if ( !session_id() ) {
          session_start();
        }

        $current_user = wp_get_current_user();

        $check_booking_days_in_past = $this->get_option('check_booking_days_in_past', self::CHECK_BOOKING_DAYS_DEFAULT);

        if($current_user->ID == $user_id) { //user self deletion

          $_SESSION['user_delete_error'] = sprintf( cb_safe_user_deletion\__('USER_DELETE_ERROR_1', 'commons-booking-safe-user-deletion', "The account can't be deleted, because this is only possible %d days after the last booking. "), $check_booking_days_in_past);
          wp_redirect( '/wp-admin/options.php?page=plugin_delete_me_confirmation' );
        }
        else { //user deletion by admin

          $_SESSION['user_delete_error'] = sprintf( cb_safe_user_deletion\__('USER_DELETE_ERROR_2', 'commons-booking-safe-user-deletion', "The account of %s can't be deleted, because this is only possible %d days after the last booking. "), $user_data->user_login, $check_booking_days_in_past);
          wp_redirect( '/wp-admin/users.php' );
        }

        exit;
      }

      //delete future bookings
      $this->delete_future_bookings($user_id);

      //anonymize user
      $this->anonymize_user_account($user_id);

    }
    else {

      //delete future bookings
      $this->delete_future_bookings($user_id);
    }

  }

  /**
  * delete bookings in the future
  */
  function delete_future_bookings($user_id) {
    $today = date('Y-m-d', strtotime('now'));

    global $wpdb;
    $bookings_table_name = $wpdb->prefix . 'cb_bookings';

    $query = "DELETE FROM $bookings_table_name WHERE date_start > '$today' AND user_id=$user_id" ;

    $wpdb->query($query);
  }

  /**
  * generates a random string of given length
  */
  function generate_random_string($length = 20) {

    $characters = '0123456789abcdefghijklmnopqrstuvwxyzABCDEFGHIJKLMNOPQRSTUVWXYZ';
    $characters_length = strlen($characters);
    $random_string = '';
    for ($i = 0; $i < $length; $i++) {
        $random_string .= $characters[rand(0, $characters_length - 1)];
    }
    return $random_string;

  }

  /**
  * anonymize the account with given user_id
  */
  function anonymize_user_account($user_id) {

    $date = new DateTime();
    $current_timestamp =  $date->getTimestamp();

    global $wpdb;
    $table_users = $wpdb->prefix . 'users';

    $current_user = wp_get_current_user();

    try {
      $new_user_login = 'deleted-user-' . $user_id . '-' . $current_timestamp;
      $hashed_password = wp_hash_password($this->generate_random_string());

      $wpdb->query(
          "
          UPDATE $table_users
          SET user_login = '" . $new_user_login . "',
          user_email = '',
          user_nicename = 'Deleted User',
          display_name = 'Deleted User',
          user_pass = '" . $hashed_password . "'
          WHERE ID = $user_id
          "
      );

      //update user meta data
      update_user_meta( $user_id, 'phone', '0000' );
      update_user_meta( $user_id, 'address', 'unknown' );
      update_user_meta( $user_id, 'nickname', $new_user_login );
      update_user_meta( $user_id, 'first_name', 'gelöscht/anonymisiert am:' );
      update_user_meta( $user_id, 'last_name', date_format($date, "Y-m-d H:i:s") );
    }
    catch(Exception $e) {
      if($current_user->ID == $user_id) { //user self-deletion
        $_SESSION['user_delete_error'] = cb_safe_user_deletion\__('USER_DELETE_ERROR_3', 'commons-booking-safe-user-deletion', "An error occurred while deleting the account. Please contact an administrator.");
        wp_redirect( '/wp-admin/options.php?page=plugin_delete_me_confirmation' );
      }
      else { //user deletion by admin
        $_SESSION['user_delete_error'] = cb_safe_user_deletion\__('USER_DELETE_ERROR_4', 'commons-booking-safe-user-deletion', "An error occurred while deleting the account. The data wasn't updated.");
        wp_redirect( '/wp-admin/users.php' );
      }

      exit;
    }

    if($current_user->ID == $user_id) { //user self deletion
      wp_logout();
      wp_redirect( '/' );
    }
    else { //user deletion by admin
      wp_redirect( '/wp-admin/users.php' );
    }

    exit;

    $current_user = wp_get_current_user();

  }

  /**
  * checks if the user with given id is ready for deletion - last booking is longer than x days ago
  **/
  function check_user_deletion_readiness($user_id) {
    $days = $this->get_option('check_booking_days_in_past', self::CHECK_BOOKING_DAYS_DEFAULT);
    $reference_date = date('Y-m-d', strtotime("-" . $days . " days"));

    $bookings = $this->find_recent_user_bookings($user_id, $reference_date, true);

    return count($bookings) == 0;
  }

  function is_user_anonymization_needed($user_id, $user_registered) {
    $reference_date = date('Y-m-d', strtotime($user_registered));

    $bookings = $this->find_recent_user_bookings($user_id, $reference_date, false);

    return count($bookings) > 0;
  }

  /**
  * returns all bookings from db for user with given id that have start date
  * within the time beetween $reference_date and today
  */
  function find_recent_user_bookings($user_id, $reference_date, $strict = false) {
    $current_day = date('Y-m-d', strtotime("now"));

    global $wpdb;

    $bookings_table_name = $wpdb->prefix . 'cb_bookings';

    $select_statement = "SELECT * " .
    "FROM " . $bookings_table_name . " ".
    "WHERE date_start >= '%s' " .
    "AND date_start <= '" . $current_day . "' " .
    "AND user_id = $user_id";

    if($strict) {

      if($this->table_column_exists($bookings_table_name, 'cancellation_time')) {
        //if there are bookings canceled after booking started, we have to consider these bookings as important
        $select_statement .= " AND (status != 'canceled' OR (status = 'canceled' AND cancellation_time IS NOT NULL AND date_start <= cancellation_time))";
      }
      else {
        $select_statement .= " AND status != 'canceled'";
      }

    }

    $sqlresult = $wpdb->get_results($wpdb->prepare($select_statement, $reference_date), OBJECT);

    return $sqlresult;
  }

  function table_column_exists( $table_name, $column_name ) {
    global $wpdb;
    $column = $wpdb->get_results( $wpdb->prepare(
      "SELECT * FROM INFORMATION_SCHEMA.COLUMNS WHERE TABLE_SCHEMA = %s AND TABLE_NAME = %s AND COLUMN_NAME = %s ",
      DB_NAME, $table_name, $column_name
    ) );
    if ( ! empty( $column ) ) {
      return true;
    }
    return false;
  }
}

?>
