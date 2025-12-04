<?php

declare(strict_types=1);

namespace WordPress\AiClient\Tests\mocks;

use Psr\EventDispatcher\EventDispatcherInterface;

/**
 * Mock event dispatcher for testing.
 *
 * This simple implementation records all dispatched events and allows
 * registration of listeners for testing purposes.
 */
class MockEventDispatcher implements EventDispatcherInterface
{
    /**
     * @var list<object> The list of dispatched events.
     */
    private array $dispatchedEvents = [];

    /**
     * @var array<string, list<callable>> The registered listeners keyed by event class name.
     */
    private array $listeners = [];

    /**
     * {@inheritDoc}
     *
     * @param object $event The event to dispatch.
     * @return object The event after being processed by listeners.
     */
    public function dispatch(object $event): object
    {
        $this->dispatchedEvents[] = $event;

        $eventClass = get_class($event);
        if (isset($this->listeners[$eventClass])) {
            foreach ($this->listeners[$eventClass] as $listener) {
                $listener($event);
            }
        }

        return $event;
    }

    /**
     * Registers a listener for a specific event class.
     *
     * @param string $eventClass The event class name.
     * @param callable $listener The listener callback.
     * @return void
     */
    public function addListener(string $eventClass, callable $listener): void
    {
        if (!isset($this->listeners[$eventClass])) {
            $this->listeners[$eventClass] = [];
        }
        $this->listeners[$eventClass][] = $listener;
    }

    /**
     * Gets all dispatched events.
     *
     * @return list<object> The dispatched events.
     */
    public function getDispatchedEvents(): array
    {
        return $this->dispatchedEvents;
    }

    /**
     * Gets dispatched events of a specific type.
     *
     * @template T of object
     * @param class-string<T> $eventClass The event class to filter by.
     * @return list<T> The filtered events.
     */
    public function getDispatchedEventsOfType(string $eventClass): array
    {
        return array_values(array_filter(
            $this->dispatchedEvents,
            static function (object $event) use ($eventClass): bool {
                return $event instanceof $eventClass;
            }
        ));
    }

    /**
     * Clears all dispatched events.
     *
     * @return void
     */
    public function clearDispatchedEvents(): void
    {
        $this->dispatchedEvents = [];
    }
}
