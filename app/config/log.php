<?php declare(strict_types=1);

/**
 * Configures the error logger that captures PHP-level issues.
 * - `enabled` controls whether error logging runs or is short-circuited.
 * - `rotation` selects the log rotation strategy (single, daily, weekly, monthly) used by Monolog or the flat file writer.
 * - `dir` defines the directory where error logs are stored (relative to project root when not absolute).
 * - `file` states the filename used for the aggregated errors stream.
 *
 * @return array{
 *     enabled: bool,
 *     rotation: string,
 *     dir: string,
 *     file: string
 * }
 */
return [
    'enabled' => env()->getBool('ERROR_LOG_ENABLED', true),
    'rotation' => env()->getString('ERROR_LOG_ROTATION', 'single') ?? 'single',
    'dir' =>
        env()->getString('ERROR_LOG_DIR', 'storage/errors') ?? 'storage/errors',
    'file' => env()->getString('ERROR_LOG_FILE', 'errors.log') ?? 'errors.log',
];
