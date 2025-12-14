<?php declare(strict_types=1);

/**
 * @package src\helpers
 */

use src\container\ContainerInterface;
use src\security\CsrfService;

if (!function_exists('csrf')) {
    /**
     * @throws RuntimeException
     */
    function csrf(): CsrfService
    {
        $container = env_container();

        if ($container === null) {
            throw new RuntimeException(
                'Container not available for csrf() helper.',
            );
        }

        if (!$container->has(CsrfService::class)) {
            throw new RuntimeException(
                'CsrfService is not registered in the container.',
            );
        }

        return $container->get(CsrfService::class);
    }
}

if (!function_exists('csrf_token')) {
    /**
     * @param string|null $tokenId
     * @return string
     */
    function csrf_token(?string $tokenId = null): string
    {
        return csrf()->generateToken($tokenId);
    }
}

if (!function_exists('csrf_field')) {
    /**
     * @param string|null $tokenId
     * @param string $name
     * @return string
     */
    function csrf_field(
        ?string $tokenId = null,
        string $name = '_csrf_token',
    ): string {
        $token = htmlspecialchars(csrf_token($tokenId), ENT_QUOTES);
        $name = htmlspecialchars($name, ENT_QUOTES);

        return sprintf(
            '<input type="hidden" name="%s" value="%s">',
            $name,
            $token,
        );
    }
}

if (!function_exists('csrf_header')) {
    /**
     * @param string|null $tokenId
     * @return array<string, string>
     */
    function csrf_header(?string $tokenId = null): array
    {
        return ['X-CSRF-TOKEN' => csrf_token($tokenId)];
    }
}
