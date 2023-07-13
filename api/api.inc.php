<?php
/*********************************************************************
    api.inc.php

    File included on every API page...handles common includes.

    Peter Rotich <peter@osticket.com>
    Copyright (c)  2006-2013 osTicket
    http://www.osticket.com

    Released under the GNU General Public License WITHOUT ANY WARRANTY.
    See LICENSE.TXT for details.

    vim: expandtab sw=4 ts=4 sts=4:
**********************************************************************/
file_exists('../main.inc.php') or die('System Error');

/*
 * Why API_SESSION ??
 * It indicates that the session is API - which session handler should handle as
 * stateless for new sessions.
 * Existing session continue to work as expected - this it's important for
 * SSO authentication, which uses /api/auth/* endpoints. Such calls are not
 * stateless.
 *
 */
define('API_SESSION', true);

// APICALL const.
define('APICALL', true);

require_once('../main.inc.php');
require_once(INCLUDE_DIR.'class.http.php');
require_once(INCLUDE_DIR.'class.api.php');

?>
