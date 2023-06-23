<?php

class PharEditor extends Module {
    var $prologue = "View and edit Php PHAR files";

    var $arguments = array(
        "command" => array(
            'help' => "Action to be performed.",
            "options" => array(
                'add' =>        'Add one or more files to the archive',
                'compress' =>   'Compress files in the archive',
                'uncompress' => 'Decompress files in the archive',
                'extract' =>    'Unpack all files to the current path, or use -D to specify a target path',
                'get' =>        'Read a single file from the archive',
                'info' =>       'Show PHAR basic information',
                'list' =>       'Show a file listing of a PHAR file',
                #'sign' =>       'Add an OpenSSL signature for a PHAR file',
                'remove' =>     'Remove one or more files from the archive',
                'write' =>      'Write contents of `input` to `path` argument inside PHAR',
            ),
        ),
        "phar" => array(
            'help' => "PHAR file to edit",
        ),
        "path(s)" => array(
            'help' => 'Path to add or extract inside the PHAR. Used with `add`, `remove`, `get` and `write` commands.',
            'required' => false,
        ),
    );

    var $options = array(
        'compress' => array('-j', '--bzip2', 'help' => 'Compress with bzip2,
            default is zlib', 'default' => Phar::GZ, 'action' => 'store_const',
            'const' => Phar::BZ2),
        'dest' => array('-D', '--dest', 'help' => 'Specify a folder to
            change to before loading or unpacking files', 'metavar'=>'path'),
        'input' => array('-i', '--input', 'help' => 'File used for input of
            `write`. Default is stdin.', 'default' => 'php://stdin',
            'metavar' => 'path'),
        'output' => array('-o', '--output', 'help' => 'File used for output of
            `get`. Default is stdout.', 'default' => 'php://stdout',
            'metavar' => 'path'),
        'recurse' => array('-R', '--recurse', 'help' => 'Recursively follow
            folders when adding files with the `add` command.', 
            'default' => false, 'action' => 'store_true'),
        'verbose' => array('-v', '--verbose', 'help' => 'Do things more
            verbosely', 'default' => false, 'action' => 'store_true'),
    );

    var $epilog = "Note: You will likely need to run PHP with
    `-dphar.readonly=0` in order to make edits to a PHAR file.";

    function run($args, $options) {
        @TextDomain::setLocale(LC_ALL);

        if (!method_exists($this, "do_{$args['command']}"))
            $this->fail(sprintf('%s: Unimplemented command', $args['command']));

        try {
            $start = microtime(true);
            $phar = new Phar($args['phar'], 0, 'unneeded');
            $options[':load_time'] = microtime(true) - $start;
        }
        catch (Exception $x) {
            $this->fail($x->getMessage());
        }

        if (!$phar) {
            $this->fail(sprintf('%s: Unable to read or create',
                $args['phar']));
        }

        call_user_func(array($this, "do_{$args['command']}"),
            $phar, $options, $args);
    }

    function do_list($phar, $options, $args) {
        global $cfg;

        $root = rtrim(realpath($args['phar']), '/\\') . '/';
        $this->stdout->write(
            "          Size Flags Modified   Filename\n" .
            " ------------- ----- ---------- ------------------------\n"
        );

        $this->stdout->write(sprintf(
            "%14.14d %-5.5s %10.10s :stub\n",
            strlen($phar->getStub()), '', ''
        )); 

        if ($phar->hasMetadata()) {
            $this->stdout->write(sprintf(
                "%14.14s %-5.5s %10.10s :meta\n",
                '?', '', ''
            )); 
        }

        foreach (new RecursiveIteratorIterator($phar) as $file) {
            $this->stdout->write(sprintf(
                "%14.14s %-5.5s %s %s\n",
                ($file->isCompressed() ? sprintf('%s/%s',
                    $file->getSize(), $file->getCompressedSize()) 
                    : $file->getCompressedSize()),
                ($file->isCompressed() ? 'c' : '-') .
                ($file->isCompressed(Phar::GZ) ? 'z' :
                    ($file->isCompressed(Phar::BZ2) ? 'j' : '-')) .
                ($file->hasMetadata() ? 'm' : '-') .
                ($file->isCRCChecked() ? '-' : 'B') .
                ($file->isExecutable() ? 'x' : '-'),
                strftime('%x', $file->getMTime()),
                str_replace("phar://$root", '', $file->getPathname())
            )); 

            if ($file->hasMetadata()) {
                $this->stdout->write(sprintf(
                    "%14.14s %-5.5s %10.10s %s:meta\n",
                    '?', '', '',
                    str_replace("phar://$root", '', $file->getPathname())
                )); 
            }
        }
    }

    function do_info($phar, $options, $args) {
        $this->stdout->write(sprintf(
            "                Size: %d\n" .
            "          File-Count: %d\n" .
            " Phar-Storage-Method: %s\n" .
            "  Compression-Format: %s\n" .
            "       Has-Meta-Data: %s\n" .
            "      Meta-Data-Size: %d\n" .
            "           Stub-Size: %d\n" .
            " Signature-Algorithm: %s\n" .
            "           Signature: %s\n" .
            "           Load-Time: %.3fms\n",
            $phar->getSize(),
            count($phar),
            $phar->isFileFormat(Phar::TAR) ? 'tar' :
                $phar->isFileFormat(Phar::ZIP) ? 'zip' :
                'phar',
            $phar->isCompressed() ? ( 'Z' ) : 'Uncompressed',
            $phar->hasMetadata() ? 'Yes' : 'No',
            0,
            strlen($phar->getStub()),
            $phar->getSignature()['hash_type'],
            $phar->getSignature()['hash'],
            $options[':load_time'] * 1000
        ));
    }

    /**
     * Fetch a file from the archive and copy it to the output. Special
     * files can be fetched with a colon (similar to Windows alternate data
     * streams. For instance `path/to/file:meta`, or `:stub`.
     */
    function do_get($phar, $options, $args) {
        $path = $args['path(s)'];
        if (is_array($path))
            $this->fail("Only one path can be used with `get`");

        list($path, $special) = explode(':', $path, 2);

        if (!($output = fopen($options['output'], 'w')))
            $this->fail(sprintf('%s: Cannot option for output',
                $options['output']));

        if ($special === 'stub') {
            fwrite($output, $phar->getStub());
            fclose($output);
            return;
        }

        if (!isset($phar[$path]))
            $this->fail(sprintf('%s: Path not found in PHAR. Use `list`',
                $path));

        $source = $phar[$path];
        if ($special === 'meta') {
            if (!$source->hasMetadata())
                $this->fail(sprintf('%s: Does not have metadata'));
            fwrite($output, var_export($source->getMetadata(), true));
        }
        else {
            $source = $source->openFile();
            while ($block = $source->fread(8192))
                fwrite($output, $block);
        }
    }

    function do_extract($phar, $options, $args) {
        $root = rtrim(realpath($args['phar']), '/\\') . '/';
        $verbose = $options['verbose'];

        if ($dest = $options['dest']) {
            if (!file_exists($dest)) {
                if (!mkdir($dest, 0777, true))
                    $this->fail(sprintf('%s: Cannot create dest folder'));
            }
            if (!is_dir($dest)) {
                $this->fail(sprintf('%s: Destination is not a folder'));
            }
            if (!chdir($dest)) {
                $this->fail(sprintf('%s: Unable to change to path', $dest));
            }
        }

        foreach (new RecursiveIteratorIterator($phar) as $file) {
            $rel = str_replace("phar://$root", '',$file->getPathname());
            $path = str_replace("phar://$root", '',$file->getPath());
            if ($verbose)
                $this->stderr->write(sprintf("%s\n", $rel));
            if (!file_exists($path)) {
                if (!mkdir($path, 0777, true))
                    $this->fail(sprintf('%s: Cannot create output folder',
                        $path));
            }
            if (!($dest = fopen($rel, 'wb')))
                $this->fail(sprintf('%s: Unable to open output file', $rel));
            $src = $file->openFile();
            while ($block = $src->fread(8192))
                fwrite($dest, $block);
            fclose($dest);
        }
    }

    function do_add($phar, $options, $args, $buffer=true) {
        if (!Phar::canWrite())
            $this->fail('Phar editing disabled. Use `-dphar.readonly=0` on the command line');

        if ($buffer)
            $phar->startBuffering();
        for ($i=2, $k=count($args); $i<$k; $i++) {
            if (!$args[$i])
                continue;

            if (is_dir($args[$i]) && $options['recurse']) {
                // If recursing, do something else first
                $subfolder = array_merge([0,0], glob("$args[$i]/*"));
                $this->do_add($phar, $options, $subfolder, false);
                continue;
            }

            if (!file_exists($args[$i]) || !is_file($args[$i])) {
                $this->stderr->write(sprintf("%s: File not found\n",
                    $args[$i]));
                continue;
            }

            $rel = str_replace(getcwd(), '', $args[$i]);
            $rel = str_replace(realpath(getcwd()), '', $args[$i]);
            $rel = ltrim($rel, '/\\');

            if ($options['verbose'])
                $this->stderr->write("$rel\n");

            $phar->addFile($rel, $rel);
        }
        if ($buffer)
            $phar->stopBuffering();
    }

    function do_remove($phar, $options, $args) {
        if (!Phar::canWrite())
            $this->fail('Phar editing disabled. Use `-dphar.readonly=0` on the command line');

        for ($i=2, $k=count($args); $i<$k; $i++) {
            if (!$args[$i])
                continue;

            if (!isset($phar[$args[$i]]))
                $this->stderr->write(sprintf("%s: No such file\n", $args[$i]));

            else {
                try {
                    $phar->delete($args[$i]);
                    if ($options['verbose'])
                        $this->stderr->write(sprintf("Removed %s\n", $args[$i]));
                }
                catch (PharException $x) {
                    $this->stderr->write(sprintf("%s: Unable to remove: %s\n",
                        $x->getMessage()));
                }
            }
        }
    }

    function do_write($phar, $options, $args) {
        if (!Phar::canWrite())
            $this->fail('Phar editing disabled. Use `-dphar.readonly=0` on the command line');

        $phar->startBuffering();
        if (!$args['path(s)'])
            $this->fail('Specify the destination in the phar with `path`');
        if (is_array($args['path(s)']))
            $this->fail('Specify a single destination in the phar with `path`');

        if (!($src = fopen($options['input'], 'r')))
            $this->fail(sprintf('%s: Cannot open for reading',
                $options['input']));

        list($path, $special) = explode(':', $args['path(s)'], 2);
        if ($special === 'stub') {
            // TODO: Set stub here
            $this->fail('Stub creation is not implemented');
        }
        if (!isset($phar[$path]))
            $phar[$path] = ' ';

        $file = $phar[$path];

        if ($special === 'meta') {
            $meta = '';
            while ($block = fread($src, 8192))
                $meta .= $block;

            // Try serialized data
            if (!($pmeta = @unserialize($meta)))
                // Try var_export data
                $pmeta = @eval(sprintf('return %s ;', $meta));

            if (!$pmeta)
                $this->fail('Unable to interpret data. Use serialize()d or var_export()ed data');

            $file->setMetadata($pmeta);
        }
        else {
            // Write to (new) file contents
            $dest = $file->openFile('w');
            while ($block = fread($src, 8192))
                $dest->fwrite($block);
        }

        fclose($src);
        $phar->stopBuffering();
    }

    function do_compress($phar, $options, $args) {
        $format = $options['compress'];
        $this->_do_compress($phar, $options, $args, $format);
    }

    function do_uncompress($phar, $options, $args) {
        $this->_do_compress($phar, $options, $args, Phar::NONE);
    }

    function _do_compress($phar, $options, $args, $format) {
        if (!Phar::canWrite())
            $this->fail('Phar editing disabled. Use `-dphar.readonly=0` on the command line');
        if (!Phar::canCompress($format))
            $this->fail('That compression format is not supported by your PHP');

        // (De)Compress only certain files
        if (count($args) > 2) {
            for ($i=2, $k=count($args); $i<$k; $i++) {
                if (!$args[$i])
                    continue;
                if (!isset($phar[$args[$i]])) {
                    $this->stderr->write(sprintf('%s: File not found', $args[$i]));
                    continue;
                }

                $path = $phar[$args[$i]];
                if ($format == Phar::NONE) {
                    // XXX: This seems to be a php bug? ->decompress() will
                    //      corrupt the phar entry? (@PHP5.5.30 anyway)
                    $comp = $path->openFile('r');
                    $phar["{$args[$i]}:dec"] = ' ';
                    $decomp = $phar["{$args[$i]}:dec"]->openFile('w');
                    while ($block = $comp->fread(8192))
                        $decomp->fwrite($block);

                    unset($comp);
                    unset($decomp);
                    $phar->delete("{$args[$i]}");
                    $phar->copy("{$args[$i]}:dec", $args[$i]);
                    $phar->delete("{$args[$i]}:dec");
                }
                elseif ($path->isCompressed()) {
                    $this->stderr->write(sprintf("%s (already compressed)\n", $args[$i]));
                }
                else {
                    if ($options['verbose'])
                        $this->stderr->write(sprintf("%s\n", $args[$i]));
                    $path->compress($format);
                }
            }
        }
        // (De)Compress everything
        else {
            $phar->compressFiles($format);
        }
    }
}
if (class_exists('Phar')) {
    Module::register('phar', 'PharEditor');
}
