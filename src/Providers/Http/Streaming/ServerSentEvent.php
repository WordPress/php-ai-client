<?php

declare(strict_types=1);

namespace WordPress\AiClient\Providers\Http\Streaming;

/**
 * Represents a single decoded event from a Server-Sent Events stream.
 *
 * @see <https://html.spec.whatwg.org/multipage/server-sent-events.html#event-stream-interpretation>.
 *
 * @since n.e.x.t
 */
final class ServerSentEvent
{
    /**
     * @var string The event name. Defaults to "message" when the stream omits it.
     */
    private string $event;

    /**
     * @var string The event payload.
     */
    private string $data;

    /**
     * @var string|null The last event ID associated with this event, if any.
     */
    private ?string $id;

    /**
     * @var int|null The reconnection time in milliseconds, if the stream sent one.
     */
    private ?int $retry;

    /**
     * Constructor.
     *
     * @since n.e.x.t
     *
     * @param string $event The event name.
     * @param string $data The event payload.
     * @param string|null $id The last event ID, or null.
     * @param int|null $retry The reconnection time in milliseconds, or null.
     */
    public function __construct(string $event, string $data, ?string $id = null, ?int $retry = null)
    {
        $this->event = $event;
        $this->data = $data;
        $this->id = $id;
        $this->retry = $retry;
    }

    /**
     * Gets the event name.
     *
     * @since n.e.x.t
     *
     * @return string The event name ("message" when unspecified).
     */
    public function getEvent(): string
    {
        return $this->event;
    }

    /**
     * Gets the event payload.
     *
     * @since n.e.x.t
     *
     * @return string The payload.
     */
    public function getData(): string
    {
        return $this->data;
    }

    /**
     * Gets the last event ID.
     *
     * @since n.e.x.t
     *
     * @return string|null The event ID, or null.
     */
    public function getId(): ?string
    {
        return $this->id;
    }

    /**
     * Gets the reconnection time in milliseconds.
     *
     * @since n.e.x.t
     *
     * @return int|null The reconnection time, or null.
     */
    public function getRetry(): ?int
    {
        return $this->retry;
    }
}
