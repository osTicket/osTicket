<?php
/*********************************************************************
    avatar.php

    Simple download utility for internally-generated avatars

    Peter Rotich <peter@osticket.com>
    Jared Hancock <jared@osticket.com>
    Copyright (c)  2006-2014 osTicket
    http://www.osticket.com

    Released under the GNU General Public License WITHOUT ANY WARRANTY.
    See LICENSE.TXT for details.

    vim: expandtab sw=4 ts=4 sts=4:
**********************************************************************/
require('client.inc.php');

if (!isset($_GET['uid']) || !isset($_GET['mode']))
    Http::response(400, '`uid` and `mode` parameters are required');

require_once INCLUDE_DIR . 'class.avatar.php';

try {
    $ra = new RandomAvatar($_GET['mode']);
    $avatar = $ra->makeAvatar($_GET['uid'], $_GET['size']);

    Http::response(200, false, 'image/png', false);
    Http::cacheable($_GET['uid'], false, 86400);
    imagepng($avatar, null, 1);
    imagedestroy($avatar);
    exit;
}
catch (InvalidArgumentException $ex) {
    Http::response(422, 'No such avatar image set');
}
