<?php
/*********************************************************************
    class.attachment.php

    Attachment Handler - mainly used for lookup...doesn't save!

    Peter Rotich <peter@osticket.com>
    Copyright (c)  2006-2012 osTicket
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
            .' WHERE f.hash='.db_input($hash);
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
?>
