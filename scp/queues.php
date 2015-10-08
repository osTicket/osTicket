<?php
/*********************************************************************
    queues.php

    Handles management of custom queues

    Jared Hancock <jared@osticket.com>
    Peter Rotich <peter@osticket.com>
    Copyright (c)  2006-2015 osTicket
    http://www.osticket.com

    Released under the GNU General Public License WITHOUT ANY WARRANTY.
    See LICENSE.TXT for details.

    vim: expandtab sw=4 ts=4 sts=4:
**********************************************************************/

require('admin.inc.php');

require_once INCLUDE_DIR . 'class.queue.php';

$nav->setTabActive('settings', 'settings.php?t='.urlencode($_GET['t']));
$errors = array();

if ($_REQUEST['id']) {
    $queue = CustomQueue::lookup($_REQUEST['id']);
}

if ($_POST) {
    switch (strtolower($_POST['do'])) {
    case 'update':
        if (!$queue) {
            $errors['err'] = '';
            break;
        }
        if ($queue->update($_POST, $errors) && $queue->save()) {
            $msg = sprintf(__('Successfully updated %s'), Format::htmlchars($_POST['name']));
        }
        elseif (!$errors['err']) {
            $errors['err']=sprintf(__('Unable to udpate %s. Correct error(s) below and try again.'),
                __('this queue'));
        }
        break;

    case 'create':
        $queue = CustomQueue::create(array(
            'flags' => CustomQueue::FLAG_PUBLIC,
            'root' => $_POST['root'] ?: 'Ticket'
        ));

        if ($queue->update($_POST, $errors) && $queue->save(true)) {
            $msg = sprintf(__('Successfully added %s'), Format::htmlchars($_POST['name']));
        }
        elseif (!$errors['err']) {
            $errors['err']=sprintf(__('Unable to add %s. Correct error(s) below and try again.'),
                __('this queue'));
        }
        break;
    }
}

require_once(STAFFINC_DIR.'header.inc.php');
include_once(STAFFINC_DIR."queue.inc.php");
include_once(STAFFINC_DIR.'footer.inc.php');
