<?php
/*********************************************************************
    class.timezone.php

    Time zone get utils.

    Peter Rotich <peter@osticket.com>
    Copyright (c)  2006-2013 osTicket
    http://www.osticket.com

    Released under the GNU General Public License WITHOUT ANY WARRANTY.
    See LICENSE.TXT for details.

    vim: expandtab sw=4 ts=4 sts=4:
**********************************************************************/

class Timezone {

    var $id;
    var $ht;

    function Timezone($id){
        $this->id=0;
        return $this->load($id);
    }

    function load($id=0) {

        if(!$id && !($id=$this->getId()))
            return false;

        $sql='SELECT * FROM '.TIMEZONE_TABLE.' WHERE id='.db_input($id);
        if(!($res=db_query($sql)) || !db_num_rows($res))
            return false;

        $this->ht=db_fetch_array($res);
        $this->id=$this->ht['id'];
        
        return $this->id;
    }

    function reload() {
        return $this->load();
    }

    function getId() { 
        return $this->id;
    }
        
    function getOffset() {
        return $this->ht['offset'];    
    }

    function getName() {
        return $this->info['timezone'];
    }

    function getDesc() {
        return $this->getName();
    }

    /* static functions */
    function lookup($id) {
        return ($id && is_numeric($id) && ($tz= new Timezone($id)) && $tz->getId()==$id)?$tz:null;
    }

    function getOffsetById($id) {
        return ($tz=Timezone::lookup($id))?$tz->getOffset():0;
    }
}
?>
