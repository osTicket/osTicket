<?php
/*********************************************************************
    class.app.php

    Application registration system
    Apps, usually to be distributed as plugins, can register themselves
    using this utility class, and navigation links will be added to the
    staff and client interfaces.

    Jared Hancock <jared@osticket.com>
    Peter Rotich <peter@osticket.com>
    Copyright (c)  2006-2014 osTicket
    http://www.osticket.com

    Released under the GNU General Public License WITHOUT ANY WARRANTY.
    See LICENSE.TXT for details.

    vim: expandtab sw=4 ts=4 sts=4:
**********************************************************************/

class Application {
    private static $client_apps;
    private static $staff_apps;
    private static $admin_apps;

    function registerStaffApp($desc, $href, $info=array()) {
        self::$staff_apps[] = array_merge($info,
            array('desc'=>$desc, 'href'=>$href));
    }

    static function getStaffApps() {
        return self::$staff_apps;
    }

    function registerClientApp($desc, $href, $info=array()) {
        self::$client_apps[] = array_merge($info,
            array('desc'=>$desc, 'href'=>$href));
    }

    static function getClientApps() {
        return self::$client_apps;
    }

    function registerAdminApp($desc, $href, $info=array()) {
        self::$admin_apps[] = array_merge($info,
            array('desc'=>$desc, 'href'=>$href));
    }

    static function getAdminApps() {
        return self::$admin_apps;
    }
}
