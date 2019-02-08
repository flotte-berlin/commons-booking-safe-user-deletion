<div class="wrap">

  <h1><?= cb_safe_user_deletion\__('SETTINGS_PAGE_HEADER', 'commons-booking-safe-user-deletion', 'Settings for safe user deletion') ?></h1>

  <p><?= cb_safe_user_deletion\__('SETTINGS_DESCRIPTION', 'commons-booking-safe-user-deletion', 'These settings help you to configure the safe user deletion.<br> If the user account holds past bookings it is transformed into an anonymous account. This prevents bookings connected to not existing users and keeps them for i.e. statistical purposes.<br> All future bookings of a deleted account will be deleted as well. ') ?></p>

  <form method="post" action="options.php">
    <?php
      settings_fields( 'cb-safe-user-deletion-settings' );
      do_settings_sections( 'cb-safe-user-deletion-settings' );
    ?>

    <h2><?= cb_safe_user_deletion\__('PREVENT_DELETION_HEADER', 'commons-booking-safe-user-deletion', 'Booking Condition') ?></h2>

    <p>
      <?= cb_safe_user_deletion\__('PREVENT_DELETION_DESCRIPTION_1', 'commons-booking-safe-user-deletion', "The value should be at least as high as the amount of days you allow users to book an item.") ?>
      <br>
      <?= cb_safe_user_deletion\__('PREVENT_DELETION_DESCRIPTION_2', 'commons-booking-safe-user-deletion', "The start date of bookings are taken into account here.") ?>
    </p>

    <p>
      <?= cb_safe_user_deletion\__('PREVENT_DELETION_LABEL', 'commons-booking-safe-user-deletion', "Users are only allowed to delete their account, if they have") ?>:
    </p>

    <?= cb_safe_user_deletion\__('PREVENT_FOR', 'commons-booking-safe-user-deletion', 'no bookings in past') ?>
    <input type="number" name="cb_safe_user_deletion_options[check_booking_days_in_past]" min="<?= CB_Safe_User_Deletion::CHECK_BOOKING_DAYS_MIN ?>" max="<?= CB_Safe_User_Deletion::CHECK_BOOKING_DAYS_MAX ?>" value="<?php echo esc_attr($cb_safe_user_deletion->get_option('check_booking_days_in_past', CB_Safe_User_Deletion::CHECK_BOOKING_DAYS_DEFAULT)) ?>">
    <?= cb_safe_user_deletion\__('DAYS', 'commons-booking-safe-user-deletion', 'days') ?> <i>(<?= cb_safe_user_deletion\__('VALID_VALUES', 'commons-booking-safe-user-deletion', 'valid values') ?>: <?= CB_Safe_User_Deletion::CHECK_BOOKING_DAYS_MIN ?> - <?= CB_Safe_User_Deletion::CHECK_BOOKING_DAYS_MAX ?>)</i>

    <?php submit_button(); ?>
  </form>

</div>
