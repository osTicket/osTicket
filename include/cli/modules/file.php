<?php

class FileManager extends Module {
    var $prologue = 'CLI file manager for osTicket';

    var $arguments = array(
        'action' => array(
            'help' => 'Action to be performed',
            'options' => array(
                'list' => 'List files matching criteria',
                'export' => 'Export files from the system',
                'import' => 'Load files exported via `export`',
                'zip' => 'Create a zip file of the matching files',
                'dump' => 'Dump file content to stdout',
                'load' => 'Load file contents from stdin',
                'migrate' => 'Migrate a file to another backend',
                'backends' => 'List configured storage backends',
                'expunge' => 'Remove matching files from the system',
            ),
        ),
    );

    var $options = array(
        'ticket' => array('-T', '--ticket', 'metavar'=>'id',
            'help' => 'Search by internal ticket id'),
        'file-id' => array('-F', '--file-id', 'metavar'=>'id',
            'help' => 'Search by file id'),
        'name' => array('-N', '--name', 'metavar'=>'name',
            'help' => 'Search by file name (subsring match)'),
        'backend' => array('-b', '--backend', 'metavar'=>'BK',
            'help' => 'Search by file backend. See `backends` action
                for a list of available backends'),
        'status' => array('-S', '--status', 'metavar'=>'STATUS',
            'help' => 'Search on ticket state (`open` or `closed`)'),
        'min-size' => array('-z', '--min-size', 'metavar'=>'SIZE',
            'help' => 'Search for files larger than this. k, M, G are welcome'),
        'max-size' => array('-Z', '--max-size', 'metavar'=>'SIZE',
            'help' => 'Search for files smaller than this. k, M, G are welcome'),

        'limit' => array('-L', '--limit', 'metavar'=>'count',
            'help' => 'Limit search results to this count'),

        'to' => array('-m', '--to', 'metavar'=>'BK',
            'help' => 'Target backend for migration. See `backends` action
                for a list of available backends'),

        'file' => array('-f', '--file', 'metavar'=>'FILE',
            'help' => 'Filename used for import and export'),

        'verbose' => array('-v', '--verbose', 'action'=>'store_true',
            'help' => 'Be more verbose'),
    );


    function run($args, $options) {
        Bootstrap::connect();
        osTicket::start();

        switch ($args['action']) {
        case 'backends':
            // List configured backends
            foreach (FileStorageBackend::allRegistered(true) as $char=>$bk) {
                print "$char -- {$bk::$desc} ($bk)\n";
            }
            break;

        case 'list':
            // List files matching criteria
            // ORM would be nice!
            $files = AttachmentFile::objects();
            $this->_applyCriteria($options, $files);
            foreach ($files as $f) {
                printf("% 5d %s % 8d %s % 16s %s\n", $f->id, $f->bk,
                    $f->size, $f->created, $f->type, $f->name);
                if ($f->attrs) {
                    printf("        %s\n", $f->attrs);
                }
            }
            break;

        case 'dump':
            $files = AttachmentFile::objects();
            $this->_applyCriteria($options, $files);
            try {
                $f = $files->one();
            }
            catch (DoesNotExist $e) {
                $this->fail('No file matches the given criteria');
            }
            catch (ObjectNotUnique $e) {
                $this->fail('Criteria must select exactly 1 file');
            }

            if ($bk = $f->open())
                $bk->passthru();
            break;

        case 'load':
            // Load file content from STDIN
            $files = AttachmentFile::objects();
            $this->_applyCriteria($options, $files);
            try {
                $f = $files->one();
            }
            catch (DoesNotExist $e) {
                $this->fail('No file matches the given criteria');
            }
            catch (ObjectNotUnique $e) {
                $this->fail('Criteria must select exactly 1 file');
            }

            try {
                if ($bk = $f->open())
                    $bk->unlink();
            }
            catch (Exception $e) {}

            if ($options['to'])
                $bk = FileStorageBackend::lookup($options['to'], $f);
            else
                // Use the system default
                $bk = AttachmentFile::getBackendForFile($f);

            $type = false;
            $signature = '';
            $finfo = new finfo(FILEINFO_MIME_TYPE);
            if ($options['file'] && $options['file'] != '-') {
                if (!file_exists($options['file']))
                    $this->fail($options['file'].': Cannot open file');
                if (!$bk->upload($options['file']))
                    $this->fail('Unable to upload file contents to backend');
                $type = $finfo->file($options['file']);
                list(, $signature) = AttachmentFile::_getKeyAndHash($options['file'], true);
            }
            else {
                $stream = fopen('php://stdin', 'rb');
                // reading from the stream will likely return an amount of
                // data different from the backend requested block size. Loop
                // until $read_size bytes are recieved.
                while (true) {
                    $contents = '';
                    $read_size = $bk->getBlockSize();
                    while ($read_size > 0 && ($block = fread($stream, $read_size))) {
                        $contents .= $block;
                        $read_size -= strlen($block);
                    }
                    if (!$contents)
                        break;
                    if (!$bk->write($contents))
                        $this->fail('Unable to send file contents to backend');
                    if (!$type)
                        $type = $finfo->buffer($contents);
                }
                if (!$bk->flush())
                    $this->fail('Unable to commit file contents to backend');
            }

            // TODO: Update file metadata
            $f->bk = $bk->getBkChar();
            $f->created = SqlFunction::NOW();
            $f->type = $type;
            $f->signature = $signature;

            if (!$f->save())
                $this->fail('Unable to update file metadata');

            $this->stdout->write("Successfully saved contents\n");
            break;

        case 'migrate':
            if (!$options['to'])
                $this->fail('Please specify a target backend for migration');

            if (!FileStorageBackend::isRegistered($options['to']))
                $this->fail('Target backend is not installed. See `backends` action');

            $files = AttachmentFile::objects();
            $this->_applyCriteria($options, $files);

            $count = 0;
            foreach ($files as $f) {
                if ($f->getBackend() == $options['to'])
                    continue;
                if ($options['verbose'])
                    $this->stdout->write('Migrating '.$f->name."\n");
                try {
                    if (!$f->migrate($options['to']))
                        $this->stderr->write('Unable to migrate '.$f->name."\n");
                    else
                        $count++;
                }
                catch (IOException $e) {
                    $this->stderr->write('IOError: '.$e->getMessage());
                }
            }
            $this->stdout->write("Migrated $count files\n");
            break;

        /**
         * export
         *
         * Export file contents to a stream file. The format of the stream
         * will be a continuous stream of file information in the following
         * format:
         *
         * AFIL<meta-length><data-length><meta><data>EOF\x1c
         *
         * Where
         *   A              is the version code of the export
         *   "FIL"          is the literal text 'FIL'
         *   meta-length    is 'V' packed header length (bytes)
         *   data-length    is 'V' packed data length (bytes)
         *   meta           is the %file record, php serialized
         *   data           is the raw content of the file
         *   "EOF"          is the literal text 'EOF'
         *   \x1c           is an ASCII 0x1c byte (file separator)
         *
         * Options:
         * --file       File to which to direct the stream output, default
         *              is stdout
         */
        case 'export':
            $files = AttachmentFile::objects();
            $this->_applyCriteria($options, $files);

            if (!$options['file'] || $options['file'] == '-')
                $options['file'] = 'php://stdout';

            if (!($stream = fopen($options['file'], 'wb')))
                $this->fail($options['file'].': Unable to open file for export stream');

            foreach ($files as $f) {
                if ($options['verbose'])
                    $this->stderr->write($f->name."\n");

                // TODO: Log %attachment and %ticket_attachment entries
                $info = array('file' => $f->getInfo());
                $header = serialize($info);
                fwrite($stream, 'AFIL'.pack('VV', strlen($header), $f->getSize()));
                fwrite($stream, $header);
                $FS = $f->open();
                while ($block = $FS->read())
                    fwrite($stream, $block);
                fwrite($stream, "EOF\x1c");
            }
            fclose($stream);
            break;

        /**
         * import
         *
         * Import a collection of file contents exported by the `export`.
         * See the export function above for details about the stream
         * format.
         *
         * Options:
         * --file       File from which to read the export stream, default
         *              is stdin
         * --to         Backend to receive the contents (@see `backends`)
         * --verbose    Show file names while importing
         */
        case 'import':
            if (!$options['file'] || $options['file'] == '-')
                $options['file'] = 'php://stdin';

            if (!($stream = fopen($options['file'], 'rb')))
                $this->fail($options['file'].': Unable to open import stream');

            while (true) {
                // Read the file header
                // struct file_data_header {
                //   char[4] marker; // Four chars, 'AFIL'
                //   int     lenMeta;
                //   int     lenData;
                // };
                if (!($header = fread($stream, 12)))
                    break; // EOF

                list(, $mark, $hlen, $dlen) = unpack('V3', $header);

                // AFIL written as little-endian 4-byte int is 0x4c4946xx (LIFA),
                // where 'A' is the version code of the export
                $version = $mark & 0xff;
                if (($mark >> 8) != 0x4c4946)
                    $this->fail('Bad file record');

                // Read the header
                $header = fread($stream, $hlen);
                if (strlen($header) != $hlen)
                    $this->fail('Short read getting header info');

                $header = unserialize($header);
                if (!$header)
                    $this->fail('Unable to decipher file header');

                // Find or create the file record
                $finfo = $header['file'];
                // TODO: Consider the $version code, drop columns which do
                // not exist in this database schema
                $f = AttachmentFile::lookup($finfo['id']);
                if ($f) {
                    // Verify file information
                    if ($f->getSize() != $finfo['size']
                        || $f->getSignature() != $finfo['signature']
                    ) {
                        $this->fail(sprintf(
                            '%s: File data does not match existing file record',
                            $finfo['name']
                        ));
                    }
                    // Drop existing file contents, if any
                    try {
                        if ($bk = $f->open())
                            $bk->unlink();
                    }
                    catch (Exception $e) {}
                }
                // Create a new file
                else {
                    // Bypass the AttachmentFile::create() because we do not
                    // have the data to send yet.
                    $f = new AttachmentFile($finfo);
                    if (!$f->save(true)) {
                        $this->fail(sprintf(
                            '%s: Unable to create new file record',
                            $finfo['name']));
                    }
                }

                // Determine the backend to recieve the file contents
                if ($options['to']) {
                    $bk = FileStorageBackend::lookup($options['to'], $f);
                }
                // Use the system default
                else {
                    $bk = AttachmentFile::getBackendForFile($f);
                }

                if ($options['verbose'])
                    $this->stdout->write('Importing '.$f->getName()."\n");

                // Write file contents to the backend
                $md5 = hash_init('md5');
                $sha1 = hash_init('sha1');
                $written = 0;

                // Handle exceptions by dropping imported file contents and
                // then returning the error to the error output stream.
                try {
                    while ($dlen > 0) {
                        $read_size = min($dlen, $bk->getBlockSize());
                        $contents = '';
                        // reading from the stream will likely return an amount of
                        // data different from the backend requested block size. Loop
                        // until $read_size bytes are recieved.
                        while ($read_size > 0 && ($block = fread($stream, $read_size))) {
                            $contents .= $block;
                            $read_size -= strlen($block);
                        }
                        if ($read_size != 0) {
                            // short read
                            throw new Exception(sprintf(
                                '%s: Some contents are missing from the stream',
                                $f->getName()
                            ));
                        }
                        // Calculate MD5 and SHA1 hashes of the file to verify
                        // contents after successfully written to backend
                        if (!$bk->write($contents))
                            throw new Exception(
                                'Unable to send file contents to backend');
                        hash_update($md5, $contents);
                        hash_update($sha1, $contents);
                        $dlen -= strlen($contents);
                        $written += strlen($contents);
                    }
                    // Some backends cannot handle flush() without a
                    // corresponding write() call.
                    if ($written && !$bk->flush())
                        throw new Exception(
                            'Unable to commit file contents to backend');

                    // Check the signature hash
                    if ($finfo['signature']) {
                        $md5 = base64_encode(hash_final($md5, true));
                        $sha1 = base64_encode(hash_final($sha1, true));
                        $sig = str_replace(
                            array('=','+','/'),
                            array('','-','_'),
                            substr($sha1, 0, 16) . substr($md5, 0, 16));
                        if ($sig != $finfo['signature']) {
                            throw new Exception(sprintf(
                                '%s: Signature verification failed',
                                $f->getName()
                            ));
                        }
                    }

                    // Update file to record current backend
                    $f->bk = $bk->getBkChar();
                    if (!$f->save())
                        return false;

                } // end try
                catch (Exception $ex) {
                    if ($bk) $bk->unlink();
                    $this->fail($ex->getMessage());
                }

                // Read file record footer
                $footer = fread($stream, 4);
                if (strlen($footer) != 4)
                    $this->fail('Unable to read file EOF marker');
                list(, $footer) = unpack('N', $footer);
                // Footer should be EOF\x1c as an int
                if ($footer != 0x454f461c)
                    $this->fail('Incorrect file EOF marker');
            }
            break;

        case 'zip':
            // Create a temporary ZIP file
            $files = AttachmentFile::objects();
            $this->_applyCriteria($options, $files);
            if (!$options['file'])
                $this->fail('Please specify zip file with `-f`');

            $zip = new ZipArchive();
            if (true !== ($reason = $zip->open($options['file'],
                    ZipArchive::CREATE)))
                $this->fail($reason.': Unable to create zip file');

            foreach ($files as $f) {
                if ($options['verbose'])
                    $this->stderr->write($f->name."\n");
                $info = pathinfo($f->getName());
                $name = Charset::transcode(
                    sprintf('%s-%d.%s',
                        $info['filename'], $f->getId(), $info['extension']),
                    'utf-8', 'cp437');
                $zip->addFromString($name, $f->getData());
            }
            $zip->close();
            break;

        case 'expunge':
            $files = AttachmentFile::objects();
            $this->_applyCriteria($options, $files);

            foreach ($files as $f) {
                // Drop associated attachment links
                $f->attachments->expunge();

                // Drop file contents
                if ($bk = $f->open())
                    $bk->unlink();

                // Drop file record
                $f->delete();
            }
        }
    }

    function _applyCriteria($options, $qs) {
        foreach ($options as $name=>$val) {
            if (!$val) continue;
            switch ($name) {
            case 'ticket':
                $qs->filter(array('attachments__thread_entry__thread__ticket__ticket_id'=>$val));
                $qs->distinct('id');
                break;
            case 'file-id':
                $qs->filter(array('id'=>$val));
                break;
            case 'name':
                $qs->filter(array('name__contains'=>$val));
                break;
            case 'backend':
                $qs->filter(array('bk'=>$val));
                break;
            case 'status':
                if (!in_array($val, array('open','closed','archived','deleted')))
                    $this->fail($val.': Unknown ticket status');

                $qs->filter(array('attachments__thread_entry__thread__ticket__status__state'=>$val));
                $qs->distinct('id');
                break;

            case 'min-size':
            case 'max-size':
                $info = array();
                if (!preg_match('/([\d.]+)([kmgbi]+)?/i', $val, $info))
                    $this->fail($val.': Invalid file size');
                if ($info[2]) {
                    $info[2] = str_replace(array('b','i'), array('',''), $info[2]);
                    $sizes = array('k'=>1<<10,'m'=>1<<20,'g'=>1<<30);
                    $val = (float) $val * $sizes[strtolower($info[2])];
                }
                if ($name == 'min-size')
                    $qs->filter(array('size__gte'=>$val));
                else
                    $qs->filter(array('size__lte'=>$val));
                break;

            case 'limit':
                if (!is_numeric($val))
                    $this->fail('Provide an result count number to --limit');
                $qs->limit($val);
                break;
            }
        }
    }
}
Module::register('file', 'FileManager');
