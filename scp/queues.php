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

$nav->setTabActive('settings', 'settings.php?t='.urlencode($_GET['t']));
$errors = array();

if ($_REQUEST['id'] && is_numeric($_REQUEST['id'])) {
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
            $errors['err']=sprintf(__('Unable to update %s. Correct error(s) below and try again.'),
                __('this queue'));
        }
        break;

    case 'create':
        $queue = CustomQueue::create(array(
            'staff_id' => 0,
            'title' => $_POST['queue-name'],
            'root' => 'T'
        ));

        if ($queue->update($_POST, $errors) && $queue->save(true)) {
            $msg = sprintf(__('Successfully added %s'),
                    Format::htmlchars($queue->getName()));
        }
        elseif (!$errors['err']) {
            $errors['err']=sprintf(__('Unable to add %s. Correct error(s) below and try again.'),
                __('this queue'));
        }
        break;

    case 'mass_process':
        $updated = 0;
        foreach (CustomQueue::objects()
            ->filter(['id__in' => $_POST['ids']]) as $queue
        ) {
            switch ($_POST['a']) {
            case 'enable':
                $queue->enable();
                if ($queue->save()) $updated++;
                break;
            case 'disable':
                $queue->disable();
                if ($queue->save()) $updated++;
                break;
            case 'delete':
                if ($queue->getId() == $cfg->getDefaultTicketQueueId())
                    $err = __('This queue is the default queue. Unable to delete. ');
                elseif ($queue->delete()) $updated++;
            }
        }
        if (!$updated) {
            Messages::error($err ?: __(
                'Unable to manage any of the selected queues'));
        }
        elseif ($_POST['count'] && $updated != $_POST['count']) {
            Messages::warning(__(
                'Not all selected items were updated'));
        }
        elseif ($updated) {
            Messages::success(__(
                'Successfully managed selected queues'));
        }

        // TODO: Consider redirecting based on the queue root
        Http::redirect('settings.php?t=tickets#queues');
    }
}
elseif (isset($_GET['a'])
    && isset($queue) && $queue instanceof CustomQueue
) {
    switch (strtolower($_GET['a'])) {
    case 'clone':
        $queue = $queue->copy();
        // Require a new name for the queue
        unset($queue->title);
        break;
    case 'sub':
        $q = new CustomQueue([
            'parent' => $queue,
            'flags' => CustomQueue::FLAG_QUEUE
                     | CustomQueue::FLAG_INHERIT_EVERYTHING,
        ]);
        $queue = $q;
        break;
    }
}

require_once(STAFFINC_DIR.'header.inc.php');
include_once(STAFFINC_DIR."queue.inc.php");
include_once(STAFFINC_DIR.'footer.inc.php');
