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
require_once(INCLUDE_DIR.'class.error.php');

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

        $sql='SELECT id, f.type, size, name, `key`, signature, ft, bk, f.created, '
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
        return $this->ht['bk'];
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

    function getKey() {
        return $this->ht['key'];
    }

    function getSignature() {
        $sig = $this->ht['signature'];
        if (!$sig) return $this->getKey();
        return $sig;
    }

    function lastModified() {
        return $this->ht['created'];
    }

    /**
     * Retrieve a signature that can be sent to scp/file.php?h= in order to
     * download this file
     */
    function getDownloadHash() {
        return strtolower($this->getKey()
            . md5($this->getId().session_id().strtolower($this->getKey())));
    }

    function open() {
        return FileStorageBackend::getInstance($this);
    }

    function sendData($redirect=true, $disposition='inline') {
        $bk = $this->open();
        if ($redirect && $bk->sendRedirectUrl($disposition))
            return;

        @ini_set('zlib.output_compression', 'Off');
        try {
            $bk->passthru();
        }
        catch (IOException $ex) {
            Http::response(404, 'File not found');
        }
    }

    function getData() {
        # XXX: This is horrible, and is subject to php's memory
        #      restrictions, etc. Don't use this function!
        ob_start();
        try {
            $this->sendData(false);
        }
        catch (IOException $ex) {
            Http::response(404, 'File not found');
        }
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
        Http::cacheable($this->getSignature(), $this->lastModified(), $ttl);
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
        $bk = $this->open();
        if ($bk->sendRedirectUrl('inline'))
            return;
        $this->makeCacheable();
        Http::download($this->getName(), $this->getType() ?: 'application/octet-stream',
            null, 'inline');
        header('Content-Length: '.$this->getSize());
        $this->sendData(false);
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
            substr($sha1, 0, 16) . substr($md5, 0, 16));

        return array($key, $hash);
    }

    /* Function assumes the files types have been validated */
    function upload($file, $ft='T') {

        if(!$file['name'] || $file['error'] || !is_uploaded_file($file['tmp_name']))
            return false;

        list($key, $sig) = self::_getKeyAndHash($file['tmp_name'], true);

        $info=array('type'=>$file['type'],
                    'filetype'=>$ft,
                    'size'=>$file['size'],
                    'name'=>$file['name'],
                    'key'=>$key,
                    'signature'=>$sig,
                    'tmp_name'=>$file['tmp_name'],
                    );

        return AttachmentFile::save($info, $ft);
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

    function save(&$file, $ft='T') {

        if (isset($file['data'])) {
            // Allow a callback function to delay or avoid reading or
            // fetching ihe file contents
            if (is_callable($file['data']))
                $file['data'] = $file['data']();

            list($key, $file['signature'])
                = self::_getKeyAndHash($file['data']);
            if (!$file['key'])
                $file['key'] = $key;
        }

        if (isset($file['size'])) {
            // Check and see if the file is already on record
            $sql = 'SELECT id, `key` FROM '.FILE_TABLE
                .' WHERE signature='.db_input($file['signature'])
                .' AND size='.db_input($file['size']);

            // If the record exists in the database already, a file with the
            // same hash and size is already on file -- just return its ID
            if (list($id, $key) = db_fetch_row(db_query($sql))) {
                $file['key'] = $key;
                return $id;
            }
        }
        elseif (!isset($file['data'])) {
            // Unable to determine the file's size
            return false;
        }

        if (!$file['type']) {
            $finfo = new finfo(FILEINFO_MIME_TYPE);
            if ($file['data'])
                $type = $finfo->buffer($file['data']);
            elseif ($file['tmp_name'])
                $type = $finfo->file($file['tmp_name']);

            if ($type)
                $file['type'] = $type;
            else
                $file['type'] = 'application/octet-stream';
        }

        $sql='INSERT INTO '.FILE_TABLE.' SET created=NOW() '
            .',type='.db_input(strtolower($file['type']))
            .',name='.db_input($file['name'])
            .',`key`='.db_input($file['key'])
            .',ft='.db_input($ft ?: 'T')
            .',signature='.db_input($file['signature']);

        if (isset($file['size']))
            $sql .= ',size='.db_input($file['size']);

        if (!(db_query($sql) && ($id = db_insert_id())))
            return false;

        if (!($f = AttachmentFile::lookup($id)))
            return false;

        // Note that this is preferred over $f->open() because the file does
        // not have a valid backend configured yet. ::getBackendForFile()
        // will consider the system configuration for storing the file
        $bks = array(self::getBackendForFile($f));
        if (!$bks[0]->getBkChar() !== 'D')
            $bks[] = new AttachmentChunkedData($f);

        // Consider the selected backen first and then save to database
        // otherwise.
        $succeeded = false;
        foreach ($bks as $bk) {
            try {
                if (isset($file['tmp_name'])) {
                    if ($bk->upload($file['tmp_name'])) {
                        $succeeded = true; break;
                    }
                }
                elseif ($bk->write($file['data']) && $bk->flush()) {
                    $succeeded = true; break;
                }
            }
            catch (Exception $e) {
                // Try next backend
            }
            // Fallthrough to default backend if different?
        }
        if (!$succeeded) {
            // Unable to save data (weird)
            return false;
        }

        $sql = 'UPDATE '.FILE_TABLE.' SET bk='.db_input($bk->getBkChar());

        if (!isset($file['size'])) {
            if ($size = $bk->getSize())
                $file['size'] = $size;
            // Prefer mb_strlen, because mbstring.func_overload will
            // automatically prefer it if configured.
            elseif (extension_loaded('mbstring'))
                $file['size'] = mb_strlen($file['data'], '8bit');
            // bootstrap.php include a compat version of mb_strlen
            else
                $file['size'] = strlen($file['data']);

            $sql .= ', `size`='.db_input($file['size']);
        }

        $sql .= ' WHERE id='.db_input($f->getId());
        db_query($sql);

        return $f->getId();
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

        // Copy the file to the new backend and hash the contents
        $target = FileStorageBackend::lookup($bk, $this);
        $source = $this->open();

        // Initialize hashing algorithm to verify uploaded contents
        $algos = $target->getNativeHashAlgos();
        $common_algo = 'sha1';
        if ($algos && is_array($algos)) {
            $supported = hash_algos();
            foreach ($algos as $a) {
                if (in_array(strtolower($a), $supported)) {
                    $common_algo = strtolower($a);
                    break;
                }
            }
        }
        $before = hash_init($common_algo);
        // TODO: Make this resumable so that if the file cannot be migrated
        //      in the max_execution_time, the migration can be continued
        //      the next time the cron runs
        try {
            while ($block = $source->read($target->getBlockSize())) {
                hash_update($before, $block);
                $target->write($block);
            }
            $target->flush();
        }
        catch (Exception $e) {
            // Migration failed
            return false;
        }

        // Ask the backend to generate its own hash if at all possible
        if (!($target_hash = $target->getHashDigest($common_algo))) {
            $after = hash_init($common_algo);
            // Verify that the hash of the target file matches the hash of
            // the source file
            $target = FileStorageBackend::lookup($bk, $this);
            while ($block = $target->read())
                hash_update($after, $block);
            $target_hash = hash_final($after);
        }

        if (hash_final($before) != $target_hash) {
            $target->unlink();
            return false;
        }

        $sql = 'UPDATE '.FILE_TABLE.' SET bk='
            .db_input($target->getBkChar())
            .' WHERE id='.db_input($this->getId());
        if (!db_query($sql) || db_affected_rows()!=1)
            return false;

        return $source->unlink();
    }

    /**
     * Considers the system's configuration for file storage selection based
     * on the file information and purpose (FAQ attachment, image, etc).
     *
     * Parameters:
     * $file - (hasharray) file information which would be passed to
     * ::save() for instance.
     *
     * Returns:
     * Instance<FileStorageBackend> backend selected based on the file
     * received.
     */
    static function getBackendForFile($file) {
        global $cfg;

        if (!$cfg)
            return new AttachmentChunkedData($file);

        $char = $cfg->getDefaultStorageBackendChar();
        return FileStorageBackend::lookup($char, $file);
    }

    /* Static functions */
    function getIdByHash($hash) {

        $sql='SELECT id FROM '.FILE_TABLE.' WHERE `key`='.db_input($hash);
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
            .") AND `ft` = 'T'";

        if (!($res = db_query($sql)))
            return false;

        while (list($id) = db_fetch_row($res))
            if (($file = self::lookup($id)) && !$file->delete())
                break;

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

class FileStorageBackend {
    var $meta;
    static $desc = false;
    static $registry;
    static $blocksize = 131072;

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
    function getBkChar() {
        foreach (self::$registry as $tc=>$class)
            if ($this instanceof $class)
                return $tc;
    }

    static function isRegistered($type) {
        return isset(self::$registry[$type]);
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
     * Returns the optimal block size for the backend. When migrating, this
     * size blocks would be best for sending to the ::write() method
     */
    function getBlockSize() {
        return static::$blocksize;
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
     * Called after all the blocks are sent to the ::write() method. This
     * method should return boolean FALSE if flushing the data was
     * somehow inhibited.
     */
    function flush() {
        return true;
    }

    /**
     * Upload a file to the backend. This method is preferred over ::write()
     * for files which are uploaded or are otherwise available out of
     * memory. The backend is encouraged to avoid reading the entire
     * contents into memory.
     */
    function upload($filepath) {
        return $this->write(file_get_contents($filepath));
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
    function sendRedirectUrl($disposition='inline') {
        return false;
    }

    /**
     * Requests the backend to remove the file contents.
     */
    function unlink() {
        return false;
    }

    /**
     * Fetches a list of hash algorithms that are supported transparently
     * through the ::write() and ::upload() methods. After writing or
     * uploading file content, the ::getHashDigest($algo) method can be
     * called to get a hash of the remote content without fetching the
     * entire data stream to verify the content locally.
     */
    function getNativeHashAlgos() {
        return array();
    }

    /**
     * Returns a hash of the content calculated remotely by the storage
     * backend. If this method fails, the hash chould be calculated by
     * downloading the content and hashing locally
     */
    function getHashDigest($algo) {
        return false;
    }

    /**
     * getSize
     *
     * Retrieves the size of the contents written or available to be read.
     * The backend should optimize this process if possible by keeping track
     * of the bytes written in a way apart from `strlen`. This value will be
     * used instead of inspecting the contents using `strlen`.
     */
    function getSize() {
        return false;
    }
}


/**
 * Attachments stored in the database are cut into 500kB chunks and stored
 * in the FILE_CHUNK_TABLE to overcome the max_allowed_packet limitation of
 * LOB fields in the MySQL database
 */
define('CHUNK_SIZE', 500*1024); # Beware if you change this...
class AttachmentChunkedData extends FileStorageBackend {
    static $desc = "In the database";
    static $blocksize = CHUNK_SIZE;

    function __construct($file) {
        $this->file = $file;
        $this->_chunk = 0;
        $this->_buffer = false;
    }

    function getSize() {
        list($length) = db_fetch_row(db_query(
             'SELECT SUM(LENGTH(filedata)) FROM '.FILE_CHUNK_TABLE
            .' WHERE file_id='.db_input($this->file->getId())));
        return $length;
    }

    function read($amount=CHUNK_SIZE, $offset=0) {
        # Read requested length of data from attachment chunks
        while (strlen($this->_buffer) < $amount + $offset) {
            list($buf) = @db_fetch_row(db_query(
                'SELECT filedata FROM '.FILE_CHUNK_TABLE.' WHERE file_id='
                .db_input($this->file->getId()).' AND chunk_id='.$this->_chunk++));
            if (!$buf)
                break;
            $this->_buffer .= $buf;
        }
        $chunk = substr($this->_buffer, $offset, $amount);
        $this->_buffer = substr($this->_buffer, $offset + $amount);
        return $chunk;
    }

    function write($what, $chunk_size=CHUNK_SIZE) {
        $offset=0;
        $what = bin2hex($what);
        for (;;) {
            $block = substr($what, $offset, $chunk_size*2);
            if (!$block) break;
            if (!db_query('REPLACE INTO '.FILE_CHUNK_TABLE
                    .' SET filedata=0x'.$block.', file_id='
                    .db_input($this->file->getId()).', chunk_id='.db_input($this->_chunk++)))
                return false;
            $offset += strlen($block);
        }

        return $this->_chunk;
    }

    function unlink() {
        db_query('DELETE FROM '.FILE_CHUNK_TABLE
            .' WHERE file_id='.db_input($this->file->getId()));
        return db_affected_rows() > 0;
    }
}
FileStorageBackend::register('D', 'AttachmentChunkedData');

?>
