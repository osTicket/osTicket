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
        return $this->entry->staff_id || $this->entry->user_id;
    }

    function isEnabled() {
        global $thisstaff;

        // You can edit your own posts or posts by your department members
        // if your a manager, or everyone's if your an admin
        return $thisstaff && (
            $thisstaff->isAdmin()
            || (($T = $this->entry->getThread()->getObject())
                && $T instanceof Ticket
                && $T->getDept()->getManagerId() == $thisstaff->getId()
            )
            || ($this->entry->getStaffId() == $thisstaff->getId())
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

    private function trigger__get() {
        global $cfg;

        include STAFFINC_DIR . 'templates/thread-entry-edit.tmpl.php';
    }

    private function trigger__post() {
        global $thisstaff;

        $old = $this->entry;
        $type = ($old->format == 'html')
            ? 'HtmlThreadEntryBody' : 'TextThreadEntryBody';
        $new = new $type($_POST['body']);

        if ($new->getClean() == $old->body)
            // No update was performed
            Http::response(201);

        $entry = ThreadEntry::create(array(
            // Copy most information from the old entry
            'poster' => $old->poster,
            'userId' => $old->user_id,
            'staffId' => $old->staff_id,
            'type' => $old->type,
            'threadId' => $old->thread_id,

            // Add in new stuff
            'title' => $_POST['title'],
            'body' => $new,
            'ip_address' => $_SERVER['REMOTE_ADDR'],
        ));

        if (!$entry)
            return $this->trigger__get();

        // Note, anything that points to the $old entry as PID should remain
        // that way for email header lookups and such to remain consistent

        if ($old->flags & ThreadEntry::FLAG_EDITED) {
            // Second and further edit ---------------
            $original = ThreadEntry::lookup(array('pid'=>$old->id));
            // Drop the previous edit, and base this edit off the original
            $old->delete();
            $old = $original;
        }

        // Mark the new entry as editited (but not hidden)
        $entry->flags = ($old->flags & ~ThreadEntry::FLAG_HIDDEN)
            | ThreadEntry::FLAG_EDITED;
        $entry->created = $old->created;
        $entry->updated = SqlFunction::NOW();
        $entry->save();

        // Hide the old entry from the object thread
        $old->pid = $entry->id;
        $old->flags |= ThreadEntry::FLAG_HIDDEN;
        $old->save();

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
    static $name = /* trans */ 'View Original';
    static $icon = 'undo';

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
        $entry = ThreadEntry::lookup(array('pid'=>$this->entry->getId()));
        include STAFFINC_DIR . 'templates/thread-entry-view.tmpl.php';
    }
}
ThreadEntry::registerAction(/* trans */ 'Manage', 'TEA_OrigThreadEntry');
