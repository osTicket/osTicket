<?php
/*********************************************************************
    class.dept.php

    Department class

    Peter Rotich <peter@osticket.com>
    Copyright (c)  2006-2013 osTicket
    http://www.osticket.com

    Released under the GNU General Public License WITHOUT ANY WARRANTY.
    See LICENSE.TXT for details.

    vim: expandtab sw=4 ts=4 sts=4:
**********************************************************************/
class Dept {
    var $id;

    var $email;
    var $sla;
    var $manager;
    var $members;
    var $groups;

    var $ht;

    function Dept($id) {
        $this->id=0;
        $this->load($id);
    }

    function load($id=0) {
        global $cfg;

        if(!$id && !($id=$this->getId()))
            return false;

        $sql='SELECT dept.*,dept.dept_id as id,dept.dept_name as name, dept.dept_signature as signature, count(staff.staff_id) as users '
            .' FROM '.DEPT_TABLE.' dept '
            .' LEFT JOIN '.STAFF_TABLE.' staff ON (dept.dept_id=staff.dept_id) '
            .' WHERE dept.dept_id='.db_input($id)
            .' GROUP BY dept.dept_id';

        if(!($res=db_query($sql)) || !db_num_rows($res))
            return false;



        $this->ht=db_fetch_array($res);
        $this->id=$this->ht['dept_id'];
        $this->email=$this->sla=$this->manager=null;
        $this->getEmail(); //Auto load email struct.
        $this->members=$this->groups=array();

        return true;
    }

    function reload() {
        return $this->load();
    }

    function asVar() {
        return $this->getName();
    }

    function getId() {
        return $this->id;
    }

    function getName() {
        return $this->ht['name'];
    }


    function getEmailId() {
        return $this->ht['email_id'];
    }

    function getEmail() {
        global $cfg;

        if(!$this->email)
            if(!($this->email = Email::lookup($this->getEmailId())) && $cfg)
                $this->email = $cfg->getDefaultEmail();

        return $this->email;
    }

    function getNumStaff() {
        return $this->ht['users'];
    }


    function getNumUsers() {
        return $this->getNumStaff();
    }

    function getNumMembers() {
        return count($this->getMembers());
    }

    function getMembers() {

        if(!$this->members) {
            $this->members = array();
            $sql='SELECT DISTINCT s.staff_id FROM '.STAFF_TABLE.' s '
                .' LEFT JOIN '.GROUP_DEPT_TABLE.' g ON(s.group_id=g.group_id) '
                .' INNER JOIN '.DEPT_TABLE.' d
                       ON(d.dept_id=s.dept_id
                            OR d.manager_id=s.staff_id
                            OR (d.dept_id=g.dept_id AND d.group_membership=1)
                        ) '
                .' WHERE d.dept_id='.db_input($this->getId())
                .' ORDER BY s.lastname, s.firstname';

            if(($res=db_query($sql)) && db_num_rows($res)) {
                while(list($id)=db_fetch_row($res))
                    $this->members[] = Staff::lookup($id);
            }
        }

        return $this->members;
    }


    function getSLAId() {
        return $this->ht['sla_id'];
    }

    function getSLA() {

        if(!$this->sla && $this->getSLAId())
            $this->sla=SLA::lookup($this->getSLAId());

        return $this->sla;
    }

    function getTemplateId() {
         return $this->ht['tpl_id'];
    }

    function getTemplate() {
        global $cfg;

        if (!$this->template) {
            if (!($this->template = EmailTemplateGroup::lookup($this->getTemplateId())))
                $this->template = $cfg->getDefaultTemplate();
        }

        return $this->template;
    }

    function getAutoRespEmail() {

        if (!$this->autorespEmail) {
            if (!$this->ht['autoresp_email_id']
                    || !($this->autorespEmail = Email::lookup($this->ht['autoresp_email_id'])))
                $this->autorespEmail = $this->getEmail();
        }

        return $this->autorespEmail;
    }

    function getEmailAddress() {
        if(($email=$this->getEmail()))
            return $email->getAddress();
    }

    function getSignature() {
        return $this->ht['signature'];
    }

    function canAppendSignature() {
        return ($this->getSignature() && $this->isPublic());
    }

    function getManagerId() {
        return $this->ht['manager_id'];
    }

    function getManager() {

        if(!$this->manager && $this->getManagerId())
            $this->manager=Staff::lookup($this->getManagerId());

        return $this->manager;
    }

    function isManager($staff) {

        if(is_object($staff)) $staff=$staff->getId();

        return ($this->getManagerId() && $this->getManagerId()==$staff);
    }


    function isPublic() {
         return ($this->ht['ispublic']);
    }

    function autoRespONNewTicket() {
        return ($this->ht['ticket_auto_response']);
    }

    function autoRespONNewMessage() {
        return ($this->ht['message_auto_response']);
    }

    function noreplyAutoResp() {
         return ($this->ht['noreply_autoresp']);
    }


    function isGroupMembershipEnabled() {
        return ($this->ht['group_membership']);
    }

    function getHashtable() {
        return $this->ht;
    }

    function getInfo() {
        return $this->getHashtable();
    }



    function getAllowedGroups() {

        if($this->groups) return $this->groups;

        $sql='SELECT group_id FROM '.GROUP_DEPT_TABLE
            .' WHERE dept_id='.db_input($this->getId());

        if(($res=db_query($sql)) && db_num_rows($res)) {
            while(list($id)=db_fetch_row($res))
                $this->groups[] = $id;
        }

        return $this->groups;
    }

    function updateAllowedGroups($groups) {

        if($groups && is_array($groups)) {
            foreach($groups as $k=>$id) {
                $sql='INSERT IGNORE INTO '.GROUP_DEPT_TABLE
                    .' SET dept_id='.db_input($this->getId()).', group_id='.db_input($id);
                db_query($sql);
            }
        }


        $sql='DELETE FROM '.GROUP_DEPT_TABLE.' WHERE dept_id='.db_input($this->getId());
        if($groups && is_array($groups))
            $sql.=' AND group_id NOT IN('.implode(',', db_input($groups)).')';

        db_query($sql);

        return true;

    }

    function update($vars, &$errors) {

        if(!$this->save($this->getId(), $vars, $errors))
            return false;

        $this->updateAllowedGroups($vars['groups']);
        $this->reload();

        return true;
    }

    function delete() {
        global $cfg;

        if(!$cfg || $this->getId()==$cfg->getDefaultDeptId() || $this->getNumUsers())
            return 0;

        $id=$this->getId();
        $sql='DELETE FROM '.DEPT_TABLE.' WHERE dept_id='.db_input($id).' LIMIT 1';
        if(db_query($sql) && ($num=db_affected_rows())) {
            // DO SOME HOUSE CLEANING
            //Move tickets to default Dept. TODO: Move one ticket at a time and send alerts + log notes.
            db_query('UPDATE '.TICKET_TABLE.' SET dept_id='.db_input($cfg->getDefaultDeptId()).' WHERE dept_id='.db_input($id));
            //Move Dept members: This should never happen..since delete should be issued only to empty Depts...but check it anyways
            db_query('UPDATE '.STAFF_TABLE.' SET dept_id='.db_input($cfg->getDefaultDeptId()).' WHERE dept_id='.db_input($id));
            //make help topic using the dept default to default-dept.
            db_query('UPDATE '.TOPIC_TABLE.' SET dept_id='.db_input($cfg->getDefaultDeptId()).' WHERE dept_id='.db_input($id));
            //Delete group access
            db_query('DELETE FROM '.GROUP_DEPT_TABLE.' WHERE dept_id='.db_input($id));
        }

        return $num;
    }

    /*----Static functions-------*/
	function getIdByName($name) {
        $id=0;
        $sql ='SELECT dept_id FROM '.DEPT_TABLE.' WHERE dept_name='.db_input($name);
        if(($res=db_query($sql)) && db_num_rows($res))
            list($id)=db_fetch_row($res);

        return $id;
    }

    function lookup($id) {
        return ($id && is_numeric($id) && ($dept = new Dept($id)) && $dept->getId()==$id)?$dept:null;
    }

    function getNameById($id) {

        if($id && ($dept=Dept::lookup($id)))
            $name= $dept->getName();

        return $name;
    }

    function getDefaultDeptName() {
        global $cfg;
        return ($cfg && $cfg->getDefaultDeptId() && ($name=Dept::getNameById($cfg->getDefaultDeptId())))?$name:null;
    }

    function getDepartments( $criteria=null) {

        $depts=array();
        $sql='SELECT dept_id, dept_name FROM '.DEPT_TABLE.' WHERE 1';
        if($criteria['publiconly'])
            $sql.=' AND  ispublic=1';

        if(($manager=$criteria['manager']))
            $sql.=' AND manager_id='.db_input(is_object($manager)?$manager->getId():$manager);

        if(($res=db_query($sql)) && db_num_rows($res)) {
            while(list($id, $name)=db_fetch_row($res))
                $depts[$id] = $name;
        }

        return $depts;
    }

    function getPublicDepartments() {
        return self::getDepartments(array('publiconly'=>true));
    }

    function create($vars, &$errors) {
        if(($id=self::save(0, $vars, $errors)) && ($dept=self::lookup($id)))
            $dept->updateAllowedGroups($vars['groups']);

        return $id;
    }

    function save($id, $vars, &$errors) {
        global $cfg;

        if($id && $id!=$vars['id'])
            $errors['err']='Missing or invalid Dept ID (internal error).';

        if(!isset($vars['id'])
                && (!$vars['email_id'] || !is_numeric($vars['email_id'])))
            $errors['email_id']='Email selection required';

        if(isset($vars['tpl_id']) && !is_numeric($vars['tpl_id']))
            $errors['tpl_id']='Template selection required';

        if(!$vars['name']) {
            $errors['name']='Name required';
        } elseif(strlen($vars['name'])<4) {
            $errors['name']='Name is too short.';
        } elseif(($did=Dept::getIdByName($vars['name'])) && $did!=$id) {
            $errors['name']='Department already exists';
        }

        if(!$vars['ispublic'] && $cfg && ($vars['id']==$cfg->getDefaultDeptId()))
            $errors['ispublic']='System default department cannot be private';

        if($errors) return false;


        $sql='SET updated=NOW() '
            .' ,ispublic='.db_input(isset($vars['ispublic'])?$vars['ispublic']:0)
            .' ,email_id='.db_input(isset($vars['email_id'])?$vars['email_id']:0)
            .' ,tpl_id='.db_input(isset($vars['tpl_id'])?$vars['tpl_id']:0)
            .' ,sla_id='.db_input(isset($vars['sla_id'])?$vars['sla_id']:0)
            .' ,autoresp_email_id='.db_input(isset($vars['autoresp_email_id'])?$vars['autoresp_email_id']:0)
            .' ,manager_id='.db_input($vars['manager_id']?$vars['manager_id']:0)
            .' ,dept_name='.db_input(Format::striptags($vars['name']))
            .' ,dept_signature='.db_input(Format::sanitize($vars['signature']))
            .' ,group_membership='.db_input(isset($vars['group_membership'])?1:0)
            .' ,ticket_auto_response='.db_input(isset($vars['ticket_auto_response'])?$vars['ticket_auto_response']:1)
            .' ,message_auto_response='.db_input(isset($vars['message_auto_response'])?$vars['message_auto_response']:1);


        if($id) {
            $sql='UPDATE '.DEPT_TABLE.' '.$sql.' WHERE dept_id='.db_input($id);
            if(db_query($sql) && db_affected_rows())
                return true;

            $errors['err']='Unable to update '.Format::htmlchars($vars['name']).' Dept. Error occurred';

        } else {
            if (isset($vars['id']))
                $sql .= ', dept_id='.db_input($vars['id']);

            $sql='INSERT INTO '.DEPT_TABLE.' '.$sql.',created=NOW()';
            if(db_query($sql) && ($id=db_insert_id()))
                return $id;


            $errors['err']='Unable to create department. Internal error';

        }


        return false;
    }

}
?>
