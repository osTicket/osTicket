<?php
/*********************************************************************
    class.canned.php

    Canned Responses AKA Premade replies

    Peter Rotich <peter@osticket.com>
    Copyright (c)  2006-2012 osTicket
    http://www.osticket.com

    Released under the GNU General Public License WITHOUT ANY WARRANTY.
    See LICENSE.TXT for details.

    vim: expandtab sw=4 ts=4 sts=4:
**********************************************************************/
include_once(INCLUDE_DIR.'class.file.php');

class Canned {
    var $id;
    var $ht;

    var $attachments;
    
    function Canned($id){
        $this->id=0;
        $this->load($id);
    }

    function load($id=0) {

        if(!$id && !($id=$this->getId()))
            return false;

        $sql='SELECT canned.*, count(attach.file_id) as attachments, '
            .' count(filter.id) as filters '
            .' FROM '.CANNED_TABLE.' canned '
            .' LEFT JOIN '.CANNED_ATTACHMENT_TABLE.' attach ON (attach.canned_id=canned.canned_id) ' 
            .' LEFT JOIN '.EMAIL_FILTER_TABLE.' filter ON (canned.canned_id = filter.canned_response_id) '
            .' WHERE canned.canned_id='.db_input($id)
            .' GROUP BY canned.canned_id';

        if(!($res=db_query($sql)) ||  !db_num_rows($res))
            return false;

        
        $this->ht = db_fetch_array($res);
        $this->id = $this->ht['canned_id'];
        $this->attachments = array();
    
        return true;
    }
  
    function reload() {
        return $this->load();
    }
    
    function getId(){
        return $this->id;
    }

    function isEnabled() {
         return ($this->ht['isenabled']);
    }

    function isActive(){
        return $this->isEnabled();
    }

    function getNumFilters() {
        return $this->ht['filters'];
    }
    
    function getTitle() {
        return $this->ht['title'];
    }

    function getResponse() {
        return $this->ht['response'];
    }

    function getReply() {
        return $this->getResponse();
    }

    function getNotes() {
        return $this->ht['notes'];
    }
    
    function getDeptId(){
        return $this->ht['dept_id'];
    }

    function getHashtable() {
        return $this->ht;
    }

    function getInfo() {
        return $this->getHashtable();
    }

    function getFilters() {
        if (!$this->_filters) {
            $this->_filters = array();
            $res = db_query(
                  'SELECT name FROM '.EMAIL_FILTER_TABLE
                .' WHERE canned_response_id = '.db_input($this->getId())
                .' ORDER BY name');
            while ($row = db_fetch_row($res))
                $this->_filters[] = $row[0];
        }
        return $this->_filters;
    }

    function update($vars, &$errors) {

        if(!$this->save($this->getId(),$vars,$errors))
            return false;
        
        $this->reload();

        return true;
    }
   
    function getNumAttachments() {
        return $this->ht['attachments'];
    }
   
    function getAttachments() {

        if(!$this->attachments && $this->getNumAttachments()) {
            
            $sql='SELECT f.id, f.size, f.hash, f.name '
                .' FROM '.FILE_TABLE.' f '
                .' INNER JOIN '.CANNED_ATTACHMENT_TABLE.' a ON(f.id=a.file_id) '
                .' WHERE a.canned_id='.db_input($this->getId());

            $this->attachments = array();
            if(($res=db_query($sql)) && db_num_rows($res)) {
                while($rec=db_fetch_array($res)) {
                    $rec['key'] =md5($rec['id'].session_id().$rec['hash']);
                    $this->attachments[] = $rec;
                }
            }
        }
        
        return $this->attachments;
    }
    /*
    @files is an array - hash table of multiple attachments.
    */
    function uploadAttachments($files) {

        $i=0;
        foreach($files as $file) {
            if(($fileId=is_numeric($file)?$file:AttachmentFile::upload($file)) && is_numeric($fileId)) {
                $sql ='INSERT INTO '.CANNED_ATTACHMENT_TABLE
                     .' SET canned_id='.db_input($this->getId()).', file_id='.db_input($fileId);
                if(db_query($sql)) $i++;
            }
        }

        if($i) $this->reload();

        return $i;
    }

    function deleteAttachment($file_id) {
        $deleted = 0;
        $sql='DELETE FROM '.CANNED_ATTACHMENT_TABLE
            .' WHERE canned_id='.db_input($this->getId())
            .'   AND file_id='.db_input($file_id);
        if(db_query($sql) && db_affected_rows()) {
            $deleted = AttachmentFile::deleteOrphans();
        }
        return ($deleted > 0);
    }

    function deleteAttachments(){

        $deleted=0;
        $sql='DELETE FROM '.CANNED_ATTACHMENT_TABLE
            .' WHERE canned_id='.db_input($this->getId());
        if(db_query($sql) && db_affected_rows()) {
            $deleted = AttachmentFile::deleteOrphans();
        }

        return $deleted;
    }

    function delete(){
        if ($this->getNumFilters() > 0) return false;

        $sql='DELETE FROM '.CANNED_TABLE.' WHERE canned_id='.db_input($this->getId()).' LIMIT 1';
        if(db_query($sql) && ($num=db_affected_rows())) {
            $this->deleteAttachments();
        }

        return $num;
    }

    /*** Static functions ***/
    function lookup($id){
        return ($id && is_numeric($id) && ($c= new Canned($id)) && $c->getId()==$id)?$c:null;
    }

    function create($vars,&$errors) { 
        return self::save(0,$vars,$errors);
    }

    function getIdByTitle($title) {
        $sql='SELECT canned_id FROM '.CANNED_TABLE.' WHERE title='.db_input($title);
        if(($res=db_query($sql)) && db_num_rows($res))
            list($id)=db_fetch_row($res);

        return $id;
    }

    function getCannedResponses($deptId=0, $explicit=false) {

        $sql='SELECT canned_id, title FROM '.CANNED_TABLE;
        if($deptId){
            $sql.=' WHERE dept_id='.db_input($deptId);
            if(!$explicit)
                $sql.=' OR dept_id=0';
        }
        $sql.=' ORDER BY title';

        $responses = array();
        if(($res=db_query($sql)) && db_num_rows($res)) {
            while(list($id,$title)=db_fetch_row($res))
                $responses[$id]=$title;
        }

        return $responses;
    }

    function responsesByDeptId($deptId, $explicit=false) {
        return self::getCannedResponses($deptId, $explicit);
    }

    function save($id,$vars,&$errors) {

        //We're stripping html tags - until support is added to tickets.
        $vars['title']=Format::striptags(trim($vars['title']));
        $vars['response']=Format::striptags(trim($vars['response']));
        $vars['notes']=Format::striptags(trim($vars['notes']));

        if($id && $id!=$vars['id'])
            $errors['err']='Internal error. Try again';

        if(!$vars['title'])
            $errors['title']='Title required';
        elseif(strlen($vars['title'])<3)
            $errors['title']='Title is too short. 3 chars minimum';
        elseif(($cid=self::getIdByTitle($vars['title'])) && $cid!=$id)
            $errors['title']='Title already exists';

        if(!$vars['response'])
            $errors['response']='Response text required';
            
        if($errors) return false;

        $sql=' updated=NOW() '.
             ',dept_id='.db_input($vars['dept_id']?$vars['dept_id']:0).
             ',isenabled='.db_input($vars['isenabled']).
             ',title='.db_input($vars['title']).
             ',response='.db_input($vars['response']).
             ',notes='.db_input($vars['notes']);

        if($id) {
            $sql='UPDATE '.CANNED_TABLE.' SET '.$sql.' WHERE canned_id='.db_input($id);
            if(db_query($sql))
                return true;

            $errors['err']='Unable to update canned response.';

        } else {
            $sql='INSERT INTO '.CANNED_TABLE.' SET '.$sql.',created=NOW()';
            if(db_query($sql) && ($id=db_insert_id()))
                return $id;

            $errors['err']='Unable to create the canned response. Internal error';
        }

        return false;
    }
}
?>
