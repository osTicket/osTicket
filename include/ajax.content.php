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
            $content=sprintf('<div
                    style="width:500px;">&nbsp;<strong>%s</strong><br><p
                    style="white-space:pre-line;">%s</p>
                    <hr><strong>%s:</strong> <em>%s</em> <strong>%s:</strong> <em>%s</em></div>',
                    $log->getTitle(),
                    Format::display(str_replace(',',', ',$log->getText())),
                    __('Log Date'),
                    Format::db_daydatetime($log->getCreateDate()),
                    __('IP Address'),
                    $log->getIP());
        }else {
            $content='<div style="width:295px;">&nbsp;<strong>'.__('Error').':</strong>'.
                sprintf(__('%s: Unknown or invalid ID.'), __('log entry')).'</div>';
        }

        return $content;
    }

    function ticket_variables() {

        $content='
<div style="width:680px;">
    <h2>'.__('Ticket Variables').'</h2>
    '.__('Please note that non-base variables depend on the context of use. Visit osTicket Wiki for up to date documentation.').'
    <br/>
    <table width="100%" border="0" cellspacing=1 cellpadding=2>
        <tr><td width="55%" valign="top"><b>'.__('Base Variables').'</b></td><td><b>'.__('Other Variables').'</b></td></tr>
        <tr>
            <td width="55%" valign="top">
                <table width="100%" border="0" cellspacing=1 cellpadding=1>
                    <tr><td width="130">%{ticket.id}</td><td>'.__('Ticket ID').' ('.__('internal ID').')</td></tr>
                    <tr><td>%{ticket.number}</td><td>'.__('Ticket number').' ('.__('external ID').')</td></tr>
                    <tr><td>%{ticket.email}</td><td>'.__('Email address').'</td></tr>
                    <tr><td>%{ticket.name}</td><td>'.__('Full name').' &mdash;
                        <em>'.__('see name expansion').'</em></td></tr>
                    <tr><td>%{ticket.subject}</td><td>'.__('Subject').'</td></tr>
                    <tr><td>%{ticket.phone}</td><td>'.__('Phone number | ext').'</td></tr>
                    <tr><td>%{ticket.status}</td><td>'.__('Status').'</td></tr>
                    <tr><td>%{ticket.priority}</td><td>'.__('Priority').'</td></tr>
                    <tr><td>%{ticket.assigned}</td><td>'.__('Assigned agent and/or team').'</td></tr>
                    <tr><td>%{ticket.create_date}</td><td>'.__('Date created').'</td></tr>
                    <tr><td>%{ticket.due_date}</td><td>'.__('Due date').'</td></tr>
                    <tr><td>%{ticket.close_date}</td><td>'.__('Date closed').'</td></tr>
                    <tr><td>%{recipient.ticket_link}</td><td>'.__('Auth. token used for auto-login').'</td></tr>
                    <tr><td>%{ticket.client_link}</td><td>'.__('Client\'s ticket view link').'</td></tr>
                    <tr><td>%{recipient.ticket_link}</td><td>'.__('Agent\'s ticket view link').'</td></tr>
                    <tr><td colspan="2" style="padding:5px 0 5px 0;"><em>'.__('Expandable Variables (See Wiki)').'</em></td></tr>
                    <tr><td>%{ticket.<b>topic</b>}</td><td>'.__('Help topic').'</td></tr>
                    <tr><td>%{ticket.<b>dept</b>}</td><td>'.__('Department').'</td></tr>
                    <tr><td>%{ticket.<b>staff</b>}</td><td>'.__('Assigned/closing agent').'</td></tr>
                    <tr><td>%{ticket.<b>team</b>}</td><td>'.__('Assigned/closing team').'</td></tr>
                </table>
            </td>
            <td valign="top">
                <table width="100%" border="0" cellspacing=1 cellpadding=1>
                    <tr><td width="100">%{message}</td><td>'.__('Incoming message').'</td></tr>
                    <tr><td>%{response}</td><td>'.__('Outgoing response').'</td></tr>
                    <tr><td>%{comments}</td><td>'.__('Assign/transfer comments').'</td></tr>
                    <tr><td>%{note}</td><td>'.__('Internal note <em>(expandable)</em>').'</td></tr>
                    <tr><td>%{assignee}</td><td>'.__('Assigned agent/team').'</td></tr>
                    <tr><td>%{assigner}</td><td>'.__('Agent assigning the ticket').'</td></tr>
                    <tr><td>%{url}</td><td>'.__('osTicket\'s base url (FQDN)').'</td></tr>
                    <tr><td>%{reset_link}</td>
                        <td>'.__('Reset link used by the password reset feature').'</td></tr>
                </table>
                <table width="100%" border="0" cellspacing=1 cellpadding=1>
                    <tr><td colspan="2"><b>'.__('Name Expansion').'</b></td></tr>
                    <tr><td>.first</td><td>'.__('First Name').'</td></tr>
                    <tr><td>.middle</td><td>'.__('Middle Name(s)').'</td></tr>
                    <tr><td>.last</td><td>'.__('Last Name').'</td></tr>
                    <tr><td>.full</td><td>'.__('First Last').'</td></tr>
                    <tr><td>.legal</td><td>'.__('First M. Last').'</td></tr>
                    <tr><td>.short</td><td>'.__('First L.').'</td></tr>
                    <tr><td>.formal</td><td>'.__('Mr. Last').'</td></tr>
                    <tr><td>.shortformal</td><td>'.__('F. Last').'</td></tr>
                    <tr><td>.lastfirst</td><td>'.__('Last, First').'</td></tr>
                </table>
            </td>
        </tr>
    </table>
</div>';

        return $content;
    }

    function getSignature($type, $id=null) {
        global $thisstaff;

        if (!$thisstaff)
            Http::response(403, 'Login Required');

        switch ($type) {
        case 'none':
            break;
        case 'mine':
            echo Format::viewableImages($thisstaff->getSignature());
            break;
        case 'dept':
            if (!($dept = Dept::lookup($id)))
                Http::response(404, 'No such department');
            echo Format::viewableImages($dept->getSignature());
            break;
        default:
            Http::response(400, 'Unknown signature type');
            break;
        }
    }

    function manageContent($id, $lang=false) {
        global $thisstaff;

        if (!$thisstaff)
            Http::response(403, 'Login Required');

        $content = Page::lookup($id, $lang);
        include STAFFINC_DIR . 'templates/content-manage.tmpl.php';
    }

    function manageNamedContent($type, $lang=false) {
        global $thisstaff;

        if (!$thisstaff)
            Http::response(403, 'Login Required');

        $content = Page::lookup(Page::getIdByType($type, $lang));
        include STAFFINC_DIR . 'templates/content-manage.tmpl.php';
    }

    function updateContent($id) {
        global $thisstaff;

        if (!$thisstaff)
            Http::response(403, 'Login Required');
        elseif (!$_POST['name'] || !$_POST['body'])
            Http::response(422, 'Please submit name and body');
        elseif (!($content = Page::lookup($id)))
            Http::response(404, 'No such content');

        $vars = array_merge($content->getHashtable(), $_POST);
        $errors = array();
        if (!$content->save($id, $vars, $errors)) {
            if ($errors['err'])
                Http::response(422, $errors['err']);
            else
                Http::response(500, 'Unable to update content: '.print_r($errors, true));
        }
    }
}
?>
