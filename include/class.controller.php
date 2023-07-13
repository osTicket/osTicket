<?php
/*********************************************************************
    class.controller.php

    Peter Rotich
    Copyright (c)  osTicket
    http://www.osticket.com

    Released under the GNU General Public License WITHOUT ANY WARRANTY.
    See LICENSE.TXT for details.

    vim: expandtab sw=4 ts=4 sts=4:
**********************************************************************/
abstract class Controller {
    /*
     * access
     *
     * This routine can be defined downstream to check if user has
     * permission to call routines in the controller.
     *
     */
    function access() {
        return true;
    }

    // Wrapper for onFatalError
    public function exerr($code, $error='') {
        return $this->onFatalError($code, $error);
    }

    public function onFatalError($code, $error) {
        $this->onError($code, $error);
        // On error should exit but we're making doubly sure
        $this->response($code, $error);
        exit();
    }

    protected function response($code, $response) {
        Http::response($code, $response);
        exit();
    }

    abstract function onError($code, $error, $title=null, $logOnly=false);
}
