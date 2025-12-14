<?php declare(strict_types=1);

namespace src\bootstrap\php;

use function config;

/**
 * @package src\bootstrap\php
 */
final class PhpRuntimeConfig
{
    /**
     * @return void
     */
    public function __construct() {}

    /**
     * @return void
     */
    public function apply(): void
    {
        $this->configureLocale();
        $this->configureTimezone();
        $this->configureDisplayErrors();
        $this->configureErrorReporting();
        $this->configureLogErrors();
    }

    /**
     * @return void
     */
    private function configureLocale(): void
    {
        $locale = config('php.lang', '');

        if ($locale === null || $locale === '') {
            return;
        }

        setlocale(LC_ALL, $locale);
    }

    /**
     * @return void
     */
    private function configureTimezone(): void
    {
        $timezone = config('php.timezone', '');

        if ($timezone === null || $timezone === '') {
            return;
        }

        try {
            date_default_timezone_set($timezone);
        } catch (\Throwable) {
            // ignore invalid timezone
        }
    }

    /**
     * @return void
     */
    private function configureDisplayErrors(): void
    {
        ini_set('display_errors', '0');
        ini_set('display_startup_errors', '0');
    }

    /**
     * @return void
     */
    private function configureErrorReporting(): void
    {
        $raw = config('php.error_reporting', null);

        if ($raw === null) {
            return;
        }

        if (is_numeric($raw)) {
            error_reporting((int) $raw);
            return;
        }

        $constant = strtoupper($raw);

        if (defined($constant)) {
            error_reporting(constant($constant));
        }
    }

    /**
     * @return void
     */
    private function configureLogErrors(): void
    {
        $value = config('php.log_errors', true);

        ini_set('log_errors', $value ? '1' : '0');
    }
}
