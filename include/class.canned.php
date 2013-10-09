<?php
/*********************************************************************
    class.canned.php

    Canned Responses AKA Premade replies

    Peter Rotich <peter@osticket.com>
    Copyright (c)  2006-2013 osTicket
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
            .' LEFT JOIN '.ATTACHMENT_TABLE.' attach
                    ON (attach.object_id=canned.canned_id AND attach.`type`=\'C\' AND NOT attach.inline) '
            .' LEFT JOIN '.FILTER_TABLE.' filter ON (canned.canned_id = filter.canned_response_id) '
            .' WHERE canned.canned_id='.db_input($id)
            .' GROUP BY canned.canned_id';

        if(!($res=db_query($sql)) ||  !db_num_rows($res))
            return false;


        $this->ht = db_fetch_array($res);
        $this->id = $this->ht['canned_id'];
        $this->attachments = new GenericAttachments($this->id, 'C');

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
    function getResponseWithImages() {
        return Format::viewableImages($this->getResponse());
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
                  'SELECT name FROM '.FILTER_TABLE
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

    function delete(){
        if ($this->getNumFilters() > 0) return false;

        $sql='DELETE FROM '.CANNED_TABLE.' WHERE canned_id='.db_input($this->getId()).' LIMIT 1';
        if(db_query($sql) && ($num=db_affected_rows())) {
            $this->attachments->deleteAll();
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

        $sql='SELECT canned_id, title FROM '.CANNED_TABLE
           .' WHERE isenabled';
        if($deptId){
            $sql.=' AND (dept_id='.db_input($deptId);
            if(!$explicit)
                $sql.=' OR dept_id=0';
            $sql.=')';
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
        global $cfg;

        $vars['title']=Format::striptags(trim($vars['title']));

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
             ',isenabled='.db_input($vars['isenabled']?$vars['isenabled']:1).
             ',title='.db_input($vars['title']).
             ',response='.db_input(Format::sanitize($vars['response'])).
             ',notes='.db_input(Format::sanitize($vars['notes']));

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
