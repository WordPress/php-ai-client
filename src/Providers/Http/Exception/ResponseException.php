<?php

declare(strict_types=1);

namespace WordPress\AiClient\Providers\Http\Exception;

use Psr\Http\Client\ClientExceptionInterface;
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
class ResponseException extends RuntimeException implements ClientExceptionInterface
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
     * Creates a ResponseException from a bad HTTP response.
     *
     * This method extracts error details from common API response formats
     * and creates an exception with a descriptive message and status code.
     *
     * @since n.e.x.t
     *
     * @param Response $response The HTTP response that failed.
     * @return self
     */
    public static function fromBadResponse(Response $response): self
    {
        $errorMessage = sprintf(
            'Bad status code: %d.',
            $response->getStatusCode()
        );

        // Handle common error formats in API responses.
        $data = $response->getData();
        if (
            is_array($data) &&
            isset($data['error']) &&
            is_array($data['error']) &&
            isset($data['error']['message']) &&
            is_string($data['error']['message'])
        ) {
            $errorMessage .= ' ' . $data['error']['message'];
        } elseif (
            is_array($data) &&
            isset($data['error']) &&
            is_string($data['error'])
        ) {
            $errorMessage .= ' ' . $data['error'];
        } elseif (
            is_array($data) &&
            isset($data['message']) &&
            is_string($data['message'])
        ) {
            $errorMessage .= ' ' . $data['message'];
        }

        return new self($errorMessage, $response->getStatusCode());
    }
}
