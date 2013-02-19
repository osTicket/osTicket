<?php
/*********************************************************************
    class.priority.php

    Priority handle

    Peter Rotich <peter@osticket.com>
    Copyright (c)  2006-2013 osTicket
    http://www.osticket.com

    Released under the GNU General Public License WITHOUT ANY WARRANTY.
    See LICENSE.TXT for details.

    vim: expandtab sw=4 ts=4 sts=4:
**********************************************************************/

class Priority {
    
    var $id;
    var $ht;

    function Priority($id){
        
        $this->id =0;
        $this->load($id);
    }

    function load($id) {
        if(!$id && !($id=$this->getId()))
            return false;


        $sql='SELECT *  FROM '.PRIORITY_TABLE
            .' WHERE priority_id='.db_input($id);
        if(!($res=db_query($sql)) || !db_num_rows($res))
            return false;

        $this->ht= db_fetch_array($res);
        $this->id= $this->ht['priority_id'];

        return true;;
    }

    function getId() {
        return $this->id;
    }

    function getTag() {
        return $this->ht['priority'];
    }

    function getDesc() {
        return $this->ht['priority_desc'];
    }

    function getColor() {
        return $this->ht['priority_color'];
    }

    function getUrgency() {
        return $this->ht['priority_urgency'];
    }

    function isPublic() {
        return ($this->ht['ispublic']);
    }

    /* ------------- Static ---------------*/
    function lookup($id) {
        return ($id && is_numeric($id) && ($p=new Priority($id)) && $p->getId()==$id)?$p:null;
    }

    function getPriorities( $publicOnly=false) {

        $priorities=array();
        $sql ='SELECT priority_id, priority_desc FROM '.PRIORITY_TABLE;
        if($publicOnly)
            $sql.=' WHERE ispublic=1';

        if(($res=db_query($sql)) && db_num_rows($res)) {
            while(list($id, $name)=db_fetch_row($res))
                $priorities[$id] = $name;
        }

        return $priorities;
    }

    function getPublicPriorities() {
        return self::getPriorities(true);
    }
}
?>
