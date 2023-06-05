<?php

namespace Laminas\Mail\Storage;

use RecursiveIterator;
use ReturnTypeWillChange;
use Stringable;

use function current;
use function key;
use function next;
use function reset;

class Folder implements RecursiveIterator, Stringable
{
    /**
     * global name (absolute name of folder)
     *
     * @var string
     */
    protected $globalName;

    /**
     * create a new mail folder instance
     *
     * @param string $localName  local name (name of folder in parent folder)
     * @param string $globalName absolute name of folder
     * @param bool $selectable if true folder holds messages, if false it's
     *     just a parent for subfolders (Default: true)
     * @param array<string, Folder> $folders subfolders of
     *     folder array(localName => \Laminas\Mail\Storage\Folder folder)
     */
    public function __construct(
        protected $localName,
        $globalName = '',
        protected $selectable = true,
        protected array $folders = []
    ) {
        $this->globalName = $globalName ?: $localName;
    }

    /**
     * implements RecursiveIterator::hasChildren()
     *
     * @return bool current element has children
     */
    #[ReturnTypeWillChange]
    public function hasChildren()
    {
        $current = $this->current();
        return $current && $current instanceof self && ! $current->isLeaf();
    }

    /**
     * implements RecursiveIterator::getChildren()
     *
     * @return Folder same as self::current()
     */
    #[ReturnTypeWillChange]
    public function getChildren()
    {
        return $this->current();
    }

    /**
     * implements Iterator::valid()
     *
     * @return bool check if there's a current element
     */
    #[ReturnTypeWillChange]
    public function valid()
    {
        return key($this->folders) !== null;
    }

    /**
     * implements Iterator::next()
     */
    #[ReturnTypeWillChange]
    public function next()
    {
        next($this->folders);
    }

    /**
     * implements Iterator::key()
     *
     * @return string key/local name of current element
     */
    #[ReturnTypeWillChange]
    public function key()
    {
        return key($this->folders);
    }

    /**
     * implements Iterator::current()
     *
     * @return Folder current folder
     */
    #[ReturnTypeWillChange]
    public function current()
    {
        return current($this->folders);
    }

    /**
     * implements Iterator::rewind()
     */
    #[ReturnTypeWillChange]
    public function rewind()
    {
        reset($this->folders);
    }

    /**
     * get subfolder named $name
     *
     * @param  string $name wanted subfolder
     * @throws Exception\InvalidArgumentException
     * @return Folder folder named $folder
     */
    public function __get($name)
    {
        if (! isset($this->folders[$name])) {
            throw new Exception\InvalidArgumentException("no subfolder named $name");
        }

        return $this->folders[$name];
    }

    /**
     * add or replace subfolder named $name
     *
     * @param string $name local name of subfolder
     * @param Folder $folder instance for new subfolder
     */
    public function __set($name, self $folder)
    {
        $this->folders[$name] = $folder;
    }

    /**
     * remove subfolder named $name
     *
     * @param string $name local name of subfolder
     */
    public function __unset($name)
    {
        unset($this->folders[$name]);
    }

    /**
     * magic method for easy output of global name
     *
     * @return string global name of folder
     */
    public function __toString(): string
    {
        return (string) $this->getGlobalName();
    }

    /**
     * get local name
     *
     * @return string local name
     */
    public function getLocalName()
    {
        return $this->localName;
    }

    /**
     * get global name
     *
     * @return string global name
     */
    public function getGlobalName()
    {
        return $this->globalName;
    }

    /**
     * is this folder selectable?
     *
     * @return bool selectable
     */
    public function isSelectable()
    {
        return $this->selectable;
    }

    /**
     * check if folder has no subfolder
     *
     * @return bool true if no subfolders
     */
    public function isLeaf()
    {
        return empty($this->folders);
    }
}
