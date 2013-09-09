<?php
/*********************************************************************
    class.csrf.php

    Provides mechanisms to protect against cross-site request forgery
    attacks. This is accomplished by using a token that is not stored in a
    session, but required to make changes to the system.

    This can be accomplished by emitting a hidden field in a form, or
    sending a separate header (X-CSRFToken) when forms are submitted (e.g Ajax).

    This technique is based on the protection mechanism in the Django
    project, detailed at and thanks to
    https://docs.djangoproject.com/en/dev/ref/contrib/csrf/.

    * TIMEOUT
    Token can be expired after X seconds of inactivity (timeout) independent of the session.


    Jared Hancock
    Copyright (c)  2006-2013 osTicket
    http://www.osticket.com

    Released under the GNU General Public License WITHOUT ANY WARRANTY.
    See LICENSE.TXT for details.

    vim: expandtab sw=4 ts=4 sts=4:
**********************************************************************/

Class CSRF {

    var $name;
    var $timeout;

    var $csrf;

    function CSRF($name='__CSRFToken__', $timeout=0) {

        $this->name = $name;
        $this->timeout = $timeout;
        $this->csrf = &$_SESSION['csrf'];
    }

    function reset() {
        $this->csrf = array();
    }

    function isExpired() {
       return ($this->timeout && (time()-$this->csrf['time'])>$this->timeout);
    }

    function getTokenName() {
        return $this->name;
    }

    function getToken() {

        if(!$this->csrf['token'] || $this->isExpired()) {

            $this->csrf['token'] = sha1(session_id().Crypto::random(16).SECRET_SALT);
            $this->csrf['time'] = time();
        } else {
            //Reset the timer
            $this->csrf['time'] = time();
        }

        return $this->csrf['token'];
    }

    function validateToken($token) {
        return ($token && trim($token)==$this->getToken() && !$this->isExpired());
    }

    function getFormInput($name='') {
        if(!$name) $name = $this->name;

        return sprintf('<input type="hidden" name="%s" value="%s" />', $name, $this->getToken());
    }
}

/* global function to add hidden token input with to forms */
function csrf_token() {
    global $ost;

    if($ost && $ost->getCSRF())
        echo $ost->getCSRFFormInput();
}
?>
