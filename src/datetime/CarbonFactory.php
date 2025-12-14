<?php declare(strict_types=1);

namespace src\datetime;

use Carbon\CarbonImmutable;

/**
 * @package src\datetime
 */
final class CarbonFactory
{
    /**
     * @param string $timezone
     */
    public function __construct(private string $timezone) {}

    /**
     * @param string $time
     * @return CarbonImmutable
     */
    public function create(string $time = 'now'): CarbonImmutable
    {
        return CarbonImmutable::parse($time, $this->timezone);
    }

    /**
     * @return CarbonImmutable
     */
    public function now(): CarbonImmutable
    {
        return $this->create('now');
    }

    /**
     * @return string
     */
    public function timezone(): string
    {
        return $this->timezone;
    }
}
