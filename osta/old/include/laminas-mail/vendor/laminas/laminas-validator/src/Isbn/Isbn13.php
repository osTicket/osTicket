<?php

namespace Laminas\Validator\Isbn;

class Isbn13
{
    /**
     * @param string $value
     * @return int|string
     */
    public function getChecksum($value)
    {
        $sum = $this->sum($value);
        return $this->checksum($sum);
    }

    /**
     * Calculate the value sum.
     *
     * @param string $value
     * @return int
     */
    private function sum($value)
    {
        $sum = 0;

        for ($i = 0; $i < 12; $i++) {
            if ($i % 2 === 0) {
                $sum += (int) $value[$i];
                continue;
            }

            $sum += 3 * (int) $value[$i];
        }

        return $sum;
    }

    /**
     * Calculate the checksum for the value's sum.
     *
     * @param int $sum
     * @return int|string
     */
    private function checksum($sum)
    {
        $checksum = 10 - ($sum % 10);

        if ($checksum === 10) {
            return '0';
        }

        return $checksum;
    }
}
