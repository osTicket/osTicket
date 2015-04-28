<?php
/*********************************************************************
    class.attachment.php

    Attachment Handler - mainly used for lookup...doesn't save!

    Peter Rotich <peter@osticket.com>
    Copyright (c)  2006-2013 osTicket
    http://www.osticket.com

    Released under the GNU General Public License WITHOUT ANY WARRANTY.
    See LICENSE.TXT for details.

    vim: expandtab sw=4 ts=4 sts=4:
**********************************************************************/
require_once(INCLUDE_DIR.'class.ticket.php');
require_once(INCLUDE_DIR.'class.file.php');

class Attachment extends VerySimpleModel {
    static $meta = array(
        'table' => ATTACHMENT_TABLE,
        'pk' => array('id'),
        'select_related' => array('file'),
        'joins' => array(
            'draft' => array(
                'constraint' => array(
                    'type' => "'D'",
                    'object_id' => 'Draft.id',
                ),
            ),
            'file' => array(
                'constraint' => array(
                    'file_id' => 'AttachmentFile.id',
                ),
            ),
            'thread_entry' => array(
                'constraint' => array(
                    'type' => "'H'",
                    'object_id' => 'ThreadEntry.id',
                ),
            ),
        ),
    );

    var $object;

    function getId() {
        return $this->id;
    }

    function getFileId() {
        return $this->file_id;
    }

    function getFile() {
        return $this->file;
    }

    function getHashtable() {
        return $this->ht;
    }

    function getInfo() {
        return $this->getHashtable();
    }

    function getObject() {

        if (!isset($this->object))
            $this->object = ObjectModel::lookup(
                    $this->ht['object_id'], $this->ht['type']);

        return $this->object;
    }

    static function lookupByFileHash($hash, $objectId=0) {
        $file = static::objects()
            ->filter(array('file__key' => $hash));

        if ($objectId)
            $file->filter(array('object_id' => $objectId));

        return $file->first();
    }

    static function lookup($var, $objectId=0) {
        return (is_string($var))
            ? static::lookupByFileHash($var, $objectId)
            : parent::lookup($var);
    }
}

class GenericAttachment extends VerySimpleModel {
    static $meta = array(
        'table' => ATTACHMENT_TABLE,
        'pk' => array('id'),
    );
}

class GenericAttachments {

    var $id;
    var $type;

    function GenericAttachments($object_id, $type) {
        $this->id = $object_id;
        $this->type = $type;
    }

    function getId() { return $this->id; }
    function getType() { return $this->type; }

    function upload($files, $inline=false, $lang=false) {
        $i=array();
        if (!is_array($files)) $files=array($files);
        foreach ($files as $file) {
            if (is_numeric($file))
                $fileId = $file;
            elseif (is_array($file) && isset($file['id']))
                $fileId = $file['id'];
            elseif ($F = AttachmentFile::upload($file))
                $fileId = $F->getId();
            else
                continue;

            $_inline = isset($file['inline']) ? $file['inline'] : $inline;

            $att = Attachment::create(array(
                'type' => $this->getType(),
                'object_id' => $this->getId(),
                'file_id' => $fileId,
                'inline' => $_inline ? 1 : 0,
            ));
            if ($lang)
                $att->lang = $lang;

            // File may already be associated with the draft (in the
            // event it was deleted and re-added)
            $att->save();
            $i[] = $fileId;
        }

        return $i;
    }

    function save($file, $inline=true) {

        if (is_numeric($file))
            $fileId = $file;
        elseif (is_array($file) && isset($file['id']))
            $fileId = $file['id'];
        elseif ($file = AttachmentFile::create($file))
            $fileId = $file->getId();
        else
            return false;

        $att = Attachment::create(array(
            'type' => $this->getType(),
            'object_id' => $this->getId(),
            'file_id' => $fileId,
            'inline' => $inline ? 1 : 0,
        ));
        if (!$att->save())
            return false;

        return $fileId;
    }

    function getInlines($lang=false) { return $this->_getList(false, true, $lang); }
    function getSeparates($lang=false) { return $this->_getList(true, false, $lang); }
    function getAll($lang=false) { return $this->_getList(true, true, $lang); }
    function count($lang=false) { return count($this->getSeparates($lang)); }

    function _getList($separate=false, $inlines=false, $lang=false) {
        return Attachment::objects()->filter(array(
            'type' => $this->getType(),
            'object_id' => $this->getId(),
        ));
    }

    function delete($file_id) {
        return Attachment::objects()->filter(array(
            'type' => $this->getType(),
            'object_id' => $this->getId(),
            'file_id' => $file_id,
        ))->delete();
    }

    function deleteAll($inline_only=false){
        $objects = Attachment::objects()->filter(array(
            'type' => $this->getType(),
            'object_id' => $this->getId(),
        ));
        if ($inline_only)
            $objects->filter(array('inline' => 1));

        return $objects->delete();
    }

    function deleteInlines() {
        return $this->deleteAll(true);
    }
}
?>
