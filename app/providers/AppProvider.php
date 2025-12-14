<?php declare(strict_types=1);

namespace app\providers;

use src\container\ContainerInterface;
use src\container\provider\BindingsScanner;
use src\container\provider\ProviderInterface;

/**
 * @package app\providers
 */
final class AppProvider implements ProviderInterface
{
    /**
     * @param ContainerInterface $container
     */
    public function register(ContainerInterface $container): void
    {
        $scanner = new BindingsScanner((string) $container->param('basePath'), [
            'app/application' => 'app\\application',
            'app/domain' => 'app\\domain',
            'app/infrastructure' => 'app\\infrastructure',
            'app/http' => 'app\\http',
            'app/console' => 'app\\console',
            'app/events' => 'app\\events',
            'app/scheduler' => 'app\\scheduler',
        ]);

        $scanner->register($container);
    }
}
