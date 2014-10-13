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

class Attachment {
    var $id;
    var $file_id;
    var $ticket_id;

    var $info;

    function Attachment($id,$tid=0) {

        $sql='SELECT * FROM '.TICKET_ATTACHMENT_TABLE.' WHERE attach_id='.db_input($id);
        if($tid)
            $sql.=' AND ticket_id='.db_input($tid);

        if(!($res=db_query($sql)) || !db_num_rows($res))
            return false;

        $this->ht=db_fetch_array($res);

        $this->id=$this->ht['attach_id'];
        $this->file_id=$this->ht['file_id'];
        $this->ticket_id=$this->ht['ticket_id'];

        $this->file=null;
        $this->ticket=null;

        return true;
    }

    function getId() {
        return $this->id;
    }

    function getTicketId() {
        return $this->ticket_id;
    }

    function getTicket() {
        if(!$this->ticket && $this->getTicketId())
            $this->ticket = Ticket::lookup($this->getTicketId());

        return $this->ticket;
    }

    function getFileId() {
        return $this->file_id;
    }

    function getFile() {
        if(!$this->file && $this->getFileId())
            $this->file = AttachmentFile::lookup($this->getFileId());

        return $this->file;
    }

    function getCreateDate() {
        return $this->ht['created'];
    }

    function getHashtable() {
        return $this->ht;
    }

    function getInfo() {
        return $this->getHashtable();
    }

    /* Static functions */
    function getIdByFileHash($hash, $tid=0) {
        $sql='SELECT attach_id FROM '.TICKET_ATTACHMENT_TABLE.' a '
            .' INNER JOIN '.FILE_TABLE.' f ON(f.id=a.file_id) '
            .' WHERE f.`key`='.db_input($hash);
        if($tid)
            $sql.=' AND a.ticket_id='.db_input($tid);

        return db_result(db_query($sql));
    }

    function lookup($var,$tid=0) {
        $id=is_numeric($var)?$var:self::getIdByFileHash($var,$tid);

        return ($id && is_numeric($id)
            && ($attach = new Attachment($id,$tid))
            && $attach->getId()==$id)?$attach:null;
    }

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

    function upload($files, $inline=false) {
        $i=array();
        if (!is_array($files)) $files=array($files);
        foreach ($files as $file) {
            if (is_numeric($file))
                $fileId = $file;
            elseif (is_array($file) && isset($file['id']))
                $fileId = $file['id'];
            elseif (!($fileId = AttachmentFile::upload($file)))
                continue;

            $_inline = isset($file['inline']) ? $file['inline'] : $inline;

            $sql ='INSERT INTO '.ATTACHMENT_TABLE
                .' SET `type`='.db_input($this->getType())
                .',object_id='.db_input($this->getId())
                .',file_id='.db_input($fileId)
                .',inline='.db_input($_inline ? 1 : 0);
            // File may already be associated with the draft (in the
            // event it was deleted and re-added)
            if (db_query($sql, function($errno) { return $errno != 1062; })
                    || db_errno() == 1062)
                $i[] = $fileId;
        }

        return $i;
    }

    function save($file, $inline=true) {

        if (is_numeric($file))
            $fileId = $file;
        elseif (is_array($file) && isset($file['id']))
            $fileId = $file['id'];
        elseif (!($fileId = AttachmentFile::save($file)))
            return false;

        $sql ='INSERT INTO '.ATTACHMENT_TABLE
            .' SET `type`='.db_input($this->getType())
            .',object_id='.db_input($this->getId())
            .',file_id='.db_input($fileId)
            .',inline='.db_input($inline ? 1 : 0);
        if (!db_query($sql) || !db_affected_rows())
            return false;

        return $fileId;
    }

    function getInlines() { return $this->_getList(false, true); }
    function getSeparates() { return $this->_getList(true, false); }
    function getAll() { return $this->_getList(true, true); }

    function _getList($separate=false, $inlines=false) {
        if(!isset($this->attachments)) {
            $this->attachments = array();
            $sql='SELECT f.id, f.size, f.`key`, f.name, a.inline '
                .' FROM '.FILE_TABLE.' f '
                .' INNER JOIN '.ATTACHMENT_TABLE.' a ON(f.id=a.file_id) '
                .' WHERE a.`type`='.db_input($this->getType())
                .' AND a.object_id='.db_input($this->getId());
            if(($res=db_query($sql)) && db_num_rows($res)) {
                while($rec=db_fetch_array($res)) {
                    $rec['download'] = AttachmentFile::getDownloadForIdAndKey(
                        $rec['id'], $rec['key']);
                    $this->attachments[] = $rec;
                }
            }
        }
        $attachments = array();
        foreach ($this->attachments as $a) {
            if ($a['inline'] != $separate || $a['inline'] == $inlines) {
                $a['file_id'] = $a['id'];
                $a['hash'] = md5($a['file_id'].session_id().strtolower($a['key']));
                $attachments[] = $a;
            }
        }
        return $attachments;
    }

    function delete($file_id) {
        $deleted = 0;
        $sql='DELETE FROM '.ATTACHMENT_TABLE
            .' WHERE object_id='.db_input($this->getId())
            .'   AND `type`='.db_input($this->getType())
            .'   AND file_id='.db_input($file_id);
        return db_query($sql) && db_affected_rows() > 0;
    }

    function deleteAll($inline_only=false){
        $deleted=0;
        $sql='DELETE FROM '.ATTACHMENT_TABLE
            .' WHERE object_id='.db_input($this->getId())
            .'   AND `type`='.db_input($this->getType());
        if ($inline_only)
            $sql .= ' AND inline = 1';
        return db_query($sql) && db_affected_rows() > 0;
    }

    function deleteInlines() {
        return $this->deleteAll(true);
    }
}
?>
