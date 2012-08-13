<?php
/*********************************************************************
    ajax.content.php

    AJAX interface for content fetching...allowed methods.

    Peter Rotich <peter@osticket.com>
    Copyright (c)  2006-2012 osTicket
    http://www.osticket.com

    Released under the GNU General Public License WITHOUT ANY WARRANTY.
    See LICENSE.TXT for details.

    vim: expandtab sw=4 ts=4 sts=4:
**********************************************************************/

if(!defined('INCLUDE_DIR')) die('!');
	    
class ContentAjaxAPI extends AjaxController {
   
    function log($id) {

        if($id && ($log=Log::lookup($id))) {
            $content=sprintf('<div style="width:500px;">&nbsp;<strong>%s</strong><br><p>%s</p>
                    <hr><strong>Log Date:</strong> <em>%s</em> <strong>IP Address:</strong> <em>%s</em></div>',
                    $log->getTitle(),
                    Format::display(str_replace(',',', ',$log->getText())),
                    Format::db_daydatetime($log->getCreateDate()),
                    $log->getIP());
        }else {
            $content='<div style="width:295px;">&nbsp;<strong>Error:</strong>Unknown or invalid log ID</div>';
        }

        return $content;
    }

    function ticket_variables() {

        $content='
<div style="width:600px;">
    <h2>Ticket Variables</h2>
    Please note that non-base variables depends on the context of use.
    <br/>
    <table width="100%" border="0" cellspacing=1 cellpadding=2>
        <tr><td width="50%" valign="top"><b>Base Variables</b></td><td><b>Other Variables</b></td></tr>
        <tr>
            <td width="50%" valign="top">
                <table width="100%" border="0" cellspacing=1 cellpadding=1>
                    <tr><td width="100">%id</td><td>Ticket ID (internal ID)</td></tr>
                    <tr><td>%ticket</td><td>Ticket number (external ID)</td></tr>
                    <tr><td>%email</td><td>Email address</td></tr>
                    <tr><td>%name</td><td>Full name</td></tr>
                    <tr><td>%subject</td><td>Subject</td></tr>
                    <tr><td>%topic</td><td>Help topic (web only)</td></tr>
                    <tr><td>%phone</td><td>Phone number | ext</td></tr>
                    <tr><td>%status</td><td>Status</td></tr>
                    <tr><td>%priority</td><td>Priority</td></tr>
                    <tr><td>%dept</td><td>Department</td></tr>
                    <tr><td>%assigned</td><td>Assigned staff or team (if any)</td></tr>
                    <tr><td>%createdate</td><td>Date created</td></tr>
                    <tr><td>%duedate</td><td>Due date</td></tr>
                    <tr><td>%closedate</td><td>Date closed</td></tr>
                </table>
            </td>
            <td valign="top">
                <table width="100%" border="0" cellspacing=1 cellpadding=1>
                    <tr><td width="100">%message</td><td>Message (incoming)</td></tr>
                    <tr><td>%response</td><td>Response (outgoing)</td></tr>
                    <tr><td>%note</td><td>Internal/transfer note</td></tr>
                    <tr><td>%staff</td><td>Staff\'s name (alert/notices)</td></tr>
                    <tr><td>%assignee</td><td>Assigned staff</td></tr>
                    <tr><td>%assigner</td><td>Staff assigning the ticket</td></tr>
                    <tr><td>%url</td><td>osTicket\'s base url (FQDN)</td></tr>
                    <tr><td>%auth</td><td>Client authentication token</td></tr>
                    <tr><td>%clientlink</td><td>Client auto-login link</td></tr>
                </table>
            </td>
        </tr>
    </table>
</div>';

        return $content;
    }
}
?>
