<?php
/*********************************************************************
    class.file.php

    Peter Rotich <peter@osticket.com>
    Copyright (c)  2006-2012 osTicket
    http://www.osticket.com

    Released under the GNU General Public License WITHOUT ANY WARRANTY.
    See LICENSE.TXT for details.

    vim: expandtab sw=4 ts=4 sts=4:
**********************************************************************/

class AttachmentFile {

    var $id;
    var $ht;

    function AttachmentFile($id) {
        $this->id =0;
        return ($this->load($id));
    }

    function load($id=0) {

        if(!$id && !($id=$this->getId()))
            return false;

        $sql='SELECT f.*, count(DISTINCT c.canned_id) as canned, count(DISTINCT t.ticket_id) as tickets '
            .' FROM '.FILE_TABLE.' f '
            .' LEFT JOIN '.CANNED_ATTACHMENT_TABLE.' c ON(c.file_id=f.id) '
            .' LEFT JOIN '.TICKET_ATTACHMENT_TABLE.' t ON(t.file_id=f.id) '
            .' WHERE f.id='.db_input($id)
            .' GROUP BY f.id';
        if(!($res=db_query($sql)) || !db_num_rows($res))
            return false;

        $this->ht=db_fetch_array($res);
        $this->id =$this->ht['id'];

        return true;
    }

    function reload() {
        return $this->load();
    }

    function getHashtable() {
        return $this->ht;
    }

    function getInfo() {
        return $this->getHashtable();
    }

    function getNumTickets() {
        return $this->ht['tickets'];
    }

    function isCanned() {
        return ($this->ht['canned']);
    }

    function isInUse() {
        return ($this->getNumTickets() || $this->isCanned());
    }

    function getId() {
        return $this->id;
    }

    function getType() {
        return $this->ht['type'];
    }

    function getMime() {
        return $this->getType();
    }

    function getSize() {
        return $this->ht['size'];
    }

    function getName() {
        return $this->ht['name'];
    }

    function getHash() {
        return $this->ht['hash'];
    }

    function getBinary() {
        return $this->ht['filedata'];
    }

    function getData() {
        return $this->getBinary();
    }

    function delete() {

        $sql='DELETE FROM '.FILE_TABLE.' WHERE id='.db_input($this->getId()).' LIMIT 1';
        return (db_query($sql) && db_affected_rows());
    }


    function display() {
       

        header('Content-type: '.$this->getType()?$this->getType():'application/octet-stream');
        header('Content-Length: '.$this->getSize());
        echo $this->getData();
        exit();
    }

    function download() {

        header('Pragma: public');
        header('Expires: 0');
        header('Cache-Control: must-revalidate, post-check=0, pre-check=0');
        header('Cache-Control: public');
        header('Content-Type: application/octet-stream');

        //header('Content-Type: '.$this->getType()?$this->getType():'application/octet-stream');
    
        $filename=basename($this->getName());
        $user_agent = strtolower ($_SERVER['HTTP_USER_AGENT']);
        if ((is_integer(strpos($user_agent,'msie'))) && (is_integer(strpos($user_agent,'win')))) {
            header('Content-Disposition: filename='.$filename.';');
        }else{
            header('Content-Disposition: attachment; filename='.$filename.';' );
        }
        
        header('Content-Transfer-Encoding: binary');
        header('Content-Length: '.$this->getSize());
        echo $this->getBinary();
        exit();
    }

    /* Function assumes the files types have been validated */
    function upload($file) {
        
        if(!$file['name'] || !is_uploaded_file($file['tmp_name']))
            return false;

        $info=array('type'=>$file['type'],
                    'size'=>$file['size'],
                    'name'=>$file['name'],
                    'hash'=>MD5(MD5_FILE($file['tmp_name']).time()),
                    'data'=>file_get_contents($file['tmp_name'])
                    );

        return AttachmentFile::save($info);
    }

    function save($file) {

        if(!$file['hash'])
            $file['hash']=MD5(MD5($file['data']).time());
        if(!$file['size'])
            $file['size']=strlen($file['data']);


        
        //TODO: Do chunked INSERTs - 
        if(($mps=db_get_variable('max_allowed_packet')) && $file['size']>($mps*0.7)) {
            @db_set_variable('max_allowed_packet',$file['size']+$mps);
        }
        
        $sql='INSERT INTO '.FILE_TABLE.' SET created=NOW() '
            .',type='.db_input($file['type'])
            .',size='.db_input($file['size'])
            .',name='.db_input($file['name'])
            .',hash='.db_input($file['hash']);

        if (!(db_query($sql) && ($id=db_insert_id())))
            return false;

        foreach (str_split($file['data'], 1024*100) as $chunk) {
            if (!db_query('UPDATE '.FILE_TABLE.' SET filedata = CONCAT(filedata,'
                    .db_input($chunk).') WHERE id='.db_input($id)))
                # Remove partially uploaded file contents
                return false;
        }
        return $id;
    }

    /* Static functions */
    function getIdByHash($hash) {

        $sql='SELECT id FROM '.FILE_TABLE.' WHERE hash='.db_input($hash);
        if(($res=db_query($sql)) && db_num_rows($res))
            list($id)=db_fetch_row($res);

        return $id;
    }

    function lookup($id) {

        $id = is_numeric($id)?$id:AttachmentFile::getIdByHash($id);
        
        return ($id && ($file = new AttachmentFile($id)) && $file->getId()==$id)?$file:null;
    }
    /**
     * Removes files and associated meta-data for files which no ticket,
     * canned-response, or faq point to any more.
     */
    /* static */ function deleteOrphans() {
        $res=db_query(
            'DELETE FROM '.FILE_TABLE.' WHERE id NOT IN ('
                # DISTINCT implies sort and may not be necessary
                .'SELECT DISTINCT(file_id) FROM ('
                    .'SELECT file_id FROM '.TICKET_ATTACHMENT_TABLE
                    .' UNION ALL '
                    .'SELECT file_id FROM '.CANNED_ATTACHMENT_TABLE
                    .' UNION ALL '
                    .'SELECT file_id FROM '.FAQ_ATTACHMENT_TABLE
                .') still_loved'
            .')');
        return db_affected_rows();
    }
}

class AttachmentList {
    function AttachmentList($table, $key) {
        $this->table = $table;
        $this->key = $key;
    }

    function all() {
        if (!isset($this->list)) {
            $this->list = array();
            $res=db_query('SELECT file_id FROM '.$this->table
                .' WHERE '.$this->key);
            while(list($id) = db_fetch_row($res)) {
                $this->list[] = new AttachmentFile($id);
            }
        }
        return $this->list;
    }
    
    function getCount() {
        return count($this->all());
    }

    function add($fileId) {
        db_query(
            'INSERT INTO '.$this->table
                .' SET '.$this->key
                .' file_id='.db_input($fileId));
    }

    function remove($fileId) {
        db_query(
            'DELETE FROM '.$this->table
                .' WHERE '.$this->key
                .' AND file_id='.db_input($fileId));
    }
}
?>
