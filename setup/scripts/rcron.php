#!/usr/bin/php -q
<?php
/*********************************************************************
    rcron.php

    PHP script used for remote cron calls.

    Peter Rotich <peter@osticket.com>
    Copyright (c)  2006-2013 osTicket
    http://www.osticket.com

    Released under the GNU General Public License WITHOUT ANY WARRANTY.
    See LICENSE.TXT for details.

    vim: expandtab sw=4 ts=4 sts=4:
**********************************************************************/

# Configuration: Enter the url and key. That is it.
#  url => URL to api/task/cron e.g http://yourdomain.com/support/api/tasks/cron
#  key => API's Key (see admin panel on how to generate a key)
#

$config = array(
        'url'=>'http://yourdomain.com/support/api/tasks/cron',
        'key'=>'API KEY HERE'
        );

#pre-checks
function_exists('curl_version') or die('CURL support required');

#set timeout
set_time_limit(30);

#curl post
$ch = curl_init();
curl_setopt($ch, CURLOPT_URL, $config['url']);
curl_setopt($ch, CURLOPT_POST, 1);
curl_setopt($ch, CURLOPT_POSTFIELDS, '');
curl_setopt($ch, CURLOPT_USERAGENT, 'osTicket API Client v1.7');
curl_setopt($ch, CURLOPT_HEADER, TRUE);
curl_setopt($ch, CURLOPT_HTTPHEADER, array( 'Expect:', 'X-API-Key: '.$config['key']));
curl_setopt($ch, CURLOPT_FOLLOWLOCATION, FALSE);
curl_setopt($ch, CURLOPT_RETURNTRANSFER, TRUE);
$result=curl_exec($ch);
curl_close($ch);

if(preg_match('/HTTP\/.* ([0-9]+) .*/', $result, $status) && $status[1] == 200)
    exit(0);

echo $result;
exit(1);
?>
