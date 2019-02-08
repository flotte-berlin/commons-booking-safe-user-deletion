# Commons Booking Safe User Deletion

**Contributors:** poilu  
**Donate link:** https://flotte-berlin.de/mitmachen/unterstuetzt-uns/  
**Tags:** booking, commons, admin  
**Tested:** Wordpress 4.9.x, Commons Booking 0.9.2.3  
**License:** GPLv2 or later  
**License URI:** http://www.gnu.org/licenses/gpl-2.0.html  

---
## Description

**Commons Booking Safe User Deletion** is a Wordpress Plugin, which extends the [Commons Booking](https://github.com/wielebenwir/commons-booking) Plugin and helps to handle user account deletion properly.  
Accounts with bookings in the past will be anonymized (bookings are kept, i.e. for statistical reasons). All bookings that lie in the future at time of account deletion will be deleted as well. This ensures that bookings are not connected to non-existing user accounts.  
In the settings one can determine when it is allowed to delete an account under consideration of recent bookings. This can be helpful to track lendings in the near past (i.e. an item got damaged).

## Dependencies

 * Wordpress-Plugin [Delete me](https://de.wordpress.org/plugins/delete-me/)

## Beschreibung

**Commons Booking Safe User Deletion** ist ein Wordpress Plugin, welches das [Commons Booking](https://github.com/wielebenwir/commons-booking) Plugin erweitert und das sichere Löschen/Anonymisieren von NutzerInnen-Konten unterstützt.  
Konten mit Buchungen in der Vergangenheit werden anonymisiert (die Buchungen werden z.B. für statische Zwecke behalten). Alle zum Zeitpunkt der Löschung in der Zukunft befindlichen Buchungen werden ebenfalls gelöscht. Dies verhindert, dass Buchungen mit nicht mehr existenten NutzerInnen verknüpft sind.  
In den Einstellungen kann festgelegt werden, wann Kontenlöschungen erfolgen dürfen unter Berücksichtigung kürzlich zurückliegender Buchungen. Dies kann aus Gründen der Nachvollziehbarkeit von Ausleihvorgängen (etwa bei Beschädigung eines Artikels) hilfreich sein.

## Abhängigkeiten

*  Wordpress-Plugin [Delete me](https://de.wordpress.org/plugins/delete-me/)
