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

class mPDFWithLocalImages extends mPDF {
    function WriteHtml($html) {
        static $filenumber = 1;
        $args = func_get_args();
        $self = $this;
        $images = $cids = array();
        // Try and get information for all the files in one query
        if (preg_match_all('/"cid:([\w._-]{32})"/', $html, $cids)) {
            foreach (AttachmentFile::objects()
                ->filter(array('key__in' => $cids[1]))
                as $file
            ) {
                $images[strtolower($file->getKey())] = $file;
            }
        }
        $args[0] = preg_replace_callback('/"cid:([\w.-]{32})"/',
            function($match) use ($self, $images, &$filenumber) {
                if (!($file = @$images[strtolower($match[1])]))
                    return $match[0];
                $key = "__attached_file_".$filenumber++;
                $self->{$key} = $file->getData();
                return 'var:'.$key;
            },
            $html
        );
        return call_user_func_array(array('parent', 'WriteHtml'), $args);
    }
}

class Ticket2PDF extends mPDFWithLocalImages
{

	var $includenotes = false;

	var $pageOffset = 0;

    var $ticket = null;

	function __construct($ticket, $psize='Letter', $notes=false) {
        global $thisstaff;

        $this->ticket = $ticket;
        $this->includenotes = $notes;

        parent::__construct('', $psize);

        $this->_print();
	}

    function getTicket() {
        return $this->ticket;
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


// Task print
class Task2PDF extends mPDFWithLocalImages {

    var $options = array();
    var $task = null;

    function __construct($task, $options=array()) {

        $this->task = $task;
        $this->options = $options;

        parent::__construct('', $this->options['psize']);
        $this->_print();
    }

    function _print() {
        global $thisstaff, $cfg;

        if (!($task=$this->task) || !$thisstaff)
            return;

        ob_start();
        include STAFFINC_DIR.'templates/task-print.tmpl.php';
        $html = ob_get_clean();
        $this->WriteHtml($html, 0, true, true);

    }
}

?>
