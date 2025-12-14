<?php declare(strict_types=1);

/**
 * Defines the metadata that describes this application build.
 * The returned array is intended to be consumed by diagnostics and telemetry consumers.
 * - The `name` entry names the running application and can be overridden per deployment via `APP_NAME`.
 * - The `version` entry tracks the release string that is shipped; it defaults to `0.1.0` when missing.
 * - The `env` entry declares the execution environment and defaults to `production` if no `APP_ENV` is supplied.
 *
 * @return array{name: string, version: string, env: string}
 */
return [
    'name' => env()->getString('APP_NAME', 'lwork') ?? 'lwork',
    'version' => env()->getString('APP_VERSION', '0.1.0') ?? '0.1.0',
    'env' => env()->getString('APP_ENV', 'production') ?? 'production',
];
