<?php declare(strict_types=1);

/**
 * Mail delivery configuration.
 * - `MAIL_DRIVER` picks the transport (nullmail, smtp, mailgun, etc.). Defaults to PHP mail().
 * - `MAIL_FROM_ADDRESS`/`MAIL_FROM_NAME` define the envelope sender for outgoing messages.
 * - `MAIL_SUBJECT_PREFIX` can prepend branding text to every subject line.
 *
 * @return array{driver: string, from: array{address: string, name: string}, subject_prefix: string}
 */
return [
    'driver' => env()->getString('MAIL_DRIVER', 'mail'),
    'from' => [
        'address' => env()->getString('MAIL_FROM_ADDRESS', 'noreply@localhost'),
        'name' => env()->getString('MAIL_FROM_NAME', 'lwork'),
    ],
    'subject_prefix' => env()->getString('MAIL_SUBJECT_PREFIX', ''),
];
