<?php declare(strict_types=1);

namespace src\console\commands;

use RuntimeException;
use src\twig\FrameworkTwigEnvironment;
use function now;

/**
 * @package src\console\commands
 */
final class FileGenerator
{
    private const TYPES = [
        'controller' => [
            'template' => 'controller.twig',
            'base_dir' => 'app/http/controllers',
            'namespace' => 'app\\http\\controllers',
            'suffix' => 'Controller',
        ],
        'use-case' => [
            'template' => 'use_case.twig',
            'base_dir' => 'app/application',
            'namespace' => 'app\\application',
            'suffix' => 'UseCase',
        ],
        'domain-entity' => [
            'template' => 'domain_entity.twig',
            'base_dir' => 'app/domain',
            'namespace' => 'app\\domain',
            'suffix' => '',
        ],
        'domain-value-object' => [
            'template' => 'value_object.twig',
            'base_dir' => 'app/domain/value-objects',
            'namespace' => 'app\\domain\\valueobjects',
            'suffix' => 'ValueObject',
        ],
        'infrastructure' => [
            'template' => 'infrastructure.twig',
            'base_dir' => 'app/infrastructure',
            'namespace' => 'app\\infrastructure',
            'suffix' => '',
        ],
        'migration' => [
            'template' => 'migration.twig',
            'base_dir' => 'app/database/migrations',
            'namespace' => '',
            'suffix' => '',
        ],
        'seeder' => [
            'template' => 'seeder.twig',
            'base_dir' => 'app/database/seeders',
            'namespace' => 'app\\database\\seeders',
            'suffix' => 'Seeder',
        ],
        'console-command' => [
            'template' => 'console_command.twig',
            'base_dir' => 'app/console/commands',
            'namespace' => 'app\\console\\commands',
            'suffix' => 'Command',
        ],
        'event' => [
            'template' => 'event.twig',
            'base_dir' => 'app/events',
            'namespace' => 'app\\events',
            'suffix' => '',
        ],
        'listener' => [
            'template' => 'listener.twig',
            'base_dir' => 'app/events/listeners',
            'namespace' => 'app\\events\\listeners',
            'suffix' => 'Listener',
        ],
        'middleware' => [
            'template' => 'middleware.twig',
            'base_dir' => 'app/http/middleware',
            'namespace' => 'app\\http\\middleware',
            'suffix' => 'Middleware',
        ],
        'scheduler-task' => [
            'template' => 'scheduler_task.twig',
            'base_dir' => 'app/scheduler/tasks',
            'namespace' => 'app\\scheduler\\tasks',
            'suffix' => 'Task',
        ],
    ];

    /**
     * @param FrameworkTwigEnvironment $twig
     * @param string $basePath
     */
    public function __construct(
        private FrameworkTwigEnvironment $twig,
        private string $basePath,
    ) {}

    /**
     * @throws RuntimeException
     */
    public function generate(
        string $type,
        string $name,
        string $context = '',
    ): string {
        if (!isset(self::TYPES[$type])) {
            throw new RuntimeException("Unknown make type: {$type}.");
        }

        $definition = self::TYPES[$type];
        [$directory, $namespace, $className] = $this->resolveTarget(
            $definition,
            $name,
            $context,
        );
        $fileName = $this->buildFileName($type, $className, $name);
        $path =
            rtrim($directory, DIRECTORY_SEPARATOR) .
            DIRECTORY_SEPARATOR .
            $fileName;

        if (is_file($path)) {
            throw new RuntimeException("File already exists: {$path}");
        }

        $this->ensureDirectory($directory);

        $data = [
            'namespace' => $namespace,
            'class' => $className,
            'timestamp' => now()->format('YmdHis'),
            'command' => $this->buildCommandName($className),
            'name' => $name,
        ];

        $content = $this->twig->render("make/{$definition['template']}", $data);
        file_put_contents($path, $content);

        return $path;
    }

    /**
     * @param array<string, mixed> $definition
     * @return array{0:string,1:string,2:string}
     */
    private function resolveTarget(
        array $definition,
        string $name,
        string $context,
    ): array {
        $normalized = trim(str_replace('\\', '/', $name), '/');
        $parts = array_filter(explode('/', $normalized));
        $class = $parts === [] ? '' : array_pop($parts);
        $class = $this->studly($class);
        $suffix = $definition['suffix'] ?? '';

        if ($suffix !== '' && !str_ends_with($class, $suffix)) {
            $class .= $suffix;
        }

        $relative = $parts === [] ? '' : implode(DIRECTORY_SEPARATOR, $parts);

        if ($context !== '') {
            $context = trim(str_replace('\\', '/', $context), '/');
            $relative =
                $relative === ''
                    ? $context
                    : $relative . DIRECTORY_SEPARATOR . $context;
        }

        $baseDir = rtrim($definition['base_dir'], DIRECTORY_SEPARATOR);
        $directory =
            rtrim($this->basePath, DIRECTORY_SEPARATOR) .
            DIRECTORY_SEPARATOR .
            str_replace(['/', '\\'], DIRECTORY_SEPARATOR, $baseDir) .
            ($relative === '' ? '' : DIRECTORY_SEPARATOR . $relative);

        $namespace = $definition['namespace'];
        if ($namespace !== '' && $relative !== '') {
            $namespace .=
                '\\' . str_replace(DIRECTORY_SEPARATOR, '\\', $relative);
        }

        return [$directory, $namespace, $class];
    }

    /**
     * @param string $directory
     * @return void
     */
    private function ensureDirectory(string $directory): void
    {
        if (!is_dir($directory)) {
            mkdir($directory, 0777, true);
        }
    }

    /**
     * @param string $type
     * @param string $class
     * @param string $name
     * @return string
     */
    private function buildFileName(
        string $type,
        string $class,
        string $name,
    ): string {
        if ($type === 'migration') {
            $timestamp = now()->format('YmdHis');
            $snake = $this->snake($name);
            return "{$timestamp}_{$snake}.php";
        }

        return "{$class}.php";
    }

    /**
     * @param string $class
     * @return string
     */
    private function buildCommandName(string $class): string
    {
        $base = $this->snake(str_replace('Command', '', $class), ':');
        if ($base === '') {
            $base = 'command';
        }

        return $base;
    }

    /**
     * @param string $value
     * @return string
     */
    private function studly(string $value): string
    {
        $value = str_replace(['-', '_'], ' ', $value);
        $value = ucwords($value);

        return str_replace(' ', '', $value);
    }

    /**
     * @param string $value
     * @param string $separator
     * @return string
     */
    private function snake(string $value, string $separator = '_'): string
    {
        $value = preg_replace('/[^A-Za-z0-9]+/', ' ', $value);
        $value = trim($value);
        if ($value === '') {
            return '';
        }

        $value = strtolower(preg_replace('/[A-Z]/', ' $0', $value));
        $parts = array_filter(explode(' ', $value));

        return implode($separator, $parts);
    }

    /**
     * @return array<int, string>
     */
    public function supportedTypes(): array
    {
        return array_keys(self::TYPES);
    }
}
