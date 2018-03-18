/* osTicket database migration patch
 *
 * @version v1.10.0+
 * @signature 9f415d0f424fdf609c76a8ad4fffefae
 *
 * increases the ost_staff table username field length to 64
 */

ALTER TABLE `%TABLE_PREFIX%staff` MODIFY `username` VARCHAR(64);
