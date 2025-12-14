<?php declare(strict_types=1);

namespace src\bootstrap\errors;

use Psr\Http\Message\ServerRequestInterface;
use src\environment\Env;
use src\session\SessionManager;
use Whoops\Exception\Formatter;
use Whoops\Handler\Handler;
use Whoops\Handler\PlainTextHandler;
use Whoops\Handler\PrettyPageHandler;

/**
 * @package src\bootstrap\errors
 */
final class FrameworkPrettyPageHandler extends PrettyPageHandler
{
    private ?ServerRequestInterface $request = null;
    private ?int $status = null;
    private ?string $errorCode = null;
    private ?bool $isApi = null;

    public function __construct(
        private Env $env,
        private SessionManager $session,
    ) {
        parent::__construct();
        $this->addResourcePath(__DIR__ . '/resources');
    }

    public function setRequest(?ServerRequestInterface $request): void
    {
        $this->request = $request;
    }

    public function setContext(
        int $status,
        string $errorCode,
        bool $isApi,
    ): void {
        $this->status = $status;
        $this->errorCode = $errorCode;
        $this->isApi = $isApi;
    }

    /**
     * @return int|null
     *
     * @throws \Exception
     */
    public function handle()
    {
        if (!$this->handleUnconditionally()) {
            if (PHP_SAPI === 'cli') {
                if (isset($_ENV['whoops-test'])) {
                    throw new \Exception(
                        'Use handleUnconditionally instead of whoops-test' .
                            ' environment variable',
                    );
                }

                return Handler::DONE;
            }
        }

        $templateFile = $this->getResource('views/layout.html.php');
        $cssFile = $this->getResource('css/whoops.base.css');
        $zeptoFile = $this->getResource('js/zepto.min.js');
        $prismJs = $this->getResource('js/prism.js');
        $prismCss = $this->getResource('css/prism.css');
        $clipboard = $this->getResource('js/clipboard.min.js');
        $jsFile = $this->getResource('js/whoops.base.js');

        $inspector = $this->getInspector();
        $frames = $this->getExceptionFrames();
        $code = $this->getExceptionCode();

        $request = $this->request;
        $method = $request?->getMethod() ?? 'n/a';
        $uri = $request?->getUri()->getPath() ?? 'n/a';
        $headers = $request?->getHeaders() ?? [];
        $routeParams = [];
        if ($request !== null) {
            $routeParams = $request->getAttribute('route_params', []);
        }

        $sessionData = [];
        if (session_status() === PHP_SESSION_ACTIVE) {
            $sessionData = $this->session->all();
        }
        if ($sessionData === []) {
            $sessionData = ['empty' => true];
        }

        $applicationTable = [
            'error_code' => $this->errorCode ?? 'n/a',
            'status' => $this->status ?? $code,
            'env' => $this->env->appEnv(),
            'is_api' => $this->isApi ? 1 : 0,
            'method' => $method,
            'uri' => $uri,
        ];

        $requestSnapshot = [
            'method' => $method,
            'uri' => $uri,
            'query' => $request?->getQueryParams() ?? [],
            'body' => $request?->getParsedBody(),
            'headers' => array_map(
                fn(array $values) => implode(', ', $values),
                $headers,
            ),
            'route_params' => $routeParams,
        ];

        $envVariables = $this->env->all();
        ksort($envVariables);

        $vars = [
            'page_title' => $this->getPageTitle(),

            'stylesheet' => file_get_contents($cssFile),
            'zepto' => file_get_contents($zeptoFile),
            'prismJs' => file_get_contents($prismJs),
            'prismCss' => file_get_contents($prismCss),
            'clipboard' => file_get_contents($clipboard),
            'javascript' => file_get_contents($jsFile),

            'header' => $this->getResource('views/header.html.php'),
            'header_outer' => $this->getResource('views/header_outer.html.php'),
            'frame_list' => $this->getResource('views/frame_list.html.php'),
            'frames_description' => $this->getResource(
                'views/frames_description.html.php',
            ),
            'frames_container' => $this->getResource(
                'views/frames_container.html.php',
            ),
            'panel_details' => $this->getResource(
                'views/panel_details.html.php',
            ),
            'panel_details_outer' => $this->getResource(
                'views/panel_details_outer.html.php',
            ),
            'panel_left' => $this->getResource('views/panel_left.html.php'),
            'panel_left_outer' => $this->getResource(
                'views/panel_left_outer.html.php',
            ),
            'frame_code' => $this->getResource('views/frame_code.html.php'),
            'env_details' => $this->getResource('views/env_details.html.php'),

            'title' => $this->getPageTitle(),
            'name' => explode('\\', $inspector->getExceptionName()),
            'message' => $inspector->getExceptionMessage(),
            'previousMessages' => $inspector->getPreviousExceptionMessages(),
            'docref_url' => $inspector->getExceptionDocrefUrl(),
            'code' => $code,
            'previousCodes' => $inspector->getPreviousExceptionCodes(),
            'plain_exception' => Formatter::formatExceptionPlain($inspector),
            'frames' => $frames,
            'has_frames' => !!count($frames),
            'handler' => $this,
            'handlers' => $this->getRun()->getHandlers(),

            'active_frames_tab' =>
                count($frames) && $frames->offsetGet(0)->isApplication()
                    ? 'application'
                    : 'all',
            'has_frames_tabs' => $this->getApplicationPaths(),

            'tables' => [
                'Application' => $applicationTable,
                'Request Snapshot' => $requestSnapshot,
                'Session' => $sessionData,
                'Environment Variables' => $envVariables,
            ],
        ];

        $extraTables = array_map(function ($table) use ($inspector) {
            return $table instanceof \Closure ? $table($inspector) : $table;
        }, $this->getDataTables());
        $vars['tables'] = array_merge($extraTables, $vars['tables']);

        $plainTextHandler = new PlainTextHandler();
        $plainTextHandler->setRun($this->getRun());
        $plainTextHandler->setException($this->getException());
        $plainTextHandler->setInspector($this->getInspector());
        $vars['preface'] =
            "<!--\n\n\n" .
            $this->templateHelper->escape(
                $plainTextHandler->generateResponse(),
            ) .
            "\n\n\n\n\n\n\n\n\n\n\n-->";

        $this->templateHelper->setVariables($vars);
        $this->templateHelper->render($templateFile);

        return Handler::QUIT;
    }
}
