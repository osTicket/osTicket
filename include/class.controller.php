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

    /**
     *  error & logging and response!
     *
     */
    function exerr($code, $error='') {
        global $ost;

        if ($error && is_array($error))
            $error = Format::array_implode(": ", "\n", $error);

        //Log the error as a warning - include api key if available.
        $msg = $error;
        if ($_SERVER['HTTP_X_API_KEY'])
            $msg.="\n*[".$_SERVER['HTTP_X_API_KEY']."]*\n";
        $ost->logWarning(__('Error')." ($code)", $msg, false);

        if (PHP_SAPI == 'cli') {
            fwrite(STDERR, "({$code}) $error\n");
        } else {
            $this->response($code, $error); //Responder should exit...
        }
        return false;
    }

    //Default response method - can be overwritten in subclasses.
    function response($code, $resp) {
        Http::response($code, $resp);
        exit();
    }
}
