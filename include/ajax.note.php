<?php

if(!defined('INCLUDE_DIR')) die('!');

require_once(INCLUDE_DIR.'class.note.php');

class NoteAjaxAPI extends AjaxController {

    function getNote($id) {
        global $thisstaff;

        if (!$thisstaff)
            Http::response(403, "Login required");
        elseif (!($note = QuickNote::lookup($id)))
            Http::response(205, "Note not found");

        Http::response(200, $note->display());
    }

    function updateNote($id) {
        global $thisstaff;

        if (!$thisstaff)
            Http::response(403, "Login required");
        elseif (!($note = QuickNote::lookup($id)))
            Http::response(205, "Note not found");
        elseif (!isset($_POST['note']) || !$_POST['note'])
            Http::response(422, "Send `note` parameter");

        $note->body = Format::sanitize($_POST['note']);
        if (!$note->save())
            Http::response(500, "Unable to save note contents");

        Http::response(200, $note->display());
    }

    function deleteNote($id) {
        global $thisstaff;

        if (!$thisstaff)
            Http::response(403, "Login required");
        elseif (!($note = QuickNote::lookup($id)))
            Http::response(205, "Note not found");
        elseif (!$note->delete())
            Http::response(500, "Unable to remove note");

        Http::response(204, "Deleted notes can be recovered by loading yesterday's backup");
    }

    function createNote($ext_id) {
        global $thisstaff;

        if (!$thisstaff)
            Http::response(403, "Login required");
        elseif (!isset($_POST['note']) || !$_POST['note'])
            Http::response(422, "Send `note` parameter");

        $note = new QuickNote(array(
            'staff_id' => $thisstaff->getId(),
            'body' => Format::sanitize($_POST['note']),
            'created' => new SqlFunction('NOW'),
            'ext_id' => $ext_id,
        ));
        if (!$note->save(true))
            Http::response(500, "Unable to create new note");

        $show_options = true;
        include STAFFINC_DIR . 'templates/note.tmpl.php';
    }
}
