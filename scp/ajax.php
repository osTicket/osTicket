<?php
/*********************************************************************
    ajax.php

    Ajax utils interface.

    Peter Rotich <peter@osticket.com>
    Copyright (c)  2006-2012 osTicket
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

require('staff.inc.php');

//Clean house...don't let the world see your crap.
ini_set('display_errors','0'); //Disable error display
ini_set('display_startup_errors','0');

//TODO: disable direct access via the browser? i,e All request must have REFER? 
if(!defined('INCLUDE_DIR'))	Http::response(500,'config error');

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
        url_get('^ticket_variables', 'ticket_variables')
    )),
    url('^/config/', patterns('ajax.config.php:ConfigAjaxAPI',
        url_get('^ui', 'scp_ui')
    )),
    url('^/report/overview/', patterns('ajax.reports.php:OverviewReportAjaxAPI',
        # Send
        url_get('^graph$', 'getPlotData'),
        url_get('^table/groups$', 'enumTabularGroups'),
        url_get('^table/export$', 'downloadTabularData'),
        url_get('^table$', 'getTabularData')
    )),
    url('^/report/overview/', patterns('ajax.reports.php:OverviewReportAjaxAPI',
        # Send
        url_get('^graph$', 'getPlotData'),
        url_get('^table/groups$', 'enumTabularGroups'),
        url_get('^table$', 'getTabularData')
    )),
    url_get('^/users$', array('ajax.users.php:UsersAjaxAPI', 'search')),
    url('^/tickets/', patterns('ajax.tickets.php:TicketsAjaxAPI',
        url_get('^(?P<tid>\d+)/preview', 'previewTicket'),
        url_get('^(?P<tid>\d+)/lock', 'acquireLock'),
        url_post('^(?P<tid>\d+)/lock/(?P<id>\d+)/renew', 'renewLock'),
        url_post('^(?P<tid>\d+)/lock/(?P<id>\d+)/release', 'releaseLock'),
        url_get('^lookup', 'lookup'),
        url_get('^search', 'search')
    )),
    url_post('^/upgrader', array('ajax.upgrader.php:UpgraderAjaxAPI', 'upgrade'))
);

# Call the respective function
print $dispatcher->resolve($_SERVER['PATH_INFO']);
?>
