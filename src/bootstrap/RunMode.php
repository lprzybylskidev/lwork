<?php

declare(strict_types=1);

namespace src\bootstrap;

/**
 * @package src\bootstrap
 */
enum RunMode: string
{
    case Http = 'http';
    case Cli = 'cli';
}
