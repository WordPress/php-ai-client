# PHP AI Client - Coding Agent Guide

## Project Overview

The PHP AI Client is a provider-agnostic PHP SDK designed to communicate with any generative AI model across various capabilities through a uniform API. It is a WordPress-agnostic PHP package that can be used in any PHP project, providing a flexible and extensible way to integrate AI features.

## Commands & Scripts

The following commands are available for development and can be run using Composer:

*   `composer lint`: Runs all linting checks.
*   `composer phpcs`: Runs PHP_CodeSniffer to check for coding standards violations.
*   `composer phpcbf`: Automatically fixes coding standards violations that can be fixed automatically.
*   `composer phpstan`: Runs PHPStan for static analysis.
*   `composer test`: Runs the unit test suite (this is an alias for `composer test:unit`).
*   `composer test:unit`: Runs the unit test suite.
*   `composer test:integration`: Runs the integration test suite (requires API keys).
*   `composer phpunit`: Runs all tests (unit and integration).

## Coding Standards & Compatibility Constraints

All code in this project MUST adhere to the coding standards, naming conventions, and documentation standards outlined in the `CONTRIBUTING.md` file. Any agent working on this project MUST read and follow the guidelines in that file before starting any work.

Key constraints include:

*   PHP 7.4 as the minimum required version.
*   PER Coding Style (extending PSR-12).
*   Strict type hinting for all parameters, return values, and properties.

## Core Principles

*   **Provider Agnostic:** The client is designed to work with any AI provider, avoiding vendor lock-in.
*   **Extensibility:** The architecture allows for new providers, models, and capabilities to be added without modifying the core functionality.
*   **Flexibility:** The client supports a wide range of AI capabilities, including text generation, image generation, and more, with arbitrary combinations of input and output modalities.
*   **Developer Experience:** The client provides two distinct APIs: a simple, fluent API for implementers and a more technical, interface-based API for extenders.

### Dependency Management

The project aims to have minimal third-party dependencies. Any new dependency requires justification and should be discussed before implementation.

### Error Handling

The project uses custom exceptions for error handling. When appropriate, throw a custom exception class. Custom exception classes should extend the base `Exception` class.

### Testing

The project uses PHPUnit for testing. Tests are organized into two suites:

*   **Unit tests** (`tests/unit/`): Fast, isolated tests that mirror the structure of the `src/` directory. Run with `composer test:unit`.
*   **Integration tests** (`tests/integration/`): Tests that make real API calls to AI providers (OpenAI, Google, Anthropic). Run with `composer test:integration`. These require API keys configured in a `.env` file, and running them will incur cost with these providers.

Supporting test infrastructure:

*   `tests/mocks/`: Mock implementations for testing purposes.
*   `tests/traits/`: Reusable testing traits.

All new code requires corresponding unit tests.

## Project Architecture Overview

The project's architecture is heavily inspired by the Vercel AI SDK and is designed to be modular and extensible. Key components include:

*   **`AiClient`:** The main entry point for interacting with the SDK, offering both a fluent API (via `AiClient::prompt()`) and a traditional method-based API.
*   **Providers:** Implementations for specific AI providers (e.g., OpenAI, Google, Anthropic). Each provider has its own models and metadata.
*   **`ProviderRegistry`:** Manages the available AI providers and models, allowing for discovery of models based on their capabilities.
*   **Models:** Represent specific AI models and their capabilities. They are responsible for handling the logic for a specific AI task.
*   **HTTP Communication:** A custom `HttpTransporter` layer abstracts HTTP communication, decoupling models from specific PSR-18 HTTP client implementations. Models create custom `Request` objects and receive custom `Response` objects, which the transporter translates to and from PSR-7 standards.

For a more detailed overview, refer to the `docs/ARCHITECTURE.md` file.

## Agent Guidelines

### Naming conventions

### DO:

*   **Read the Docs:** Before making any changes, thoroughly read `CONTRIBUTING.md` and `docs/ARCHITECTURE.md`.
*   **Follow Coding Standards:** Strictly adhere to the coding standards defined in `CONTRIBUTING.md` and enforced by PHP_CodeSniffer.
*   **Write Tests:** All new features or bug fixes must be accompanied by corresponding unit tests.
*   **Use the Fluent API:** When writing examples or tests for the implementer API, prefer the fluent API for readability.
*   **Use `{@inheritDoc}`:** When implementing an interface method, use `{@inheritDoc}` in the PHPDoc block to avoid duplicating documentation, as specified in `CONTRIBUTING.md`.

### DON'T:

*   **Bypass the `HttpTransporter`:** Do not use a PSR-18 HTTP client directly within a model. All HTTP communication must go through the `HttpTransporter`.
*   **Add Provider-Specific Logic to Core:** Keep the core client agnostic. Provider-specific logic should be encapsulated within the provider's implementation in `src/ProviderImplementations/`.
*   **Introduce Out-of-Scope Features:** Do not add features that are out of scope for this project, such as agents or the Model Context Protocol (MCP), as noted in `docs/REQUIREMENTS.md`.
*   **Duplicate Documentation:** Avoid duplicating PHPDoc descriptions for methods that implement an interface.

### Exception handling

All exceptions must use the project's custom exception classes rather than PHP built-in exceptions. This includes:

- Use the custom primitive exceptions in `WordPress\AiClient\Common\Exception\` instead of the PHP primitive exceptions
- If a PHP primitive exception is needed that doesn't have a custom exception counterpart, then create one that extends the primitive and implements AiClientExceptionInterface
- All custom exceptions implement `WordPress\AiClient\Exceptions\AiClientExceptionInterface` for unified exception handling
- Follow usage-driven design: only implement static factory methods that are actually used in the codebase

## Common Pitfalls

*   **Direct HTTP Client Usage:** A common mistake is to instantiate a PSR-18 client directly in a model. This is incorrect. Instead, the model should receive an `HttpTransporter` instance and use it to send requests.
*   **Ignoring the Fluent API:** While the traditional API is available, the fluent API is the preferred way for implementers to use the client. Avoid writing complex, nested method calls when the fluent API provides a cleaner alternative.
*   **Duplicating Interface Documentation:** Manually writing PHPDoc descriptions for methods that implement an interface is a common pitfall. The `{@inheritDoc}` tag should be used instead to inherit the documentation from the interface.
