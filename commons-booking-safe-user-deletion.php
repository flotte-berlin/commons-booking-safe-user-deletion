<?php
/*
Plugin Name: Commons Booking Safe User Deletion
Plugin URI:   https://github.com/flotte-berlin/commons-booking-safe-user-deletion
Description: Ein Plugin zur sicheren Löschung von User Accounts bei Nutzung von Commons Booking - Löschung zukünftiger und Sicherung vergangener Buchungen
Version: 0.1.1
Author:       poilu
Author URI:   https://github.com/poilu
License:      GPLv2 or later
License URI:  https://www.gnu.org/licenses/gpl-2.0.html
*/

define( 'CB_SUD_PATH', plugin_dir_path( __FILE__ ) );

require_once( CB_SUD_PATH . 'classes/class-cb-safe-user-deletion.php' );

require_once( CB_SUD_PATH . 'functions/translate.php' );

load_plugin_textdomain( 'commons-booking-safe-user-deletion', false, 'commons-booking-safe-user-deletion/languages/' );

require_once( CB_SUD_PATH . 'classes/class-cb-safe-user-deletion-settings.php' );

$cb_safe_user_deletion = new CB_Safe_User_Deletion();

$cb_safe_user_deletion_settings = new CB_Safe_User_Deletion_Settings();
$cb_safe_user_deletion_settings->prepare_settings($cb_safe_user_deletion);
add_filter( "plugin_action_links_" . plugin_basename( __FILE__ ), array($cb_safe_user_deletion_settings, 'add_settings_link') );

add_action( 'admin_notices', array($cb_safe_user_deletion, 'show_user_delete_error_message') );

add_action( 'delete_user', array($cb_safe_user_deletion, 'handle_delete_user'));

?>
