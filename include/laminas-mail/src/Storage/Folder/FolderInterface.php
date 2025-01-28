<?php

namespace Laminas\Mail\Storage\Folder;

use Laminas\Mail\Storage\Exception\ExceptionInterface;
use Laminas\Mail\Storage\Folder;

interface FolderInterface
{
    /**
     * get root folder or given folder
     *
     * @param string $rootFolder get folder structure for given folder, else root
     * @return Folder root or wanted folder
     */
    public function getFolders($rootFolder = null);

    /**
     * select given folder
     *
     * folder must be selectable!
     *
     * @param Folder|string $globalName global name of folder or instance for subfolder
     * @throws ExceptionInterface
     */
    public function selectFolder($globalName);

    /**
     * get Laminas\Mail\Storage\Folder instance for current folder
     *
     * @return string instance of current folder
     * @throws ExceptionInterface
     */
    public function getCurrentFolder();
}
