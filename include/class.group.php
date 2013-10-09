<?php
/*********************************************************************
    class.group.php

    User Group - Everything about a group!

    Peter Rotich <peter@osticket.com>
    Copyright (c)  2006-2013 osTicket
    http://www.osticket.com

    Released under the GNU General Public License WITHOUT ANY WARRANTY.
    See LICENSE.TXT for details.

    vim: expandtab sw=4 ts=4 sts=4:
**********************************************************************/

class Group {

    var $id;
    var $ht;

    var $members;
    var $departments;

    function Group($id){

        $this->id=0;
        return $this->load($id);
    }

    function load($id=0) {

        if(!$id && !($id=$this->getId()))
            return false;

        $sql='SELECT grp.*,grp.group_name as name, grp.group_enabled as isactive, count(staff.staff_id) as users '
            .'FROM '.GROUP_TABLE.' grp '
            .'LEFT JOIN '.STAFF_TABLE.' staff USING(group_id) '
            .'WHERE grp.group_id='.db_input($id).' GROUP BY grp.group_id ';
        if(!($res=db_query($sql)) || !db_num_rows($res))
            return false;

        $this->ht=db_fetch_array($res);
        $this->id=$this->ht['group_id'];
        $this->members=array();
        $this->departments = array();

        return $this->id;
    }

    function reload(){
        return $this->load();
    }

    function getHashtable() {
        return $this->ht;
    }

    function getInfo(){
        return  $this->getHashtable();
    }

    function getId(){
        return $this->id;
    }

    function getName(){
        return $this->ht['name'];
    }

    function getNumUsers(){
        return $this->ht['users'];
    }


    function isEnabled(){
        return ($this->ht['isactive']);
    }

    function isActive(){
        return $this->isEnabled();
    }
 
    //Get members of the group.
    function getMembers() {

        if(!$this->members && $this->getNumUsers()) {
            $sql='SELECT staff_id FROM '.STAFF_TABLE
                .' WHERE group_id='.db_input($this->getId())
                .' ORDER BY lastname, firstname';
            if(($res=db_query($sql)) && db_num_rows($res)) {
                while(list($id)=db_fetch_row($res))
                    if(($staff=Staff::lookup($id)))
                        $this->members[]= $staff;
            }
        }

        return $this->members;
    }

    //Get departments the group is allowed to access.
    function getDepartments() {

        if(!$this->departments) {
            $sql='SELECT dept_id FROM '.GROUP_DEPT_TABLE
                .' WHERE group_id='.db_input($this->getId());
            if(($res=db_query($sql)) && db_num_rows($res)) {
                while(list($id)=db_fetch_row($res))
                    $this->departments[]= $id;
            }
        }

        return $this->departments;
    }

        
    function updateDeptAccess($depts) {


        if($depts && is_array($depts)) {
            foreach($depts as $k=>$id) {
                $sql='INSERT IGNORE INTO '.GROUP_DEPT_TABLE
                    .' SET group_id='.db_input($this->getId())
                    .', dept_id='.db_input($id);
                db_query($sql);
            }
        }

        $sql='DELETE FROM '.GROUP_DEPT_TABLE.' WHERE group_id='.db_input($this->getId());
        if($depts && is_array($depts)) // just inserted departments IF any.
            $sql.=' AND dept_id NOT IN('.implode(',', db_input($depts)).')';

        db_query($sql);

        return true;
    }

    function update($vars,&$errors) {

        if(!Group::save($this->getId(),$vars,$errors))
            return false;

        $this->updateDeptAccess($vars['depts']);
        $this->reload();
        
        return true;
    }

    function delete() {

        //Can't delete with members
        if($this->getNumUsers())
            return false;

        $res = db_query('DELETE FROM '.GROUP_TABLE.' WHERE group_id='.db_input($this->getId()).' LIMIT 1');
        if(!$res || !db_affected_rows($res))
            return false;

        //Remove dept access entry.
        db_query('DELETE FROM '.GROUP_DEPT_TABLE.' WHERE group_id='.db_input($this->getId()));

        return true;
    }

    /*** Static functions ***/
    function getIdByName($name){
        $sql='SELECT group_id FROM '.GROUP_TABLE.' WHERE group_name='.db_input(trim($name));
        if(($res=db_query($sql)) && db_num_rows($res))
            list($id)=db_fetch_row($res);

        return $id;
    }

    function lookup($id){
        return ($id && is_numeric($id) && ($g= new Group($id)) && $g->getId()==$id)?$g:null;
    }

    function create($vars, &$errors) { 
        if(($id=self::save(0,$vars,$errors)) && ($group=self::lookup($id)))
            $group->updateDeptAccess($vars['depts']);

        return $id;
    }

    function save($id,$vars,&$errors) {

        if($id && $vars['id']!=$id)
            $errors['err']='Missing or invalid group ID';
            
        if(!$vars['name']) {
            $errors['name']='Group name required';
        }elseif(strlen($vars['name'])<3) {
            $errors['name']='Group name must be at least 3 chars.';
        }elseif(($gid=Group::getIdByName($vars['name'])) && $gid!=$id){
            $errors['name']='Group name already exists';
        }
        
        if($errors) return false;
            
        $sql=' SET updated=NOW() '
            .', group_name='.db_input(Format::striptags($vars['name']))
            .', group_enabled='.db_input($vars['isactive'])
            .', can_create_tickets='.db_input($vars['can_create_tickets'])
            .', can_delete_tickets='.db_input($vars['can_delete_tickets'])
            .', can_edit_tickets='.db_input($vars['can_edit_tickets'])
            .', can_assign_tickets='.db_input($vars['can_assign_tickets'])
            .', can_transfer_tickets='.db_input($vars['can_transfer_tickets'])
            .', can_close_tickets='.db_input($vars['can_close_tickets'])
            .', can_ban_emails='.db_input($vars['can_ban_emails'])
            .', can_manage_premade='.db_input($vars['can_manage_premade'])
            .', can_manage_faq='.db_input($vars['can_manage_faq'])
            .', can_post_ticket_reply='.db_input($vars['can_post_ticket_reply'])
            .', can_view_staff_stats='.db_input($vars['can_view_staff_stats'])
            .', notes='.db_input(Format::sanitize($vars['notes']));

        if($id) {
            
            $sql='UPDATE '.GROUP_TABLE.' '.$sql.' WHERE group_id='.db_input($id);
            if(($res=db_query($sql)))
                return true;

            $errors['err']='Unable to update group. Internal error occurred.';
            
        }else{
            $sql='INSERT INTO '.GROUP_TABLE.' '.$sql.',created=NOW()';
            if(($res=db_query($sql)) && ($id=db_insert_id()))
                return $id;
                
            $errors['err']='Unable to create the group. Internal error';
        }
        
        return false;
    }
}
?>
