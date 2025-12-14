<?php declare(strict_types=1);

namespace src\bootstrap\providers;

use Dotenv\Dotenv;
use Faker\Factory as FakerFactory;
use Faker\Generator;
use function config;
use src\bootstrap\errors\ErrorHandler;
use src\bootstrap\php\PhpRuntimeConfig;
use src\config\ConfigManager;
use src\container\ContainerInterface;
use src\container\provider\ProviderInterface;
use src\console\CommandDiscovery;
use src\console\commands\FileGenerator;
use src\database\DatabaseConfigLoader;
use src\database\DatabaseManager;
use src\database\DatabaseSchemaGuard;
use src\database\PhinxConfigFactory;
use src\database\drivers\MssqlDriver;
use src\database\drivers\MysqlDriver;
use src\database\drivers\PgsqlDriver;
use src\database\drivers\SqliteDriver;
use src\datetime\CarbonFactory;
use src\environment\Env;
use src\events\EventBus;
use src\events\EventListenerDiscovery;
use src\http\responder\Responder;
use src\log\ErrorFileLogger;
use src\mail\Mailer;
use src\queue\QueueManager;
use src\queue\driver\FilesystemQueueDriver;
use src\security\CsrfMiddleware;
use src\security\CsrfService;
use src\security\SecurityConfig;
use src\security\SecurityHeadersMiddleware;
use src\security\ThrottleMiddleware;
use src\security\ThrottleService;
use src\session\DatabaseSessionHandler;
use src\session\SessionManager;
use src\session\SessionSettings;
use src\flash\FlashBag;
use src\twig\FrameworkTwigEnvironment;
use src\validation\Validator;
use Symfony\Component\Security\Csrf\CsrfTokenManager;
use Twig\Loader\FilesystemLoader;
use src\scheduler\Scheduler;
use src\scheduler\TaskDiscovery;

/**
 * @package src\bootstrap\providers
 */
final class CommonProvider implements ProviderInterface
{
    /**
     * @param ContainerInterface $container
     */
    public function register(ContainerInterface $container): void
    {
        $basePath = (string) $container->param('basePath');

        $dotenv = Dotenv::createImmutable($basePath);
        $dotenv->safeLoad();

        $container->singleton(Env::class);

        $handler = new ErrorHandler();
        $handler->register();
        $container->instance(ErrorHandler::class, $handler);

        $container->singleton(ConfigManager::class, function (
            ContainerInterface $c,
        ): ConfigManager {
            return new ConfigManager((string) $c->param('basePath'));
        });

        $container->singleton(Mailer::class, function (
            ContainerInterface $c,
        ): Mailer {
            return new Mailer($c->get(ConfigManager::class));
        });

        $container->singleton(EventListenerDiscovery::class, function (
            ContainerInterface $c,
        ): EventListenerDiscovery {
            return new EventListenerDiscovery((string) $c->param('basePath'), [
                'src/events/listeners' => 'src\\events\\listeners',
            ]);
        });

        $container->singleton(EventBus::class, function (
            ContainerInterface $c,
        ): EventBus {
            return new EventBus($c, $c->get(EventListenerDiscovery::class));
        });

        $container->singleton(FrameworkTwigEnvironment::class, function (
            ContainerInterface $c,
        ): FrameworkTwigEnvironment {
            $basePath = (string) $c->param('basePath');
            $loader = new FilesystemLoader(
                $basePath .
                    DIRECTORY_SEPARATOR .
                    'src' .
                    DIRECTORY_SEPARATOR .
                    'resources' .
                    DIRECTORY_SEPARATOR .
                    'templates',
            );

            return new FrameworkTwigEnvironment($loader, [
                'autoescape' => false,
            ]);
        });

        $container->singleton(FileGenerator::class, function (
            ContainerInterface $c,
        ): FileGenerator {
            return new FileGenerator(
                $c->get(FrameworkTwigEnvironment::class),
                (string) $c->param('basePath'),
            );
        });

        $container->singleton(CommandDiscovery::class, function (
            ContainerInterface $c,
        ): CommandDiscovery {
            return new CommandDiscovery($c, (string) $c->param('basePath'));
        });

        $container->singleton(Generator::class, function (
            ContainerInterface $c,
        ): Generator {
            return FakerFactory::create(config('php.lang', 'en_US'));
        });

        $container->singleton(Validator::class, function (): Validator {
            return new Validator();
        });

        $container->singleton(Responder::class, function (): Responder {
            return new Responder();
        });

        $container->singleton(CarbonFactory::class, function (
            ContainerInterface $c,
        ): CarbonFactory {
            return new CarbonFactory(config('php.timezone', 'UTC'));
        });

        $container->get(PhpRuntimeConfig::class)->apply();

        $container->singleton(ErrorFileLogger::class, function (
            ContainerInterface $c,
        ): ErrorFileLogger {
            return new ErrorFileLogger((string) $c->param('basePath'));
        });

        $container->singleton(
            CsrfTokenManager::class,
            function (): CsrfTokenManager {
                return new CsrfTokenManager();
            },
        );

        $container->singleton(CsrfService::class, function (
            ContainerInterface $c,
        ): CsrfService {
            return new CsrfService($c->get(CsrfTokenManager::class));
        });

        $container->singleton(CsrfMiddleware::class, function (
            ContainerInterface $c,
        ): CsrfMiddleware {
            return new CsrfMiddleware($c->get(CsrfService::class));
        });

        $container->singleton(FilesystemQueueDriver::class, function (
            ContainerInterface $c,
        ): FilesystemQueueDriver {
            $basePath = (string) $c->param('basePath');
            $dir =
                $basePath .
                DIRECTORY_SEPARATOR .
                'storage' .
                DIRECTORY_SEPARATOR .
                'queue';

            return new FilesystemQueueDriver($dir);
        });

        $container->singleton(QueueManager::class, function (
            ContainerInterface $c,
        ): QueueManager {
            return new QueueManager($c->get(FilesystemQueueDriver::class), $c);
        });

        $container->singleton(
            SecurityConfig::class,
            function (): SecurityConfig {
                return SecurityConfig::fromConfig();
            },
        );

        $container->singleton(ThrottleService::class, function (
            ContainerInterface $c,
        ): ThrottleService {
            $dir =
                (string) $c->param('basePath') .
                DIRECTORY_SEPARATOR .
                'storage' .
                DIRECTORY_SEPARATOR .
                'cache' .
                DIRECTORY_SEPARATOR .
                'throttle';

            return new ThrottleService($dir);
        });

        $container->singleton(SecurityHeadersMiddleware::class, function (
            ContainerInterface $c,
        ): SecurityHeadersMiddleware {
            return new SecurityHeadersMiddleware(
                $c->get(SecurityConfig::class),
            );
        });

        $container->singleton(ThrottleMiddleware::class, function (
            ContainerInterface $c,
        ): ThrottleMiddleware {
            return new ThrottleMiddleware(
                $c->get(ThrottleService::class),
                $c->get(SecurityConfig::class),
            );
        });

        $container->singleton(DatabaseConfigLoader::class, function (
            ContainerInterface $c,
        ): DatabaseConfigLoader {
            return new DatabaseConfigLoader($c->get(ConfigManager::class));
        });

        $container->singleton(DatabaseManager::class, function (
            ContainerInterface $c,
        ): DatabaseManager {
            $loader = $c->get(DatabaseConfigLoader::class);
            $manager = new DatabaseManager($loader->load());
            $manager->registerDriver('mysql', new MysqlDriver());
            $manager->registerDriver('pgsql', new PgsqlDriver());
            $manager->registerDriver('mssql', new MssqlDriver());
            $manager->registerDriver('sqlite', new SqliteDriver());
            return $manager;
        });

        $container->singleton(DatabaseSchemaGuard::class, function (
            ContainerInterface $c,
        ): DatabaseSchemaGuard {
            return new DatabaseSchemaGuard(
                $c->get(DatabaseManager::class),
                $c->get(DatabaseConfigLoader::class),
            );
        });

        $container
            ->get(DatabaseSchemaGuard::class)
            ->ensureTables(['sessions', 'queue_jobs']);

        $container->singleton(DatabaseSessionHandler::class, function (
            ContainerInterface $c,
        ): DatabaseSessionHandler {
            $loader = $c->get(DatabaseConfigLoader::class);

            return new DatabaseSessionHandler(
                $c
                    ->get(DatabaseManager::class)
                    ->connection($loader->defaultConnection()),
                (int) (config('session.cookie.lifetime', 3600) ?? 3600),
            );
        });

        $container->singleton(SessionManager::class, function (
            ContainerInterface $c,
        ): SessionManager {
            $cookieDomain = config('session.cookie.domain', '');
            $cookieDomain =
                $cookieDomain === '' ? null : (string) $cookieDomain;

            $settings = new SessionSettings(
                config('session.cookie.name', 'lwork_session') ??
                    'lwork_session',
                (int) (config('session.cookie.lifetime', 3600) ?? 3600),
                config('session.cookie.path', '/') ?? '/',
                $cookieDomain,
                config('session.cookie.secure', null),
                (bool) (config('session.cookie.http_only', true) ?? true),
                config('session.cookie.same_site', 'lax') ?? 'lax',
                config('security.scheme', 'http') ?? 'http',
            );

            $manager = new SessionManager();
            $manager->configure($settings);
            $manager->setHandler($c->get(DatabaseSessionHandler::class));
            $manager->start();

            return $manager;
        });

        $container->singleton(FlashBag::class, function (
            ContainerInterface $c,
        ): FlashBag {
            return new FlashBag($c->get(SessionManager::class));
        });

        $container->singleton(PhinxConfigFactory::class, function (
            ContainerInterface $c,
        ): PhinxConfigFactory {
            return new PhinxConfigFactory(
                $c->get(DatabaseConfigLoader::class),
                (string) $c->param('basePath'),
                'app/database/migrations',
                'app/database/seeders',
                ['src/database/migrations/core'],
            );
        });

        $container->singleton(TaskDiscovery::class, function (
            ContainerInterface $c,
        ): TaskDiscovery {
            return new TaskDiscovery((string) $c->param('basePath'), [
                'app/scheduler/tasks' => 'app\\scheduler\\tasks',
                'src/scheduler/tasks' => 'src\\scheduler\\tasks',
            ]);
        });

        $container->singleton(Scheduler::class, function (
            ContainerInterface $c,
        ): Scheduler {
            return new Scheduler(
                $c,
                $c->get(TaskDiscovery::class),
                $c->get(CommandDiscovery::class),
                $c->get(EventBus::class),
            );
        });
    }
}
