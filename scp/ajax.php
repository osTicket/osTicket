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
        url('^faq/(?P<id>\d+)/access', 'manageFaqAccess'),
        url_get('^faq/(?P<id>\d+)$', 'faq')
    )),
    url('^/content/', patterns('ajax.content.php:ContentAjaxAPI',
        url_get('^log/(?P<id>\d+)', 'log'),
        url_get('^context$', 'context'),
        url_get('^ticket_variables', 'ticket_variables'),
        url_get('^signature/(?P<type>\w+)(?:/(?P<id>\d+))?$', 'getSignature'),
        url_get('^(?P<id>\d+)/(?:(?P<lang>\w+)/)?manage$', 'manageContent'),
        url_get('^(?P<id>[\w-]+)/(?:(?P<lang>\w+)/)?manage$', 'manageNamedContent'),
        url_post('^(?P<id>\d+)(?:/(?P<lang>\w+))?$', 'updateContent')
    )),
    url('^/config/', patterns('ajax.config.php:ConfigAjaxAPI',
        url_get('^scp', 'scp'),
        url_get('^links', 'templateLinks'),
        url_get('^date-format', 'dateFormat')
    )),
    url('^/form/', patterns('ajax.forms.php:DynamicFormsAjaxAPI',
        url_get('^help-topic/(?P<id>\d+)$', 'getFormsForHelpTopic'),
        url_get('^field-config/(?P<id>\d+)$', 'getFieldConfiguration'),
        url_post('^field-config/(?P<id>\d+)$', 'saveFieldConfiguration'),
        url_delete('^answer/(?P<entry>\d+)/(?P<field>\d+)$', 'deleteAnswer'),
        url_post('^upload/(\d+)?$', 'upload'),
        url_post('^upload/(\w+)?$', 'attach'),
        url_post('^upload/(?P<object>ticket|task)/(\w+)$', 'attach'),
        url_get('^(?P<id>\d+)/fields/view$', 'getAllFields')
    )),
    url('^/filter/', patterns('ajax.filter.php:FilterAjaxAPI',
        url_get('^action/(?P<type>\w+)/config$', 'getFilterActionForm')
    )),
    url('^/schedule/', patterns('ajax.schedule.php:ScheduleAjaxAPI',
       url('^add$', 'add'),
       url('^(?P<id>\d+)/clone$', 'cloneSchedule'),
       url('^(?P<id>\d+)/diagnostic$', 'diagnostic'),
       url_post('^(?P<id>\d+)/delete-entries$', 'deleteEntries'),
       url('^(?P<id>\d+)/entry/add$', 'addEntry'),
       url('^(?P<sid>\d+)/entry/(?P<eid>\d+)/update$', 'updateEntry')
    )),
    url('^/list/', patterns('ajax.forms.php:DynamicFormsAjaxAPI',
        url_get('^(?P<list>\w+)/items$', 'getListItems'),
        url_get('^(?P<list>\w+)/items/search$', 'searchListItems'),
        url_get('^(?P<list>\w+)/item/(?P<id>\d+)/update$', 'getListItem'),
        url_post('^(?P<list>\w+)/item/(?P<id>\d+)/update$', 'saveListItem'),
        url_get('^(?P<list>\w+)/items/(?P<id>\d+)/preview$', 'previewListItem'),
        url('^(?P<list>\w+)/item/add$', 'addListItem'),
        url('^(?P<list>\w+)/import$', 'importListItems'),
        url('^(?P<list>\w+)/manage$', 'massManageListItems'),
        url_post('^(?P<list>\w+)/delete$', 'deleteItems'),
        url_post('^(?P<list>\w+)/disable$', 'disableItems'),
        url_post('^(?P<list>\w+)/enable$', 'undisableItems')
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
        url_post('^/(?P<id>\d+)/forms/manage$', 'updateForms'),
        url('^/(?P<id>\d+)/tickets/export$', 'exportTickets')
    )),
    url('^/orgs', patterns('ajax.orgs.php:OrgsAjaxAPI',
        url_get('^$', 'search'),
        url_get('^/search$', 'search'),
        url_get('^/(?P<id>\d+)$', 'getOrg'),
        url_post('^/(?P<id>\d+)$', 'updateOrg'),
        url_post('^/(?P<id>\d+)/profile$', 'updateOrg', array(true)),
        url('^/(?P<id>\d+)/tickets/export$', 'exportTickets'),
        url_get('^/(?P<id>\d+)/edit$', 'editOrg'),
        url_get('^/lookup/form$', 'lookup'),
        url_post('^/lookup$', 'lookup'),
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
    url('^/lock/', patterns('ajax.tickets.php:TicketsAjaxAPI',
        url_post('^ticket/(?P<tid>\d+)$', 'acquireLock'),
        url_post('^(?P<id>\d+)/ticket/(?P<tid>\d+)/renew', 'renewLock'),
        url_post('^(?P<id>\d+)/release', 'releaseLock')
    )),
    url('^/tickets/', patterns('ajax.tickets.php:TicketsAjaxAPI',
        url_get('^(?P<tid>\d+)/change-user$', 'changeUserForm'),
        url_post('^(?P<tid>\d+)/change-user$', 'changeUser'),
        url_get('^(?P<tid>\d+)/user$', 'viewUser'),
        url_post('^(?P<tid>\d+)/user$', 'updateUser'),
        url_get('^(?P<tid>\d+)/preview', 'previewTicket'),
        url_get('^(?P<tid>\d+)/forms/manage$', 'manageForms'),
        url_post('^(?P<tid>\d+)/forms/manage$', 'updateForms'),
        url_get('^(?P<tid>\d+)/merge$', 'mergeTickets'),
        url_post('^(?P<tid>\d+)/merge$', 'updateMerge'),
        url_get('^(?P<tid>\d+)/link', 'mergeTickets'),
        url_post('^(?P<tid>\d+)/link', 'updateMerge'),
        url_get('^(?P<tid>\d+)/merge/preview$', 'previewMerge'),
        url_get('^(?P<tid>\d+)/relations', 'relations'),
        url_get('^(?P<tid>\d+)/canned-resp/(?P<cid>\w+).(?P<format>json|txt)', 'cannedResponse'),
        url_get('^(?P<tid>\d+)/status/(?P<status>\w+)(?:/(?P<sid>\d+))?$', 'changeTicketStatus'),
        url_post('^(?P<tid>\d+)/status$', 'setTicketStatus'),
        url('^(?P<tid>\d+)/thread/(?P<thread_id>\d+)/(?P<action>\w+)$', 'triggerThreadAction'),
        url_get('^status/(?P<status>\w+)(?:/(?P<sid>\d+))?$', 'changeSelectedTicketsStatus'),
        url_post('^status/(?P<state>\w+)$', 'setSelectedTicketsStatus'),
        url_get('^(?P<tid>\d+)/tasks$', 'tasks'),
        url('^(?P<tid>\d+)/add-task$', 'addTask'),
        url_get('^(?P<tid>\d+)/tasks/(?P<id>\d+)/view$', 'task'),
        url_post('^(?P<tid>\d+)/tasks/(?P<id>\d+)$', 'task'),
        url_get('^lookup', 'lookup'),
        url_get('^number-lookup', 'lookupByNumber'),
        url('^mass/(?P<action>\w+)(?:/(?P<what>\w+))?', 'massProcess'),
        url('^(?P<tid>\d+)/transfer$', 'transfer'),
        url('^(?P<tid>\d+)/field/(?P<fid>\d+)/edit$', 'editField'),
        url('^(?P<tid>\d+)/field/(?P<field>\w+)/edit$', 'editField'),
        url('^(?P<tid>\d+)/field/(?P<fid>\d+)/view$', 'viewField'),
        url('^(?P<tid>\d+)/field/(?P<field>\w+)/view$', 'viewField'),
        url('^(?P<tid>\d+)/assign(?:/(?P<to>\w+))?$', 'assign'),
        url('^(?P<tid>\d+)/release$', 'release'),
        url('^(?P<tid>\d+)/mark/(?P<action>\w+)$', 'markAs'),
        url('^(?P<tid>\d+)/refer(?:/(?P<to>\w+))?$', 'refer'),
        url('^(?P<tid>\d+)/referrals$', 'referrals'),
        url('^(?P<tid>\d+)/claim$', 'claim'),
        url('^export/(?P<id>\d+)$', 'export'),
        url('^export/adhoc,(?P<key>[\w=/+]+)$', 'export'),
        url('^search', patterns('ajax.search.php:SearchAjaxAPI',
            url_get('^$', 'getAdvancedSearchDialog'),
            url_post('^$', 'doSearch'),
            url_get('^/(?P<id>\d+)$', 'editSearch'),
            url_get('^/adhoc,(?P<key>[\w=/+]+)$', 'getAdvancedSearchDialog'),
            url_get('^/create$', 'createSearch'),
            url_post('^/(?P<id>\d+)/save$', 'saveSearch'),
            url_post('^/save$', 'saveSearch'),
            url_delete('^/(?P<id>\d+)$', 'deleteSearch'),
            url_get('^/field/(?P<id>[\w_!:]+)$', 'addField'),
            url('^/column/edit/(?P<id>\d+)$', 'editColumn'),
            url('^/sort/edit/(?P<id>\d+)$', 'editSort'),
            url_post('^(?P<id>\d+)/delete$', 'deleteQueues'),
            url_post('^(?P<id>\d+)/disable$', 'disableQueues'),
            url_post('^(?P<id>\d+)/enable$', 'undisableQueues')
        ))
    )),
    url('^/tasks/', patterns('ajax.tasks.php:TasksAjaxAPI',
        url_get('^(?P<tid>\d+)/preview$', 'preview'),
        url_get('^(?P<tid>\d+)/edit', 'edit'),
        url_post('^(?P<tid>\d+)/edit$', 'edit'),
        url('^(?P<tid>\d+)/field/(?P<fid>\d+)/edit$', 'editField'),
        url('^(?P<tid>\d+)/field/(?P<field>\w+)/edit$', 'editField'),
        url_get('^(?P<tid>\d+)/transfer', 'transfer'),
        url_post('^(?P<tid>\d+)/transfer$', 'transfer'),
        url('^(?P<tid>\d+)/assign(?:/(?P<to>\w+))?$', 'assign'),
        url('^(?P<tid>\d+)/claim$', 'claim'),
        url_get('^(?P<tid>\d+)/delete', 'delete'),
        url_post('^(?P<tid>\d+)/delete$', 'delete'),
        url('^(?P<tid>\d+)/close', 'close'),
        url('^(?P<tid>\d+)/reopen', 'reopen'),
        url_get('^(?P<tid>\d+)/view$', 'task'),
        url_post('^(?P<tid>\d+)$', 'task'),
        url('^(?P<tid>\d+)/thread/(?P<thread_id>\d+)/(?P<action>\w+)$', 'triggerThreadAction'),
        url('^add$', 'add'),
        url('^(?P<tid>\d+)/add', 'add'),
        url('^lookup', 'lookup'),
        url('^mass/(?P<action>\w+)(?:/(?P<what>\w+))?', 'massProcess')
    )),
    url('^/thread/', patterns('ajax.thread.php:ThreadAjaxAPI',
        url_get('^(?P<tid>\d+)/collaborators/(?P<manage>\d+)/preview$', 'previewCollaborators'),
        url_get('^(?P<tid>\d+)/collaborators/(?P<manage>\d+)$', 'showCollaborators'),
        url_post('^(?P<tid>\d+)/collaborators$', 'updateCollaborators'),
        url_get('^(?P<tid>\d+)/add-collaborator/(?P<type>\w+)/(?P<uid>\d+)$', 'addCollaborator'),
        url_get('^(?P<tid>\d+)/add-collaborator/(?P<type>\w+)/auth:(?P<bk>\w+):(?P<id>.+)$', 'addRemoteCollaborator'),
        url('^(?P<tid>\d+)/add-collaborator/(?P<type>\w+)$', 'addCollaborator'),
        url_get('^(?P<tid>\d+)/collaborators/(?P<cid>\d+)/view$', 'viewCollaborator'),
        url_post('^(?P<tid>\d+)/collaborators/(?P<cid>\d+)$', 'updateCollaborator')
    )),
    url('^/draft/', patterns('ajax.draft.php:DraftAjaxAPI',
        url_post('^(?P<id>\d+)$', 'updateDraft'),
        url_delete('^(?P<id>\d+)$', 'deleteDraft'),
        url_post('^(?P<id>\d+)/attach$', 'uploadInlineImage'),
        url_post('^(?P<namespace>[\w.]+)/attach$', 'uploadInlineImageEarly'),
        url_get('^(?P<namespace>[\w.]+)$', 'getDraft'),
        url_post('^(?P<namespace>[\w.]+)$', 'createDraft'),
        url_get('^images/browse$', 'getFileList')
    )),
    url('^/export/', patterns('ajax.export.php:ExportAjaxAPI',
        url('^(?P<id>\w+)/check$', 'check')
    )),
    url('^/note/', patterns('ajax.note.php:NoteAjaxAPI',
        url_get('^(?P<id>\d+)$', 'getNote'),
        url_post('^(?P<id>\d+)$', 'updateNote'),
        url_delete('^(?P<id>\d+)$', 'deleteNote'),
        url_post('^attach/(?P<ext_id>\w\d+)$', 'createNote')
    )),
    url('^/sequence/', patterns('ajax.sequence.php:SequenceAjaxAPI',
        url_get('^(?P<id>\d+)$', 'current'),
        url_get('^manage$', 'manage'),
        url_post('^manage$', 'manage')
    )),
    url_post('^/upgrader', array('ajax.upgrader.php:UpgraderAjaxAPI', 'upgrade')),
    url('^/help/', patterns('ajax.tips.php:HelpTipAjaxAPI',
        url_get('^tips/(?P<namespace>[\w_.]+)$', 'getTipsJson'),
        url_get('^(?P<lang>[\w_]+)?/tips/(?P<namespace>[\w_.]+)$', 'getTipsJsonForLang')
    )),
    url('^/i18n/', patterns('ajax.i18n.php:i18nAjaxAPI',
        url_get('^langs/all$', 'getConfiguredLanguages'),
        url_get('^langs$', 'getSecondaryLanguages'),
        url_get('^translate/(?P<tag>\w+)$', 'getTranslations'),
        url_post('^translate/(?P<tag>\w+)$', 'updateTranslations'),
        url_get('^(?P<lang>[\w_]+)/(?P<tag>\w+)$', 'getLanguageFile')
    )),
    url('^/admin', patterns('ajax.admin.php:AdminAjaxAPI',
        url('^/quick-add', patterns('ajax.admin.php:AdminAjaxAPI',
            url('^/department$', 'addDepartment'),
            url('^/team$', 'addTeam'),
            url('^/role$', 'addRole'),
            url('^/staff$', 'addStaff'),
            url('^/queue-column$', 'addQueueColumn'),
            url('^/queue-sort$', 'addQueueSort')
        )),
        url_get('^/role/(?P<id>\d+)/perms', 'getRolePerms')
    )),
    url('^/staff', patterns('ajax.staff.php:StaffAjaxAPI',
        url('^/(?P<id>\d+)/set-password$', 'setPassword'),
        url('^/(?P<id>\d+)/change-password$', 'changePassword'),
        url_get('^/(?P<id>\d+)/perms', 'getAgentPerms'),
        url('^/reset-permissions', 'resetPermissions'),
        url('^/change-department', 'changeDepartment'),
        url('^/(?P<id>\d+)/avatar/change', 'setAvatar'),
        url('^/(?P<id>\d+)/2fa/configure(?:/(?P<mfid>.+))?$', 'configure2FA'),
        url('^/(?P<id>\d+)/reset-2fa', 'reset2fA')
    )),
    url('^/queue/', patterns('ajax.search.php:SearchAjaxAPI',
        url('^(?P<id>\d+/)?preview$', 'previewQueue'),
        url_get('^(?P<id>\d+)$', 'getQueue'),
        url_get('^addColumn$', 'addColumn'),
        url_get('^condition/add$', 'addCondition'),
        url_get('^condition/addProperty$', 'addConditionProperty'),
        url_get('^counts$', 'collectQueueCounts'),
        url('^(?P<id>\d+)/delete$', 'deleteQueue')
    ))
);

Signal::send('ajax.scp', $dispatcher);

# Call the respective function
$rv = $dispatcher->resolve(Osticket::get_path_info());

// Indicate JSON response content-type
if (is_string($rv) && $rv[0] == '{')
    Http::response(200, $rv, 'application/json');

print $rv;
?>
