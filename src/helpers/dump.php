<?php declare(strict_types=1);

/**
 * @package src\helpers
 */

use Symfony\Component\VarDumper\VarDumper;

if (!function_exists('dump')) {
    /**
     * @param mixed $value
     * @return void
     */
    function dump(mixed $value): void
    {
        VarDumper::dump($value);
    }
}

if (!function_exists('dd')) {
    /**
     * @param mixed ...$values
     * @return void
     */
    function dd(mixed ...$values): void
    {
        foreach ($values as $value) {
            VarDumper::dump($value);
        }

        exit(1);
    }
}
