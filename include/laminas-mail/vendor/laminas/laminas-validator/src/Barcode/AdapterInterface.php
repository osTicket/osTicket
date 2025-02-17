<?php

namespace Laminas\Validator\Barcode;

interface AdapterInterface
{
    /**
     * Checks the length of a barcode
     *
     * @param  string $value  The barcode to check for proper length
     * @return bool
     */
    public function hasValidLength($value);

    /**
     * Checks for allowed characters within the barcode
     *
     * @param  string $value The barcode to check for allowed characters
     * @return bool
     */
    public function hasValidCharacters($value);

    /**
     * Validates the checksum
     *
     * @param string $value The barcode to check the checksum for
     * @return bool
     */
    public function hasValidChecksum($value);

    /**
     * Returns the allowed barcode length
     *
     * @return int|string|array|null
     */
    public function getLength();

    /**
     * Returns the allowed characters
     *
     * @return int|string|array|null
     */
    public function getCharacters();

    /**
     * Returns if barcode uses a checksum
     *
     * @return string|null
     */
    public function getChecksum();

    /**
     * Sets the checksum validation, if no value is given, the actual setting is returned
     *
     * @param  bool|null $check
     * @return $this|bool
     * @psalm-return ($check is null ? bool : static)
     */
    public function useChecksum($check = null);
}
