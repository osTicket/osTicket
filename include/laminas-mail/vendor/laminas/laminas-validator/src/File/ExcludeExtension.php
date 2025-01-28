<?php

namespace Laminas\Validator\File;

use function in_array;
use function is_readable;
use function strrpos;
use function strtolower;
use function substr;

/**
 * Validator for the excluding file extensions
 */
class ExcludeExtension extends Extension
{
    use FileInformationTrait;

    /**
     * @const string Error constants
     */
    public const FALSE_EXTENSION = 'fileExcludeExtensionFalse';
    public const NOT_FOUND       = 'fileExcludeExtensionNotFound';

    /** @var array Error message templates */
    protected $messageTemplates = [
        self::FALSE_EXTENSION => 'File has an incorrect extension',
        self::NOT_FOUND       => 'File is not readable or does not exist',
    ];

    /**
     * Returns true if and only if the file extension of $value is not included in the
     * set extension list
     *
     * @param  string|array $value Real file to check for extension
     * @param  array        $file  File data from \Laminas\File\Transfer\Transfer (optional)
     * @return bool
     */
    public function isValid($value, $file = null)
    {
        $fileInfo = $this->getFileInfo($value, $file);

        // Is file readable ?
        if (
            ! $this->getAllowNonExistentFile()
            && (empty($fileInfo['file']) || false === is_readable($fileInfo['file']))
        ) {
            $this->error(self::NOT_FOUND);
            return false;
        }

        $this->setValue($fileInfo['filename']);

        $extension  = substr($fileInfo['filename'], strrpos($fileInfo['filename'], '.') + 1);
        $extensions = $this->getExtension();

        if ($this->getCase() && (! in_array($extension, $extensions))) {
            return true;
        } elseif (! $this->getCase()) {
            foreach ($extensions as $ext) {
                if (strtolower($ext) === strtolower($extension)) {
                    $this->error(self::FALSE_EXTENSION);
                    return false;
                }
            }

            return true;
        }

        $this->error(self::FALSE_EXTENSION);
        return false;
    }
}
