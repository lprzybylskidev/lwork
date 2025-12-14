<?php declare(strict_types=1);

namespace src\console\commands;

use src\container\ContainerInterface;
use src\http\routing\RouteDefinition;
use src\http\routing\Router;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Helper\Table;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

/**
 * @package src\console\commands
 */
final class ListRoutesCommand extends Command
{
    protected static string $defaultName = 'route:list';
    protected static string $defaultDescription = 'Display all registered HTTP routes.';

    private string $basePath;

    public function __construct(
        private Router $router,
        private ContainerInterface $container,
    ) {
        parent::__construct('route:list');
        $this->setDescription(self::$defaultDescription);
        $this->basePath = dirname(__DIR__, 3);
    }

    protected function execute(
        InputInterface $input,
        OutputInterface $output,
    ): int {
        $this->loadRoutes($this->router);

        $routes = $this->router->definitions();

        if ($routes === []) {
            $output->writeln('<comment>No routes registered.</comment>');
            return Command::SUCCESS;
        }

        usort(
            $routes,
            static fn(RouteDefinition $a, RouteDefinition $b): int => strcmp(
                $a->path(),
                $b->path(),
            ),
        );

        $table = new Table($output);
        $table->setHeaders(['METHODS', 'PATH', 'NAME', 'MIDDLEWARE']);

        foreach ($routes as $route) {
            $methods = implode(',', $route->methods());
            $middleware = $route->middleware();
            $table->addRow([
                $methods,
                $route->path(),
                $route->name() ?? '-',
                $middleware === [] ? '-' : implode(',', $middleware),
            ]);
        }

        $table->render();

        return Command::SUCCESS;
    }

    private function loadRoutes(Router $router): void
    {
        $this->loadRoutesFromFile($router, 'app/routes/web.php');
        $this->loadRoutesFromFile($router, 'app/routes/api.php');

        if (!$router->hasPath('/error/{code}')) {
            $this->loadRoutesFromFile($router, 'src/routes/error.php');
        }
    }

    private function loadRoutesFromFile(
        Router $router,
        string $relativePath,
    ): void {
        $path =
            $this->basePath .
            DIRECTORY_SEPARATOR .
            str_replace('/', DIRECTORY_SEPARATOR, $relativePath);

        if (!is_file($path)) {
            return;
        }

        $routes = require $path;

        if (!is_callable($routes)) {
            return;
        }

        $args = [$router];

        if ($this->requiresContainer($routes)) {
            $args[] = $this->container;
        }

        $routes(...$args);
    }

    private function requiresContainer(callable $routes): bool
    {
        $function = new \ReflectionFunction(\Closure::fromCallable($routes));

        return $function->getNumberOfParameters() >= 2;
    }
}
