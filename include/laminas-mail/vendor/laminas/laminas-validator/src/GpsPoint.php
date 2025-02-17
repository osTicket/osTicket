<?php

namespace Laminas\Validator;

use function explode;
use function is_numeric;
use function preg_match;
use function preg_match_all;
use function preg_replace;
use function str_contains;
use function str_replace;

final class GpsPoint extends AbstractValidator
{
    public const OUT_OF_BOUNDS         = 'gpsPointOutOfBounds';
    public const CONVERT_ERROR         = 'gpsPointConvertError';
    public const INCOMPLETE_COORDINATE = 'gpsPointIncompleteCoordinate';

    protected array $messageTemplates = [
        self::OUT_OF_BOUNDS         => '%value% is out of Bounds.',
        self::CONVERT_ERROR         => '%value% can not converted into a Decimal Degree Value.',
        self::INCOMPLETE_COORDINATE => '%value% did not provided a complete Coordinate',
    ];

    /**
     * Returns true if and only if $value meets the validation requirements
     *
     * If $value fails validation, then this method returns false, and
     * getMessages() will return an array of messages that explain why the
     * validation failed.
     *
     * @throws Exception\RuntimeException If validation of $value is impossible.
     */
    public function isValid(mixed $value): bool
    {
        if (! str_contains($value, ',')) {
            $this->error(self::INCOMPLETE_COORDINATE, $value);
            return false;
        }

        [$lat, $long] = explode(',', $value);

        return $this->isValidCoordinate($lat, 90.0000) && $this->isValidCoordinate($long, 180.000);
    }

    private function isValidCoordinate(string $value, float $maxBoundary): bool
    {
        $this->value = $value;

        $value = $this->removeWhiteSpace($value);
        if ($this->isDMSValue($value)) {
            $value = $this->convertValue($value);
        } else {
            $value = $this->removeDegreeSign($value);
        }

        if ($value === false) {
            $this->error(self::CONVERT_ERROR);
            return false;
        }

        $castedValue = (float) $value;
        if (! is_numeric($value) && $castedValue === 0.0) {
            $this->error(self::CONVERT_ERROR);
            return false;
        }

        if (! $this->isValueInbound($castedValue, $maxBoundary)) {
            $this->error(self::OUT_OF_BOUNDS);
            return false;
        }

        return true;
    }

    /**
     * Determines if the give value is a Degrees Minutes Second Definition
     */
    private function isDMSValue(string $value): bool
    {
        return preg_match('/([°\'"]+[NESW])/', $value) > 0;
    }

    private function convertValue(string $value): false|float
    {
        $matches = [];
        $result  = preg_match_all('/(\d{1,3})°(\d{1,2})\'(\d{1,2}[\.\d]{0,6})"[NESW]/i', $value, $matches);

        if ($result === false || $result === 0) {
            return false;
        }

        return $matches[1][0] + $matches[2][0] / 60 + ((float) $matches[3][0]) / 3600;
    }

    private function removeWhiteSpace(string $value): string
    {
        return preg_replace('/\s/', '', $value);
    }

    private function removeDegreeSign(string $value): string
    {
        return str_replace('°', '', $value);
    }

    private function isValueInbound(float $value, float $boundary): bool
    {
        $max = $boundary;
        $min = -1 * $boundary;
        return $min <= $value && $value <= $max;
    }
}
