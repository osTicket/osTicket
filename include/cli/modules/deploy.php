<?php
require_once dirname(__file__) . "/unpack.php";

ini_set('memory_limit', '512M');

class Deployment extends Unpacker {
    var $prologue = "Deploys osTicket into target install path";

    var $epilog =
        "Deployment is used from the continuous development model. If you
        are following the upstream git repo, then you can use the deploy
        script to deploy changes made by you or upstream development to your
        installation target";

    function __construct() {
        $this->options['dry-run'] = array('-t','--dry-run',
            'action'=>'store_true',
            'help'=>'Don\'t actually deploy new code. Just show the files
                that would be copied');
        $this->options['setup'] = array('-s','--setup',
            'action'=>'store_true',
            'help'=>'Deploy the setup folder. Useful for deploying for new
                installations.');
        $this->options['clean'] = array('-C','--clean',
            'action'=>'store_true',
            'help'=>'Remove files from the destination that are no longer
                included in this repository');
        $this->options['git'] = array('-g','--git',
            'action'=>'store_true',
            'help'=>'Use `git ls-files -s` as files source. Eliminates
                possibility of deploying untracked files');
        $this->options['force'] = array('-f', '--force',
            'action'=>'store_true',
            'help'=>'Deploy all files, even if they have not changed');
        $this->options['compress'] = array('-Z', '--compress',
            'action'=>'store_true',
            'help'=>'Compress PHP source files at destination');
        # super(*args);
        call_user_func_array(array('parent', '__construct'), func_get_args());
    }

    function find_root_folder() {
        # Hop up to the root folder of this repo
        $start = dirname(__file__);
        for (;;) {
            if (is_file($start . '/main.inc.php')) break;
            $start .= '/..';
        }
        return self::realpath($start);
    }

    /**
     * Removes files from the deployment location that no longer exist in
     * the local repository
     */
    function clean($local, $destination, $root, $recurse=0, $exclude=false) {
        $dryrun = $this->getOption('dry-run', false);
        $verbose = $dryrun || $this->getOption('verbose');
        $destination = rtrim($destination, '/') . '/';
        $contents = glob($destination.'{,.}*', GLOB_BRACE|GLOB_NOSORT);
        foreach ($contents as $i=>$file) {
            $relative = str_replace($root, "", $file);
            if ($this->exclude($exclude, $relative))
                continue;
            if (is_file($file)) {
                $ltarget = $local . '/' . basename($file);
                if (is_file($ltarget))
                    continue;
                if ($verbose)
                    $this->stdout->write("(delete): $file\n");
                if (!$dryrun)
                    unlink($file);
                unset($contents[$i]);
            }
            elseif (in_array(basename($file), array('.','..'))) {
                // Doesn't indicate that the folder has contents
                unset($contents[$i]);
            }
        }
        if ($recurse) {
            $folders = glob(dirname($destination).'/'.basename($destination).'/*',
                GLOB_BRACE|GLOB_ONLYDIR|GLOB_NOSORT);
            foreach ($folders as $dir) {
                if (in_array(basename($dir), array('.','..')))
                    continue;
                $relative = str_replace($root, "", $dir);
                if ($this->exclude($exclude, "$relative/"))
                    continue;
                $this->clean(
                    $local.'/'.basename($dir),
                    $destination.basename($dir),
                    $root, $recurse - 1, $exclude);
            }
        }
        if (!$contents || !glob($destination.'{,.}*', GLOB_BRACE|GLOB_NOSORT)) {
            if ($verbose)
                $this->stdout->write("(delete-folder): $destination\n");
            if (!$dryrun)
                rmdir($destination);
        }
    }

    function writeManifest($root) {
        $lines = array();
        foreach ($this->manifest as $F=>$H)
            $lines[] = "$H $F";

        return file_put_contents($this->include_path.'/.MANIFEST', implode("\n", $lines));
    }

    function hashContents($file) {
        $md5 = md5($file);
        $sha1 = sha1($file);
        return substr($md5, -20) . substr($sha1, -20);
    }

    function getEditedContents($src) {
        static $short = false;
        static $version = false;

        if (substr($src, -4) != '.php')
            return false;

        if (!$short) {
            $hash = exec('git rev-parse HEAD');
            $short = substr($hash, 0, 7);
        }

        if (!$version)
            $version = exec('git describe');

        if (!$short || !$version)
            return false;

        $source = file_get_contents($src);
        $original = crc32($source);

        // Set THIS_VERSION
        $source = preg_replace("/^(\s*)define\s*\(\s*'THIS_VERSION'.*$/m",
            "$1define('THIS_VERSION', '".$version."'); // Set by installer",
            $source);
        // Set GIT_VERSION
        $source = preg_replace("/^(\s*)define\s*\(\s*'GIT_VERSION'.*$/m",
            "$1define('GIT_VERSION', '".$short."'); // Set by installer",
            $source);
        // Disable error display
        $source = preg_replace("/^(\s*)ini_set\s*\(\s*'(display_errors|display_startup_errors)'.*$/m",
            "$1ini_set('$2', '0'); // Set by installer",
            $source);

        // Search for CDN and compress imports
        // CSS
        $source = $this->_rewrite_css($source);
        // Javascript
        $source = $this->_rewrite_js($source);

        $source = preg_replace(':<link(.*) href="([^"]+)\.css"([^/>]*)/?>:', # <?php
            '<link$1 href="$2.css?'.$short.'"$3/>',
            $source);
        $source = preg_replace(':<script([^\n]*) src="([^"]+)\.js"></script>:',
            '<script$1 src="$2.js?'.$short.'"></script>',
            $source);

        // Compress modified PHP source
        if ($this->getOption('compress') && strpos($src, '/bootstrap.php') === false)
            $source = $this->compress_php($source);

        // return FALSE if the edited contents do not differ from the
        // original contents
        return $original != crc32($source) ? $source : false;
    }

    function isChanged($source, $hash=false) {
        $local = str_replace($this->source.'/', '', $source);
        $hash = $hash ?: $this->hashFile($source);
        list($shash, $flag) = explode(':', $this->readManifest($local));
        return ($flag === 'rewrite') ? $flag : $shash != $hash;
    }

    function copyFile($source, $dest, $hash=false, $mode=0644, $contents=false) {
        if (substr($source, -3) == '.js')
            return $this->copyJsFile($source, $dest, $hash, $mode, $contents);

        if (substr($source, -4) == '.css')
            return $this->copyCssFile($source, $dest, $hash, $mode, $contents);

        $contents = $contents ?: $this->getEditedContents($source);
        if ($contents === false)
            // Regular file
            return parent::copyFile($source, $dest, $hash, $mode);

        if (!file_put_contents($dest, $contents))
            $this->fail($dest.": Unable to apply rewrite rules");

        $this->updateManifest($source, "$hash:rewrite");
        return chmod($dest, $mode);
    }

    function copyCssFile($src, $dest, $hash=false, $mode=0644, $contents=false) {
        // Stylesheets
        $source = $contents ?: file_get_contents($src);
        if (strpos($src, '.min.') === false)
            $source = $this->minify_css($source);
        if (!$source || !file_put_contents($dest, $source))
            $this->fail("Unable to apply rewrite rules to ".$dest);
        $this->updateManifest($src, $hash);
        return chmod($dest, $mode);
    }

    function copyJsFile($src, $dest, $hash=false, $mode=0644, $contents=false) {
        // Javascript
        $source = $contents ?: file_get_contents($src);
        $rel = str_replace($this->destination, '', $dest);
        $source = $this->_rewrite_js($source, '../'.dirname($rel));
        if (!$source || !file_put_contents($dest, $source))
            $this->fail("Unable to apply rewrite rules to ".$dest);
        $this->updateManifest($src, $hash);
        return chmod($dest, $mode);
    }

    function unpackage($folder, $destination, $recurse=0, $exclude=false) {
        $use_git = $this->getOption('git', false);
        if (!$use_git)
            return parent::unpackage($folder, $destination, $recurse, $exclude);

        // Attempt to read from git using `git ls-files` for deployment
        if (substr($destination, -1) !== '/')
            $destination .= '/';
        $source = $this->source;
        if (substr($source, -1) != '/')
            $source .= '/';
        $local = str_replace(array($source, '{,.}*'), array('',''), $folder);

        $pipes = array();
        $patterns = array();
        foreach ((array) $exclude as $x) {
            $patterns[] = str_replace($source, '', $x);
        }
        $X = implode(' --exclude-per-directory=', $patterns);
        chdir($source.$local);
        if (!($files = proc_open(
            "git ls-files -zs --exclude-standard --exclude-per-directory=$X -- .",
            array(1 => array('pipe', 'w')),
            $pipes
        ))) {
            return parent::unpackage($folder, $destination, $recurse, $exclude);
        }

        $dryrun = $this->getOption('dry-run', false);
        $verbose = $this->getOption('verbose') || $dryrun;
        $force = $this->getOption('force');
        while ($line = stream_get_line($pipes[1], 255, "\x00")) {
            list($mode, $hash, , $path) = preg_split('/\s+/', $line);
            $src = $source.$local.$path;
            if ($this->exclude($exclude, $src))
                continue;
            if (!$force && false === ($flag = $this->isChanged($src, $hash)))
                continue;
            $dst = $destination.$path;
            if ($verbose) {
                $msg = $dst;
                if (is_string($flag))
                    $msg = "$msg ({$flag})";
                $this->stdout->write("$msg\n");
            }
            if ($dryrun)
                continue;
            if (!is_dir(dirname($dst)))
                mkdir(dirname($dst), 0755, true);
            $this->copyFile($src, $dst, $hash, octdec($mode));
        }
    }

    function _rewrite_css($source) {
        $compressed_css = array();
        $self = $this;
        $source = preg_replace_callback(':<link(.*)((?!/>).)*/?>:',
        function ($m) use ($self, &$compressed_css) {
            $attrs = array();
            $link = str_replace(
                array('<?php echo ', '; ?>', 'ROOT_PATH', 'ASSETS_PATH'),
                array('', '' , $self->root.'/', $self->root.'/assets/default/'),
                $m[0]);
            preg_match_all('/[^= ]+="[^"]+"/', $link, $attrs);
            foreach ($attrs[0] as $A) {
                list($lhs, $rhs) = explode('=', $A);
                $attrs[$lhs] = trim($rhs, '"');
            }
            if (isset($attrs['data-group'])) {
                $compressed_css[$attrs['data-group']][] = $attrs['href'];
                return '<!-- '.$m[0].' -->';
            }
            else if (isset($attrs['href']) && strpos($attrs['href'], '//') === 0) {
                // Fetch and deploy the CDN file
                $this->deployCDN($attrs['href'], $this->destination.'css/');
                $attrs['href'] = '<?php echo ROOT_PATH; ?>css/'
                    .basename($attrs['href']);
                $link = '<link ';
                foreach ($attrs as $k=>$v) {
                    $link .= " {$k}=\"{$v}\"";
                }
                return $link . ' />';
            }
            return $m[0];
        },
        $source);
        if ($compressed_css) {
            $compressed = '';
            foreach ($compressed_css as $group=>$items) {
                foreach ($items as $file) {
                    if (strpos($file, '//') === 0) {
                        // fetch the CDN file
                        list($code, $contents) = $this->_http_get('http:'.$file);
                        if ($code != 200)
                            $this->fail(sprintf('%s: Unable to fetch from CDN', $file));
                        // TODO: Look for CDN (recursively) included files
                        preg_replace_callback(':url\([\'"]?((?!data)[^)"\']+)[\'"]?\):',
                        function ($m) use ($self, $file, &$remote_urls) {
                            $base = dirname($file);
                            $url = $base . '/' . $m[1];
                            $self->deployCDN($url, $self->destination.'css/'.dirname($m[1]));
                            return $m[0];
                        },
                        $contents);
                    }
                    else {
                        $contents = file_get_contents($file);
                        // Copy out referenced files to compressed stage
                        preg_replace_callback(':url\([\'"]?((?!data)[^)"\']+)[\'"]?\):',
                        function ($m) use ($self, $file) {
                            $base = dirname($file);
                            @list($include, $query) = explode('?', rtrim($m[1],'/'));
                            @list($include, $hash) = explode('#', $include, 2);
                            $url = $base . '/' . $include;
                            if (is_file($url))
                                $self->copyFile($url, $self->destination.'css/'.$include);
                            return $m[0];
                        },
                        $contents);
                    }
                    if (strpos($file, '.min.') === false)
                        $contents = $this->minify_css($contents);
                    $compressed .= $contents;
                }
                $filename = $this->destination.'css/'.md5('css::'.$group).'.css';
                file_put_contents($filename, $compressed);
            }
            $source = preg_replace('/'.preg_quote('<!-- {#} CSS -->').'/',
                '\0<link rel="stylesheet" type="text/css" href="<?php echo ROOT_PATH; ?>css/'
                    .md5('css::'.$group).'.css"/>',
                $source);
        }
        return $source;
    }

    function deployCDN($url, $dest) {
        $dryrun = $this->getOption('dry-run', false);
        $verbose = $this->getOption('verbose') || $dryrun;
        @list($target, $query) = explode('?', rtrim($dest,'/').'/'.basename($url));
        @list($target, $hash) = explode('#', $target, 2);
        if (!file_exists($dest))
            mkdir($dest, 0777-umask(), true);
        $source = basename($url);
        if (file_exists($target))
            return true;
        list($code, $contents) = $this->_http_get('http:'.$url);
        if ($code != 200)
            $this->fail(sprintf('%s: Unable to fetch CDN file', $url));
        if ($verbose) {
            $this->stdout->write("CDN://{$source} => {$target}\n");
        }
        file_put_contents($target, $contents);
    }

    function _rewrite_js($source, $path=false) {
        $compressed = array();
        $self = $this;
        $source = preg_replace_callback(':<script.*>\s*</script>:',
        function ($m) use ($self, $path, &$compressed) {
            $attrs = array();
            $script = str_replace(
                array('<?php echo ', '; ?>', 'ROOT_PATH'), # <?php
                array('', '' , $self->root.'/'),
                $m[0]);
            preg_match_all('/[^= ]+="[^"]+"/', $script, $attrs);
            foreach ($attrs[0] as $A) {
                list($lhs, $rhs) = explode('=', $A);
                $attrs[$lhs] = trim($rhs, '"');
            }
            if (!isset($attrs['src']))
                return $m[0];
            unset($attrs[0]);
            if (isset($attrs['data-group'])) {
                $compressed[$attrs['data-group']][] = $attrs['src'];
                return '<!-- script @src='.$attrs['src'].' -->';
            }
            else if (isset($attrs['src']) && strpos($attrs['src'], '//') === 0) {
                if ($path)
                    $path = rtrim($path, '/').'/';
                // Fetch and deploy the CDN file
                $this->deployCDN($attrs['src'], $this->destination.(@$path ?: 'js').'/');
                if (!$path)
                    $path = '<?php echo ROOT_PATH; ?>js/';
                // FIXME: Figure out relationship of $attrs['src'] and $path
                $attrs['src'] = $path.basename($attrs['src']);
                $script = '<script ';
                foreach ($attrs as $k=>$v) {
                    $script .= " {$k}=\"{$v}\"";
                }
                return $script . '></script>';
            }
            return $m[0];
        },
        $source);
        if ($compressed) {
            $compacted = array();
            foreach ($compressed as $group=>$items) {
                foreach ($items as $file) {
                    if (strpos($file, '//') === 0) {
                        // fetch the CDN file
                        list($code, $contents) = $this->_http_get('http:'.$file);
                        if ($code != 200)
                            $this->fail(sprintf('%s: Unable to fetch from CDN', $file));
                    }
                    else {
                        $contents = file_get_contents($file);
                    }
                    if (strpos($file, '.min.') === false)
                        $contents = $this->minify_js($contents)
                            ?: $this->minify_js2($contents);
                    $compacted[] = trim($contents, "\n");
                }
                $basename = 'js/'.md5('javascript::'.$group).'.js';
                $filename = $this->destination.$basename;
                file_put_contents($filename, implode("\n\n", $compacted));
            }
            $source = preg_replace('/'.preg_quote('<!-- {#} JS -->', '/').'/',
                '\0<script type="text/javascript" src="<?php echo ROOT_PATH; ?>'.$basename.'"></script>',
                $source);
        }
        return $source;
    }

    function compress_php($source) {
        $tokens = array();
        $sig_ws = false;
        static $sigws_tokens = array(
            T_GLOBAL => 1,
            T_STATIC => 1,
            T_CLASS => 1,
            T_FUNCTION => 1,
            T_RETURN => 1,
            T_ECHO => 1,
            T_PRINT => 1,
            T_INCLUDE => 1,
            T_INCLUDE_ONCE => 1,
            T_REQUIRE => 1,
            T_REQUIRE_ONCE => 1,
            T_CASE => 1,
            T_VAR => 1,
            T_INSTANCEOF => 1,
            T_INTERFACE => 1,
            T_NAMESPACE => 1,
            T_NEW => 1,
            T_PRIVATE => 1,
            T_PUBLIC => 1,
            T_PROTECTED => 1,
            T_THROW => 1,
            T_CONST => 1,
            T_ABSTRACT => 1,
            T_ELSE => 1,
            T_AS => 1,
            T_USE => 1,
            T_LOGICAL_AND => 1,
            T_LOGICAL_OR => 1,
            T_LOGICAL_XOR => 1,
            T_EXTENDS => 1,
            T_IMPLEMENTS => 1,
            T_END_HEREDOC => 1,
        );
        $_toks = token_get_all($source);
        while (list($i, $T) = each($_toks)) {
            switch ($T[0]) {
            case T_WHITESPACE:
                if ($sig_ws) {
                    $sig_ws = false;
                    $tokens[] = $T[1][0] ?: ' ';
                }
            case T_DOC_COMMENT:
            case T_COMMENT:
                continue;
            case T_END_HEREDOC:
                $tokens[] = $T[1];
                // This _has_ to be the last thing on a line
                $tokens[] = "\n";
                continue;
            case T_AS:
            case T_USE:
            case T_LOGICAL_AND:
            case T_LOGICAL_OR:
            case T_LOGICAL_XOR:
            case T_EXTENDS:
            case T_INSTANCEOF:
            case T_IMPLEMENTS:
                // Operators — preceeding whitespace is also significant
                $tokens[] = ' ';
            default:
                // Following whitespace is significant
                $sig_ws = isset($sigws_tokens[$T[0]]);
                if (is_string($T[0]))
                    $tokens[] = $T[0];
                else
                    $tokens[] = $T[1];
            }
        }
        return implode('', $tokens);
    }

    function _http_get($url) {
        $this->stdout->write(">>> Downloading $url\n");
        #curl post
        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL, $url);
        curl_setopt($ch, CURLOPT_USERAGENT, 'osTicket/cli');
        curl_setopt($ch, CURLOPT_HEADER, FALSE);
        curl_setopt($ch, CURLOPT_FOLLOWLOCATION, FALSE);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, TRUE);
        $result=curl_exec($ch);
        $code = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        curl_close($ch);

        return array($code, $result);
    }

    function run($args, $options) {
        $this->destination = $args['install-path'];
        if (!is_dir($this->destination))
            if (!@mkdir($this->destination, 0751, true))
                die("Destination path does not exist and cannot be created");
        $this->destination = self::realpath($this->destination).'/';

        # Determine if this is an upgrade, and if so, where the include/
        # folder is currently located
        $upgrade = file_exists("{$this->destination}/main.inc.php");

        # Get the current value of the INCLUDE_DIR before overwriting
        # bootstrap.php
        $include = ($upgrade) ? $this->get_include_dir()
            : ($options['include'] ? $options['include']
                : rtrim($this->destination, '/')."/include");
        $this->include_path = $include = rtrim($include, '/').'/';

        # Locate the upload folder
        $this->root = $root = $this->find_root_folder();
        $this->source = $rootPattern = str_replace("\\","\\\\", $root); //need for windows case

        # Prime the manifest system
        $this->readManifest($this->destination.'/.MANIFEST');

        $exclusions = array("$rootPattern/include/*", "$rootPattern/.git*",
            "*.sw[a-z]","*.md", "*.txt");
        if (!$options['setup'])
            $exclusions[] = "$rootPattern/setup/*";

        # Unpack everything but the include/ folder
        $this->unpackage("$root/{,.}*", $this->destination, -1,
            $exclusions);
        # Unpack the include folder
        $this->unpackage("$root/include/{,.}*", $include, -1,
            array("*/include/ost-config.php", "*.sw[a-z]"));
        if (!$options['dry-run']) {
            if ($include != "{$this->destination}/include/")
                $this->change_include_dir($include);
        }

        if ($options['clean']) {
            // Clean everything but include folder first
            $local_include = str_replace($this->destination, "", $include);
            $this->clean($root, $this->destination, $this->destination, -1,
                array($local_include, "setup/"));
            $this->clean("$root/include", $include, $include, -1,
                array("ost-config.php","settings.php","plugins/",
                "*/.htaccess", ".MANIFEST"));
        }

        if (!$options['dry-run'])
            $this->writeManifest($this->destination);
    }

    function minify_js($source) {
        // Minify contents using `uglifyjs` (if available)
        // (`npm install -g uglifyjs`)
        $pipes = array();
        if ($files = proc_open(
            "uglifyjs", array(0 => array('pipe', 'r'), 1 => array('pipe', 'w')),
            $pipes
        )) {
            fwrite($pipes[0], $source);
            fclose($pipes[0]);
            $source = '';
            while ($block = fread($pipes[1], 8192))
                $source .= $block;
            proc_close($files);
        }
        return $source;
    }

    function minify_js2($str){
        /* This works similar to the minify_css() routine except. It doesn't
         * shorten the method or variable names, though.
         */
        # remove comments first (simplifies the other regex)
        $re1 = <<<'EOS'
(?sx)
      # quotes
        (
          "(?:[^"\\\n]++|\\.)*+"
        | '(?:[^'\\\n]++|\\.)*+'
        )
      |
        # comments
        (?:
          /\* (?> .*? \*/ )
        | //[^\n]++
        )
EOS;

        $re2 = <<<'EOS'
(?six)
      # quotes
      (
        "(?:[^"\\\n]++|\\.)*+"
      | '(?:[^'\\\n]++|\\.)*+'
      )
    |
      # ; before }
      \s*+ ; \s*+ ( } )
    |
      # all spaces around meta chars/operators
      \s*+ ( [[({;,<>=!:.]+ ) \s*+
    |
      # spaces at beginning/end of string (ending with ;)
      ^ \s++ | (?=;)\s++ \z
    |
      # double spaces to single
      (\s)\s+
EOS;

        $str = preg_replace("%$re1%", '$1', $str);
        return preg_replace("%$re2%", '$1$2$3$4', $str);
    }

    // Thanks, http://stackoverflow.com/a/15195752
    function minify_css($str){
        # remove comments first (simplifies the other regex)
        $re1 = <<<'EOS'
(?sx)
      # quotes
        (
          "(?:[^"\\]++|\\.)*+"
        | '(?:[^'\\]++|\\.)*+'
        )
      |
        # comments
        /\* (?> .*? \*/ )
EOS;

        $re2 = <<<'EOS'
(?six)
      # quotes
      (
        "(?:[^"\\]++|\\.)*+"
      | '(?:[^'\\]++|\\.)*+'
      )
    |
      # ; before } (and the spaces after it while we're here)
      \s*+ ; \s*+ ( } ) \s*+
    |
      # calc() and its contents
      ( calc\([^)]+\) )
    |
      # all spaces around meta chars/operators
      \s*+ ( [*$~^|]?+= | [{};,>~+] | !important\b ) \s*+
    |
      # spaces right of ( [ :
      ( [[(:] ) \s++
    |
      # spaces left of ) ]
      \s++ ( [])] )
    |
      # spaces left (and right) of :
      \s++ ( : ) \s*+
      # but not in selectors: not followed by a {
      (?!
        (?>
          [^{}"']++
        | "(?:[^"\\]++|\\.)*+"
        | '(?:[^'\\]++|\\.)*+'
        )*+
        {
      )
    |
      # spaces at beginning/end of string
      ^ \s++ | \s++ \z
    |
      # double spaces to single
      (\s)\s+
EOS;

        $str = preg_replace("%$re1%", '$1', $str);
        return preg_replace("%$re2%", '$1$2$3$4$5$6$7$8', $str);
    }

}

Module::register('deploy', 'Deployment');
?>
