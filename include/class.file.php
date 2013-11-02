<?php
/*********************************************************************
    class.file.php

    Peter Rotich <peter@osticket.com>
    Copyright (c)  2006-2013 osTicket
    http://www.osticket.com

    Released under the GNU General Public License WITHOUT ANY WARRANTY.
    See LICENSE.TXT for details.

    vim: expandtab sw=4 ts=4 sts=4:
**********************************************************************/
require_once(INCLUDE_DIR.'class.signal.php');

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

        $sql='SELECT id, f.type, size, name, hash, ft, f.created, '
            .' count(DISTINCT a.object_id) as canned, count(DISTINCT t.ticket_id) as tickets '
            .' FROM '.FILE_TABLE.' f '
            .' LEFT JOIN '.ATTACHMENT_TABLE.' a ON(a.file_id=f.id) '
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

    function getBackend() {
        return $this->ht['ft'];
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

    function lastModified() {
        return $this->ht['created'];
    }

    /**
     * Retrieve a hash that can be sent to scp/file.php?h= in order to
     * download this file
     */
    function getDownloadHash() {
        return strtolower($this->getHash() . md5($this->getId().session_id().$this->getHash()));
    }

    function open() {
        return AttachmentStorageBackend::getInstance($this);
    }

    function sendData($redirect=true) {
        $bk = $this->open();
        if ($redirect && $bk->sendRedirectUrl())
            return;

        @ini_set('zlib.output_compression', 'Off');
        $bk->passthru();
    }

    function getData() {
        # XXX: This is horrible, and is subject to php's memory
        #      restrictions, etc. Don't use this function!
        ob_start();
        $this->sendData(false);
        $data = &ob_get_contents();
        ob_end_clean();
        return $data;
    }

    function delete() {

        $sql='DELETE FROM '.FILE_TABLE.' WHERE id='.db_input($this->getId()).' LIMIT 1';
        if(!db_query($sql) || !db_affected_rows())
            return false;

        if ($bk = $this->open())
            $bk->unlink();

        return true;
    }

    function makeCacheable($ttl=86400) {
        Http::cacheable($this->getHash(), $this->lastModified(), $ttl);
    }

    function display($scale=false) {
        $this->makeCacheable();

        if ($scale && extension_loaded('gd')) {
            $image = imagecreatefromstring($this->getData());
            $width = imagesx($image);
            if ($scale <= $width) {
                $height = imagesy($image);
                if ($width > $height) {
                    $heightp = $height * (int)$scale / $width;
                    $widthp = $scale;
                } else {
                    $widthp = $width * (int)$scale / $height;
                    $heightp = $scale;
                }
                $thumb = imagecreatetruecolor($widthp, $heightp);
                $white = imagecolorallocate($thumb, 255,255,255);
                imagefill($thumb, 0, 0, $white);
                imagecopyresized($thumb, $image, 0, 0, 0, 0, $widthp,
                    $heightp, $width, $height);
                header('Content-Type: image/png');
                imagepng($thumb);
                return;
            }
        }
        header('Content-Type: '.($this->getType()?$this->getType():'application/octet-stream'));
        header('Content-Length: '.$this->getSize());
        $this->sendData();
        exit();
    }

    function download() {
        $this->makeCacheable();

        header('Content-Type: '.($this->getType()?$this->getType():'application/octet-stream'));

        $filename=basename($this->getName());
        $user_agent = strtolower ($_SERVER['HTTP_USER_AGENT']);
        if (false !== strpos($user_agent,'msie') && false !== strpos($user_agent,'win'))
            header('Content-Disposition: filename='.rawurlencode($filename).';');
        elseif (false !== strpos($user_agent, 'safari') && false === strpos($user_agent, 'chrome'))
            // Safari and Safari only can handle the filename as is
            header('Content-Disposition: filename='.str_replace(',', '', $filename).';');
        else
            // Use RFC5987
            header("Content-Disposition: filename*=UTF-8''".rawurlencode($filename).';' );

        header('Content-Transfer-Encoding: binary');
        header('Content-Length: '.$this->getSize());
        $this->sendData();
        exit();
    }

    function _getKeyAndHash($data=false, $file=false) {
        if ($file) {
            $sha1 = base64_encode(sha1_file($data, true));
            $md5 = base64_encode(md5_file($data, true));
        }
        else {
            $sha1 = base64_encode(sha1($data, true));
            $md5 = base64_encode(md5($data, true));
        }

        // Use 5 chars from the microtime() prefix and 27 chars from the
        // sha1 hash. This should make a sufficiently strong unique key for
        // file content. In the event there is a sha1 collision for data, it
        // should be unlikely that there will be a collision for the
        // microtime hash coincidently.  Remove =, change + and / to chars
        // better suited for URLs and filesystem paths
        $prefix = base64_encode(sha1(microtime(), true));
        $key = str_replace(
            array('=','+','/'),
            array('','-','_'),
            substr($prefix, 0, 5) . $sha1);

        // The hash is a 32-char value where the first half is from the last
        // 16 chars from the SHA1 hash and the last 16 chars are the last 16
        // chars from the MD5 hash. This should provide for better
        // resiliance against hash collisions and attacks against any one
        // hash algorithm. Since we're using base64 encoding, with 6-bits
        // per char, we should have a total hash strength of 192 bits.
        $hash = str_replace(
            array('=','+','/'),
            array('','-','_'),
            substr($sha1, -16) . substr($md5, -16));

        return array($key, $hash);
    }

    /* Function assumes the files types have been validated */
    function upload($file, $ft=false) {

        if(!$file['name'] || $file['error'] || !is_uploaded_file($file['tmp_name']))
            return false;

        list($key, $hash) = static::_getKeyAndHash($file['tmp_name'], true);

        $info=array('type'=>$file['type'],
                    'filetype'=>$ft,
                    'size'=>$file['size'],
                    'name'=>$file['name'],
                    'key'=>$key,
                    'hash'=>$hash,
                    'tmp_nape'=>$file['tmp_name'],
                    );

        return AttachmentFile::save($info);
    }

    function uploadLogo($file, &$error, $aspect_ratio=3) {
        /* Borrowed in part from
         * http://salman-w.blogspot.com/2009/04/crop-to-fit-image-using-aspphp.html
         */
        if (!extension_loaded('gd'))
            return self::upload($file, 'L');

        $source_path = $file['tmp_name'];

        list($source_width, $source_height, $source_type) = getimagesize($source_path);

        switch ($source_type) {
            case IMAGETYPE_GIF:
            case IMAGETYPE_JPEG:
            case IMAGETYPE_PNG:
                break;
            default:
                // TODO: Return an error
                $error = 'Invalid image file type';
                return false;
        }

        $source_aspect_ratio = $source_width / $source_height;

        if ($source_aspect_ratio >= $aspect_ratio)
            return self::upload($file, 'L');

        $error = 'Image is too square. Upload a wider image';
        return false;
    }

    function save($file, $save_bk=true) {

        if (isset($file['data'])) {
            // Allow a callback function to delay or avoid reading or
            // fetching ihe file contents
            if (is_callable($file['data']))
                $file['data'] = $file['data']();

            list($file['key'], $file['hash'])
                = static::_getKeyAndHash($file['data']);

            if (!isset($file['size']))
                $file['size'] = strlen($file['data']);
        }

        // Check and see if the file is already on record
        $sql = 'SELECT id FROM '.FILE_TABLE
            .' WHERE hash='.db_input($file['hash'])
            .' AND size='.db_input($file['size']);

        // If the record exists in the database already, a file with the
        // same hash and size is already on file -- just return its ID
        if ($id = db_result(db_query($sql)))
            return $id;

        $sql='INSERT INTO '.FILE_TABLE.' SET created=NOW() '
            .',type='.db_input(strtolower($file['type']))
            .',size='.db_input($file['size'])
            .',name='.db_input($file['name'])
            .',hash='.db_input($file['hash']);

        if (!(db_query($sql) && ($file['id']=db_insert_id())))
            return false;

        $bk = self::getBackendForFile($file);
        if (isset($file['tmp_file'])) {
            if (!$bk->upload($file['tmp_file']))
                return false;
        }
        elseif (!$bk->write($file['data'])) {
            // XXX: Fallthrough to default backend if different?
            return false;
        }

        # XXX: ft does not exists during the upgrade when attachments are
        #      migrated!
        if ($save_bk) {
            $sql .= 'UPDATE '.FILE_TABLE.' SET ft='
                .db_input(AttachmentStorageBackend::getTypeChar($bk))
                .' WHERE id='.db_input($file->getId());
            db_query($sql);
        }

        return $file->getId();
    }

    /**
     * Migrate this file from the current backend to the backend specified.
     *
     * Parameters:
     * $bk - (string) type char of the target storage backend. Use
     *      AttachmentStorageBackend::allRegistered() to get a list of type
     *      chars and associated class names
     *
     * Returns:
     * True if the migration was successful and false otherwise.
     */
    function migrate($bk) {
        $before = hash_init('sha1');
        $after = hash_init('sha1');

        // Copy the file to the new backend and hash the contents
        $target = AttachmentStorageBackend::lookup($bk, $this->ht);
        print("Opening source\n");
        $source = $this->open();
        print("Copying data ");
        // TODO: Make this resumable so that if the file cannot be migrated
        //      in the max_execution_time, the migration can be continued
        //      the next time the cron runs
        while ($block = $source->read()) {
            print(".");
            hash_update($before, $block);
            $target->write($block);
        }
        print(" Done\n");

        // Verify that the hash of the target file matches the hash of the
        // source file
        print("Verifying transferred data\n");
        $target = AttachmentStorageBackend::lookup($bk, $this->ht);
        while ($block = $target->read())
            hash_update($after, $block);

        if (hash_final($before) != hash_final($after)) {
            $target->unlink();
            return false;
        }

        print("Updating file meta table\n");
        $sql = 'UPDATE '.FILE_TABLE.' SET ft='
            .db_input($target->getTypeChar())
            .' WHERE id='.db_input($this->getId());
        if (!db_query($sql) || db_affected_rows()!=1)
            return false;

        print("Unlinking source data\n");
        return $source->unlink();
    }

    static function getBackendForFile($info) {
        return AttachmentStorageBackend::lookup('T', $info);
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

    static function create($info, &$errors) {
        if (isset($info['encoding'])) {
            switch ($info['encoding']) {
                case 'base64':
                    $info['data'] = base64_decode($info['data']);
            }
        }
        return self::save($info);
    }

    /*
      Method formats http based $_FILE uploads - plus basic validation.
      @restrict - make sure file type & size are allowed.
     */
    function format($files, $restrict=false) {
        global $ost;

        if(!$files || !is_array($files))
            return null;

        //Reformat $_FILE  for the sane.
        $attachments = array();
        foreach($files as $k => $a) {
            if(is_array($a))
                foreach($a as $i => $v)
                    $attachments[$i][$k] = $v;
        }

        //Basic validation.
        foreach($attachments as $i => &$file) {
            //skip no file upload "error" - why PHP calls it an error is beyond me.
            if($file['error'] && $file['error']==UPLOAD_ERR_NO_FILE) {
                unset($attachments[$i]);
                continue;
            }

            if($file['error']) //PHP defined error!
                $file['error'] = 'File upload error #'.$file['error'];
            elseif(!$file['tmp_name'] || !is_uploaded_file($file['tmp_name']))
                $file['error'] = 'Invalid or bad upload POST';
            elseif($restrict) { // make sure file type & size are allowed.
                if(!$ost->isFileTypeAllowed($file))
                    $file['error'] = 'Invalid file type for '.Format::htmlchars($file['name']);
                elseif($ost->getConfig()->getMaxFileSize()
                        && $file['size']>$ost->getConfig()->getMaxFileSize())
                    $file['error'] = sprintf('File %s (%s) is too big. Maximum of %s allowed',
                            Format::htmlchars($file['name']),
                            Format::file_size($file['size']),
                            Format::file_size($ost->getConfig()->getMaxFileSize()));
            }
        }
        unset($file);

        return array_filter($attachments);
    }

    /**
     * Removes files and associated meta-data for files which no ticket,
     * canned-response, or faq point to any more.
     */
    /* static */ function deleteOrphans() {

        // XXX: Allow plugins to define filetypes which do not represent
        //      files attached to tickets or other things in the attachment
        //      table and are not logos
        $sql = 'SELECT id FROM '.FILE_TABLE.' WHERE id NOT IN ('
                .'SELECT file_id FROM '.TICKET_ATTACHMENT_TABLE
                .' UNION '
                .'SELECT file_id FROM '.ATTACHMENT_TABLE
            .") AND `ft` NOT IN ('L')";

        if (!($res = db_query($sql)))
            return false;

        while (list($id) = db_fetch_row($res))
            if (($file = self::lookup($id))
                    && ($bk = $file->open()))
                $bk->unlink();

        return true;
    }

    /* static */
    function allLogos() {
        $sql = 'SELECT id FROM '.FILE_TABLE.' WHERE ft="L"
            ORDER BY created';
        $logos = array();
        $res = db_query($sql);
        while (list($id) = db_fetch_row($res))
            $logos[] = AttachmentFile::lookup($id);
        return $logos;
    }
}

class AttachmentStorageBackend {
    var $meta;
    static $desc = false;
    static $registry;

    /**
     * All storage backends should call this function during the request
     * bootstrap phase.
     */
    static function register($typechar, $class) {
        self::$registry[$typechar] = $class;
    }

    static function allRegistered() {
        return self::$registry;
    }

    /**
     * Retrieves the type char registered for this storage backend's class.
     * Null is returned if the backend is not properly registered.
     */
    function getTypeChar() {
        foreach (self::$registry as $tc=>$class)
            if ($this instanceof $class)
                return $tc;
    }

    static function lookup($type, $file=null) {
        if (!isset(self::$registry[$type]))
            throw new Exception("No such backend registered");

        $class = self::$registry[$type];
        return new $class($file);
    }

    static function getInstance($file) {
        if (!isset(self::$registry[$file->getBackend()]))
            throw new Exception("No such backend registered");

        $class = self::$registry[$file->getBackend()];
        return new $class($file);
    }

    /**
     * Create an instance of the storage backend linking the related file.
     * Information about the file metadata is accessible via the received
     * filed object.
     */
    function __construct($meta) {
        $this->meta = $meta;
    }

    /**
     * Commit file to the storage backend. This method is used if the
     * backend cannot support writing a file directly. Otherwise, the
     * ::upload($file) method is preferred.
     *
     * Parameters:
     * $data - (string|binary) file contents to be written to the backend
     */
    function write($data) {
        return false;
    }

    /**
     * Upload a file to the backend. This method is preferred over ::write()
     * for files which are uploaded or are otherwise available out of
     * memory. The backend is encouraged to avoid reading the entire
     * contents into memory.
     */
    function upload($filepath) {
        return static::write(file_get_contents($filepath));
    }

    /**
     * Returns data from the backend, optionally returning only the number
     * of bytes indicated at the specified offset. If the data is available
     * in chunks, one chunk may be returned at a time. The backend should
     * return boolean false when no more chunks are available.
     */
    function read($amount=0, $offset=0) {
        return false;
    }

    /**
     * Convenience method to send all the file to standard output
     */
    function passthru() {
        while ($block = $this->read())
            echo $block;
    }

    /**
     * If the data is not stored or not available locally, a redirect
     * response can be sent to the user agent indicating the actual HTTP
     * location of the data.
     *
     * If the data is available locally, this method should return boolean
     * false to indicate that the read() method should be used to retrieve
     * the data and broker it to the user agent.
     */
    function sendRedirectUrl() {
        return false;
    }

    /**
     * Requests the backend to remove the file contents.
     */
    function unlink() {
        return false;
    }
}

/**
 * Attachments stored in the database are cut into 500kB chunks and stored
 * in the FILE_CHUNK_TABLE to overcome the max_allowed_packet limitation of
 * LOB fields in the MySQL database
 */
define('CHUNK_SIZE', 500*1024); # Beware if you change this...
class AttachmentChunkedData extends AttachmentStorageBackend {
    static $desc = "In the database";

    function __construct($file) {
        $this->file = $file;
        $this->_pos = 0;
    }

    function length() {
        list($length) = db_fetch_row(db_query(
             'SELECT SUM(LENGTH(filedata)) FROM '.FILE_CHUNK_TABLE
            .' WHERE file_id='.db_input($this->file->getId())));
        return $length;
    }

    function read() {
        # Read requested length of data from attachment chunks
        list($buffer) = @db_fetch_row(db_query(
            'SELECT filedata FROM '.FILE_CHUNK_TABLE.' WHERE file_id='
            .db_input($this->file->getId()).' AND chunk_id='.$this->_pos++));
        return $buffer;
    }

    function write($what, $chunk_size=CHUNK_SIZE) {
        $offset=0;
        for (;;) {
            $block = substr($what, $offset, $chunk_size);
            if (!$block) break;
            if (!db_query('REPLACE INTO '.FILE_CHUNK_TABLE
                    .' SET filedata=0x'.bin2hex($block).', file_id='
                    .db_input($this->file->getId()).', chunk_id='.db_input($this->_pos++)))
                return false;
            $offset += strlen($block);
        }

        return $this->_pos;
    }

    function unlink() {
        db_query('DELETE FROM '.FILE_CHUNK_TABLE
            .' WHERE file_id='.db_input($this->file->getId()));
        return db_affected_rows() > 0;
    }
}
AttachmentStorageBackend::register('T', 'AttachmentChunkedData');

?>
