<?php
/*********************************************************************
    ajax.kbase.php

    AJAX interface for knowledge base related...allowed methods.

    Peter Rotich <peter@osticket.com>
    Copyright (c)  2006-2013 osTicket
    http://www.osticket.com

    Released under the GNU General Public License WITHOUT ANY WARRANTY.
    See LICENSE.TXT for details.

    vim: expandtab sw=4 ts=4 sts=4:
**********************************************************************/
if(!defined('INCLUDE_DIR')) die('!');


class KbaseAjaxAPI extends AjaxController {

    function cannedResp($id, $format='') {
        global $thisstaff, $cfg;

        include_once(INCLUDE_DIR.'class.canned.php');

        if(!$id || !($canned=Canned::lookup($id)) || !$canned->isEnabled())
            Http::response(404, 'No such premade reply');

        //Load ticket.
        if($_GET['tid']) {
            include_once(INCLUDE_DIR.'class.ticket.php');
            $ticket = Ticket::lookup($_GET['tid']);
        }

        $resp = array();
        switch($format) {
            case 'json':
                $resp['id'] = $canned->getId();
                $resp['ticket'] = $canned->getTitle();
                $resp['response'] = $ticket
                    ? $ticket->replaceVars($canned->getResponseWithImages())
                    : $canned->getResponseWithImages();
                $resp['files'] = $canned->attachments->getSeparates();

                if (!$cfg->isHtmlThreadEnabled()) {
                    $resp['response'] = Format::html2text($resp['response'], 90);
                    $resp['files'] += $canned->attachments->getInlines();
                }

                $response = $this->json_encode($resp);
                break;

            case 'txt':
            default:
                $response =$ticket?$ticket->replaceVars($canned->getResponse()):$canned->getResponse();

                if (!$cfg->isHtmlThreadEnabled())
                    $response = Format::html2text($response, 90);
        }

        return $response;
    }

    function faq($id, $format='html') {
        //XXX: user ajax->getThisStaff() (nolint)
        global $thisstaff;
        include_once(INCLUDE_DIR.'class.faq.php');

        if(!($faq=FAQ::lookup($id)))
            return null;

        //TODO: $fag->getJSON() for json format. (nolint)
        $resp = sprintf(
                '<div style="width:650px;">
                 <strong>%s</strong><div class="thread-body">%s</div>
                 <div class="clear"></div>
                 <div class="faded">Last updated %s</div>
                 <hr>
                 <a href="faq.php?id=%d">View</a> | <a href="faq.php?id=%d">Attachments (%s)</a>',
                $faq->getQuestion(),
                $faq->getAnswerWithImages(),
                Format::db_daydatetime($faq->getUpdateDate()),
                $faq->getId(),
                $faq->getId(),
                $faq->getNumAttachments());
        if($thisstaff && $thisstaff->canManageFAQ()) {
            $resp.=sprintf(' | <a href="faq.php?id=%d&a=edit">Edit</a>',$faq->getId());

        }
        $resp.='</div>';

        return $resp;
    }
}
?>
