<?php
/*********************************************************************
    class.thread_actions.php

    Actions for thread entries. This serves as a simple repository for
    drop-down actions which can be triggered on the ticket-view page for an
    object's thread.

    Jared Hancock <jared@osticket.com>
    Peter Rotich <peter@osticket.com>
    Copyright (c)  2006-2014 osTicket
    http://www.osticket.com

    Released under the GNU General Public License WITHOUT ANY WARRANTY.
    See LICENSE.TXT for details.

    vim: expandtab sw=4 ts=4 sts=4:
**********************************************************************/
include_once(INCLUDE_DIR.'class.thread.php');

class TEA_ShowEmailHeaders extends ThreadEntryAction {
    static $id = 'view_headers';
    static $name = /* trans */ 'View Email Headers';
    static $icon = 'envelope';

    function isVisible() {
        global $thisstaff;

        if (!$this->entry->getEmailHeader())
            return false;

        return $thisstaff && $thisstaff->isAdmin();
    }

    function getJsStub() {
        return sprintf("$.dialog('%s');",
            $this->getAjaxUrl()
        );
    }

    function trigger() {
        switch ($_SERVER['REQUEST_METHOD']) {
        case 'GET':
            return $this->trigger__get();
        }
    }

    private function trigger__get() {
        $headers = $this->entry->getEmailHeader();

        include STAFFINC_DIR . 'templates/thread-email-headers.tmpl.php';
    }
}
ThreadEntry::registerAction(/* trans */ 'E-Mail', 'TEA_ShowEmailHeaders');

class TEA_EditThreadEntry extends ThreadEntryAction {
    static $id = 'edit';
    static $name = /* trans */ 'Edit';
    static $icon = 'pencil';

    function isVisible() {
        // Can't edit system posts
        return ($this->entry->staff_id || $this->entry->user_id)
            && $this->entry->type != 'R';
    }

    function isEnabled() {
        global $thisstaff;

        $T = $this->entry->getThread()->getObject();
        // You can edit your own posts or posts by your department members
        // if your a manager, or everyone's if your an admin
        return $thisstaff && (
            $thisstaff->getId() == $this->entry->staff_id
            || ($T instanceof Ticket
                && $T->getDept()->getManagerId() == $thisstaff->getId()
            )
            || ($T instanceof Ticket
                && $thisstaff->getRole($T->getDeptId())->hasPerm(ThreadEntry::PERM_EDIT)
            )
        );
    }

    function getJsStub() {
        return sprintf(<<<JS
var url = '%s';
$.dialog(url, [201], function(xhr, resp) {
  var json = JSON.parse(resp);
  if (!json || !json.thread_id)
    return;
  $('#thread-id-'+json.thread_id)
    .attr('id', 'thread-id-' + json.new_id)
    .find('div')
    .html(json.body)
    .closest('td')
    .effect('highlight')
}, {size:'large'});
JS
        , $this->getAjaxUrl());
    }


    function trigger() {
        switch ($_SERVER['REQUEST_METHOD']) {
        case 'GET':
            return $this->trigger__get();
        case 'POST':
            return $this->trigger__post();
        }
    }

    protected function trigger__get() {
        global $cfg, $thisstaff;

        include STAFFINC_DIR . 'templates/thread-entry-edit.tmpl.php';
    }

    function updateEntry($guard=false) {
        $old = $this->entry;
        $type = ($old->format == 'html')
            ? 'HtmlThreadEntryBody' : 'TextThreadEntryBody';
        $new = new $type($_POST['body']);

        if ($new->getClean() == $old->body)
            // No update was performed
            return $old;

        $entry = ThreadEntry::create(array(
            // Copy most information from the old entry
            'poster' => $old->poster,
            'userId' => $old->user_id,
            'staffId' => $old->staff_id,
            'type' => $old->type,
            'threadId' => $old->thread_id,

            // Connect the new entry to be a child of the previous
            'pid' => $old->id,

            // Add in new stuff
            'title' => $_POST['title'],
            'body' => $new,
            'ip_address' => $_SERVER['REMOTE_ADDR'],
        ));

        if (!$entry)
            return false;

        // Note, anything that points to the $old entry as PID should remain
        // that way for email header lookups and such to remain consistent

        if ($old->flags & ThreadEntry::FLAG_EDITED
            and !($old->flags & ThreadEntry::FLAG_GUARDED)
        ) {
            // Replace previous edit --------------------------
            $original = $old->getParent();
            // Drop the previous edit, and base this edit off the original
            $old->delete();
            $old = $original;
        }

        // Mark the new entry as edited (but not hidden)
        $entry->flags = ($old->flags & ~ThreadEntry::FLAG_HIDDEN)
            | ThreadEntry::FLAG_EDITED;

        // Guard against deletes on future edit if requested. This is done
        // if an email was triggered by the last edit. In such a case, it
        // should not be replace by a subsequent edit.
        if ($guard)
            $entry->flags |= ThreadEntry::FLAG_GUARDED;

        // Sort in the same place in the thread — XXX: Add a `sequence` id
        $entry->created = $old->created;
        $entry->updated = SqlFunction::NOW();
        $entry->save();

        // Hide the old entry from the object thread
        $old->flags |= ThreadEntry::FLAG_HIDDEN;
        $old->save();

        return $entry;
    }

    protected function trigger__post() {
        global $thisstaff;

        if (!($entry = $this->updateEntry()))
            return $this->trigger__get();

        Http::response('201', JsonDataEncoder::encode(array(
            'thread_id' => $this->entry->id,
            'new_id' => $entry->id,
            'body' => $entry->getBody()->toHtml(),
        )));
    }
}
ThreadEntry::registerAction(/* trans */ 'Manage', 'TEA_EditThreadEntry');

class TEA_OrigThreadEntry extends ThreadEntryAction {
    static $id = 'previous';
    static $name = /* trans */ 'View History';
    static $icon = 'copy';

    function isVisible() {
        // Can't edit system posts
        return $this->entry->flags & ThreadEntry::FLAG_EDITED;
    }

    function getJsStub() {
        return sprintf("$.dialog('%s');",
            $this->getAjaxUrl()
        );
    }

    function trigger() {
        switch ($_SERVER['REQUEST_METHOD']) {
        case 'GET':
            return $this->trigger__get();
        }
    }

    private function trigger__get() {
        $entry = $this->entry->getParent();
        if (!$entry)
            Http::response(404, 'No history for this entry');
        include STAFFINC_DIR . 'templates/thread-entry-view.tmpl.php';
    }
}
ThreadEntry::registerAction(/* trans */ 'Manage', 'TEA_OrigThreadEntry');

class TEA_ResendThreadEntry extends TEA_EditThreadEntry {
    static $id = 'resend';
    static $name = /* trans */ 'Edit and Resend';
    static $icon = 'reply-all';

    function isVisible() {
        // Can only resend replies
        return $this->entry->staff_id && $this->entry->type == 'R';
    }

    protected function trigger__post() {
        $resend = @$_POST['commit'] == 'resend';

        if (!($entry = $this->updateEntry($resend)))
            return $this->trigger__get();

        if (@$_POST['commit'] == 'resend')
            $this->resend($entry);

        Http::response('201', JsonDataEncoder::encode(array(
            'thread_id' => $this->entry->id,
            'new_id' => $entry->id,
            'body' => $entry->getBody()->toHtml(),
        )));
    }

    function resend($response) {
        global $cfg, $thisstaff;

        $vars = $_POST;
        $ticket = $response->getThread()->getObject();

        $dept = $ticket->getDept();

        if ($thisstaff && $vars['signature'] == 'mine')
            $signature = $thisstaff->getSignature();
        elseif ($vars['signature'] == 'dept' && $dept && $dept->isPublic())
            $signature = $dept->getSignature();
        else
            $signature = '';

        $variables = array(
            'response' => $response,
            'signature' => $signature,
            'staff' => $response->getStaff(),
            'poster' => $response->getStaff());
        $options = array('thread' => $response);

        if (($email=$dept->getEmail())
            && ($tpl = $dept->getTemplate())
            && ($msg=$tpl->getReplyMsgTemplate())
        ) {
            $msg = $ticket->replaceVars($msg->asArray(),
                $variables + array('recipient' => $ticket->getOwner()));

            $attachments = $cfg->emailAttachments()
                ? $response->getAttachments() : array();
            $email->send($ticket->getOwner(), $msg['subj'], $msg['body'],
                $attachments, $options);
        }
        // TODO: Add an option to the dialog
        $ticket->notifyCollaborators($response, array('signature' => $signature));
    }
}
ThreadEntry::registerAction(/* trans */ 'Manage', 'TEA_ResendThreadEntry');
