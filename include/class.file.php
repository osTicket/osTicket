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

        $sql='SELECT id, type, size, name, hash, f.created, '
            .' count(DISTINCT c.canned_id) as canned, count(DISTINCT t.ticket_id) as tickets '
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

    function open() {
        return new AttachmentChunkedData($this->id);
    }

    function sendData() {
        $file = $this->open();
        while ($chunk = $file->read())
            echo $chunk;
    }

    function getData() {
        # XXX: This is horrible, and is subject to php's memory
        #      restrictions, etc. Don't use this function!
        ob_start();
        $this->sendData();
        $data = &ob_get_contents();
        ob_end_clean();
        return $data;
    }

    function delete() {

        $sql='DELETE FROM '.FILE_TABLE.' WHERE id='.db_input($this->getId()).' LIMIT 1';
        if(!db_query($sql) || !db_affected_rows())
            return false;

        //Delete file data.
        AttachmentChunkedData::deleteOrphans();

        return true;
    }


    function display() {
       

        header('Content-Type: '.($this->getType()?$this->getType():'application/octet-stream'));
        header('Content-Length: '.$this->getSize());
        $this->sendData();
        exit();
    }

    function download() {

        header('Pragma: public');
        header('Expires: 0');
        header('Cache-Control: must-revalidate, post-check=0, pre-check=0');
        header('Cache-Control: public');
        header('Content-Type: '.($this->getType()?$this->getType():'application/octet-stream'));
    
        $filename=basename($this->getName());
        $user_agent = strtolower ($_SERVER['HTTP_USER_AGENT']);
        if ((is_integer(strpos($user_agent,'msie'))) && (is_integer(strpos($user_agent,'win')))) {
            header('Content-Disposition: filename='.$filename.';');
        }else{
            header('Content-Disposition: attachment; filename='.$filename.';' );
        }
        
        header('Content-Transfer-Encoding: binary');
        header('Content-Length: '.$this->getSize());
        $this->sendData();
        exit();
    }

    /* Function assumes the files types have been validated */
    function upload($file) {
        
        if(!$file['name'] || $file['error'] || !is_uploaded_file($file['tmp_name']))
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
        
        $sql='INSERT INTO '.FILE_TABLE.' SET created=NOW() '
            .',type='.db_input($file['type'])
            .',size='.db_input($file['size'])
            .',name='.db_input($file['name'])
            .',hash='.db_input($file['hash']);

        if (!(db_query($sql) && ($id=db_insert_id())))
            return false;

        $data = new AttachmentChunkedData($id);
        if (!$data->write($file['data']))
            return false;

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
        
        $sql = 'DELETE FROM '.FILE_TABLE.' WHERE id NOT IN ('
                # DISTINCT implies sort and may not be necessary
                .'SELECT DISTINCT(file_id) FROM ('
                    .'SELECT file_id FROM '.TICKET_ATTACHMENT_TABLE
                    .' UNION ALL '
                    .'SELECT file_id FROM '.CANNED_ATTACHMENT_TABLE
                    .' UNION ALL '
                    .'SELECT file_id FROM '.FAQ_ATTACHMENT_TABLE
                .') still_loved'
            .')';

        db_query($sql);
        
        //Delete orphaned chuncked data!
        AttachmentChunkedData::deleteOrphans();
    
        return true;

    }
}

/**
 * Attachments stored in the database are cut into 256kB chunks and stored
 * in the FILE_CHUNK_TABLE to overcome the max_allowed_packet limitation of
 * LOB fields in the MySQL database
 */
define('CHUNK_SIZE', 500*1024); # Beware if you change this...
class AttachmentChunkedData {
    function AttachmentChunkedData($file) {
        $this->_file = $file;
        $this->_pos = 0;
    }

    function length() {
        list($length) = db_fetch_row(db_query(
             'SELECT SUM(LENGTH(filedata)) FROM '.FILE_CHUNK_TABLE
            .' WHERE file_id='.db_input($this->_file)));
        return $length;
    }

    function read() {
        # Read requested length of data from attachment chunks
        list($buffer) = @db_fetch_row(db_query(
            'SELECT filedata FROM '.FILE_CHUNK_TABLE.' WHERE file_id='
            .db_input($this->_file).' AND chunk_id='.$this->_pos++));
        return $buffer;
    }

    function write($what, $chunk_size=CHUNK_SIZE) {
        $offset=0;
        for (;;) {
            $block = substr($what, $offset, $chunk_size);
            if (!$block) break;
            if (!db_query('REPLACE INTO '.FILE_CHUNK_TABLE
                    .' SET filedata=0x'.bin2hex($block).', file_id='
                    .db_input($this->_file).', chunk_id='.db_input($this->_pos++)))
                return false;
            $offset += strlen($block);
        }

        return $this->_pos;
    }

    function deleteOrphans() {
            
        $sql = 'DELETE c.* FROM '.FILE_CHUNK_TABLE.' c '
             . ' LEFT JOIN '.FILE_TABLE.' f ON(f.id=c.file_id) '
             . ' WHERE f.id IS NULL';
        
        return db_query($sql)?db_affected_rows():0;
    }
}
?>
