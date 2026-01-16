<?php

namespace Laminas\Mail\Storage\Folder;

use Laminas\Mail\Storage;
use Laminas\Mail\Storage\Exception;
use Laminas\Mail\Storage\Exception\InvalidArgumentException;
use Laminas\Mail\Storage\Folder;
use Laminas\Mail\Storage\ParamsNormalizer;
use Laminas\Stdlib\ErrorHandler;

use function array_pop;
use function array_push;
use function closedir;
use function explode;
use function is_dir;
use function opendir;
use function readdir;
use function rtrim;
use function sort;
use function str_contains;
use function str_starts_with;
use function strlen;
use function substr;
use function trim;

use const DIRECTORY_SEPARATOR;
use const E_WARNING;

class Maildir extends Storage\Maildir implements FolderInterface
{
    /**
     * root folder for folder structure
     *
     * @var Storage\Folder
     */
    protected $rootFolder;

    /**
     * rootdir of folder structure
     *
     * @var string
     */
    protected $rootdir;

    /**
     * name of current folder
     *
     * @var string
     */
    protected $currentFolder;

    /**
     * delim char for subfolders
     *
     * @var string
     */
    protected $delim;

    /**
     * Create instance with parameters
     *
     * Supported parameters are:
     *
     * - dirname rootdir of maildir structure
     * - delim   delim char for folder structure, default is '.'
     * - folder initial selected folder, default is 'INBOX'
     *
     * @param  object|array $params mail reader specific parameters
     * @throws Exception\InvalidArgumentException
     */
    public function __construct($params)
    {
        $params = ParamsNormalizer::normalizeParams($params);

        if (! isset($params['dirname'])) {
            throw new Exception\InvalidArgumentException('no dirname provided in params');
        }

        $dirname = (string) $params['dirname'];

        if (! is_dir($dirname)) {
            throw new Exception\InvalidArgumentException('$dirname provided in params is not a directory');
        }

        $this->rootdir = rtrim($dirname, DIRECTORY_SEPARATOR) . DIRECTORY_SEPARATOR;

        $delim       = $params['delim'] ?? '.';
        $this->delim = (string) $delim;

        $folder = $params['folder'] ?? 'INBOX';

        $this->buildFolderTree();
        $this->selectFolder((string) $folder);
        $this->has['top']   = true;
        $this->has['flags'] = true;
    }

    /**
     * find all subfolders and mbox files for folder structure
     *
     * Result is save in Storage\Folder instances with the root in $this->rootFolder.
     * $parentFolder and $parentGlobalName are only used internally for recursion.
     *
     * @throws Exception\RuntimeException
     */
    protected function buildFolderTree()
    {
        $this->rootFolder        = new Storage\Folder('/', '/', false);
        $this->rootFolder->INBOX = new Storage\Folder('INBOX', 'INBOX', true);

        ErrorHandler::start(E_WARNING);
        $dh    = opendir($this->rootdir);
        $error = ErrorHandler::stop();
        if (! $dh) {
            throw new Exception\RuntimeException("can't read folders in maildir", 0, $error);
        }
        $dirs = [];

        while (($entry = readdir($dh)) !== false) {
            // maildir++ defines folders must start with .
            if ($entry[0] != '.' || $entry == '.' || $entry == '..') {
                continue;
            }

            if ($this->isMaildir($this->rootdir . $entry)) {
                $dirs[] = $entry;
            }
        }
        closedir($dh);

        sort($dirs);
        $stack        = [null];
        $folderStack  = [null];
        $parentFolder = $this->rootFolder;
        $parent       = '.';

        foreach ($dirs as $dir) {
            do {
                if (str_starts_with($dir, $parent)) {
                    $local = substr($dir, strlen((string) $parent));
                    if (str_contains($local, $this->delim)) {
                        throw new Exception\RuntimeException('error while reading maildir');
                    }
                    array_push($stack, $parent);
                    $parent               = $dir . $this->delim;
                    $folder               = new Storage\Folder($local, substr($dir, 1), true);
                    $parentFolder->$local = $folder;
                    array_push($folderStack, $parentFolder);
                    $parentFolder = $folder;
                    break;
                } elseif ($stack) {
                    $parent       = array_pop($stack);
                    $parentFolder = array_pop($folderStack);
                }
            } while ($stack);
            if (! $stack) {
                throw new Exception\RuntimeException('error while reading maildir');
            }
        }
    }

    /**
     * get root folder or given folder
     *
     * @param string $rootFolder get folder structure for given folder, else root
     * @throws InvalidArgumentException
     * @return Folder root or wanted folder
     */
    public function getFolders($rootFolder = null)
    {
        if (! $rootFolder || $rootFolder == 'INBOX') {
            return $this->rootFolder;
        }

        // rootdir is same as INBOX in maildir
        if (str_starts_with($rootFolder, 'INBOX' . $this->delim)) {
            $rootFolder = substr($rootFolder, 6);
        }
        $currentFolder = $this->rootFolder;
        $subname       = trim($rootFolder, $this->delim);

        while ($currentFolder) {
            if (str_contains($subname, $this->delim)) {
                [$entry, $subname] = explode($this->delim, $subname, 2);
            } else {
                $entry   = $subname;
                $subname = null;
            }

            $currentFolder = $currentFolder->$entry;

            if (! $subname) {
                break;
            }
        }

        if ($currentFolder->getGlobalName() != rtrim($rootFolder, $this->delim)) {
            throw new Exception\InvalidArgumentException("folder $rootFolder not found");
        }
        return $currentFolder;
    }

    /**
     * select given folder
     *
     * folder must be selectable!
     *
     * @param Storage\Folder|string $globalName global name of folder or
     *     instance for subfolder
     * @throws Exception\RuntimeException
     */
    public function selectFolder($globalName)
    {
        $this->currentFolder = (string) $globalName;

        // getting folder from folder tree for validation
        $folder = $this->getFolders($this->currentFolder);

        try {
            $this->openMaildir($this->rootdir . '.' . $folder->getGlobalName());
        } catch (Exception\ExceptionInterface $e) {
            // check what went wrong
            if (! $folder->isSelectable()) {
                throw new Exception\RuntimeException("{$this->currentFolder} is not selectable", 0, $e);
            }
            // seems like file has vanished; rebuilding folder tree - but it's still an exception
            $this->buildFolderTree();
            throw new Exception\RuntimeException(
                'seems like the maildir has vanished; I have rebuilt the folder tree; '
                . 'search for another folder and try again',
                0,
                $e
            );
        }
    }

    /**
     * get Storage\Folder instance for current folder
     *
     * @return string instance of current folder
     */
    public function getCurrentFolder()
    {
        return $this->currentFolder;
    }
}
