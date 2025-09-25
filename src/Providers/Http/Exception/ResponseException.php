<?php

declare(strict_types=1);

namespace WordPress\AiClient\Providers\Http\Exception;

use WordPress\AiClient\Common\Exception\RuntimeException;
use WordPress\AiClient\Providers\Http\DTO\Response;
use WordPress\AiClient\Providers\Http\Utilities\ErrorMessageExtractor;

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
     * Creates a ResponseException from invalid data in an API response.
     *
     * @since n.e.x.t
     *
     * @param string $apiName The name of the API service (e.g., 'OpenAI', 'Anthropic').
     * @param string $message The specific error message describing the invalid data.
     * @return self
     */
    public static function fromInvalidData(string $apiName, string $message): self
    {
        return new self(sprintf('Unexpected %s API response: %s', $apiName, $message));
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

        // Extract error message from response data using centralized utility
        $extractedError = ErrorMessageExtractor::extractFromResponseData($response->getData());
        if ($extractedError !== null) {
            $errorMessage .= ' ' . $extractedError;
        }

        return new self($errorMessage, $response->getStatusCode());
    }
}
