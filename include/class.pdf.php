<?php
/*********************************************************************
    class.pdf.php

    Ticket PDF Export

    Peter Rotich <peter@osticket.com>
    Copyright (c)  2006-2013 osTicket
    http://www.osticket.com

    Released under the GNU General Public License WITHOUT ANY WARRANTY.
    See LICENSE.TXT for details.

    vim: expandtab sw=4 ts=4 sts=4:
**********************************************************************/

define('THIS_DIR', str_replace('\\', '/', Misc::realpath(dirname(__FILE__))) . '/'); //Include path..

require_once(INCLUDE_DIR.'mpdf/mpdf.php');

class Ticket2PDF extends mPDF
{

	var $includenotes = false;

	var $pageOffset = 0;

    var $ticket = null;

	function Ticket2PDF($ticket, $psize='Letter', $notes=false) {
        global $thisstaff;

        $this->ticket = $ticket;
        $this->includenotes = $notes;

        parent::__construct('', $psize);

        $this->_print();
	}

    function getTicket() {
        return $this->ticket;
    }

    function WriteHtml() {
        static $filenumber = 1;
        $args = func_get_args();
        $text = &$args[0];
        $self = $this;
        $text = preg_replace_callback('/cid:([\w.-]{32})/',
            function($match) use ($self, &$filenumber) {
                if (!($file = AttachmentFile::lookup($match[1])))
                    return $match[0];
                $key = "__attached_file_".$filenumber++;
                $self->{$key} = $file->getData();
                return 'var:'.$key;
            },
            $text
        );
        call_user_func_array(array('parent', 'WriteHtml'), $args);
    }

    function _print() {
        global $thisstaff, $thisclient, $cfg;

        if(!($ticket=$this->getTicket()))
            return;

        ob_start();
        if ($thisstaff)
            include STAFFINC_DIR.'templates/ticket-print.tmpl.php';
        elseif ($thisclient)
            include CLIENTINC_DIR.'templates/ticket-print.tmpl.php';
        else
            return;
        $html = ob_get_clean();

        $this->WriteHtml($html, 0, true, true);
    }
}
?>
