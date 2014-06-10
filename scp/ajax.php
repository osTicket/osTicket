<?php
/*********************************************************************
    ajax.php

    Ajax utils interface.

    Peter Rotich <peter@osticket.com>
    Copyright (c)  2006-2013 osTicket
    http://www.osticket.com

    Released under the GNU General Public License WITHOUT ANY WARRANTY.
    See LICENSE.TXT for details.

    vim: expandtab sw=4 ts=4 sts=4:
**********************************************************************/
# Override staffLoginPage() defined in staff.inc.php to return an
# HTTP/Forbidden status rather than the actual login page.
# XXX: This should be moved to the AjaxController class
function staffLoginPage($msg='Unauthorized') {
    Http::response(403,'Must login: '.Format::htmlchars($msg));
    exit;
}

define('AJAX_REQUEST', 1);
require('staff.inc.php');

//Clean house...don't let the world see your crap.
ini_set('display_errors','0'); //Disable error display
ini_set('display_startup_errors','0');

//TODO: disable direct access via the browser? i,e All request must have REFER?
if(!defined('INCLUDE_DIR'))	Http::response(500, 'Server configuration error');

require_once INCLUDE_DIR.'/class.dispatcher.php';
require_once INCLUDE_DIR.'/class.ajax.php';
$dispatcher = patterns('',
    url('^/kb/', patterns('ajax.kbase.php:KbaseAjaxAPI',
        # Send ticket-id as a query arg => canned-response/33?ticket=83
        url_get('^canned-response/(?P<id>\d+).(?P<format>json|txt)', 'cannedResp'),
        url_get('^faq/(?P<id>\d+)', 'faq')
    )),
    url('^/content/', patterns('ajax.content.php:ContentAjaxAPI',
        url_get('^log/(?P<id>\d+)', 'log'),
        url_get('^ticket_variables', 'ticket_variables'),
        url_get('^signature/(?P<type>\w+)(?:/(?P<id>\d+))?$', 'getSignature'),
        url_get('^(?P<id>\d+)/(?:(?P<lang>\w+)/)?manage$', 'manageContent'),
        url_get('^(?P<id>[\w-]+)/(?:(?P<lang>\w+)/)?manage$', 'manageNamedContent'),
        url_post('^(?P<id>\d+)(?:/(?P<lang>\w+))?$', 'updateContent')
    )),
    url('^/config/', patterns('ajax.config.php:ConfigAjaxAPI',
        url_get('^scp', 'scp')
    )),
    url('^/form/', patterns('ajax.forms.php:DynamicFormsAjaxAPI',
        url_get('^help-topic/(?P<id>\d+)$', 'getFormsForHelpTopic'),
        url_get('^field-config/(?P<id>\d+)$', 'getFieldConfiguration'),
        url_post('^field-config/(?P<id>\d+)$', 'saveFieldConfiguration'),
        url_delete('^answer/(?P<entry>\d+)/(?P<field>\d+)$', 'deleteAnswer')
    )),
    url('^/list/', patterns('ajax.forms.php:DynamicFormsAjaxAPI',
        url_get('^item/(?P<id>\d+)/properties$', 'getListItemProperties'),
        url_post('^item/(?P<id>\d+)/properties$', 'saveListItemProperties')
    )),
    url('^/report/overview/', patterns('ajax.reports.php:OverviewReportAjaxAPI',
        # Send
        url_get('^graph$', 'getPlotData'),
        url_get('^table/groups$', 'enumTabularGroups'),
        url_get('^table/export$', 'downloadTabularData'),
        url_get('^table$', 'getTabularData')
    )),
    url('^/users', patterns('ajax.users.php:UsersAjaxAPI',
        url_get('^$', 'search'),
        url_get('^/local$', 'search', array('local')),
        url_get('^/remote$', 'search', array('remote')),
        url_get('^/(?P<id>\d+)$', 'getUser'),
        url_post('^/(?P<id>\d+)$', 'updateUser'),
        url_get('^/(?P<id>\d+)/preview$', 'preview'),
        url_get('^/(?P<id>\d+)/edit$', 'editUser'),
        url('^/lookup$', 'getUser'),
        url_get('^/lookup/form$', 'lookup'),
        url_post('^/lookup/form$', 'addUser'),
        url_get('^/add$', 'addUser'),
        url('^/import$', 'importUsers'),
        url_get('^/select$', 'selectUser'),
        url_get('^/select/(?P<id>\d+)$', 'selectUser'),
        url_get('^/select/auth:(?P<bk>\w+):(?P<id>.+)$', 'addRemoteUser'),
        url_get('^/(?P<id>\d+)/register$', 'register'),
        url_post('^/(?P<id>\d+)/register$', 'register'),
        url_get('^/(?P<id>\d+)/delete$', 'delete'),
        url_post('^/(?P<id>\d+)/delete$', 'delete'),
        url_get('^/(?P<id>\d+)/manage(?:/(?P<target>\w+))?$', 'manage'),
        url_post('^/(?P<id>\d+)/manage(?:/(?P<target>\w+))?$', 'manage'),
        url_get('^/(?P<id>\d+)/org(?:/(?P<orgid>\d+))?$', 'updateOrg'),
        url_post('^/(?P<id>\d+)/org$', 'updateOrg'),
        url_get('^/staff$', 'searchStaff'),
        url_post('^/(?P<id>\d+)/note$', 'createNote'),
        url_get('^/(?P<id>\d+)/forms/manage$', 'manageForms'),
        url_post('^/(?P<id>\d+)/forms/manage$', 'updateForms')
    )),
    url('^/orgs', patterns('ajax.orgs.php:OrgsAjaxAPI',
        url_get('^$', 'search'),
        url_get('^/search$', 'search'),
        url_get('^/(?P<id>\d+)$', 'getOrg'),
        url_post('^/(?P<id>\d+)$', 'updateOrg'),
        url_post('^/(?P<id>\d+)/profile$', 'updateOrg', array(true)),
        url_get('^/(?P<id>\d+)/edit$', 'editOrg'),
        url_get('^/lookup/form$', 'lookup'),
        url_post('^/lookup/form$', 'addOrg'),
        url_get('^/add$', 'addOrg'),
        url_post('^/add$', 'addOrg'),
        url_get('^/select$', 'selectOrg'),
        url_get('^/select/(?P<id>\d+)$', 'selectOrg'),
        url_get('^/(?P<id>\d+)/add-user(?:/(?P<userid>\d+))?$', 'addUser'),
        url_get('^/(?P<id>\d+)/add-user(?:/auth:(?P<userid>.+))?$', 'addUser', array(true)),
        url_post('^/(?P<id>\d+)/add-user$', 'addUser'),
        url('^/(?P<id>\d+)/import-users$', 'importUsers'),
        url_get('^/(?P<id>\d+)/delete$', 'delete'),
        url_delete('^/(?P<id>\d+)/delete$', 'delete'),
        url_post('^/(?P<id>\d+)/note$', 'createNote'),
        url_get('^/(?P<id>\d+)/forms/manage$', 'manageForms'),
        url_post('^/(?P<id>\d+)/forms/manage$', 'updateForms')
    )),
    url('^/tickets/', patterns('ajax.tickets.php:TicketsAjaxAPI',
        url_get('^(?P<tid>\d+)/change-user$', 'changeUserForm'),
        url_post('^(?P<tid>\d+)/change-user$', 'changeUser'),
        url_get('^(?P<tid>\d+)/user$', 'viewUser'),
        url_post('^(?P<tid>\d+)/user$', 'updateUser'),
        url_get('^(?P<tid>\d+)/preview', 'previewTicket'),
        url_post('^(?P<tid>\d+)/lock$', 'acquireLock'),
        url_post('^(?P<tid>\d+)/lock/(?P<id>\d+)/renew', 'renewLock'),
        url_post('^(?P<tid>\d+)/lock/(?P<id>\d+)/release', 'releaseLock'),
        url_get('^(?P<tid>\d+)/collaborators/preview$', 'previewCollaborators'),
        url_get('^(?P<tid>\d+)/collaborators$', 'showCollaborators'),
        url_post('^(?P<tid>\d+)/collaborators$', 'updateCollaborators'),
        url_get('^(?P<tid>\d+)/add-collaborator/(?P<uid>\d+)$', 'addCollaborator'),
        url_get('^(?P<tid>\d+)/add-collaborator/auth:(?P<bk>\w+):(?P<id>.+)$', 'addRemoteCollaborator'),
        url('^(?P<tid>\d+)/add-collaborator$', 'addCollaborator'),
        url_get('^lookup', 'lookup'),
        url_get('^search', 'search'),
        url_get('^(?P<tid>\d+)/forms/manage$', 'manageForms'),
        url_post('^(?P<tid>\d+)/forms/manage$', 'updateForms'),
        url_get('^(?P<tid>\d+)/canned-resp/(?P<cid>\w+).(?P<format>json|txt)', 'cannedResponse')
    )),
    url('^/collaborators/', patterns('ajax.tickets.php:TicketsAjaxAPI',
        url_get('^(?P<cid>\d+)/view$', 'viewCollaborator'),
        url_post('^(?P<cid>\d+)$', 'updateCollaborator')
    )),
    url('^/draft/', patterns('ajax.draft.php:DraftAjaxAPI',
        url_post('^(?P<id>\d+)$', 'updateDraft'),
        url_delete('^(?P<id>\d+)$', 'deleteDraft'),
        url_post('^(?P<id>\d+)/attach$', 'uploadInlineImage'),
        url_get('^(?P<namespace>[\w.]+)$', 'getDraft'),
        url_post('^(?P<namespace>[\w.]+)$', 'createDraft'),
        url_get('^images/browse$', 'getFileList')
    )),
    url('^/note/', patterns('ajax.note.php:NoteAjaxAPI',
        url_get('^(?P<id>\d+)$', 'getNote'),
        url_post('^(?P<id>\d+)$', 'updateNote'),
        url_delete('^(?P<id>\d+)$', 'deleteNote'),
        url_post('^attach/(?P<ext_id>\w\d+)$', 'createNote')
    )),
    url_post('^/upgrader', array('ajax.upgrader.php:UpgraderAjaxAPI', 'upgrade')),
    url('^/help/', patterns('ajax.tips.php:HelpTipAjaxAPI',
        url_get('^tips/(?P<namespace>[\w_.]+)$', 'getTipsJson'),
        url_get('^(?P<lang>[\w_]+)?/tips/(?P<namespace>[\w_.]+)$', 'getTipsJsonForLang')
    ))
);

Signal::send('ajax.scp', $dispatcher);

# Call the respective function
print $dispatcher->resolve($ost->get_path_info());
?>
