<?php declare(strict_types=1);

/**
 * Defines the default security policy applied across HTTP and CLI runtimes.
 * - `scheme` enforces the URL scheme assumed when building absolute URLs inside helpers.
 * - `headers` enumerates the default HTTP security headers that are sent on every response.
 * - `throttle` contains rate limiting configuration that protects APIs and can be toggled per route.
 *   * `enabled` flips the entire throttle subsystem.
 *   * `global` configures the shared limit/window pair that applies when no route override exists.
 *   * `routes` is an indexed list of route-specific throttle rules that match prefixes via `match`.
 *   * `whitelist` declares clients that should skip throttle checks.
 *
 * @return array{
 *     scheme: string,
 *     headers: array<string, string>,
 *     throttle: array{
 *         enabled: bool,
 *         global: array{limit: int, window: int},
 *         routes: array<int, array{match: string, limit: int, window: int}>,
 *         whitelist: array<int, string>
 *     }
 * }
 */
return [
    'scheme' =>
        env()->getEnum('APP_SCHEME', ['http', 'https'], 'http') ?? 'http',
    'headers' => [
        'X-Frame-Options' => 'DENY',
        'X-Content-Type-Options' => 'nosniff',
        'Referrer-Policy' => 'strict-origin-when-cross-origin',
        'Content-Security-Policy' => "default-src 'self'",
        'Strict-Transport-Security' =>
            'max-age=63072000; includeSubDomains; preload',
    ],
    'throttle' => [
        'enabled' => env()->getBool('THROTTLE_ENABLED', true),
        'global' => [
            'limit' => env()->getInt('THROTTLE_LIMIT', 60),
            'window' => env()->getInt('THROTTLE_WINDOW', 60),
        ],
        'routes' => [
            [
                'match' => '/api',
                'limit' => env()->getInt('THROTTLE_API_LIMIT', 30),
                'window' => env()->getInt('THROTTLE_API_WINDOW', 60),
            ],
        ],
        'whitelist' => [],
    ],
];
