<?php
/*********************************************************************
    class.dept.php
    
    Department class

    Peter Rotich <peter@osticket.com>
    Copyright (c)  2006-2012 osTicket
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
    var $ht;
  
    function Dept($id){
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
        $this->members=array();

        return true;
    }

    function reload(){
        return $this->load();
    }

    function getId(){
        return $this->id;
    }
    
    function getName(){
        return $this->ht['name'];
    }

        
    function getEmailId(){
        return $this->ht['email_id'];
    }

    function getEmail(){
        
        if(!$this->email && $this->getEmailId())
            $this->email=Email::lookup($this->getEmailId());
            
        return $this->email;
    }

    function getNumStaff(){
        return $this->ht['users'];
    }

    function getNumMembers(){
        return $this->getNumStaff();
    }

    function getNumUsers(){
        return $this->getNumStaff();
    }

    function getAvailableMembers(){

        if(!$this->members && $this->getNumStaff()){
            $sql='SELECT m.staff_id FROM '.STAFF_TABLE.' m '
                .'WHERE m.dept_id='.db_input($this->getId())
                .' AND s.staff_id IS NOT NULL '
                .'ORDER BY s.lastname, s.firstname';
            if(($res=db_query($sql)) && db_num_rows($res)){
                while(list($id)=db_fetch_row($res))
                    if(($staff=Staff::lookup($id)) && $staff->isAvailable())
                        $this->members[]= $staff;
            }
        }
        return $this->members;
    }

    function getSLAId(){
        return $this->ht['sla_id'];
    }

    function getSLA(){

        if(!$this->sla && $this->getSLAId())
            $this->sla=SLA::lookup($this->getSLAId());

        return $this->sla;
    }

    function getTemplateId() {
         return $this->ht['tpl_id'];
    }

    function getTemplate() {

        if(!$this->template && $this->getTemplateId())
            $this->template = Template::lookup($this->getTemplateId());

        return $this->template;
    }
   
    function getAutoRespEmail() {

        if(!$this->autorespEmail && $this->ht['autoresp_email_id'] && ($email=Email::lookup($this->ht['autoresp_email_id'])))
            $this->autorespEmail=$email;
        else // Defualt to dept email if autoresp is not specified or deleted.
            $this->autorespEmail=$this->getEmail();

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
    
    function getManagerId(){
        return $this->ht['manager_id'];
    }

    function getManager(){
     
        if(!$this->manager && $this->getManagerId())
            $this->manager=Staff::lookup($this->getManagerId());

        return $this->manager;
    }

    function isPublic(){
         return ($this->ht['ispublic']);
    }
    
    function autoRespONNewTicket(){
        return ($this->ht['ticket_auto_response']);
    }
        
    function autoRespONNewMessage(){
        return ($this->ht['message_auto_response']);
    }

    function noreplyAutoResp(){
         return ($this->ht['noreply_autoresp']);
    }
   
    function getHashtable() {
        return $this->ht;
    }

    function getInfo(){
        return $this->getHashtable();
    }

    function update($vars,&$errors){

        if($this->save($this->getId(),$vars,$errors)) {
            $this->reload();
            return true;
        }

        return false;
    }

    function delete() {
        global $cfg;
        
        if(!$cfg || $this->getId()==$cfg->getDefaultDeptId() || $this->getNumUsers())
            return 0;

        $id=$this->getId();
        $sql='DELETE FROM '.DEPT_TABLE.' WHERE dept_id='.db_input($id).' LIMIT 1';
        if(db_query($sql) && ($num=db_affected_rows())){
            // DO SOME HOUSE CLEANING
            //Move tickets to default Dept. TODO: Move one ticket at a time and send alerts + log notes.
            db_query('UPDATE '.TICKET_TABLE.' SET dept_id='.db_input($cfg->getDefaultDeptId()).' WHERE dept_id='.db_input($id));
            //Move Dept members: This should never happen..since delete should be issued only to empty Depts...but check it anyways
            db_query('UPDATE '.STAFF_TABLE.' SET dept_id='.db_input($cfg->getDefaultDeptId()).' WHERE dept_id='.db_input($id));
            //make help topic using the dept default to default-dept.
            db_query('UPDATE '.TOPIC_TABLE.' SET dept_id='.db_input($cfg->getDefaultDeptId()).' WHERE dept_id='.db_input($id));
            
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

    function lookup($id){
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

    function getDepartments( $publiconly=false) {
        
        $depts=array();
        $sql ='SELECT dept_id, dept_name FROM '.DEPT_TABLE;
        if($publiconly)
            $sql.=' WHERE ispublic=1';

        if(($res=db_query($sql)) && db_num_rows($res)) {
            while(list($id, $name)=db_fetch_row($res))
                $depts[$id] = $name;
        }

        return $depts;
    }

    function getPublicDepartments() {
        return self::getDepartments(true);
    }

    function create($vars,&$errors) {
        return Dept::save(0,$vars,$errors);
    }

    function save($id,$vars,&$errors) {
        global $cfg;
                
        if($id && $id!=$vars['id'])
            $errors['err']='Missing or invalid Dept ID (internal error).';
            
        if(!$vars['email_id'] || !is_numeric($vars['email_id']))
            $errors['email_id']='Email selection required';
            
        if(!is_numeric($vars['tpl_id']))
            $errors['tpl_id']='Template selection required';

        if(!$vars['name']){
            $errors['name']='Name required';
        }elseif(strlen($vars['name'])<4) {
            $errors['name']='Name is too short.';
        }elseif(($did=Dept::getIdByName($vars['name'])) && $did!=$id){
            $errors['name']='Department already exist';
        }
        
        if(!$vars['ispublic'] && ($vars['id']==$cfg->getDefaultDeptId()))
            $errors['ispublic']='System default department can not be private';

        if($errors) return false;

            
        $sql='SET updated=NOW() '
            .' ,ispublic='.db_input($vars['ispublic'])
            .' ,email_id='.db_input($vars['email_id'])
            .' ,tpl_id='.db_input($vars['tpl_id'])
            .' ,sla_id='.db_input($vars['sla_id'])
            .' ,autoresp_email_id='.db_input($vars['autoresp_email_id'])
            .' ,manager_id='.db_input($vars['manager_id']?$vars['manager_id']:0)
            .' ,dept_name='.db_input(Format::striptags($vars['name']))
            .' ,dept_signature='.db_input(Format::striptags($vars['signature']))
            .' ,ticket_auto_response='.db_input(isset($vars['ticket_auto_response'])?$vars['ticket_auto_response']:1)
            .' ,message_auto_response='.db_input(isset($vars['message_auto_response'])?$vars['message_auto_response']:1);

            
        if($id) {
            $sql='UPDATE '.DEPT_TABLE.' '.$sql.' WHERE dept_id='.db_input($id);
            if(db_query($sql) && db_affected_rows())
                return true;
            
            $errors['err']='Unable to update '.Format::htmlchars($vars['name']).' Dept. Error occurred';
           
        }else{
            $sql='INSERT INTO '.DEPT_TABLE.' '.$sql.',created=NOW()';
            if(db_query($sql) && ($id=db_insert_id()))
                return $id;

            
            $errors['err']='Unable to create department. Internal error';
            
        }

        
        return false;
    }

}
?>
