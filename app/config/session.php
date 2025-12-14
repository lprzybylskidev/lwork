<?php declare(strict_types=1);

/**
 * Shapes the base session configuration, preparing a deterministic cookie name and secure flag from environment overrides.
 * The secure flag respects `SESSION_SECURE`, and the cookie name falls back to a sanitized `APP_NAME`.
 */
$secureRaw = env()->getString('SESSION_SECURE', '');
$secure = in_array(
    strtolower((string) $secureRaw),
    ['1', 'true', 'yes', 'on'],
    true,
);

$appName = env()->getString('APP_NAME', 'lwork') ?? 'lwork';
$cookieDefault =
    strtolower(preg_replace('/[^a-z0-9]+/', '_', $appName) ?: 'lwork') .
    '_session';

/**
 * Outputs the normalized session configuration map.
 * - `cookie.name` is either the explicitly configured name or a sanitized `APP_NAME` fallback.
 * - `cookie.lifetime` controls how long the session cookie lives, defaulting to 1 hour.
 * - `cookie.path`, `cookie.domain`, `cookie.secure`, `cookie.http_only`, and `cookie.same_site` expose all Layer 7 cookie attributes for HTTP clients.
 * - `regenerate_interval` defines how frequently session identifiers should be rotated.
 *
 * @return array{
 *     cookie: array{
 *         name: string,
 *         lifetime: int,
 *         path: string,
 *         domain: string,
 *         secure: bool|null,
 *         http_only: bool,
 *         same_site: string
 *     },
 *     regenerate_interval: int
 * }
 */
return [
    'cookie' => [
        'name' =>
            env()->getString('SESSION_COOKIE', $cookieDefault) ??
            'lwork_session',
        'lifetime' => env()->getInt('SESSION_LIFETIME', 3600) ?? 3600,
        'path' => '/',
        'domain' => env()->getString('SESSION_DOMAIN', '') ?? '',
        'secure' => $secureRaw === '' ? null : $secure,
        'http_only' => env()->getBool('SESSION_HTTP_ONLY', true),
        'same_site' =>
            env()->getEnum(
                'SESSION_SAMESITE',
                ['lax', 'strict', 'none', ''],
                'lax',
            ) ?? 'lax',
    ],
    'regenerate_interval' =>
        env()->getInt('SESSION_REGENERATE_INTERVAL', 300) ?? 300,
];
