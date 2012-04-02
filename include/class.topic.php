<?php
/*********************************************************************
    class.topic.php

    Help topic helper

    Peter Rotich <peter@osticket.com>
    Copyright (c)  2006-2012 osTicket
    http://www.osticket.com

    Released under the GNU General Public License WITHOUT ANY WARRANTY.
    See LICENSE.TXT for details.

    vim: expandtab sw=4 ts=4 sts=4:
**********************************************************************/

class Topic {
    var $id;
    var $topic;
 
    var $ht;
    
    function Topic($id){
        $this->id=0;
        $this->load($id);
    }

    function load($id=0) {

        if(!$id && !($id=$this->getId()))
            return false;

        $sql='SELECT * FROM '.TOPIC_TABLE
            .' WHERE topic_id='.db_input($id);
        if(!($res=db_query($sql)) || !db_num_rows($res))
            return false;

        $this->ht=db_fetch_array($res);
        $this->id=$this->ht['topic_id'];
    
        return true;
    }
  
    function reload() {
        return $this->load();
    }
    
    function getId(){
        return $this->id;
    }
    
    function getName(){
        return $this->ht['topic'];
    }
    
    function getDeptId(){
        return $this->ht['dept_id'];
    }

    function getSLAId(){
        return $this->ht['sla_id'];
    }

    function getPriorityId(){
        return $this->ht['priority_id'];
    }

    function getStaffId(){
        return $this->ht['staff_id'];
    }

    function getTeamId(){
        return $this->ht['team_id'];
    }
    
    function autoRespond() {
        return (!$this->ht['noautoresp']);
    }

    function isEnabled() {
         return ($this->ht['isactive']);
    }

    function isActive(){
        return $this->isEnabled();
    }

    function isPublic(){
        return ($this->ht['ispublic']);
    }

    function getHashtable() {
        return $this->ht;
    }

    function getInfo() {
        return $this->getHashtable();
    }

    function update($vars,&$errors) {

        if($this->save($this->getId(),$vars,$errors)){
            $this->reload();
            return true;
        }
        return false;
    }

    function delete(){
        $sql='DELETE FROM '.TOPIC_TABLE.' WHERE topic_id='.db_input($this->getId()).' LIMIT 1';
        if(db_query($sql) && ($num=db_affected_rows())){
            db_query('UPDATE '.TICKET_TABLE.' SET topic_id=0 WHERE topic_id='.db_input($this->getId()));
            db_query('DELETE FROM '.FAQ_TOPIC_TABLE.' WHERE topic_id='.db_input($this->getId()));
        }

        return $num;
    }
    /*** Static functions ***/
    function create($vars,&$errors) { 
        return self::save(0,$vars,$errors);
    }

    function getHelpTopics($publicOnly=false) {

        $topics=array();
        $sql='SELECT topic_id, topic FROM '.TOPIC_TABLE
            .' WHERE isactive=1';
        if($publicOnly)
            $sql.=' AND ispublic=1';
        $sql.=' ORDER BY topic';
        if(($res=db_query($sql)) && db_num_rows($res))
            while(list($id,$name)=db_fetch_row($res))
                $topics[$id]=$name;

        return $topics;
    }

    function getPublicHelpTopics() {
        return self::getHelpTopics(true);
    }


    function getIdByName($topic){
        $sql='SELECT topic_id FROM '.TOPIC_TABLE.' WHERE topic='.db_input($topic);
        if(($res=db_query($sql)) && db_num_rows($res))
            list($id)=db_fetch_row($res);

        return $id;
    }

    function lookup($id){
        return ($id && is_numeric($id) && ($t= new Topic($id)) && $t->getId()==$id)?$t:null;
    }

    function save($id,$vars,&$errors) {

        $vars['topic']=Format::striptags(trim($vars['topic']));

        if($id && $id!=$vars['id'])
            $errors['err']='Internal error. Try again';

        if(!$vars['topic'])
            $errors['topic']='Help topic required';
        elseif(strlen($vars['topic'])<5)
            $errors['topic']='Topic is too short. 5 chars minimum';
        elseif(($tid=self::getIdByName($vars['topic'])) && $tid!=$id)
            $errors['topic']='Topic already exists';

        if(!$vars['dept_id'])
            $errors['dept_id']='You must select a department';
            
        if(!$vars['priority_id'])
            $errors['priority_id']='You must select a priority';
        
        if($errors) return false;

        $sql=' updated=NOW(),topic='.db_input($vars['topic']).
             ',dept_id='.db_input($vars['dept_id']).
             ',priority_id='.db_input($vars['priority_id']).
             ',sla_id='.db_input($vars['sla_id']).
             ',isactive='.db_input($vars['isactive']).
             ',ispublic='.db_input($vars['ispublic']).
             ',noautoresp='.db_input(isset($vars['noautoresp'])?1:0).
             ',notes='.db_input($vars['notes']);

        //Auto assign ID is overloaded...
        if($vars['assign'] && $vars['assign'][0]=='s')
             $sql.=',team_id=0,staff_id='.db_input(preg_replace("/[^0-9]/", "",$vars['assign']));
        elseif($vars['assign'] && $vars['assign'][0]=='t')
            $sql.=',staff_id=0,team_id='.db_input(preg_replace("/[^0-9]/", "",$vars['assign']));
        else
            $sql.=',staff_id=0,team_id=0 '; //no auto-assignment!
            
        if($id) {
            $sql='UPDATE '.TOPIC_TABLE.' SET '.$sql.' WHERE topic_id='.db_input($id);
            if(db_query($sql))
                return true;

            $errors['err']='Unable to update topic. Internal error occurred';
        }else{
            $sql='INSERT INTO '.TOPIC_TABLE.' SET '.$sql.',created=NOW()';
            if(db_query($sql) && ($id=db_insert_id()))
                return $id;
            
            $errors['err']='Unable to create the topic. Internal error';
        }
        
        return false;
    }
}
?>
