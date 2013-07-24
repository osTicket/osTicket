<?php
/*********************************************************************
    ajax.content.php

    AJAX interface for content fetching...allowed methods.

    Peter Rotich <peter@osticket.com>
    Copyright (c)  2006-2013 osTicket
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
<div style="width:680px;">
    <h2>Ticket Variables</h2>
    Please note that non-base variables depend on the context of use. Visit osTicket Wiki for up-to-date documentation.
    <br/>
    <table width="100%" border="0" cellspacing=1 cellpadding=2>
        <tr><td width="55%" valign="top"><b>Base Variables</b></td><td><b>Other Variables</b></td></tr>
        <tr>
            <td width="55%" valign="top">
                <table width="100%" border="0" cellspacing=1 cellpadding=1>
                    <tr><td width="130">%{ticket.id}</td><td>Ticket ID (internal ID)</td></tr>
                    <tr><td>%{ticket.number}</td><td>Ticket number (external ID)</td></tr>
                    <tr><td>%{ticket.email}</td><td>Email address</td></tr>
                    <tr><td>%{ticket.name}</td><td>Full name</td></tr>
                    <tr><td>%{ticket.subject}</td><td>Subject</td></tr>
                    <tr><td>%{ticket.phone}</td><td>Phone number | ext</td></tr>
                    <tr><td>%{ticket.status}</td><td>Status</td></tr>
                    <tr><td>%{ticket.priority}</td><td>Priority</td></tr>
                    <tr><td>%{ticket.assigned}</td><td>Assigned staff and/or team</td></tr>
                    <tr><td>%{ticket.create_date}</td><td>Date created</td></tr>
                    <tr><td>%{ticket.due_date}</td><td>Due date</td></tr>
                    <tr><td>%{ticket.close_date}</td><td>Date closed</td></tr>
                    <tr><td>%{ticket.auth_token}</td><td>Auth. token used for auto-login</td></tr>
                    <tr><td>%{ticket.client_link}</td><td>Client\'s ticket view link</td></tr>
                    <tr><td>%{ticket.staff_link}</td><td>Staff\'s ticket view link</td></tr>
                    <tr><td colspan="2" style="padding:5px 0 5px 0;"><em>Expandable Variables (See Wiki)</em></td></tr>
                    <tr><td>%{ticket.<b>topic</b>}</td><td>Help topic</td></tr>
                    <tr><td>%{ticket.<b>dept</b>}</td><td>Department</td></tr>
                    <tr><td>%{ticket.<b>staff</b>}</td><td>Assigned/closing staff</td></tr>
                    <tr><td>%{ticket.<b>team</b>}</td><td>Assigned/closing team</td></tr>
                </table>
            </td>
            <td valign="top">
                <table width="100%" border="0" cellspacing=1 cellpadding=1>
                    <tr><td width="100">%{message}</td><td>Incoming message</td></tr>
                    <tr><td>%{response}</td><td>Outgoing response</td></tr>
                    <tr><td>%{comments}</td><td>Assign/transfer comments</td></tr>
                    <tr><td>%{note}</td><td>Internal note <em>(expandable)</em></td></tr>
                    <tr><td>%{assignee}</td><td>Assigned staff/team</td></tr>
                    <tr><td>%{assigner}</td><td>Staff assigning the ticket</td></tr>
                    <tr><td>%{url}</td><td>osTicket\'s base url (FQDN)</td></tr>
                    <tr><td>%{reset_link}</td>
                        <td>Reset link used by the password reset feature</td></tr>
                </table>
            </td>
        </tr>
    </table>
</div>';

        return $content;
    }
}
?>
