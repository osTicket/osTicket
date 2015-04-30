<?php
/*********************************************************************
    class.thread_actions.php

    Actions for thread entries. This serves as a simple repository for
    drop-down actions which can be triggered on the ticket-view page for an
    object's thread.

    Jared Hancock <jared@osticket.com>
    Peter Rotich <peter@osticket.com>
    Copyright (c)  2006-2014 osTicket
    http://www.osticket.com

    Released under the GNU General Public License WITHOUT ANY WARRANTY.
    See LICENSE.TXT for details.

    vim: expandtab sw=4 ts=4 sts=4:
**********************************************************************/
include_once(INCLUDE_DIR.'class.thread.php');

class TEA_ShowEmailHeaders extends ThreadEntryAction {
    static $id = 'view_headers';
    static $name = /* trans */ 'View Email Headers';
    static $icon = 'envelope';

    function isEnabled() {
        global $thisstaff;

        return $thisstaff && $thisstaff->isAdmin();
    }

    function isVisible() {
        return (bool) $this->thread->getEmailHeader();
    }

    function getJsStub() {
        return sprintf("$.dialog('%s');",
            $this->getAjaxUrl()
        );
    }

    function trigger() {
        switch ($_SERVER['REQUEST_METHOD']) {
        case 'GET':
            return $this->trigger__get();
        }
    }

    private function trigger__get() {
        $headers = $this->thread->getEmailHeader();

        include STAFFINC_DIR . 'templates/thread-email-headers.tmpl.php';
    }
}
ThreadEntry::registerAction(/* trans */ 'E-Mail', 'TEA_ShowEmailHeaders');
