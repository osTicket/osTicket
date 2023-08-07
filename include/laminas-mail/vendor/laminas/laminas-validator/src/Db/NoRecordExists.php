<?php

namespace Laminas\Validator\Db;

use Laminas\Validator\Exception;

/**
 * Confirms a record does not exist in a table.
 */
class NoRecordExists extends AbstractDb
{
    /**
     * @param mixed $value
     * @return bool
     */
    public function isValid($value)
    {
        /*
         * Check for an adapter being defined. If not, throw an exception.
         */
        if (null === $this->adapter) {
            throw new Exception\RuntimeException('No database adapter present');
        }

        $valid = true;
        $this->setValue($value);

        $result = $this->query($value);
        if ($result) {
            $valid = false;
            $this->error(self::ERROR_RECORD_FOUND);
        }

        return $valid;
    }
}
