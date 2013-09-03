<?php
/*********************************************************************
    class.error.php

    Error handling for PHP < 5.0. Allows for returning a formal error from a
    function since throwing it isn't available. Also allows for consistent
    logging and debugging of errors in the osTicket system log.

    Peter Rotich <peter@osticket.com>
    Jared Hancock <jared@osticket.com>
    Copyright (c)  2006-2013 osTicket
    http://www.osticket.com

    Released under the GNU General Public License WITHOUT ANY WARRANTY.
    See LICENSE.TXT for details.

    vim: expandtab sw=4 ts=4 sts=4:
**********************************************************************/

class Error /* extends Exception */ {
    var $title = '';

    function Error($message) {
        call_user_func_array(array($this,'__construct'), func_get_args());
    }
    function __construct($message) {
        global $ost;

        $message = str_replace(ROOT_DIR, '(root)/', $message);

        if ($ost->getConfig()->getLogLevel() == 3)
            $message .= "\n\n" . $this->formatBacktrace(debug_backtrace());

        $ost->logError($this->getTitle(), $message);
    }

    function getTitle() {
        return get_class($this) . ": {$this->title}";
    }

    function formatBacktrace($bt) {
        $buffer = array();
        foreach ($bt as $i=>$frame)
            $buffer[] = sprintf("#%d %s%s%s at [%s:%d]", $i,
                $frame['class'], $frame['type'], $frame['function'],
                str_replace(ROOT_DIR, '', $frame['file']), $frame['line']);
        return implode("\n", $buffer);
    }
}

class InitialDataError extends Error {
    var $title = 'Problem with install initial data';
}

function raise_error($message, $class=false) {
    if (!$class) $class = 'Error';
    new $class($message);
}

?>
