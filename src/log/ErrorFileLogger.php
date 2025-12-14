<?php declare(strict_types=1);

namespace src\log;

use function config;

/**
 * @package src\log
 */
final class ErrorFileLogger
{
    /**
     * @param string $basePath
     */
    public function __construct(private string $basePath) {}

    /**
     * @param \Throwable $e
     * @param string $errorCode
     * @param array<string, mixed> $context
     * @return void
     */
    public function log(
        \Throwable $e,
        string $errorCode,
        array $context = [],
    ): void {
        if (!config('log.enabled', true)) {
            return;
        }

        $dir = config('log.dir', 'storage/errors') ?? 'storage/errors';
        $dir = rtrim($dir, '/\\');
        $fullDir =
            $this->basePath .
            DIRECTORY_SEPARATOR .
            str_replace(['/', '\\'], DIRECTORY_SEPARATOR, $dir);

        if (!is_dir($fullDir)) {
            @mkdir($fullDir, 0777, true);
        }

        $path = $fullDir . DIRECTORY_SEPARATOR . $this->resolveFileName();

        $line = $this->formatLine($e, $errorCode, $context);

        @file_put_contents($path, $line . "\n", FILE_APPEND | LOCK_EX);
    }

    /**
     * @return string
     */
    private function resolveFileName(): string
    {
        $rotation = strtolower(config('log.rotation', 'single') ?? 'single');
        $baseFile = config('log.file', 'errors.log') ?? 'errors.log';

        $baseFile = trim($baseFile);
        if ($baseFile === '') {
            $baseFile = 'errors.log';
        }

        $ext = pathinfo($baseFile, PATHINFO_EXTENSION);
        $name = pathinfo($baseFile, PATHINFO_FILENAME);

        $ext = $ext !== '' ? $ext : 'log';

        $suffix = match ($rotation) {
            'daily' => date('Y-m-d'),
            'monthly' => date('Y-m'),
            default => null,
        };

        if ($suffix === null) {
            return "{$name}.{$ext}";
        }

        return "{$name}-{$suffix}.{$ext}";
    }

    /**
     * @param \Throwable $e
     * @param string $errorCode
     * @param array<string, mixed> $context
     * @return string
     */
    private function formatLine(
        \Throwable $e,
        string $errorCode,
        array $context,
    ): string {
        $ts = date('c');
        $env = config('app.env', 'production');

        $ctx = $this->normalizeContext($context);
        $ctxJson =
            $ctx !== []
                ? json_encode(
                    $ctx,
                    JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES,
                )
                : '';

        $trace = str_replace("\n", ' | ', $e->getTraceAsString());

        return sprintf(
            '[%s] env=%s error_code=%s %s: %s in %s:%d trace=%s context=%s',
            $ts,
            $env,
            $errorCode,
            get_class($e),
            $e->getMessage(),
            $e->getFile(),
            $e->getLine(),
            $trace,
            $ctxJson,
        );
    }

    /**
     * @param array<string, mixed> $context
     * @return array<string, string|int|float|bool|null>
     */
    private function normalizeContext(array $context): array
    {
        $out = [];

        foreach ($context as $k => $v) {
            if (is_scalar($v) || $v === null) {
                $out[$k] = $v;
                continue;
            }

            $out[$k] = json_encode(
                $v,
                JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES,
            );
        }

        return $out;
    }
}
