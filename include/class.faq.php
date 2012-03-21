<?php
/*********************************************************************
    class.faq.php

    Backend support for article creates, edits, deletes, and attachments.

    Copyright (c)  2006-2012 osTicket
    http://www.osticket.com

    Released under the GNU General Public License WITHOUT ANY WARRANTY.
    See LICENSE.TXT for details.

    vim: expandtab sw=4 ts=4 sts=4:
**********************************************************************/
require_once('class.file.php');
require_once('class.category.php');

class FAQ {

    var $id;
    var $ht;
    var $category;

    function FAQ($id) {


        $this->id=0;
        $this->ht = array();
        $this->load($id);
    }

    function load($id) {

        $sql='SELECT faq.*,cat.ispublic, count(attach.file_id) as attachments '
            .' FROM '.FAQ_TABLE.' faq '
            .' LEFT JOIN '.FAQ_CATEGORY_TABLE.' cat ON(cat.category_id=faq.category_id) '
            .' LEFT JOIN '.FAQ_ATTACHMENT_TABLE.' attach ON(attach.faq_id=faq.faq_id) '
            .' WHERE faq.faq_id='.db_input($id)
            .' GROUP BY faq.faq_id';

        if (!($res=db_query($sql)) || !db_num_rows($res)) 
            return false;

        $this->ht = db_fetch_array($res);
        $this->ht['id'] = $this->id = $this->ht['faq_id'];
        $this->category = null;

        return true;
    }

    function reload() {
        return $this->load($this->getId());
    }

    /* ------------------> Getter methods <--------------------- */
    function getId() { return $this->id; }
    function getHashtable() { return $this->ht; }
    function getKeywords() { return $this->ht['keywords']; }
    function getQuestion() { return $this->ht['question']; }
    function getAnswer() { return $this->ht['answer']; }
    function getNotes() { return $this->ht['notes']; }
    function getNumAttachments() { return $this->ht['attachments']; }

    function isPublished() { return (!!$this->ht['ispublished'] && !!$this->ht['ispublic']); }

    function getCreateDate() { return $this->ht['created']; }
    function getUpdateDate() { return $this->ht['updated']; }
    
    function getCategoryId() { return $this->ht['category_id']; }
    function getCategory() { 
        if(!$this->category && $this->getCategoryId())
            $this->category = Category::lookup($this->getCategoryId());

        return $this->category;
    }

    function getHelpTopicsIds() {

        if (!isset($this->ht['topics']) && ($topics=$this->getHelpTopics())) {
            $this->ht['topics'] = array_keys($topics);
        }

        return $this->ht['topics'];
    }

    function getHelpTopics() {
        //XXX: change it to obj (when needed)!
     
        if (!isset($this->topics)) {
            $this->topics = array();
            $sql='SELECT t.topic_id, t.topic  FROM '.TOPIC_TABLE.' t '
                .' INNER JOIN '.FAQ_TOPIC_TABLE.' ft USING(topic_id) '
                .' WHERE ft.faq_id='.db_input($this->id)
                .' ORDER BY t.topic';
            if (($res=db_query($sql)) && db_num_rows($res)) {
                while(list($id,$name) = db_fetch_row($res))
                    $this->topics[$id]=$name;
            }
        }

        return $this->topics;
    }

    /* ------------------> Setter methods <--------------------- */
    function setPublished($val) { $this->ht['ispublished'] = !!$val; }
    function setQuestion($question) { $this->ht['question'] = Format::striptags(trim($question)); }
    function setAnswer($text) { $this->ht['answer'] = $text; }
    function setKeywords($words) { $this->ht['keywords'] = $words; }
    function setNotes($text) { $this->ht['notes'] = $text; }

    /* For ->attach() and ->detach(), use $this->attachments() */
    function attach($file) { return $this->_attachments->add($file); }
    function detach($file) { return $this->_attachments->remove($file); }

    function publish() {
        $this->setPublished(1);

        return $this->apply();
    }

    function unpublish() {
        $this->setPublished(0);

        return $this->apply();
    }

    /* Same as update - but mainly called after one or more setters are changed. */
    function apply() {
        //XXX: set errors and add ->getErrors() & ->getError()
        return $this->update($this->ht, $errors);
    }

    function updateTopics($ids){

        if($ids) {
            $topics = $this->getHelpTopicsIds();
            foreach($ids as $k=>$id) {
                if($topics && in_array($id,$topics)) continue;
                $sql='INSERT IGNORE INTO '.FAQ_TOPIC_TABLE
                    .' SET faq_id='.db_input($this->getId())
                    .', topic_id='.db_input($id);
                db_query($sql);
            }
        }

        $sql='DELETE FROM '.FAQ_TOPIC_TABLE.' WHERE faq_id='.db_input($this->getId());
        if($ids)
            $sql.=' AND topic_id NOT IN('.implode(',',$ids).')';

        db_query($sql);

        return true;
    }

    function update($vars, &$errors) {

        if(!$this->save($this->getId(), $vars, $errors))
            return false;

        $this->updateTopics($vars['topics']);
        $this->reload();

        return true;
    }


    function getAttachments() {

        if(!$this->attachments && $this->getNumAttachments()) {

            $sql='SELECT f.id, f.size, f.hash, f.name '
                .' FROM '.FILE_TABLE.' f '
                .' INNER JOIN '.FAQ_ATTACHMENT_TABLE.' a ON(f.id=a.file_id) '
                .' WHERE a.faq_id='.db_input($this->getId());

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

    function getAttachmentsLinks($separator=' ',$target='') {

        $str='';
        if(($attachments=$this->getAttachments())) {
            foreach($attachments as $attachment ) {
            /* The h key must match validation in file.php */
            $hash=$attachment['hash'].md5($attachment['id'].session_id().$attachment['hash']);
            if($attachment['size'])
                $size=sprintf('(<i>%s</i>)',Format::file_size($attachment['size']));

            $str.=sprintf('<a class="Icon file" href="file.php?h=%s" target="%s">%s</a>%s&nbsp;%s',
                    $hash, $target, Format::htmlchars($attachment['name']), $size, $separator);
        
            }
        }
        return $str;
    }
    
    function uploadAttachments($files) {

        foreach($files as $file) {
            if(($fileId=is_numeric($file)?$file:AttachmentFile::upload($file)) && is_numeric($fileId)) {
                $sql ='INSERT INTO '.FAQ_ATTACHMENT_TABLE
                     .' SET faq_id='.db_input($this->getId()).', file_id='.db_input($fileId);
                if(db_query($sql)) $i++;
            }
        }

        if($i) $this->reload();

        return $i;
    }

    function deleteAttachments(){

        $deleted=0;
        $sql='DELETE FROM '.FAQ_ATTACHMENT_TABLE
            .' WHERE faq_id='.db_input($this->getId());
        if(db_query($sql) && db_affected_rows()) {
            $deleted = AttachmentFile::deleteOrphans();
        }

        return $deleted;
    }


    function delete() {
       
        $sql='DELETE FROM '.FAQ_TABLE
            .' WHERE faq_id='.db_input($this->getId())
            .' LIMIT 1';
        if(!db_query($sql) || !db_affected_rows())
            return false;
        
        //Cleanup help topics.
        db_query('DELETE FROM '.FAQ_TOPIC_TABLE.' WHERE faq_id='.db_input($this->id));
        //Cleanup attachments.
        $this->deleteAttachments();
        
        return true;
    }

    /* ------------------> Static methods <--------------------- */
   
    function add($vars, &$errors) {
        if(($id=self::create($vars, $errors)) && ($faq=self::lookup($id)))
            $faq->updateTopics($vars['topics']);

        return$faq;
    }

    function create($vars, &$errors) {   
        return self::save(0, $vars, $errors);
    }

    function lookup($id) {
        return ($id && is_numeric($id) && ($obj= new FAQ($id)) && $obj->getId()==$id)? $obj : null;
    }

    function countPublishedFAQs() {
        $sql='SELECT count(faq.faq_id) '
            .' FROM '.FAQ_TABLE.' faq '
            .' INNER JOIN '.FAQ_CATEGORY_TABLE.' cat ON(cat.category_id=faq.category_id AND cat.ispublic=1) '
            .' WHERE faq.ispublished=1';

        return db_result(db_query($sql));
    }

    function findIdByQuestion($question) {
        $sql='SELECT faq_id FROM '.FAQ_TABLE
            .' WHERE question='.db_input($question);

        list($id) =db_fetch_row(db_query($sql));

        return $id;
    }

    function findByQuestion($question) {

        if(($id=self::getIdByQuestion($question)))
            return self::lookup($id);

        return false;
    }
    
    function save($id, $vars, &$errors, $validation=false) {

        //Cleanup.
        $vars['question']=Format::striptags(trim($vars['question']));

        //validate
        if($id && $id!=$vars['id'])
            $errors['err'] = 'Internal error. Try again';

        if(!$vars['question'])
            $errors['question'] = 'Question required';
        elseif(($qid=self::findIdByQuestion($vars['question'])) && $qid!=$id)
            $errors['question'] = 'Question already exists';

        if(!$vars['category_id'] || !($category=Category::lookup($vars['category_id'])))
            $errors['category_id'] = 'Category is required';

        if(!$vars['answer'])
            $errors['answer'] = 'FAQ answer is required';

        if($errors || $validation) return (!$errors);

        //save
        $sql=' updated=NOW() '
            .', question='.db_input($vars['question'])
            .', answer='.db_input(Format::safe_html($vars['answer']))
            .', category_id='.db_input($vars['category_id'])
            .', ispublished='.db_input(isset($vars['ispublished'])?$vars['ispublished']:0)
            .', notes='.db_input($vars['notes']);

        if($id) {
            $sql='UPDATE '.FAQ_TABLE.' SET '.$sql.' WHERE faq_id='.db_input($id);
            if(db_query($sql))
                return true;
           
            $errors['err']='Unable to update FAQ.';

        } else {
            $sql='INSERT INTO '.FAQ_TABLE.' SET '.$sql.',created=NOW()';
            if(db_query($sql) && ($id=db_insert_id()))
                return $id;

            $errors['err']='Unable to create FAQ. Internal error';
        }

        return false;
    }
}
?>
