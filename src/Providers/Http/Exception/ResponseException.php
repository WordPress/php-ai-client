<?php

declare(strict_types=1);

namespace WordPress\AiClient\Providers\Http\Exception;

use WordPress\AiClient\Common\Exception\RuntimeException;
use WordPress\AiClient\Providers\Http\DTO\Response;

/**
 * Exception class for HTTP response errors.
 *
 * This is used when response data is unexpected or malformed,
 * typically indicating that a provider changed in ways our code
 * is not aware of or when parsing response data fails.
 *
 * @since 0.1.0
 */
class ResponseException extends RuntimeException
{
    /**
     * Creates a ResponseException for missing expected data.
     *
     * @since n.e.x.t
     *
     * @param string $apiName The name of the API/provider.
     * @param string $fieldName The field that was expected but missing.
     * @param string $context Additional context about where the field was expected.
     * @return self
     */
    public static function fromMissingData(string $apiName, string $fieldName, string $context = ''): self
    {
        $message = sprintf('Unexpected %s API response: Missing the "%s" key', $apiName, $fieldName);
        if ($context !== '') {
            $message .= ' in ' . $context;
        }
        $message .= '.';

        return new self($message);
    }

    /**
     * Creates a ResponseException for unexpected API response structure.
     *
     * @since n.e.x.t
     *
     * @param string $apiName The name of the API/provider.
     * @param string $expected What structure was expected.
     * @param string $actual What was actually received.
     * @return self
     */
    public static function fromUnexpectedStructure(string $apiName, string $expected, string $actual = 'unknown'): self
    {
        return new self(sprintf(
            'Unexpected %s API response structure. Expected: %s, Got: %s',
            $apiName,
            $expected,
            $actual
        ));
    }

    /**
     * Creates a ResponseException for malformed response data.
     *
     * @since n.e.x.t
     *
     * @param string $apiName The name of the API/provider.
     * @param string $reason Why the response is considered malformed.
     * @param Response|null $response The response object if available.
     * @return self
     */
    public static function fromMalformedResponse(string $apiName, string $reason, ?Response $response = null): self
    {
        $message = sprintf('Malformed %s API response: %s', $apiName, $reason);

        $statusCode = $response ? $response->getStatusCode() : 0;

        return new self($message, $statusCode);
    }

    /**
     * Creates a ResponseException from response parsing failure.
     *
     * @since n.e.x.t
     *
     * @param string $apiName The name of the API/provider.
     * @param string $dataType The type of data that failed to parse.
     * @param \Throwable|null $previous The previous exception that caused parsing to fail.
     * @return self
     */
    public static function fromParsingFailure(string $apiName, string $dataType, ?\Throwable $previous = null): self
    {
        $message = sprintf('Failed to parse %s from %s API response', $dataType, $apiName);

        return new self($message, 0, $previous);
    }
}
