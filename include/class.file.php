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


/**
 * Represents a file stored in a storage backend. It is generally attached
 * to something; however company logos, login page backdrops, and other
 * items are also stored in the database for various purposes.
 *
 * FileType-Definitions:
 *    The `ft` field is used to represent the type or purpose of the file
 *    with respect to the system. These are the defined file types (placed
 *    here as the definitions are not needed in code).
 *
 *    - 'T' => Attachments
 *    - 'L' => Logo
 *    - 'B' => Backdrop
 */
class AttachmentFile extends VerySimpleModel {

    static $meta = array(
        'table' => FILE_TABLE,
        'pk' => array('id'),
        'joins' => array(
            'attachments' => array(
                'reverse' => 'Attachment.file'
            ),
        ),
    );
    static $keyCache = array();

    function __onload() {
        // Cache for lookup in the ::lookupByHash method below
        static::$keyCache[$this->key] = $this;
    }

    function getHashtable() {
        return $this->ht;
    }

    function getInfo() {
        return $this->getHashtable();
    }

    function getNumEntries() {
        return $this->attachments->count();
    }

    function isCanned() {
        return $this->getNumEntries();
    }

    function isInUse() {
        return $this->getNumEntries();
    }

    function getId() {
        return $this->id;
    }

    function getType() {
        return $this->type;
    }

    function getMimeType() {
        return $this->getType();
    }

    function getBackend() {
        return $this->bk;
    }

    function getSize() {
        return $this->size;
    }

    function getName() {
        return $this->name;
    }

    function getKey() {
        return $this->key;
    }

    function getAttrs() {
        return $this->attrs;
    }

    function getSignature($cascade=false) {
        $sig = $this->signature;
        if (!$sig && $cascade) return $this->getKey();
        return $sig;
    }

    function lastModified() {
        return $this->created;
    }

    function open() {
        return FileStorageBackend::getInstance($this);
    }

    function sendData($redirect=true, $ttl=false, $disposition='inline') {
        $bk = $this->open();
        if ($redirect && $bk->sendRedirectUrl($disposition, $ttl))
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

        if (!parent::delete())
            return false;

        if ($bk = $this->open())
            $bk->unlink();

        return true;
    }

    function makeCacheable($ttl=86400) {
        Http::cacheable($this->getSignature(true), $this->lastModified(), $ttl);
    }

    function display($scale=false, $ttl=86400) {
        $this->makeCacheable($ttl);

        if ($scale && extension_loaded('gd')
                && ($image = imagecreatefromstring($this->getData()))) {
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
        header("Content-Security-Policy: default-src 'self'");
        $this->sendData();
        exit();
    }

    function getDownloadUrl($options=array()) {
        // Add attachment ref id if object type is set
        if (isset($options['type'])
                && !isset($options['id'])
                && ($a=$this->attachments->findFirst(array(
                            'type' => $options['type']))))
            $options['id'] = $a->getId();

        return static::generateDownloadUrl($this->getId(),
                strtolower($this->getKey()), $this->getSignature(),
                $options);
    }

    // Generates full download URL for external sources.
    // e.g. https://domain.tld/file.php?args=123
    function getExternalDownloadUrl($options=array()) {
        global $cfg;

        $download = $this->getDownloadUrl($options);
        // Separate URL handle and args
        list($handle, $args) = explode('file.php?', $download);

        return (string) rtrim($cfg->getBaseUrl(), '/').'/file.php?'.$args;
    }

    static function generateDownloadUrl($id, $key, $hash, $options = array()) {

        // Expire at the nearest midnight, allow at least12 hrs access
        $minage = @$options['minage'] ?: 43200;
        $gmnow = Misc::gmtime() +  $options['minage'];
        $expires = $gmnow + 86400 - ($gmnow % 86400);

        // Generate a signature based on secret content
        $signature = static::_genUrlSignature($id, $key, $hash, $expires);

        // Handler / base url
        $handler = @$options['handler'] ?: ROOT_PATH . 'file.php';

        // Return sanitized query string
        $args = array(
            'key' => $key,
            'expires' => $expires,
            'signature' => $signature,
        );

        if (isset($options['disposition']))
            $args['disposition'] =  $options['disposition'];

        if (isset($options['id']))
            $args['id'] =  $options['id'];

        return sprintf('%s?%s', $handler, http_build_query($args));
    }

    function verifySignature($signature, $expires) {
        $gmnow = Misc::gmtime();
        if ($expires < $gmnow)
            return false;

        $check = static::_genUrlSignature($this->getId(), $this->getKey(),
            $this->getSignature(), $expires);
        return $signature == $check;
    }

    static function _genUrlSignature($id, $key, $signature, $expires) {
        $pieces = array(
            'Host='.$_SERVER['HTTP_HOST'],
            'Path='.ROOT_PATH,
            'Id='.$id,
            'Key='.strtolower($key),
            'Hash='.$signature,
            'Expires='.$expires,
        );
        return hash_hmac('sha1', implode("\n", $pieces), SECRET_SALT);
    }

    function download($name=false, $disposition=false, $expires=false) {
        $thisstaff = StaffAuthenticationBackend::getUser();
        $inline = ($thisstaff ? ($thisstaff->getImageAttachmentView() === 'inline') : false);
        $disposition = ((($disposition && strcasecmp($disposition, 'inline') == 0)
              || $inline)
              && strpos($this->getType(), 'image/') !== false)
            ? 'inline' : 'attachment';
        $ttl = ($expires) ? $expires - Misc::gmtime() : false;
        $bk = $this->open();
        if ($bk->sendRedirectUrl($disposition, $ttl))
            return;
        $this->makeCacheable($ttl);
        $type = $this->getType() ?: 'application/octet-stream';
        Http::download($name ?: $this->getName(), $type, null, $disposition);
        header('Content-Length: '.$this->getSize());
        $this->sendData(false);
        exit();
    }

    static function _getKeyAndHash($data=false, $file=false) {
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
    static function upload($file, $ft='T', $deduplicate=true) {

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

        return static::create($info, $ft, $deduplicate);
    }

    static function uploadBackdrop(array $file, &$error) {
        if (extension_loaded('gd')) {
            $source_path = $file['tmp_name'];
            list($source_width, $source_height, $source_type) = getimagesize($source_path);

            switch ($source_type) {
                case IMAGETYPE_GIF:
                case IMAGETYPE_JPEG:
                case IMAGETYPE_PNG:
                    break;
                default:
                    $error = __('Invalid image file type');
                    return false;
            }
        }
        return self::upload($file, 'B', false);
    }

    static function uploadLogo($file, &$error, $aspect_ratio=2) {
        /* Borrowed in part from
         * http://salman-w.blogspot.com/2009/04/crop-to-fit-image-using-aspphp.html
         */
        if (extension_loaded('gd')) {
            $source_path = $file['tmp_name'];
            list($source_width, $source_height, $source_type) = getimagesize($source_path);

            switch ($source_type) {
                case IMAGETYPE_GIF:
                case IMAGETYPE_JPEG:
                case IMAGETYPE_PNG:
                    break;
                default:
                    $error = __('Invalid image file type');
                    return false;
            }

            $source_aspect_ratio = $source_width / $source_height;

            if ($source_aspect_ratio < $aspect_ratio) {
                $error = __('Image is too square. Upload a wider image');
                return false;
            }
        }
        return self::upload($file, 'L', false);
    }

    static function create(&$file, $ft='T', $deduplicate=true) {
        if (isset($file['encoding'])) {
            switch ($file['encoding']) {
            case 'base64':
                $file['data'] = base64_decode($file['data']);
            }
        }

        if (!isset($file['data']) && isset($file['data_cbk'])
                && is_callable($file['data_cbk'])) {
            // Allow a callback function to delay or avoid reading or
            // fetching ihe file contents
            $file['data'] = $file['data_cbk']();
        }

        if (isset($file['data'])) {
            list($key, $file['signature'])
                = self::_getKeyAndHash($file['data']);
            if (!$file['key'])
                $file['key'] = $key;
        }

        if (isset($file['size']) && $file['size'] > 0) {
            // Check and see if the file is already on record
            $existing = static::objects()->filter(array(
                'signature' => $file['signature'],
                'size' => $file['size']
            ))->first();

            // If the record exists in the database already, a file with
            // the same hash and size is already on file -- just return
            // the file
            if ($deduplicate && $existing) {
                $file['key'] = $existing->key;
                return $existing;
            }
        }
        elseif (!isset($file['data'])) {
            // Unable to determine the file's size
            return false;
        }

        if (!$file['type'] && extension_loaded('fileinfo')) {
            $finfo = new finfo(FILEINFO_MIME_TYPE);
            if ($file['data'])
                $type = $finfo->buffer($file['data']);
            elseif ($file['tmp_name'])
                $type = $finfo->file($file['tmp_name']);

            if ($type)
                $file['type'] = $type;
        }
        if (!$file['type'])
            $file['type'] = 'application/octet-stream';


        $f = new static(array(
            'type' => strtolower($file['type']),
            'name' => $file['name'],
            'key' => $file['key'],
            'ft' => $ft ?: 'T',
            'signature' => $file['signature'],
            'created' => SqlFunction::NOW(),
        ));

        if (isset($file['size']))
            $f->size = $file['size'];

        if (!$f->save())
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

        $f->bk = $bk->getBkChar();
        $f->attrs = $bk->getAttrs() ?: NULL;

        if (!isset($file['size'])) {
            if ($size = $bk->getSize())
                $f->size = $size;
            // Prefer mb_strlen, because mbstring.func_overload will
            // automatically prefer it if configured.
            elseif (extension_loaded('mbstring'))
                $f->size = mb_strlen($file['data'], '8bit');
            // bootstrap.php include a compat version of mb_strlen
            else
                $f->size = strlen($file['data']);
        }

        $f->save();
        return $f;
    }

    static function __create($file, &$errors) {
        return static::create($file);
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

        $this->bk = $target->getBkChar();
        $this->attrs = $target->getAttrs() ?: NULL;
        if (!$this->save())
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

        $char = null;
        if ($cfg) {
            $char = $cfg->getDefaultStorageBackendChar();
        }
        try {
            return FileStorageBackend::lookup($char ?: 'D', $file);
        }
        catch (Exception $x) {
            return new AttachmentChunkedData($file);
        }
    }

    static function lookupByHash($hash) {
        if (isset(static::$keyCache[$hash]))
            return static::$keyCache[$hash];

        // Cache a negative lookup if no such file exists
        return parent::lookup(array('key' => $hash));
    }

    static function lookup($id) {
        return is_string($id)
            ? static::lookupByHash($id)
            : parent::lookup($id);
    }

    /*
      Method formats http based $_FILE uploads - plus basic validation.
     */
    static function format($files) {
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
            $file['name'] = Format::sanitize($file['name']);

            //skip no file upload "error" - why PHP calls it an error is beyond me.
            if($file['error'] && $file['error']==UPLOAD_ERR_NO_FILE) {
                unset($attachments[$i]);
                continue;
            }

            if($file['error']) //PHP defined error!
                $file['error'] = 'File upload error #'.$file['error'];
            elseif(!$file['tmp_name'] || !is_uploaded_file($file['tmp_name']))
                $file['error'] = 'Invalid or bad upload POST';
        }
        unset($file);

        return array_filter($attachments);
    }

    /**
     * Removes files and associated meta-data for files which no ticket,
     * canned-response, or faq point to any more.
     */
    static function deleteOrphans() {

        // XXX: Allow plugins to define filetypes which do not represent
        //      files attached to tickets or other things in the attachment
        //      table and are not logos
        $files = static::objects()
            ->filter(array(
                'attachments__object_id__isnull' => true,
                'ft' => 'T',
                'created__lt' => SqlFunction::NOW()->minus(SqlInterval::DAY(1)),
            ));

        foreach ($files as $f) {
            if (!$f->delete())
                break;
        }

        return true;
    }

    static function allLogos() {
        return static::objects()
            ->filter(array('ft' => 'L'))
            ->order_by('created');
    }

    static function allBackdrops() {
        return static::objects()
            ->filter(array('ft' => 'B'))
            ->order_by('created');
    }
}

class FileStorageBackend {
    var $meta;
    static $desc = false;
    static $registry;
    static $blocksize = 131072;
    static $private = false;

    /**
     * All storage backends should call this function during the request
     * bootstrap phase.
     */
    static function register($typechar, $class) {
        self::$registry[$typechar] = $class;
    }

    static function allRegistered($private=false) {
        $R = self::$registry;
        if (!$private) {
            foreach ($R as $i=>$bk) {
                if ($bk::$private)
                    unset($R[$i]);
            }
        }
        return $R;
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
    function sendRedirectUrl($disposition='inline', $ttl=false) {
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

    /**
     * getAttrs
     *
     * Get backend storage attributes.
     *
     */
    function getAttrs() {
        return false;
    }
}


/**
 * Attachments stored in the database are cut into 500kB chunks and stored
 * in the FILE_CHUNK_TABLE to overcome the max_allowed_packet limitation of
 * LOB fields in the MySQL database
 */
define('CHUNK_SIZE', 500*1024); # Beware if you change this...
class AttachmentFileChunk extends VerySimpleModel {
    static $meta = array(
        'table' => FILE_CHUNK_TABLE,
        'pk' => array('file_id', 'chunk_id'),
        'joins' => array(
            'file' => array(
                'constraint' => array('file_id' => 'AttachmentFile.id'),
            ),
        ),
    );
}

class AttachmentChunkedData extends FileStorageBackend {
    static $desc = /* @trans */ "In the database";
    static $blocksize = CHUNK_SIZE;

    function __construct($file) {
        $this->file = $file;
        $this->_chunk = 0;
        $this->_buffer = false;
        $this->eof = false;
    }

    function getSize() {
        $row = AttachmentFileChunk::objects()
            ->filter(array('file' => $this->file))
            ->aggregate(array('length' => SqlAggregate::SUM(SqlFunction::LENGTH(new SqlField('filedata')))))
            ->one();
        return $row['length'];
    }

    function read($amount=CHUNK_SIZE, $offset=0) {
        # Read requested length of data from attachment chunks
        if ($this->eof)
            return false;

        while (strlen($this->_buffer) < $amount + $offset) {
            try {
                list($buf) = AttachmentFileChunk::objects()
                    ->filter(array('file' => $this->file, 'chunk_id' => $this->_chunk++))
                    ->values_flat('filedata')
                    ->one();
            }
            catch (DoesNotExist $e) {
                $this->eof = true;
                break;
            }
            $this->_buffer .= $buf;
        }
        $chunk = substr($this->_buffer, $offset, $amount);
        $this->_buffer = substr($this->_buffer, $offset + $amount);
        return $chunk;
    }

    function write($what, $chunk_size=CHUNK_SIZE) {
        $offset=0;
        while ($block = substr($what, $offset, $chunk_size)) {
            // Chunks are considered immutable. Importing chunks should
            // forceable remove the contents of a file before write()ing new
            // chunks. Therefore, inserts should be safe.
            $chunk = new AttachmentFileChunk(array(
                'file' => $this->file,
                'chunk_id' => $this->_chunk++,
                'filedata' => $block
            ));
            if (!$chunk->save())
                return false;
            $offset += strlen($block);
        }

        return $this->_chunk;
    }

    function unlink() {
        return AttachmentFileChunk::objects()
            ->filter(array('file' => $this->file))
            ->delete();
    }
}
FileStorageBackend::register('D', 'AttachmentChunkedData');

/**
 * This class provides an interface for files attached on the filesystem in
 * versions previous to v1.7. The upgrader will keep the attachments on the
 * disk where they were and write the path into the `attrs` field of the
 * %file table. This module will continue to serve those files until they
 * are migrated with the `file` cli app
 */
class OneSixAttachments extends FileStorageBackend {
    static $desc = "upload_dir folder (from osTicket v1.6)";
    static $private = true;

    function read($bytes=32768, $offset=false) {
        $filename = $this->meta->attrs;
        if (!$this->fp)
            $this->fp = @fopen($filename, 'rb');
        if (!$this->fp)
            throw new IOException($filename.': Unable to open for reading');
        if ($offset)
            fseek($this->fp, $offset);
        if (($status = @fread($this->fp, $bytes)) === false)
            throw new IOException($filename.': Unable to read from file');
        return $status;
    }

    function passthru() {
        $filename = $this->meta->attrs;
        if (($status = @readfile($filename)) === false)
            throw new IOException($filename.': Unable to read from file');
        return $status;
    }

    function write($data) {
        throw new IOException('This backend does not support new files');
    }

    function upload($filepath) {
        throw new IOException('This backend does not support new files');
    }

    function unlink() {
        $filename = $this->meta->attrs;
        if (!@unlink($filename))
            throw new IOException($filename.': Unable to delete file');
        // Drop usage of the `attrs` field
        $this->meta->attrs = null;
        $this->meta->save();
        return true;
    }
}
FileStorageBackend::register('6', 'OneSixAttachments');

// FileObject - wrapper for SplFileObject class
class FileObject extends SplFileObject {

    protected $_filename;

    function __construct($file, $mode='r') {
        parent::__construct($file, $mode);
    }

    /* This allows us to set REAL file name as opposed to basename of the
     * FS file in question
     */
    function setFilename($filename) {
        $this->_filename = $filename;
    }

    function getFilename() {
        return $this->_filename ?: parent::getFilename();
    }

    /*
     * Set mime type - well formated mime is expected.
     */
    function setMimeType($type) {
        $this->_mimetype = $type;
    }

    function getMimeType() {
        if (!isset($this->_mimetype)) {
            // Try to to auto-detect mime type
            $finfo = new finfo(FILEINFO_MIME);
            $this->_mimetype = $finfo->buffer($this->getContents(),
                    FILEINFO_MIME_TYPE);
        }

        return $this->_mimetype;
    }

    function getContents() {
        $this->fseek(0);
        return $this->fread($this->getSize());
    }

    /*
     * XXX: Needed for mailer attachments interface
     */
    function getData() {
        return $this->getContents();
    }
}

?>
