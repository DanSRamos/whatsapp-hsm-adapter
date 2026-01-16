<?php

namespace App\Traits;

trait DateTrait
{
    /**
     * @param string $timestamp
     * @param string $format
     *
     * @return void
     */
    public function fromTimestamp($timestamp = null, $format = 'Y-m-d H:i:s'): string
    {
        if (empty($timestamp)) {
            $timestamp = time();
        }

        return gmdate($format, $timestamp);
    }

    /**
     * Validates a datetime.
     */
    public function validateDatetime(string $date, string $format = 'Y-m-d H:i:s'): bool
    {
        if (!in_array($format, ['Y-m-d H:i:s', 'Y-m-d', 'UTC'])) {
            throw new \Exception('invalid_datetime_format');
        }

        $datetimeFormat = \DateTime::createFromFormat($format, $date);

        return $datetimeFormat && $datetimeFormat->format($format) === $date;
    }
}
