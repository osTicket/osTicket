<?php
/*********************************************************************
    ajax.php

    Ajax utils for the installer interface.

    Peter Rotich <peter@osticket.com>
    Copyright (c)  2006-2013 osTicket
    http://www.osticket.com

    Released under the GNU General Public License WITHOUT ANY WARRANTY.
    See LICENSE.TXT for details.

    vim: expandtab sw=4 ts=4 sts=4:
**********************************************************************/

require('setup.inc.php');

if(!defined('INCLUDE_DIR'))
    Http::response(500, __('Server configuration error'));
require_once INCLUDE_DIR.'/class.dispatcher.php';
require_once INCLUDE_DIR.'/class.ajax.php';

$dispatcher = patterns('',
    url('^/help/', patterns('ajax.tips.php:HelpTipAjaxAPI',
        url_get('^tips/(?P<namespace>[\w_.]+)$', 'getTipsJson'),
        url_get('^(?P<lang>[\w_]+)?/tips/(?P<namespace>[\w_.]+)$', 'getTipsJsonForLang')
    ))
);
print $dispatcher->resolve(Osticket::get_path_info());
?>
