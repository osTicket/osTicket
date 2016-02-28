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
<div style="width:740px;">
    <h2>'.__('Ticket Variables').'</h2>
    '.__('Please note that non-base variables depend on the context of use. Visit osTicket Wiki for up to date documentation.').'
    <br/>
    <table width="100%" border="0" cellspacing=1 cellpadding=2>
        <tr>
            <td width="50%" valign="top">
                <table width="100%" border="0" cellspacing=1 cellpadding=1 style="padding-bottom: 10px;">
                    <tr><td colspan="2" style="padding:5px 0 5px 0;"><em><b>'.__('Base Variables').'</b></em></td></tr>
                    <tr><td nowrap>%{ticket.id}</td><td>'.__('Ticket ID').' ('.__('internal ID').')</td></tr>
                    <tr><td nowrap>%{ticket.number}</td><td>'.__('Ticket number').' ('.__('external ID').')</td></tr>
                    <tr><td nowrap>%{ticket.email}</td><td>'.__('Email address').'</td></tr>
                    <tr><td nowrap>%{ticket.subject}</td><td>'.__('Subject').'</td></tr>
                    <tr><td nowrap>%{ticket.phone}</td><td>'.__('Phone number | ext').'</td></tr>
                    <tr><td nowrap>%{ticket.status}</td><td>'.__('Status').'</td></tr>
                    <tr><td nowrap>%{ticket.priority}</td><td>'.__('Priority').'</td></tr>
                    <tr><td nowrap>%{ticket.assigned}</td><td>'.__('Assigned agent and/or team').'</td></tr>
                    <tr><td nowrap>%{ticket.create_date}</td><td>'.__('Date created').'</td></tr>
                    <tr><td nowrap>%{ticket.due_date}</td><td>'.__('Due date').'</td></tr>
                    <tr><td nowrap>%{ticket.close_date}</td><td>'.__('Date closed').'</td></tr>
                    <tr><td nowrap>%{ticket.recipients}</td><td>'.__('List of all recipient names').'</td></tr>
                    <tr><td nowrap>%{recipient.ticket_link}</td><td>'.__('Auth. token used for auto-login').'<br/>
                    '.__('Agent\'s ticket view link').'</td></tr>
                    <tr><td nowrap>%{recipient.name.email}</td><td>'.__('Recipient Email').'</td></tr>
                </table>
                <table width="100%" border="0" cellspacing=1 cellpadding=1>
                    <tr><td colspan="2" style="padding:5px 0 5px 0;"><em><b>'.__('Other Variables').'</b></em></td></tr>
                    <tr><td nowrap>%{message}</td><td>'.__('Incoming message Variables').'</td></tr>
                    <tr><td nowrap>%{response}</td><td>'.__('Outgoing response').'</td></tr>
                    <tr><td nowrap>%{response.poster}</td><td>'.__('Name of responder').'</td></tr>
                    <tr><td nowrap>%{comments}</td><td>'.__('Assign/transfer comments').'</td></tr>
                    <tr><td nowrap>%{note}</td><td>'.__('Internal note').'</td></tr>
                    <tr><td nowrap>%{note.poster}</td><td>'.__('Internal note poster').'</td></tr>
                    <tr><td nowrap>%{note.title}</td><td>'.__('Internal note title').'</td></tr>
                    <tr><td nowrap>%{note.message}</td><td>'.__('Internal note message').'</td></tr>
                    <tr><td nowrap>%{assignee}</td><td>'.__('Assigned agent/team').'</td></tr>
                    <tr><td nowrap>%{assigner}</td><td>'.__('Agent assigning the ticket').'</td></tr>
                    <tr><td nowrap>%{url}</td><td>'.__('osTicket\'s base url (FQDN)').'</td></tr>
                    <tr><td nowrap>%{reset_link}</td><td>'.__('Reset link used by the password reset feature').'</td></tr>
                    <tr><td nowrap>%{signature}</td><td>'.__('Signature').'</td></tr>
                    <tr><td nowrap>%{company.name}</td><td>'.__('Company Name').'</td></tr>
                    <tr><td nowrap>%{poster.name}</td><td>'.__('Posters Name').'</td></tr>
                </table>
            </td>
            <td width="50%" valign="top">
                <table width="100%" border="0" cellspacing=1 cellpadding=1 style="padding-bottom: 10px;">
                    <tr><td colspan="2" style="padding:5px 0 5px 0;"><em><b>'.__('Ticket Variables').'</b></em></td></tr>
                    <tr><td nowrap>%{ticket.auth_token}</td><td>'.__('Auth. token used for auto-login').'</td></tr>
                    <tr><td nowrap>%{ticket.staff_link}</td><td>'.__('Staff\'s ticket view link').'</td></tr>
                    <tr><td nowrap>%{ticket.topic}</td><td>'.__('Help topic').'</td></tr>
                    <tr><td nowrap>%{ticket.topic.name}</td><td>'.__('Ticket Topic Name').'</td></tr>
                    <tr><td nowrap>%{ticket.dept}</td><td>'.__('Department').'</td></tr>
                    <tr><td nowrap>%{ticket.dept.name}</td><td>'.__('Department Name').'</td></tr>
                    <tr><td nowrap>%{ticket.dept.manager.name}</td><td>'.__('Department Manager Name').'</td></tr>
                    <tr><td nowrap>%{ticket.staff}</td><td>'.__('Assigned/closing staff').'</td></tr>
                    <tr><td nowrap>%{ticket.team}</td><td>'.__('Assigned/closing team').'</td></tr>
                    <tr><td nowrap>%{ticket.staff}</td><td>'.__('Assigned/closing agent').'</td></tr>
                    <tr><td nowrap>%{ticket.team}</td><td>'.__('Assigned/closing team').'</td></tr>
                    <tr><td nowrap>%{ticket.thread}</td><td>'.__('Ticket Thread').'</td></tr>
                    <tr><td nowrap>%{ticket.thread.original}</td><td>'.__('Original ticket body').'</td></tr>
                    <tr><td nowrap>%{ticket.thread.lastmessage}</td><td>'.__('To get the last message').'</td></tr>
                </table>
                <table width="100%" border="0" cellspacing=1 cellpadding=1>
                    <tr><td colspan="2" style="padding:5px 0 5px 0;"><em><b>'.__('Name Variables<br>(replace ### with ticket, staff or recipient)').'</b></em></td></tr>
                    <tr><td nowrap>%{ticket.name}</td><td>'.__('Full name').'</td></tr>
                    <tr><td nowrap>%{staff.name}</td><td>'.__('Staff Full name').'</td></tr>
                    <tr><td nowrap>%{recipient.name}</td><td>'.__('Recipient Full name').'</td></tr>
                    <tr><td nowrap>%{###.name.first}</td><td>'.__('First name').'</td></tr>
                    <tr><td nowrap>%{###.name.last}</td><td>'.__('Last name').'</td></tr>
                    <tr><td nowrap>%{###.name.lastfirst}</td><td>'.__('Last, First name').'</td></tr>
                    <tr><td nowrap>%{###.name.short}</td><td>'.__('Short name - First L.').'</td></tr>
                    <tr><td nowrap>%{###.name.shortformal}</td><td>'.__('Short Formal - F. Last').'</td></tr>
                    <tr><td nowrap>%{###.name.full}</td><td>'.__('Full').'</td></tr>
                    <tr><td nowrap>%{###.name.original}</td><td>'.__('Original').'</td></tr>
                    <tr><td nowrap>%{###.name.formal}</td><td>'.__('Formal').'</td></tr>
                    <tr><td nowrap>%{###.name.initials}</td><td>'.__('Initials').'</td></tr>
                    <tr><td nowrap>%{###.name.legal}</td><td>'.__('Legal').'</td></tr>
                </table>';
            global $cfg;
            if ($cfg->isLimeSurveyEnabled()) {
        $content= $content . '
                <table width="100%" border="0" cellspacing=1 cellpadding=1>
                    <tr><td colspan="2" style="padding:5px 0 5px 0;"><em><b>'.__('Survey Variables').'</b></em></td></tr>
                    <tr><td nowrap>%{SurveyURL}</td><td>'.__('Survey URL Link').'</td></tr>
                </table>';
            }
        $content= $content . '
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
        $info = $content->getHashtable();
        include STAFFINC_DIR . 'templates/content-manage.tmpl.php';
    }

    function manageNamedContent($type, $lang=false) {
        global $thisstaff;

        if (!$thisstaff)
            Http::response(403, 'Login Required');

        $content = Page::lookup(Page::getIdByType($type, $lang));
        $info = $content->getHashtable();
        include STAFFINC_DIR . 'templates/content-manage.tmpl.php';
    }

    function updateContent($id) {
        global $thisstaff;

        if (!$thisstaff)
            Http::response(403, 'Login Required');
        elseif (!($content = Page::lookup($id)))
            Http::response(404, 'No such content');

        if (!isset($_POST['body']))
            $_POST['body'] = '';

        $vars = array_merge($content->getHashtable(), $_POST);
        $errors = array();
        // Allow empty content for the staff banner
        if ($content->save($id, $vars, $errors,
            $content->getType() == 'banner-staff')
        ) {
            Http::response(201, 'Have a great day!');
        }
        if (!$errors['err'])
            $errors['err'] = __('Correct the error(s) below and try again!');
        $info = $_POST;
        $errors = Format::htmlchars($errors);
        include STAFFINC_DIR . 'templates/content-manage.tmpl.php';
    }
}
?>
