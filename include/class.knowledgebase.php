<?php
/*********************************************************************
    class.knowledgebase.php

    Backend support for knowledgebase creates, edits, deletes, and
    attachments.

    Copyright (c)  2006-2013 osTicket
    http://www.osticket.com

    Released under the GNU General Public License WITHOUT ANY WARRANTY.
    See LICENSE.TXT for details.

    vim: expandtab sw=4 ts=4 sts=4:
**********************************************************************/
require_once("class.file.php");

class Knowledgebase {

    function Knowledgebase($id) {
        $res=db_query(
            'SELECT title, isenabled, dept_id, created, updated '
           .'FROM '.CANNED_TABLE.' WHERE canned_id='.db_input($id));
        if (!$res || !db_num_rows($res)) return false;
        list(   $this->title,
                $this->enabled,
                $this->department,
                $this->created,
                $this->updated) = db_fetch_row($res);
        $this->id = $id;
        $this->_attachments = new AttachmentList(
            CANNED_ATTACHMENT_TABLE, 'canned_id='.db_input($id));
    }

    /* ------------------> Getter methods <--------------------- */
    function getTitle() { return $this->title; }
    function isEnabled() { return !!$this->enabled; }
    function getAnswer() { 
        if (!isset($this->answer)) {
            if ($res=db_query('SELECT answer FROM '.CANNED_TABLE
                    .' WHERE canned_id='.db_input($this->id))) {
                list($this->answer)=db_fetch_row($res);
            }
        }
        return $this->answer;
    }
    function getCreated() { return $this->created; }
    function lastUpdated() { return $this->updated; }
    function attachments() { return $this->_attachments; }
    function getDeptId() { return $this->department; }
    function getDepartment() { return new Dept($this->department); }
    function getId() { return $this->id; }

    /* ------------------> Setter methods <--------------------- */
    function publish() { $this->published = true; }
    function unpublish() { $this->published = false; }
    function setPublished($val) { $this->published = !!$val; }
    function setEnabled($val) { $this->enabled = !!$val; }
    function setTitle($title) { $this->title = $title; }
    function setKeywords($words) { $this->keywords = $words; }
    function setAnswer($text) { $this->answer = $text; }
    function setDepartment($id) { $this->department = $id; }

    /* -------------> Validation and Clean methods <------------ */
    function validate(&$errors, $what=null) {
        if (!$what) $what=$this->getHashtable();
        else $this->clean($what);
        # TODO: Validate current values ($this->yada)
        # Apply hashtable to this -- return error list
        $validation = array(
            'title' => array('is_string', __('Title is required'))
        );
        foreach ($validation as $key=>$details) {
            list($func, $error) = $details;
            if (!call_user_func($func, $what[$key])) {
                $errors[$key] = $error;
            }
        }
        return count($errors) == 0;
    }

    function clean(&$what) {
        if (isset($what['topic']))
            $what['topic']=Format::striptags(trim($what['topic']));
    }

    function getHashtable() {
        # TODO: Return hashtable like the one that would be passed into
        #       $this->save() or self::create()
        return array('title'=>$this->title, 'department'=>$this->department,
            'isenabled'=>$this->enabled);
    }

    /* -------------> Database access methods <----------------- */
    function update() { 
        if (!@$this->validate()) return false;
        db_query(
            'UPDATE '.CANNED_TABLE.' SET title='.db_input($this->title)
                .', isenabled='.db_input($this->enabled)
                .', dept_id='.db_input($this->department)
                .', updated=NOW()'
                .((isset($this->answer)) 
                    ? ', answer='.db_input($this->answer) : '')
                .' WHERE canned_id='.db_input($this->id));
        return db_affected_rows() == 1;
    }
    function delete() {
        db_query('DELETE FROM '.CANNED_TABLE.' WHERE canned_id='
            .db_input($this->id));
        return db_affected_rows() == 1;
    }
    /* For ->attach() and ->detach(), use $this->attachments() */
    function attach($file) { return $this->_attachments->add($file); }
    function detach($file) { return $this->_attachments->remove($file); }

    /* ------------------> Static methods <--------------------- */
    function create($hash, &$errors) {
        if (!self::validate($hash, $errors)) return false;
        db_query('INSERT INTO '.CANNED_TABLE
            .' (title, answer, department, isenabled, created, updated) VALUES ('
            .db_input($hash['title']).','
            .db_input($hash['answer']).','
            .db_input($hash['dept']).','
            .db_input($hash['isenabled']).',NOW(),NOW()');
        return db_insert_id();
    }

    function save($id, $new_stuff, &$errors) {
        if (!$id) return self::create($new_stuff, $errors);
        if (!self::validate($errors, $new_stuff)) return false;

        # else
        if (!($obj = new Knowledgebase($id))) { return false; }
        $obj->setEnabled($new_stuff['enabled']);
        $obj->setTitle($new_stuff['title']);
        $obj->setAnswer($new_stuff['answer']);
        $obj->setDepartment($new_stuff['dept']);

        return $obj->update();
    }

    function findByTitle($title) {
        $res=db_query('SELECT canned_id FROM '.CANNED_TABLE
            .' WHERE title LIKE '.db_input($title));
        if (list($id) = db_fetch_row($res)) {
            return new Knowledgebase($id);
        }
        return false;
    }

    function lookup($id) {
        return ($id && is_numeric($id) && ($obj= new Knowledgebase($id)) && $obj->getId()==$id)
            ? $obj : null;
    }
}
