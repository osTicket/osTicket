<?php
/*********************************************************************
    ajax.php

    Ajax utils for client interface.

    Peter Rotich <peter@osticket.com>
    Copyright (c)  2006-2013 osTicket
    http://www.osticket.com

    Released under the GNU General Public License WITHOUT ANY WARRANTY.
    See LICENSE.TXT for details.

    vim: expandtab sw=4 ts=4 sts=4:
**********************************************************************/

function clientLoginPage($msg='Unauthorized') {
    Http::response(403,'Must login: '.Format::htmlchars($msg));
    exit;
}

require('client.inc.php');

if(!defined('INCLUDE_DIR'))	Http::response(500, 'Server configuration error');
require_once INCLUDE_DIR.'/class.dispatcher.php';
require_once INCLUDE_DIR.'/class.ajax.php';

$dispatcher = patterns('',
    url('^/config/', patterns('ajax.config.php:ConfigAjaxAPI',
        url_get('^client$', 'client')
    )),
    url('^/draft/', patterns('ajax.draft.php:DraftAjaxAPI',
        url_post('^(?P<id>\d+)$', 'updateDraftClient'),
        url_delete('^(?P<id>\d+)$', 'deleteDraftClient'),
        url_post('^(?P<id>\d+)/attach$', 'uploadInlineImageClient'),
        url_get('^(?P<namespace>[\w.]+)$', 'getDraftClient'),
        url_post('^(?P<namespace>[\w.]+)$', 'createDraftClient')
    )),
    url('^/form/', patterns('ajax.forms.php:DynamicFormsAjaxAPI',
        url_get('^help-topic/(?P<id>\d+)$', 'getClientFormsForHelpTopic'),
        url_post('^upload/(\d+)?$', 'upload'),
        url_post('^upload/(\w+)?$', 'attach')
    )),
    url('^/i18n/(?P<lang>[\w_]+)/', patterns('ajax.i18n.php:i18nAjaxAPI',
        url_get('(?P<tag>\w+)$', 'getLanguageFile')
    ))
);
Signal::send('ajax.client', $dispatcher);
print $dispatcher->resolve($ost->get_path_info());
?>
