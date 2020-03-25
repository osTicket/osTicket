#!/usr/bin/php -q
<?php
/*********************************************************************
    automail.php

    PHP script used for remote email piping...same as as the perl version.

    Peter Rotich <peter@osticket.com>
    Copyright (c)  2006-2020 osTicket
    http://www.osticket.com

    Released under the GNU General Public License WITHOUT ANY WARRANTY.
    See LICENSE.TXT for details.

    vim: expandtab sw=4 ts=4 sts=4:
**********************************************************************/

# Configuration: Enter the url and key. That is it.
#  url => URL to api/tickets.email e.g http://yourdomain.com/api/tickets.email
#  key => API's Key (see admin panel on how to generate a key)
#

$config = array(
        'url'=>'http://yourdomain.com/api/tickets.email',
        'key'=>'API KEY HERE'
        );

#pre-checks
function_exists('file_get_contents') or die('upgrade php >=4.3');
function_exists('curl_version') or die('CURL support required');
#read stdin (piped email)
$data=file_get_contents('php://stdin') or die('Error reading stdin. No message');

#set timeout
set_time_limit(10);

#curl post
$ch = curl_init();
curl_setopt($ch, CURLOPT_URL, $config['url']);
curl_setopt($ch, CURLOPT_POST, 1);
curl_setopt($ch, CURLOPT_POSTFIELDS, $data);
curl_setopt($ch, CURLOPT_USERAGENT, 'osTicket API Client v1.14');
curl_setopt($ch, CURLOPT_HEADER, TRUE);
curl_setopt($ch, CURLOPT_HTTPHEADER, array( 'Expect:', 'X-API-Key: '.$config['key']));
curl_setopt($ch, CURLOPT_FOLLOWLOCATION, FALSE);
curl_setopt($ch, CURLOPT_RETURNTRANSFER, TRUE);
$result=curl_exec($ch);
curl_close($ch);

//Use postfix exit codes...expected by MTA.
$code = 75;
if(preg_match('/HTTP\/.* ([0-9]+) .*/', $result, $status)) {
    switch($status[1]) {
        case 201: //Success
            $code = 0;
            break;
        case 400:
            $code = 66;
            break;
        case 401: /* permission denied */
        case 403:
            $code = 77;
            break;
        case 415:
        case 416:
        case 417:
        case 501:
            $code = 65;
            break;
        case 503:
            $code = 69;
            break;
        case 500: //Server error.
        default: //Temp (unknown) failure - retry
            $code = 75;
    }
}

if ($code == 66) {
    echo "HTTPS protocol required. Please update the URL in automail.php to include 'https'.\r\n";
}
exit($code);
?>
