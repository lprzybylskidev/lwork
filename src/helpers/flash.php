<?php declare(strict_types=1);

/**
 * @package src\helpers
 */

use src\flash\FlashBag;

if (!function_exists('flash_bag')) {
    /**
     * @throws RuntimeException
     */
    function flash_bag(): FlashBag
    {
        $container = env_container();

        if ($container === null) {
            throw new RuntimeException(
                'Container not available for flash helper.',
            );
        }

        if (!$container->has(FlashBag::class)) {
            throw new RuntimeException('FlashBag is not registered.');
        }

        return $container->get(FlashBag::class);
    }
}

if (!function_exists('flash')) {
    /**
     * @param string $type
     * @param string $message
     * @return void
     */
    function flash(string $type, string $message): void
    {
        flash_bag()->add($type, $message);
    }
}

if (!function_exists('flash_now')) {
    /**
     * @param string $type
     * @param string $message
     * @return void
     */
    function flash_now(string $type, string $message): void
    {
        flash_bag()->now($type, $message);
    }
}

if (!function_exists('flash_messages')) {
    /**
     * @param string|null $type
     * @return array<string, array<int, string>>|array<int, string>
     */
    function flash_messages(?string $type = null): array
    {
        if ($type === null) {
            return flash_bag()->all();
        }

        return flash_bag()->get($type);
    }
}

if (!function_exists('flash_has')) {
    /**
     * @param string $type
     * @return bool
     */
    function flash_has(string $type): bool
    {
        return flash_bag()->has($type);
    }
}
