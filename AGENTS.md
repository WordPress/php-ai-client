# PHP AI Client SDK

## Project brief

The project implements a provider agnostic PHP AI client SDK to communicate with any generative AI models of various capabilities using a uniform API.

The project is stewarded by [WordPress AI Team](https://make.wordpress.org/ai/) members and contributors. It is however WordPress agnostic, so that any PHP project can use it.

## High-level architecture

The project architecture has several key design principles:

### API layers

1. **Implementer API**: For developers using the SDK to add AI features to their applications
    - Fluent API: Chain methods for readable, declarative code (e.g. `AiClient::prompt('...')->generateText()`)
    - Traditional API: Method-based approach with arrays of arguments (e.g. `AiClient::generateText('...')`)

2. **Extender API**: For developers adding new providers, models, or extending functionality
    - Provider Registry system for managing available AI providers
    - Model discovery based on capabilities and requirements

### Core concepts

- **Provider Agnostic**: Abstracts away provider-specific details, allowing code to work with any AI provider (Google, OpenAI, Anthropic, etc.)
- **Capability-Based Model Selection**: Models can be discovered and selected based on their supported capabilities (text generation, image generation, etc.) and options (input modalities, output formats, etc.)
- **Uniform Data Structures**: Consistent message formats, file representations, and results across all providers
- **Modality Support**: Designed to support arbitrary combinations of input/output modalities (text, image, audio, video)

### Key design patterns

- **Interface Segregation**: Separate interfaces for different model capabilities (TextGenerationModelInterface, ImageGenerationModelInterface, etc.)
- **Composition over Inheritance**: Models compose capabilities through interfaces rather than inheritance
- **DTO Pattern**: Data Transfer Objects for messages, results, and configurations with JSON schema support
- **Builder Pattern**: Fluent builders for constructing prompts and messages

### Directory structure

- **Production Code**: All production code is found in the `src` directory, in subdirectories that match the namespace structure.
- **Tests**: PHPUnit tests are found in the `tests/unit` directory, with each test file covering a specific class from `src`.
    - Each test file is named after the tested class, suffixed with `Test`.
    - Each test file is located in a subdirectory equivalent to the tested class's directory within `src`.
    - Test specific mock classes and traits are located in `tests/mocks` and `tests/traits` respectively.

### Namespace structure

The production code in `src` follows a structured namespace hierarchy under the root namespace `WordPress\AiClient`:

- `Builders`: Fluent API builders (PromptBuilder, MessageBuilder)
- `Embeddings`: Embedding-related data structures
- `Files`: File handling contracts and implementations
- `Messages`: Message DTOs and enums
- `Operations`: Long-running operation support
- `Providers`: Provider system with contracts, models, and registry
- `Results`: Result data structures and transformations
- `Tools`: Function calling and tool support
- `Util`: Utility classes for common operations

## Development tooling and commands

### Linting and code quality

- **Run all linting checks**: `composer lint` (runs both PHPCS and PHPStan)
- **Run PHP CodeSniffer**: `composer phpcs`
- **Fix code style issues**: `composer phpcbf`
- **Run PHPStan static analysis**: `composer phpstan`

### Testing

- **Run PHPUnit tests**: `composer phpunit`

### Dependencies

- **Install dependencies**: `composer install`
- **Update dependencies**: `composer update`

## Coding standards and best practices

- **Code style**: All code must be compliant with the [PER Coding Style](https://www.php-fig.org/per/coding-style/), which extends [PSR-12](https://www.php-fig.org/psr/psr-12/).
- **Minimum required PHP version**: All code must be backward compatible with PHP 7.4. For newer PHP functions, polyfills can be used.

### Type hints

All parameters, return values, and properties must use explicit type hints, except in cases where providing the correct type hint would be impossible given limitations of backward compatibility with PHP 7.4. In any case, concrete type annotations using PHPStan should be present.

### Exception handling

All exceptions must use the project's custom exception classes rather than PHP built-in exceptions. This includes:

- Use `WordPress\AiClient\Common\Exception\InvalidArgumentException` instead of PHP's `\InvalidArgumentException`
- Use `WordPress\AiClient\Common\Exception\RuntimeException` instead of PHP's `\RuntimeException`
- All custom exceptions implement `WordPress\AiClient\Exceptions\AiClientExceptionInterface` for unified exception handling
- Follow usage-driven design: only implement static factory methods that are actually used in the codebase

### Naming conventions

The following naming conventions must be followed for consistency and autoloading:

- Interfaces are suffixed with `Interface`.
- Traits are suffixed with `Trait`.
- Enums are suffixed with `Enum`.
- File names are the same as the class, trait, and interface name for PSR-4 autoloading.
- Classes, interfaces, and traits, and namespaces are not prefixed with `Ai`, excluding the root namespace.

## Further reading

The `docs` folder in this repository provides additional in-depth information about various aspects of the project, such as its requirements or architecture.
