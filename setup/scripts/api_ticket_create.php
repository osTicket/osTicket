#!/usr/bin/php -q
<?php
#
# Configuration: Enter the url and key. That is it.
#  url => URL to api/task/cron e.g #  http://yourdomain.com/support/api/tickets.json
#  key => API's Key (see admin panel on how to generate a key)
#

$config = array(
        'url'=>'http://domain.com/api/tickets.json',
        'key'=>'<api key>'
        );

# Fill in the data for the new ticket, this will likely come from $_POST.

$data = array(
    'name'      =>      'John Doe',
    'email'     =>      'mailbox@host.com',
    'subject'   =>      'Message de test de l\'API',
    'message'   =>      'Ceci est un test de l\'API d\'osTicket',
    'ip'        =>      $_SERVER['REMOTE_ADDR'],
    'attachments' => array(),
);

/* 
 * Add in attachments here if necessary

$data['attachments'][] =
array('filename.pdf' =>
        'data:image/png;base64,' .
            base64_encode(file_get_contents('/path/to/filename.pdf')));
 */

#pre-checks
function_exists('curl_version') or die('Utilisation de CURL requise');
function_exists('json_encode') or die('Utilisation de JSON requise');

#set timeout
set_time_limit(30);

#curl post
$ch = curl_init();
curl_setopt($ch, CURLOPT_URL, $config['url']);
curl_setopt($ch, CURLOPT_POST, 1);
curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($data));
curl_setopt($ch, CURLOPT_USERAGENT, 'osTicket API Client v1.7');
curl_setopt($ch, CURLOPT_HEADER, FALSE);
curl_setopt($ch, CURLOPT_HTTPHEADER, array( 'Expect:', 'X-API-Key: '.$config['key']));
curl_setopt($ch, CURLOPT_FOLLOWLOCATION, FALSE);
curl_setopt($ch, CURLOPT_RETURNTRANSFER, TRUE);
$result=curl_exec($ch);
$code = curl_getinfo($ch, CURLINFO_HTTP_CODE);
curl_close($ch);

if ($code != 201)
    die('Impossible de créer un ticket : '.$result);

$ticket_id = (int) $result;

# Continue onward here if necessary. $ticket_id has the ID number of the
# newly-created ticket

?>
