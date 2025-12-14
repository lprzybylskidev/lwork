<?php declare(strict_types=1);

namespace src\events;

use src\container\ContainerInterface;

/**
 * @package src\events
 */
final class EventBus
{
    /**
     * @var array<string, array<int, class-string<EventListenerInterface>>>
     */
    private array $listeners = [];

    /**
     * @param array<int, class-string<EventListenerInterface>> $listenerClasses
     */
    /**
     * @param ContainerInterface $container
     * @param EventListenerDiscovery $discovery
     */
    public function __construct(
        private ContainerInterface $container,
        private EventListenerDiscovery $discovery,
    ) {
        $this->loadListeners();
    }

    /**
     * @param EventInterface $event
     * @return void
     */
    public function dispatch(EventInterface $event): void
    {
        $eventClass = get_class($event);

        foreach ($this->listeners[$eventClass] ?? [] as $listener) {
            $this->container->get($listener)->handle($event);
        }
    }

    /**
     * @return void
     */
    private function loadListeners(): void
    {
        foreach ($this->discovery->discover() as $listenerClass) {
            $eventClass = $listenerClass::listensTo();

            if (!isset($this->listeners[$eventClass])) {
                $this->listeners[$eventClass] = [];
            }

            $this->listeners[$eventClass][] = $listenerClass;
        }
    }
}
