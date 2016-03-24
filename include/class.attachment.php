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

    function getFilename() {
        return $this->name ?: $this->file->name;
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

class GenericAttachments
extends InstrumentedList {

    var $lang;

    function getId() { return $this->key['object_id']; }
    function getType() { return $this->key['type']; }

    /**
     * Drop attachments whose file_id values are not in the included list,
     * additionally, add new files whose IDs are in the list provided.
     */
    function keepOnlyFileIds($ids, $inline=false, $lang=false) {
        if (!$ids) $ids = array();
        $new = array_fill_keys($ids, 1);
        foreach ($this as $A) {
            if (!isset($new[$A->file_id]) && $A->lang == $lang && $A->inline == $inline)
                // Not in the $ids list, delete
                $this->remove($A);
            unset($new[$A->file_id]);
        }
        // Everything remaining in $new is truly new
        $this->upload(array_keys($new), $inline, $lang);
    }

    function upload($files, $inline=false, $lang=false) {
        $i=array();
        if (!is_array($files))
            $files = array($files);
        foreach ($files as $file) {
            if (is_numeric($file))
                $fileId = $file;
            elseif (is_array($file) && isset($file['id']))
                $fileId = $file['id'];
            elseif (isset($file['tmp_name']) && ($F = AttachmentFile::upload($file)))
                $fileId = $F->getId();
            elseif ($F = AttachmentFile::create($file))
                $fileId = $F->getId();
            else
                continue;

            $_inline = isset($file['inline']) ? $file['inline'] : $inline;

            $att = $this->add(new Attachment(array(
                'file_id' => $fileId,
                'inline' => $_inline ? 1 : 0,
            )));

            // Record varying file names in the attachment record
            if (is_array($file) && isset($file['name'])) {
                $filename = $file['name'];
            }
            if ($filename) {
                // This should be a noop since the ORM caches on PK
                $file = $F ?: AttachmentFile::lookup($fileId);
                // XXX: This is not Unicode safe
                if ($file && 0 !== strcasecmp($file->name, $filename))
                    $att->name = $filename;
            }
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
        $ids = $this->upload($file, $inline);
        return $ids[0];
    }

    function getInlines($lang=false) { return $this->_getList(false, true, $lang); }
    function getSeparates($lang=false) { return $this->_getList(true, false, $lang); }
    function getAll($lang=false) { return $this->_getList(true, true, $lang); }
    function count($lang=false) { return count($this->getSeparates($lang)); }

    function _getList($separates=false, $inlines=false, $lang=false) {
        $base = $this;

        if ($separates && !$inlines)
            $base = $base->filter(array('inline' => 0));
        elseif (!$separates && $inlines)
            $base = $base->filter(array('inline' => 1));

        if ($lang)
            $base = $base->filter(array('lang' => $lang));

        return $base;
    }

    function delete($file_id) {
        return $this->objects()->filter(array('file_id'=>$file_id))->delete();
    }

    function deleteAll($inline_only=false){
        if ($inline_only)
            return $this->objects()->filter(array('inline' => 1))->delete();

        return parent::expunge();
    }

    function deleteInlines() {
        return $this->deleteAll(true);
    }

    static function forIdAndType($id, $type) {
        return new static(array(
            'Attachment',
            array('object_id' => $id, 'type' => $type)
        ));
    }
}
?>
