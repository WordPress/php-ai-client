<?php

declare(strict_types=1);

namespace WordPress\AiClient\Messages\DTO;

use WordPress\AiClient\Common\AbstractDataTransferObject;

/**
 * Represents opaque message data that only the originating provider may interpret.
 *
 * Providers can use this DTO to preserve native conversation items and replay them
 * in their original order without adding a core message type for each wire format.
 * Providers are responsible for checking the provider identifier before replaying
 * the data; the DTO does not validate or interpret the provider-native payload.
 *
 * @since n.e.x.t
 *
 * @phpstan-type ProviderDataArrayShape array{
 *     providerId: string,
 *     data: array<string, mixed>
 * }
 *
 * @extends AbstractDataTransferObject<ProviderDataArrayShape>
 */
class ProviderData extends AbstractDataTransferObject
{
    /**
     * @since n.e.x.t
     * @var string The provider identifier key.
     */
    public const KEY_PROVIDER_ID = 'providerId';

    /**
     * @since n.e.x.t
     * @var string The provider-native data key.
     */
    public const KEY_DATA = 'data';

    /**
     * @since n.e.x.t
     * @var string The identifier of the provider that owns this data.
     */
    private string $providerId;

    /**
     * @since n.e.x.t
     * @var array<string, mixed> The opaque provider-native data.
     */
    private array $data;

    /**
     * Constructor.
     *
     * @since n.e.x.t
     *
     * @param string $providerId The identifier of the provider that owns this data.
     * @param array<string, mixed> $data The opaque provider-native data.
     */
    public function __construct(string $providerId, array $data)
    {
        $this->providerId = $providerId;
        $this->data = $data;
    }

    /**
     * Gets the identifier of the provider that owns this data.
     *
     * @since n.e.x.t
     *
     * @return string The provider identifier.
     */
    public function getProviderId(): string
    {
        return $this->providerId;
    }

    /**
     * Gets the opaque provider-native data.
     *
     * @since n.e.x.t
     *
     * @return array<string, mixed> The provider-native data.
     */
    public function getData(): array
    {
        return $this->data;
    }

    /**
     * {@inheritDoc}
     *
     * @since n.e.x.t
     */
    public static function getJsonSchema(): array
    {
        return [
            'type' => 'object',
            'properties' => [
                self::KEY_PROVIDER_ID => [
                    'type' => 'string',
                    'description' => 'The identifier of the provider that owns this data.',
                ],
                self::KEY_DATA => [
                    'type' => 'object',
                    'additionalProperties' => true,
                    'description' => 'Opaque provider-native message data.',
                ],
            ],
            'required' => [self::KEY_PROVIDER_ID, self::KEY_DATA],
            'additionalProperties' => false,
        ];
    }

    /**
     * {@inheritDoc}
     *
     * @since n.e.x.t
     *
     * @return ProviderDataArrayShape The provider-scoped data.
     */
    public function toArray(): array
    {
        return [
            self::KEY_PROVIDER_ID => $this->providerId,
            self::KEY_DATA => $this->data,
        ];
    }

    /**
     * {@inheritDoc}
     *
     * @since n.e.x.t
     */
    public static function fromArray(array $array): self
    {
        static::validateFromArrayData($array, [self::KEY_PROVIDER_ID, self::KEY_DATA]);

        return new self($array[self::KEY_PROVIDER_ID], $array[self::KEY_DATA]);
    }
}
