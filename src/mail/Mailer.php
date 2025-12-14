<?php declare(strict_types=1);

namespace src\mail;

use src\config\ConfigManager;

/**
 * @package src\mail
 */
final class Mailer
{
    public function __construct(private ConfigManager $config) {}

    /**
     * @param string|array<int,string> $to
     * @param string $subject
     * @param string $body
     * @param array<string,string> $headers
     * @param bool $html
     * @return bool
     */
    public function send(
        string|array $to,
        string $subject,
        string $body,
        array $headers = [],
        bool $html = false,
    ): bool {
        $driver = $this->config->get('mail.driver', 'mail');
        $from = $this->config->get('mail.from', []);
        $prefix = (string) $this->config->get('mail.subject_prefix', '');

        if ($prefix !== '') {
            $subject = trim("{$prefix} {$subject}");
        }

        $toHeader = is_array($to) ? implode(', ', $to) : $to;

        $defaultHeaders = [
            'From' => $this->formatAddress($from),
            'MIME-Version' => '1.0',
        ];

        if ($html) {
            $defaultHeaders['Content-type'] = 'text/html; charset=UTF-8';
        } else {
            $defaultHeaders['Content-type'] = 'text/plain; charset=UTF-8';
        }

        $allHeaders = array_merge($defaultHeaders, $headers);

        $flattened = $this->flattenHeaders($allHeaders);

        if ($driver === 'mail') {
            return mail($toHeader, $subject, $body, $flattened);
        }

        return false;
    }

    /**
     * @param array<string,string> $headers
     * @return string
     */
    private function flattenHeaders(array $headers): string
    {
        $lines = [];

        foreach ($headers as $name => $value) {
            if ($value === '') {
                continue;
            }
            $lines[] = "{$name}: {$value}";
        }

        return implode("\r\n", $lines);
    }

    /**
     * @param mixed $from
     * @return string
     */
    private function formatAddress(mixed $from): string
    {
        if (is_string($from) && $from !== '') {
            return $from;
        }

        if (is_array($from)) {
            $address = $from['address'] ?? '';
            $name = $from['name'] ?? '';

            if ($address === '') {
                return '';
            }

            return $name === ''
                ? $address
                : sprintf('%s <%s>', $name, $address);
        }

        return '';
    }
}
