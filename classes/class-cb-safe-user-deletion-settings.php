<?php

class CB_Safe_User_Deletion_Settings {

  private $cb_safe_user_deletion;

  public function prepare_settings($cb_safe_user_deletion) {
    $this->cb_safe_user_deletion = $cb_safe_user_deletion;

    add_action('admin_menu', function() {
        add_options_page( cb_safe_user_deletion\__('SETTINGS_TITLE', 'commons-booking-safe-user-deletion', 'Settings for safe user deletion'), cb_safe_user_deletion\__('SETTINGS_MENU', 'commons-booking-safe-user-deletion', 'Safe User Deletion' ), 'manage_options', 'commons-booking-safe-user-deletion', array($this, 'render_options_page') );
    });

    add_action( 'admin_init', function() {
      register_setting( 'cb-safe-user-deletion-settings', 'cb_safe_user_deletion_options', array($this, 'validate_options') );
    });

  }

  /**
  * sanitize and validate the options provided by input array
  **/
  public function validate_options($input = array()) {
    //var_dump($input);
    $validated_input = array();

    if(isset($input['check_booking_days_in_past'])) {
      $check_booking_days_in_past = (integer) $input['check_booking_days_in_past'];
      if($check_booking_days_in_past >= CB_Safe_User_Deletion::CHECK_BOOKING_DAYS_MIN && $check_booking_days_in_past <= CB_Safe_User_Deletion::CHECK_BOOKING_DAYS_MAX) {
        $validated_input['check_booking_days_in_past'] = $check_booking_days_in_past;
      }
      else {
        $validated_input['check_booking_days_in_past'] = CB_Safe_User_Deletion::CHECK_BOOKING_DAYS_DEFAULT;
      }
    }
    else {
      $validated_input['check_booking_days_in_past'] = CB_Safe_User_Deletion::CHECK_BOOKING_DAYS_DEFAULT;
    }

    return $validated_input;
  }

  public function add_settings_link( $links ) {
    $settings_link = '<a href="options-general.php?page=commons-booking-safe-user-deletion">' . __( 'Settings') . '</a>';
    array_unshift( $links, $settings_link );
    return $links;
  }

  public function render_options_page() {
    $cb_safe_user_deletion = $this->cb_safe_user_deletion;

    include_once( CB_SUD_PATH . 'templates/settings-page-template.php');
  }
}

?>
