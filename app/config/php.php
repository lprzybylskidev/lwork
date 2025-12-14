<?php declare(strict_types=1);

/**
 * Collects PHP runtime preferences derived from environment variables.
 * - `lang` defines the locale used by Faker, Twig helpers, and translation tooling.
 * - `timezone` controls PHP/Twig defaults and the Carbon default timezone.
 * - `error_reporting` sets the `error_reporting` level executed during bootstrap.
 * - `log_errors` toggles PHP's native `log_errors` flag via `ini_set`.
 *
 * @return array{
 *     lang: string,
 *     timezone: string,
 *     error_reporting: string,
 *     log_errors: bool
 * }
 */
return [
    'lang' => env()->getString('APP_LANG', 'en_US') ?? 'en_US',
    'timezone' => env()->getString('APP_TIMEZONE', 'UTC') ?? 'UTC',
    'error_reporting' =>
        env()->getString('PHP_ERROR_REPORTING', 'E_ALL') ?? 'E_ALL',
    'log_errors' => env()->getBool('PHP_LOG_ERRORS', true),
];
