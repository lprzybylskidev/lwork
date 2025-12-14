<?php declare(strict_types=1);

namespace src\events;

/**
 * @package src\events
 */
interface EventListenerInterface
{
    /**
     * @return class-string<EventInterface>
     */
    public static function listensTo(): string;

    /**
     * @param EventInterface $event
     * @return void
     */
    public function handle(EventInterface $event): void;
}
